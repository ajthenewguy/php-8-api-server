<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Account;

use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Models\PasswordResetToken;
use Ajthenewguy\Php8ApiServer\Models\User;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;

class RecoverController extends Controller
{
    public function getForm(Request $request)
    {
        return $request->validate([
            'token' => ['required', 'exists:password_reset_tokens,token']
        ], [
            'token.exists' => 'Token invalid or expired.'
        ])->then(function ($validated) {
            [$data, $errors] = $validated;
            // if ($errors) {
            //     return Response::make(join(', ', $errors), 422);
            // }

            return Response::make('<html><head><title>Account Recovery</title><body>Enter a new password:</body></html>');
        });
    }

    public function postForm(Request $request)
    {
        return $request->validate([
            'password' => ['required', 'string', 'regex:/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{12,}$/'],
            'token' => ['required', 'exists:password_reset_tokens,token']
        ], [
            'password.regex' => 'Password must at least 12 characters and include a number.',
            'token.exists' => 'Token invalid or expired.'
        ])->then(function ($validated) use ($request) {
            [$data, $errors] = $validated;
            if ($errors) {
                // return Response::make(join(', ', $errors), 422);
                // header(sprintf("Location: %s/account/recover", $_ENV['APP_URL']), true, 302);
                // die();
                return Response::redirect('/account/recover');
            }

            return PasswordResetToken::where('token', $data['token'])->first()->then(function ($Token) use ($request, $data) {
                return User::find($Token->user_id)->then(function ($User) use ($request, $data) {
                    if ($User) {
                        $attributes = [
                            'password' => $data['password'],
                            'verified_at' => new \DateTime()
                        ];

                        return UserRepository::update($User, $attributes)->then(function ($User) {
                            return PasswordResetToken::where('user_id', $User->id)->delete()->then(function () use ($User) {
                                // header(sprintf("Location: %s/login?email=%s", $_ENV['APP_URL'], $User->email), true, 302);
                                // die();
                                return Response::redirect(sprintf("/login?email=%s", $User->email));
                            });
                        });
                    } else {
                        return $request->redirectBackWithErrors(['user' => ['The specified user does not exist.']]);
                    }
                });
            });
        });
    }
}