<?php declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 'on');

require __DIR__ . '/vendor/autoload.php';
\Dotenv\Dotenv::createImmutable(__DIR__)->load();

define('IS_CLI_INTERFACE', php_sapi_name() === 'cli');
define('ROOT_PATH', __DIR__);
define('SERVER_SCRIPT', __FILE__);
define('CONFIG_PATH', dirname($_ENV['APP_CONFIG']));
