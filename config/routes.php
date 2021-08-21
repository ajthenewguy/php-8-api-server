<?php declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Models\User;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;
use Ajthenewguy\Php8ApiServer\Routing\Guard;
use Ajthenewguy\Php8ApiServer\Routing\Route;
use Ajthenewguy\Php8ApiServer\Services\AuthService;

/**
 * Define application routes below.
 */

Route::get('/', function (Request $request) {
    return JsonResponse::make("Hello there!\n");
});

Route::post('/auth/register', function (Request $request) {
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
                'id' => $User->id
            ]], 201);
        });
    });
});

Route::post('/auth/login', function (Request $request) {
    if ($request->contentType() !== 'application/json') {
        return JsonResponse::make(['errors' => ['title' => 'Unsupported Media Type']], 415);
    }

    $validated = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string']
    ]);

    return $validated->then(function ($validation) {
        [$data, $errors] = $validation;
        if ($errors) {
            return JsonResponse::make(['errors' => $errors], 422);
        }

        return User::where('email', $data['email'])->first()->then(function ($User) use ($data) {
            if (AuthService::authenticate($User, $data['password'])) {
                $App = Ajthenewguy\Php8ApiServer\Application::singleton();
                $tokenLifetime = $App->config()->get('security.tokenLifetime');
                $claims = new \stdClass();
                $claims->exp = time() + (60 * $tokenLifetime);
                $claims->iat = time();
                $claims->email = $User->email;

                $token = Ajthenewguy\Php8ApiServer\Auth\Jwt::createToken($claims);

                return JsonResponse::make([
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $claims->exp - time()
                ]);
            } else {
                return JsonResponse::make(['errors' => ['authentication' => 'Invalid email or password.']], 401);
            }
        });
    });
});
