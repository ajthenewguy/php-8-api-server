<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Models;

use Ajthenewguy\Php8ApiServer\Str;

class User extends Model
{
    protected static string $table = 'users';

    protected array $dates = ['verified_at'];

    public static function generateVerificationCode(): string
    {
        return Str::simpleRandom(12);
    }
}