<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Ajthenewguy\Php8ApiServer\Routing\RouteParameter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise;

class RouteMiddleware
{
    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();

            if ($Route = Route::lookup($requestMethod, $requestTarget)) {
                // $promise = Promise\resolve($next($request));
                // return $promise->then(function (ResponseInterface $response) use ($request, $Route, $requestTarget) {
                    $parameters = [];

                    if ($Route->hasParams()) {
                        $parameterKeys = $Route->getParameters()->map(function (RouteParameter $Parameter) {
                            return $Parameter->getName();
                        })->toArray();

                        $parameters = $Route->matchParameters($requestTarget);

                        foreach ($parameterKeys as $name) {
                            if (!array_key_exists($name, $parameters)) {
                                $parameters[$name] = null;
                            }
                        }
                    }

                    return $Route->dispatch($request, $parameters);
                // });
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        }

        return JsonResponse::make('Not found', 404);
    }
}