<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Models;

class User extends Model
{
    protected static string $table = 'users';

    protected array $dates = ['verified_at'];

    public static function generateVerificationCode(): string
    {
        return substr(uniqid(), mt_rand(0, 5), 7);
    }
}