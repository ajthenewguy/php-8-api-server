<?php

declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Exceptions\Http\ServerError;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ServerRequestInterface;

class AuthenticationMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();

            if ($Route = Route::lookup($requestMethod, $requestTarget)) {
                
                // Check for a Route Guard
                if ($Guard = $Route->getGuard()) {

                    return $Guard->validate(AuthService::getClaims($request))->then(function ($result) use ($request, $next, $Route) {
                        if ($result === false) {
                            return JsonResponse::make('Unauthorized', 401);
                        }

                        $request = $request->withAttribute('Route', $Route);

                        // Pass through attached middleware
                        // @todo - test
                        if ($middlewares = $Route->getMiddleware()) {
                            foreach ($middlewares as $middleware) {
                                $next = Application::singleton()->handleNext($request, $next, $middleware);
                            }
                        }

                        return $next($request);
                    });
                }
            }
        } catch (ServerError $e) {
            return JsonResponse::make($e->getMessage() . ' Authentication.', $e->getCode());
        } catch (SignatureInvalidException $e) {
            return JsonResponse::make($e->getMessage(), 403);
        } catch (ExpiredException $e) {
            return JsonResponse::make($e->getMessage(), 401);
        }

        return $next($request);
    }
}
