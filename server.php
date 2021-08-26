#!/usr/bin/env php
<?php declare(strict_types=1);

define('SCRIPT_NAME', $argv[0]);

require __DIR__ . '/bootstrap.php';

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use React\EventLoop\Loop;

require CONFIG_PATH . '/middleware.php';
require CONFIG_PATH . '/routes.php';

$http = new React\Http\HttpServer(
    ...Application::singleton()->handleRequest()
);
// $http = new React\Http\HttpServer(
//     new Ajthenewguy\Php8ApiServer\Http\Controllers\HomeController(),
// );

$socket = new React\Socket\SocketServer($_ENV['SERVER_HOST'] . ':' . $_ENV['SERVER_PORT']);

$http->on('error', function (Throwable $e) {
    Log::error($e->getMessage());
    Log::error($e->getFile() . ':' . $e->getLine());
});
$http->listen($socket);

Loop::addTimer(0.75, function () {
    Log::info('[ok] Listening on ' . $_ENV['SERVER_HOST'] . ':' . $_ENV['SERVER_PORT']);
});