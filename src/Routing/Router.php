<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Routing;

use Ajthenewguy\Php8ApiServer\Facades\Log;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class Router
{
    public function __invoke(ServerRequestInterface $request): Response
    {
        $response = new Response(404, ['Content-Type' => 'application/json'], 'Not found');

        try {
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();

            if ($Route = Route::lookup($requestMethod, $requestTarget)) {
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
            }

            
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        }

        return $response;
    }
}