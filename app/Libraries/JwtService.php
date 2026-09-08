<?php

namespace App\Libraries;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;
use UnexpectedValueException;

/**
 * Wraps firebase/php-jwt for QualiDoc's auth: generates a signed token
 * carrying the patient id + admin flag, valid for JWT_EXPIRY seconds (15 days).
 */
class JwtService
{
    private string $secret;
    private int $expiry;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET');
        $this->expiry = (int) env('JWT_EXPIRY', 1296000);
    }

    public function generate(int $patientId, bool $admin): string
    {
        $now = time();

        $payload = [
            'sub'   => $patientId,
            'admin' => $admin,
            'iat'   => $now,
            'exp'   => $now + $this->expiry,
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Decodes and validates a token. Returns null if it's invalid,
     * malformed, or expired (JWT::decode throws in those cases).
     */
    public function validate(string $token): ?stdClass
    {
        try {
            return JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (UnexpectedValueException) {
            return null;
        }
    }
}
