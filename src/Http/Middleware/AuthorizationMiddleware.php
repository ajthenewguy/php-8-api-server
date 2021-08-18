<?php

declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationMiddleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();

            if ($Route = Route::lookup($requestMethod, $requestTarget)) {
                // Check for a Route Guard
                if ($Guard = $Route->getGuard()) {
                    if (!$Guard->validate($request)) {
                        return JsonResponse::make('Unauthorized', 401);
                    }
                }
            }

        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        }

        return $next($request);
    }
}
