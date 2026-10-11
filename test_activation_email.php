<?php

header('Content-Type: application/json; charset=UTF-8');

$to = 'r.rojas@usls.edu.ph';

$firstName = 'John';

$activationKey = bin2hex(random_bytes(32));

$subject = 'Account Activation Code';

$message = "
Hello {$firstName},

Thank you for registering your account.

Your account activation code is:

{$activationKey}

This activation code will expire in 30 minutes.

Please open the mobile application and enter your
registered email address and this activation code
to activate your account.

If you did not create this account, you may safely
ignore this email.

Thank you.
";

$headers = [];

$headers[] = 'From: YOUR_SENDER_EMAIL@yourdomain.com';
$headers[] = 'Reply-To: YOUR_SENDER_EMAIL@yourdomain.com';
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$result = mail(
    $to,
    $subject,
    $message,
    implode("\r\n", $headers)
);

echo json_encode([
    'success' => $result,
    'message' => $result
        ? 'Activation email accepted for delivery.'
        : 'mail() failed.',
    'recipient' => $to
]);