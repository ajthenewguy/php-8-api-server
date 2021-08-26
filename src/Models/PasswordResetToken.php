<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Models;

class PasswordResetToken extends Model
{
    protected static string $table = 'password_reset_tokens';

    protected array $dates = ['token_expiry'];

    protected const CREATED_FIELD = null;

    protected const UPDATED_FIELD = null;
}