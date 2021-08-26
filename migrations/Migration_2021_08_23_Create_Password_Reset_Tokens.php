<?php declare(strict_types=1);

use Ajthenewguy\Php8ApiServer\Database\Migration;
use Ajthenewguy\Php8ApiServer\Database\Query;
use React\Promise\PromiseInterface;

class Migration_2021_08_23_Create_Password_Reset_Tokens extends Migration
{
    public function up(): PromiseInterface
    {
        return Query::driver()->exec("‍CREATE TABLE IF NOT EXISTS password_reset_tokens (    
            user_id VARCHAR(36) NOT NULL,    
            token VARCHAR(128) NOT NULL UNIQUE,    
            token_expiry TIMESTAMP NOT NULL,    
            PRIMARY KEY (user_id, token),
        )");
    }

    public function down(): PromiseInterface
    {
        return Query::driver()->exec("DROP TABLE IF EXISTS password_reset_tokens");
    }
}
