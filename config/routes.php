<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Http\Controllers;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Routing\Guard;
use Ajthenewguy\Php8ApiServer\Routing\Route;

/**
 * ##################################
 * # User Interface / HTML Routes
 * # --------------------------------
 * # These routes are for rendering HTML pages intended to iteract with 
 * # the API server via a browser.
 * #
 */


/**
 * Define application UI public routes below.
 */

Route::get('/', new Controllers\HomeController());

Route::get('/login', [Controllers\Auth\LoginController::class, 'getForm']);
Route::post('/login', [Controllers\Auth\LoginController::class, 'postForm']);
Route::get('/logout', new Controllers\Auth\LogoutController());

Route::get('/account/recover', [Controllers\Account\RecoverController::class, 'getForm']);
Route::post('/account/recover', [Controllers\Account\RecoverController::class, 'postForm']);

/**
 * Define application UI guarded routes below.
 */

Route::get('/account', [Controllers\Account\ProfileController::class, 'index'], new Guard());
Route::get('/account/profile', [Controllers\Account\ProfileController::class, 'getForm'], new Guard());
Route::post('/account/profile', [Controllers\Account\ProfileController::class, 'postForm'], new Guard());




/**
 * ##################################
 * # API Routes
 * # --------------------------------
 * # These routes are for return JSON responses intended for
 * # clients of the API.
 * #
 */

/**
 * Define application API public routes below.
 */

Route::get('/ping', function (Request $request) {
    return JsonResponse::make("PONG\n");
});

Route::post('/auth/register', new Controllers\API\v1\Auth\RegisterController());
Route::post('/auth/reset', new Controllers\API\v1\Auth\ResetController());
Route::post('/auth/verify', new Controllers\API\v1\Auth\VerifyController());
Route::post('/auth/login', new Controllers\API\v1\Auth\LoginController());

/**
 * Define application API guarded routes below.
 */

Route::get('/auth/refresh', new Controllers\API\v1\Auth\RefreshController(), new Guard());
