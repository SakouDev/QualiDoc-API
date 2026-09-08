<?php

namespace App\Libraries;

use stdClass;

/**
 * Holds the currently authenticated patient (decoded from the JWT by
 * JwtAuthFilter) for the lifetime of the request, so controllers behind
 * the filter can read it without re-decoding the token.
 */
class AuthContext
{
    private static ?stdClass $user = null;

    public static function setUser(stdClass $payload): void
    {
        self::$user = $payload;
    }

    public static function id(): int
    {
        return (int) self::$user->sub;
    }

    public static function isAdmin(): bool
    {
        return (bool) self::$user->admin;
    }
}
