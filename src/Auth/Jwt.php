<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Auth;

use Ajthenewguy\Php8ApiServer\Application;
use Firebase\JWT\JWT as FirebaseJWT;

class Jwt
{
    public static function createToken(\stdClass|array $payload): string
    {
        $key = static::getPrivateKey();
        $algorithm = static::getKeyAlgorithm();
        
        return FirebaseJWT::encode($payload, $key, $algorithm);
    }

    public static function decodeToken(string $token): \stdClass
    {
        $key = static::getPublicKey();
        $algorithm = static::getKeyAlgorithm();
        
        return FirebaseJWT::decode($token, $key, [$algorithm]);
    }

    public static function getPrivateKey(): string
    {
        return Application::singleton()->config()->get('security.privateKey');
    }

    public static function getPublicKey(): string
    {
        return Application::singleton()->config()->get('security.publicKey');
    }

    public static function getKeyAlgorithm(): string
    {
        return Application::singleton()->config()->get('security.keyAlgorithm');
    }
}