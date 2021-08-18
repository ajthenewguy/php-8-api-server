<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Define application routes below.
 */

Route::get('/', function (ServerRequestInterface $request) {
    return Response::make("Hello there!\n");
});
