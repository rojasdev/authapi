<?php

//Activation Code Configuration
define('ACTIVATION_CODE_LENGTH', 10);
define('ACTIVATION_CODE_EXPIRY', 30 * 60);
define('ACTIVATION_MAX_ATTEMPTS', 5);

//Characters used for activation codes
define(
    'ACTIVATION_CHARACTERS',
    'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
);

//Generate Activation Code
function generateActivationCode(): string
{
    $characters = ACTIVATION_CHARACTERS;

    $length = strlen($characters);

    $code = '';

    for ($i = 0; $i < ACTIVATION_CODE_LENGTH; $i++) {

        $code .= $characters[
            random_int(0, $length - 1)
        ];
    }

    return $code;
}


//Hash Activation Code
function hashActivationCode(
    string $activationCode
): string {

    return hash(
        'sha256',
        $activationCode
    );
}


//Get Activation Expiration
function getActivationExpiration(): string
{
    return date(
        'Y-m-d H:i:s',
        time() + ACTIVATION_CODE_EXPIRY
    );
}


//Verify Activation Code
function verifyActivationCode(
    string $activationCode,
    string $storedHash
): bool {

    $submittedHash = hashActivationCode(
        strtoupper(trim($activationCode))
    );

    return hash_equals(
        $storedHash,
        $submittedHash
    );
}