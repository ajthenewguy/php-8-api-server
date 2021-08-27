<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Auth;

use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Facades\View;
use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use Ajthenewguy\Php8ApiServer\Str;

class LoginController extends Controller
{
    public static string $redirectTo = '/';

    public function getForm(Request $request)
    {
        return $request->user()->then(function ($User) {
            return Response::redirect(static::$redirectTo);
        }, function (\Exception $e) use ($request) {
            return View::make('login');
        });
    }

    public function postForm(Request $request)
    {
        return $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string']
        ])->then(function ($validated) use ($request) {
            [$data, $errors] = $validated;
            if ($errors) {
                return $request->redirectBackWithErrors($errors);
            }

            return UserRepository::getForLogin($data['email'])->then(function ($User) use ($request, $data) {
                if (AuthService::authenticate($User, $data['password'])) {
                    if ($User->verified_at) {
                        $request->Session()->set('user_id', $User->id);
                        $request->Session()->set('User', $User);

                        return $request->redirectToIntended(static::$redirectTo);
                    } else {
                        return $request->redirectBackWithErrors(['email' => ['User email unverified.']]);
                    }
                } else {
                    return $request->redirectBackWithErrors(['password' => ['Invalid email or password.']]);
                }
            });
        });
    }
}