<?php

require_once 'auth_middleware.php';

header(
    'Content-Type: application/json; charset=UTF-8'
);

$user = requireAuth();

echo json_encode([
    'success' => true,
    'user' => $user
]);