<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Models;

class User extends Model
{
    protected static string $table = 'users';

    protected array $dates = ['verified_at'];

}