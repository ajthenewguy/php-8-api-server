<?php declare(strict_types=1);

use Ajthenewguy\Php8ApiServer\Database\Migration;
use Ajthenewguy\Php8ApiServer\Database\Query;
use React\Promise\PromiseInterface;

class Migration_2021_08_23_Create_Email extends Migration
{
    public function up(): PromiseInterface
    {
        return Query::driver()->exec("CREATE TABLE IF NOT EXISTS email (
            id INTEGER PRIMARY KEY,
            to_email VARCHAR (255),
            to_name VARCHAR (255),
            from_email VARCHAR (255),
            from_name VARCHAR (255),
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            sent_at TIMESTAMP,
            created_at TIMESTAMP DEFAULT (strftime('%s','now')),
            updated_at TIMESTAMP
        )");
    }

    public function down(): PromiseInterface
    {
        return Query::driver()->exec("DROP TABLE IF EXISTS email");
    }
}
