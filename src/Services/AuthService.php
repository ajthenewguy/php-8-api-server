<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Services;

use Ajthenewguy\Php8ApiServer\Auth\Jwt;
use Ajthenewguy\Php8ApiServer\Models\User;
use Psr\Http\Message\ServerRequestInterface;

class AuthService
{
    public static function authenticate(User $User, string $password): bool
    {
        return static::verify($password, $User->password);
    }

    public static function getClaims(ServerRequestInterface $request): ?\stdClass
    {
        if ($token = static::extractToken($request)) {
            return Jwt::decodeToken($token);
        }

        return null;
    }

    public static function hash(string $data): string
    {
        return password_hash($data, PASSWORD_DEFAULT);
    }

    /**
     * Get the JWT token from the Authorization header.
     */
    protected static function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeader('Authorization');

        if (!empty($authHeader) && preg_match("/Bearer\s+(.*)$/i", $authHeader[0], $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}