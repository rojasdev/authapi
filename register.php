<?php

require_once 'config.php';
require_once 'email.php';
require_once 'activation.php';

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

//Get input


$firstName = trim($input['first_name'] ?? '');
$lastName  = trim($input['last_name'] ?? '');
$email     = strtolower(trim($input['email'] ?? ''));
$password  = $input['password'] ?? '';

//Validate required fields


if (
    $firstName === '' ||
    $lastName === '' ||
    $email === '' ||
    $password === ''
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'All required fields must be provided.'
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

//Validate password


if (strlen($password) < 8) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Password must contain at least 8 characters.'
    ]);

    exit;
}

//Registration process

try {

    //Check if email already exists

    $stmt = $pdo->prepare("
        SELECT user_id, account_status
        FROM tbl_susers
        WHERE email = ?
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $existingUser = $stmt->fetch();

    if ($existingUser) {

        http_response_code(409);

        echo json_encode([
            'success' => false,
            'message' => 'An account with this email already exists.'
        ]);

        exit;
    }

    //Hash password

    $passwordHash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    if ($passwordHash === false) {

        throw new Exception(
            'Password hashing failed.'
        );
    }

    //Generate activation code

    $activationKey = generateActivationCode();

    //Hash activation code


    $activationHash = hashActivationCode(
        $activationKey
    );

    //Generate expiration time
 

    $expiresAt = getActivationExpiration();

    //Begin database transaction


    $pdo->beginTransaction();

    //Insert user


    $stmt = $pdo->prepare("
        INSERT INTO tbl_susers
        (
            email,
            password_hash,
            first_name,
            last_name,
            account_status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            'pending'
        )
    ");

    $stmt->execute([
        $email,
        $passwordHash,
        $firstName,
        $lastName
    ]);

    $userId = $pdo->lastInsertId();

    //Insert activation record


    $stmt = $pdo->prepare("
        INSERT INTO tbl_account_activation
        (
            user_id,
            activation_hash,
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
        $userId,
        $activationHash,
        $expiresAt
    ]);

    //Commit database transaction


    $pdo->commit();

    //Send activation email


    $emailSent = sendActivationEmail(
        $email,
        $firstName,
        $activationKey
    );

    //Email sending failed


    if (!$emailSent) {

        error_log(
            "Activation email failed for user ID: {$userId}"
        );

        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Registration was completed, but we could not send the activation email. Please contact support.'
        ]);

        exit;
    }

    //Registration successful

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful. Please check your email for your account activation code.'
    ]);

} catch (Throwable $e) {

    //Rollback transaction if active


    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }

    //Log actual error


    error_log(
        'Registration API Error: ' .
        $e->getMessage()
    );

    //Safe response


    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Registration could not be completed.'
    ]);

    exit;
}