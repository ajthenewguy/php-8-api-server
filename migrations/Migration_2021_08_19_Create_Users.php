<?php declare(strict_types=1);

use Ajthenewguy\Php8ApiServer\Database\Migration;
use Ajthenewguy\Php8ApiServer\Database\Query;

class Migration_2021_08_19_Create_Users extends Migration
{
    public function up()
    {
        Query::driver()->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY,
            email VARCHAR (255),
            password VARCHAR (255),
            verification_code VARCHAR (255),
            verified_at TIMESTAMP,
            name_first VARCHAR(255) NOT NULL,
            name_last VARCHAR(255) NOT NULL,
            is_closed BOOL DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT (strftime('%s','now')),
            updated_at TIMESTAMP
        )");
    }

    public function down()
    {
        Query::driver()->exec("DROP TABLE IF EXISTS users");
    }
}
