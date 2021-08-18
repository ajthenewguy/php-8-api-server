<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Guard;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Define application routes below.
 */

Route::get('/', function (ServerRequestInterface $request) {
    return JsonResponse::make("Hello there!\n");
});
