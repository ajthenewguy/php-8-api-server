<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Http\Middleware;

/**
 * Core application middleware.
 */

Application::singleton()->registerMiddleware('session', Middleware\SessionMiddleware::class);
Application::singleton()->registerMiddleware('static_file', Middleware\StaticResourceMiddleware::class);
Application::singleton()->registerMiddleware('auth', Middleware\AuthenticationMiddleware::class);
Application::singleton()->registerMiddleware('access_logging', Middleware\AccessLoggingMiddleware::class);
Application::singleton()->registerMiddleware('dispatch', Middleware\RouteMiddleware::class);

/**
 * Register additional application middleware below.
 */
 