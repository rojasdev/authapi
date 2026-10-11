<?php
//Authentication Token Configuration

define(
    'AUTH_TOKEN_EXPIRY',
    7 * 24 * 60 * 60
);


//Generate Authentication Token

function generateAuthToken(): string
{
    return bin2hex(
        random_bytes(32)
    );
}


//Hash Authentication Token

function hashAuthToken(
    string $token
): string {

    return hash(
        'sha256',
        $token
    );
}


//Get Token Expiration

function getAuthTokenExpiration(): string
{
    return date(
        'Y-m-d H:i:s',
        time() + AUTH_TOKEN_EXPIRY
    );
}