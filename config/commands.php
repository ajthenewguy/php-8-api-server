<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Commands;

/**
 * Core application CLI commands.
 */

Application::singleton()->bindCommand('make:migration', new Commands\MakeMigrationCommand());
Application::singleton()->bindCommand('db:migrate', new Commands\DbMigrateCommand());
Application::singleton()->bindCommand('db:rollback', new Commands\DbRollbackCommand());

/**
 * Register additional application CLI commands below.
 */
 