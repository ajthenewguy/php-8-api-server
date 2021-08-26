<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Application;
use Psr\Http\Message\ServerRequestInterface;
use WyriHaximus\React\Http\Middleware\SessionMiddleware as BaseMiddleware;

class SessionMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $Cache = Application::singleton()->cache();
        $Middleware = new BaseMiddleware(
            'CookieName',
            $Cache, // Instance implementing React\Cache\CacheInterface
            [ // Optional array with cookie settings, order matters
                0, // expiresAt, int, default
                '/', // path, string, default
                '', // domain, string, default
                false, // secure, bool, default
                false // httpOnly, bool, default
            ],
        );
        return $Middleware($request, $next);
    }
}