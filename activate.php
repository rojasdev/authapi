<?php

require_once 'config.php';
require_once 'activation.php';

header('Content-Type: application/json; charset=UTF-8');

// db connection
$pdo = getDbConnection();

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);

    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.'
    ]);

    exit;
}

// read JSON request
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

// get input
$email = strtolower(
    trim($input['email'] ?? '')
);

$activationCode = strtoupper(
    trim($input['activation_code'] ?? '')
);

//validate input
if ($email === '' || $activationCode === '') {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Email and activation code are required.'
    ]);

    exit;
}

// validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address.'
    ]);

    exit;
}

// validate activation code format 10 characters A-Z and 2-9
if (
    strlen($activationCode) !== ACTIVATION_CODE_LENGTH ||
    !preg_match(
        '/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{10}$/',
        $activationCode
    )
) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid activation code format.'
    ]);

    exit;
}

try {

    // check for user record
    $stmt = $pdo->prepare("
        SELECT
            u.user_id,
            u.account_status,
            a.activation_hash,
            a.expires_at,
            a.attempts,
            a.used_at
        FROM tbl_susers u
        INNER JOIN tbl_account_activation a
            ON a.user_id = u.user_id
        WHERE u.email = ?
        ORDER BY a.user_id DESC
        LIMIT 1
    ");

    $stmt->execute([
        $email
    ]);

    $account = $stmt->fetch();

    // if account is not on record
    if (!$account) {

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid email or activation code.'
        ]);

        exit;
    }

    // check accoubnt status
    if ($account['account_status'] === 'active') {

        echo json_encode([
            'success' => true,
            'message' => 'Account is already activated.'
        ]);

        exit;
    }

    // check usage

    if ($account['used_at'] !== null) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'This activation code has already been used.'
        ]);

        exit;
    }

    // check attempt count
    if ((int)$account['attempts'] >= ACTIVATION_MAX_ATTEMPTS) {

        http_response_code(429);

        echo json_encode([
            'success' => false,
            'message' => 'Maximum activation attempts exceeded.'
        ]);

        exit;
    }

    // check expiration
    if (
        strtotime($account['expires_at']) < time()
    ) {

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => 'The activation code has expired.'
        ]);

        exit;
    }

    //verify code
    $isValid = verifyActivationCode(
        $activationCode,
        $account['activation_hash']
    );

    // invaled activation
    if (!$isValid) {

        $stmt = $pdo->prepare("
            UPDATE tbl_account_activation
            SET attempts = attempts + 1
            WHERE user_id = ?
        ");

        $stmt->execute([
            $account['user_id']
        ]);

        $newAttempts = (int)$account['attempts'] + 1;

        $remainingAttempts =
            ACTIVATION_MAX_ATTEMPTS - $newAttempts;

        if ($remainingAttempts > 0) {

            $message =
                'Invalid activation code. ' .
                $remainingAttempts .
                ' attempt(s) remaining.';

        } else {

            $message =
                'Maximum activation attempts exceeded.';
        }

        http_response_code(400);

        echo json_encode([
            'success' => false,
            'message' => $message
        ]);

        exit;
    }

    // activate account
    $pdo->beginTransaction();

    // update user
    $stmt = $pdo->prepare("
        UPDATE tbl_susers
        SET
            account_status = 'active'
        WHERE user_id = ?
          AND account_status = 'pending'
    ");

    $stmt->execute([
        $account['user_id']
    ]);

    // check account updated
    if ($stmt->rowCount() !== 1) {

        throw new Exception(
            'Unable to activate account.'
        );
    }

    // mark activation as used
    $stmt = $pdo->prepare("
        UPDATE tbl_account_activation
        SET
            used_at = NOW()
        WHERE user_id = ?
          AND used_at IS NULL
    ");

    $stmt->execute([
        $account['user_id']
    ]);


    $pdo->commit();

    // if success
    echo json_encode([
        'success' => true,
        'message' => 'Account activated successfully.'
    ]);

} catch (Throwable $e) {

    // if error rollback

    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }

    // error log
    error_log(
        'Account Activation Error: ' .
        $e->getMessage()
    );

    // success message
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Account activation could not be completed.'
    ]);

    exit;
}