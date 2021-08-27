<?php

declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Exceptions\Http\ServerError;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationMiddleware extends Middleware
{
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        try {
            $claims = AuthService::getClaims($request);
            if ($User = UserRepository::getById($claims->user_id)) {
                if ($User->email === 'allenmccabe@gmail.com') {
                    return $next($request);
                }
            }

            return JsonResponse::make('Forbidden', 403);
            
        } catch (ServerError $e) {
            return JsonResponse::make($e->getMessage() . ' Authorization!', $e->getCode());
        } catch (SignatureInvalidException $e) {
            return JsonResponse::make($e->getMessage(), 403);
        } catch (ExpiredException $e) {
            return JsonResponse::make($e->getMessage(), 401);
        }

        return $next($request);
    }
}
