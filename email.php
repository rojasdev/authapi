<?php

function sendActivationEmail(
    string $email,
    string $firstName,
    string $activationKey
): bool {

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

    $headers[] = 'From: NO_REPLY@authapi.com';
    $headers[] = 'Reply-To: NO_REPLY@authapi.com';
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

    $result = mail(
        $email,
        $subject,
        $message,
        implode("\r\n", $headers)
    );

    error_log(
        'Activation email to ' .
        $email .
        ': ' .
        ($result ? 'SUCCESS' : 'FAILED')
    );

    return $result;
}