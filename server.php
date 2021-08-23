#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Application;
use React\EventLoop\Loop;

$Application = Application::singleton(Dotenv\Dotenv::createImmutable(__DIR__), isset($argv[1]));

define('ROOT_PATH', __DIR__);
define('SERVER_SCRIPT', __FILE__);
define('SCRIPT_NAME', $argv[0]);
define('CONFIG_PATH', $Application->getConfigDirectoryPath());

require CONFIG_PATH . '/middleware.php';
require CONFIG_PATH . '/routes.php';

if (isset($argv[1])) {
    require CONFIG_PATH . '/commands.php';
    
    $arguments = array_slice($argv, 2);
    $Application->runCommand($argv[1], $arguments)->done();

} else {
    $http = new React\Http\HttpServer(
        ...$Application->handleRequest()
    );
    $socket = new React\Socket\SocketServer($_ENV['APP_URL']);

    $http->listen($socket);

    Loop::addTimer(0.75, function () {
        echo '[ok] Listening on ' . $_ENV['APP_URL'] . PHP_EOL;
    });
}