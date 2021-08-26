<?php

declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Exceptions\Http\ServerError;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ServerRequestInterface;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;

class AuthenticationMiddleware extends Middleware
{
    public function __invoke(Request $request, callable $next)
    {
        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();
            $contentType = $request->contentType();

            if ($contentType !== 'application/json' && $contentType !== 'application/vnd.api+json') {
                $request->session()->begin();
            }

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
                        // if ($middlewares = $Route->getMiddleware()) {
                        //     foreach ($middlewares as $middleware) {
                        //         $next = Application::singleton()->handleNext($request, $next, $middleware);
                        //     }
                        // }

                        return $next($request);
                    });
                }
            }
        } catch (ServerError $e) {
            return JsonResponse::make($e->getMessage(), $e->getCode());
        } catch (SignatureInvalidException $e) {
            return JsonResponse::make($e->getMessage(), 403);
        } catch (ExpiredException $e) {
            return JsonResponse::make($e->getMessage(), 401);
        } catch (\Throwable $e) {
            print $e->getMessage().' in '.$e->getFile(). ':' . $e->getLine().PHP_EOL;
        }

        return $next($request);
    }
}
