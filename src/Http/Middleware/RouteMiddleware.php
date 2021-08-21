<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Exceptions\Http\ServerError;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Ajthenewguy\Php8ApiServer\Routing\RouteParameter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\Promise;

class RouteMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request)
    {
        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();

            if ($Route = Route::lookup($requestMethod, $requestTarget)) {
                $parameters = [];

                if ($Route->hasParams()) {
                    $parameterKeys = $Route->getParameters()->map(function (RouteParameter $Parameter) {
                        return $Parameter->getName();
                    })->toArray();

                    $parameters = $Route->pregMatch($requestTarget);

                    foreach ($parameterKeys as $name) {
                        if (!array_key_exists($name, $parameters)) {
                            $parameters[$name] = null;
                        }
                    }
                }

                return $Route->dispatch($request, $parameters)->then(function (Response $response) {
                    return $response;
                });
            }
        } catch (ServerError $e) {
            return JsonResponse::make($e->getMessage() . ' - RouteMiddleware', $e->getCode());
        }

        return JsonResponse::make('Internal Server Error', 500);
    }
}