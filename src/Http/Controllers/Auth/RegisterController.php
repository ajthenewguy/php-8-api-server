<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Auth;

use Ajthenewguy\Php8ApiServer\Facades\DB;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Models\Email;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;

class RegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->contentType() !== 'application/json') {
            return JsonResponse::make(['errors' => ['title' => 'Unsupported Media Type']], 415);
        }

        return $request->validate([
            'email' => ['required', 'email', 'not-exists:users,email'],
            'password' => ['required', 'string'],
            'name_first' => ['required', 'string'],
            'name_last' => ['required', 'string']
        ], [
            'email.not-exists' => 'An account with that email address already exists.'
        ])->then(function ($validated) {
            [$data, $errors] = $validated;
            if ($errors) {
                return JsonResponse::make(['errors' => $errors], 422);
            }

            $Repo = new UserRepository();

            return $Repo->create($data)->then(function ($User) {
                return JsonResponse::make(['data' => [
                    'type' => 'User',
                    'id' => $User->id,
                    'verification_code' => $User->verification_code
                ]], 201);
            });
        });
    }
}