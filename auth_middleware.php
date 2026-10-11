<?php

require_once 'config.php';
require_once 'auth.php';

///Require Authentication
/*
| Validates:
| - Authorization header
| - Bearer token format
| - Token existence
| - Token expiration
| - Token revocation
| - User existence
| - Active account status
|
| Returns the authenticated user record.
|
*/

function requireAuth(): array
{
    
    $pdo = getDbConnection();

    ///Get Authorization header

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

    ///Validate Authorization header

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
            'message' => 'Authentication required.'
        ]);

        exit;
    }

    //Extract token

    $token = trim($matches[1]);

    if ($token === '') {

        http_response_code(401);

        echo json_encode([
            'success' => false,
            'message' => 'Invalid authentication token.'
        ]);

        exit;
    }

    // Hash token


    $tokenHash = hashAuthToken(
        $token
    );

    try {

        //Find valid token and user


        $stmt = $pdo->prepare("
            SELECT
                t.token_id,
                t.user_id,
                t.expires_at,
                t.revoked_at,

                u.email,
                u.first_name,
                u.last_name,
                u.account_status

            FROM tbl_auth_tokens t

            INNER JOIN tbl_susers u
                ON u.user_id = t.user_id

            WHERE t.token_hash = ?

            LIMIT 1
        ");

        $stmt->execute([
            $tokenHash
        ]);

        $auth = $stmt->fetch();

        //Token not found

        if (!$auth) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid authentication token.'
            ]);

            exit;
        }

        //Check token revocation


        if ($auth['revoked_at'] !== null) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Authentication token has been revoked.'
            ]);

            exit;
        }

        //Check token expiration


        if (
            strtotime($auth['expires_at']) <= time()
        ) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Authentication token has expired.'
            ]);

            exit;
        }

        //Check account status
 

        if ($auth['account_status'] !== 'active') {

            http_response_code(403);

            echo json_encode([
                'success' => false,
                'message' => 'Account is not active.'
            ]);

            exit;
        }

        //Return authenticated user
 

        return [
            'user_id' => (int)$auth['user_id'],
            'email' => $auth['email'],
            'first_name' => $auth['first_name'],
            'last_name' => $auth['last_name'],
            'account_status' => $auth['account_status'],
            'token_id' => (int)$auth['token_id'],
            'token_expires_at' => $auth['expires_at']
        ];

    } catch (Throwable $e) {

        //Log actual error

        error_log(
            'Authentication Middleware Error: ' .
            $e->getMessage()
        );

        //Safe response


        http_response_code(500);

        echo json_encode([
            'success' => false,
            'message' => 'Authentication could not be verified.'
        ]);

        exit;
    }
}