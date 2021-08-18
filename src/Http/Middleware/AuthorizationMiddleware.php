<?php

declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Auth\Jwt;
use Ajthenewguy\Php8ApiServer\Exceptions\Http\ServerError;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Firebase\JWT\SignatureInvalidException;
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
                    if (!$Guard->validate($this->getClaims($request))) {
                        return JsonResponse::make('Unauthorized', 401);
                    }
                }
            }
        } catch (ServerError $e) {
            return JsonResponse::make($e->getMessage(), $e->getCode());
        } catch (SignatureInvalidException $e) {
            return JsonResponse::make($e->getMessage(), 403);
        } catch (\Throwable $e) {
            Log::error($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
        }

        return $next($request);
    }

    public function getClaims(ServerRequestInterface $request): ?\stdClass
    {
        if ($token = $this->extractToken($request)) {
            return Jwt::decodeToken($token);
        }

        return null;
    }

    /**
     * Get the JWT token from the Authorization header.
     */
    protected function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeader('Authorization');

        if (!empty($authHeader) && preg_match("/Bearer\s+(.*)$/i", $authHeader[0], $matches)) {
            return $matches[1];
        }

        return null;
    }
}
