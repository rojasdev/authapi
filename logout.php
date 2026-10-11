<?php

require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=UTF-8');


$pdo = getDbConnection();


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}

//Get Authorization header

$authorizationHeader = '';

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {

    $authorizationHeader = trim(
        $_SERVER['HTTP_AUTHORIZATION']
    );

} elseif (function_exists('getallheaders')) {

    $headers = getallheaders();

    foreach ($headers as $key => $value) {

        if (strtolower($key) === 'authorization') {

            $authorizationHeader = trim($value);
            break;
        }
    }
}

//Validate Bearer token


if (
    $authorizationHeader === '' ||
    !preg_match(
        '/^Bearer\s+(.+)$/i',
        $authorizationHeader,
        $matches
    )
) {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Authorization token is required.'
    ]);

    exit;
}

$token = trim($matches[1]);

if ($token === '') {

    http_response_code(401);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid authorization token.'
    ]);

    exit;
}

try {

    //Hash supplied token


    $tokenHash = hashAuthToken(
        $token
    );

    //Revoke token


    $stmt = $pdo->prepare("
        UPDATE tbl_auth_tokens
        SET revoked_at = NOW()
        WHERE token_hash = ?
          AND revoked_at IS NULL
          AND expires_at > NOW()
    ");

    $stmt->execute([
        $tokenHash
    ]);

    //Check whether token was actually revoked


    if ($stmt->rowCount() === 0) {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid or expired authentication token.'
        ]);

        exit;
    }

    //Logout successful


    echo json_encode([
        'success' => true,
        'message' => 'Logout successful.'
    ]);

} catch (Throwable $e) {

    //Log actual error
 
    error_log(
        'Logout API Error: ' .
        $e->getMessage()
    );

    //Safe response


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Logout could not be completed.'
    ]);

    exit;
}