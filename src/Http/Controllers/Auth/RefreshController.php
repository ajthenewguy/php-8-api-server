<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Auth;

use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Services\AuthService;

class RefreshController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->contentType() !== 'application/json') {
            return JsonResponse::make(['errors' => ['title' => 'Unsupported Media Type']], 415);
        }

        return $request->user()->then(function ($User) {
            $token = AuthService::createToken($User);

            return JsonResponse::make([
                'access_token' => (string) $token,
                'token_type' => 'Bearer',
                'expires_in' => $token->claims->exp - time()
            ]);
        });
    }
}