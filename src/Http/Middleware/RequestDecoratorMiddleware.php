<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Http\Request;
use Psr\Http\Message\ServerRequestInterface;

class RequestDecoratorMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, $next)
    {
        $request = new Request($request);
        $contentType = $request->contentType();

        if ($contentType !== 'application/json' && $contentType !== 'application/vnd.api+json') {
            $request->session()->begin();
        }

        return $next($request);
    }
}