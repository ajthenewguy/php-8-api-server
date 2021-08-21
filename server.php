#!/usr/bin/env php
<?php declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Http\Middleware;

$Application = Application::singleton(Dotenv\Dotenv::createImmutable(__DIR__));

require __DIR__ . '/config/middleware.php';
require __DIR__ . '/config/routes.php';

if (isset($argv[1])) {
    require __DIR__ . '/config/commands.php';
    
    $arguments = array_slice($argv, 2);
    $Application->runCommand($argv[1], ...$arguments);
} else {
    $http = new React\Http\HttpServer(
        ...$Application->handleRequest()
    );
    $socket = new React\Socket\SocketServer($_ENV['APP_URL']);

    $http->listen($socket);
}
