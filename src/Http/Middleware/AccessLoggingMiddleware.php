<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise;

class AccessLoggingMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $promise = Promise\resolve($next($request));
        return $promise->then(function (ResponseInterface $response) use ($request) {
            $serverParams = $request->getServerParams();
            $remoteAddr = $serverParams['REMOTE_ADDR'];
            $requestTime = date('d/M/Y:H:i:s O');
            $requestMethod = $request->getMethod();
            $requestTarget = $request->getRequestTarget();
            $protocol = 'HTTP/' . $request->getProtocolVersion();
            $responseCode = $response->getStatusCode();
            $responseBytes = $response->getBody()->getSize();
            
            Log::info(sprintf('%s - - [%s] "%s %s %s" %d %d', $remoteAddr, $requestTime, $requestMethod, $requestTarget, $protocol, $responseCode, $responseBytes));

            return $response;

        }, function (\Exception $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());

            return JsonResponse::make($e->getMessage(), 500);
        });
    }
}