<?php

header('Content-Type: application/json; charset=UTF-8');

$to = 'r.rojas@usls.edu.ph';

$subject = 'Test Account Activation Email';

$message = "
Hello,

This is a test email from the account registration system.

If you received this message, GoDaddy email sending is working correctly.

Thank you.
";

$headers = [];

$headers[] = 'From: DONOTREPLY@yourdomain.com';
$headers[] = 'Reply-To: DONOTREPLY@yourdomain.com';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$result = mail(
    $to,
    $subject,
    $message,
    implode("\r\n", $headers)
);

if ($result) {

    echo json_encode([
        'success' => true,
        'message' => 'Email was accepted for delivery.'
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Email could not be sent.'
    ]);
}