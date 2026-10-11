<?php
/**
 * config.php
 * Database connection settings. Adjust to your environment.
 */

define('DB_HOST', 'XXXXX');
define('DB_NAME', 'XXXX');
define('DB_USER', 'XXXXX');
define('DB_PASS', 'XXXXX');
define('DB_CHARSET', 'utf8mb4');

function getDbConnection(): PDO
{
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
        exit;
    }
}