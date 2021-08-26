<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Commands;

/**
 * Core application CLI commands.
 */

$Application = Application::singleton(true);
$Application->bindCommand('make:migration', new Commands\MakeMigrationCommand());
$Application->bindCommand('db:migrate', new Commands\DbMigrateCommand());
$Application->bindCommand('db:rollback', new Commands\DbRollbackCommand());
$Application->bindCommand('mail:send', new Commands\SendMailCommand());

/**
 * Register additional application CLI commands below.
 */
 