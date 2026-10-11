<?php

require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=UTF-8');

//Database connection

$pdo = getDbConnection();

//Only allow POST requests

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}

//Read JSON request


$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON request.'
    ]);

    exit;
}

//Get credentials


$email = strtolower(
    trim($input['email'] ?? '')
);

$password = $input['password'] ?? '';

//Validate input


if ($email === '' || $password === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required.'
    ]);

    exit;
}

//Validate email


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);

    exit;
}

try {

  //Find user


    $stmt = $pdo->prepare("
        SELECT
            user_id,
            email,
            password_hash,
            first_name,
            last_name,
            account_status
        FROM tbl_susers
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $user = $stmt->fetch();

    //invalid credentials

    if (!$user) {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);

        exit;
    }

   //Verify password

    if (!password_verify(
        $password,
        $user['password_hash']
    )) {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or password.'
        ]);

        exit;
    }

    //Check account status


    if ($user['account_status'] !== 'active') {

        http_response_code(403);

        echo json_encode([
            'success' => false,
            'message' => 'Account is not activated.'
        ]);

        exit;
    }

    //Generate authentication token
    $token = generateAuthToken();

    //Hash token for database storage
 
    $tokenHash = hashAuthToken(
        $token
    );

    //Token expiration

    $expiresAt = getAuthTokenExpiration();

    //Store token


    $stmt = $pdo->prepare("
        INSERT INTO tbl_auth_tokens
        (
            user_id,
            token_hash,
            expires_at
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    $stmt->execute([
        $user['user_id'],
        $tokenHash,
        $expiresAt
    ]);

    //Successful login


    echo json_encode([
        'success' => true,
        'message' => 'Login successful.',
        'token' => $token,
        'expires_at' => $expiresAt,
        'user' => [
            'user_id' => (int)$user['user_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]
    ]);

} catch (Throwable $e) {

   //Log actual error
 
    error_log(
        'Login API Error: ' .
        $e->getMessage()
    );

    //Safe response


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Login could not be completed.'
    ]);

    exit;
}