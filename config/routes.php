<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Http\Controllers;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Routing\Guard;
use Ajthenewguy\Php8ApiServer\Routing\Route;

/**
 * Define application UI public routes below.
 */

Route::get('/', function (Request $request) {
    return Response::make("Hello there!\n");
});

/**
 * Define application UI guarded routes below.
 */

Route::get('/account', function (Request $request) {
    return $request->user()->then(function ($User) {
        return Response::make(sprintf("Welcome %s.\n", $User->name_first));
    });
}, new Guard());


/**
 * Define application API public routes below.
 */

Route::get('/ping', function (Request $request) {
    return JsonResponse::make("PONG\n");
});

Route::post('/auth/register', new Controllers\Auth\RegisterController());
Route::post('/auth/reset', new Controllers\Auth\ResetController());
Route::post('/auth/verify', new Controllers\Auth\VerifyController());
Route::post('/auth/login', new Controllers\Auth\LoginController());

/**
 * Define application API guarded routes below.
 */

Route::get('/auth/refresh', new Controllers\Auth\RefreshController(), new Guard());
