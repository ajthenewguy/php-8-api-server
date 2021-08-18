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
            $serverParams = $request->getServerParams();
            $remoteAddr = $serverParams['REMOTE_ADDR'];
            $requestTime = date('d/M/Y:H:i:s O');
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();
            $protocol = 'HTTP/' . $request->getProtocolVersion();

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
                
                $response = $Route->dispatch($request, $parameters);
            }

            $responseCode = $response->getStatusCode();
            $responseBytes = $response->getBody()->getSize();

            Log::info(sprintf('%s - - [%s] "%s %s %s" %d %d', $remoteAddr, $requestTime, $requestMethod, $requestTarget, $protocol, $responseCode, $responseBytes));
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        }

        return $response;
    }
}