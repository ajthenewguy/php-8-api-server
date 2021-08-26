<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\API\v1\Auth;

use Ajthenewguy\Php8ApiServer\Auth\Jwt;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\JsonResponse;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Models\Email;
use Ajthenewguy\Php8ApiServer\Models\PasswordResetToken;
use Ajthenewguy\Php8ApiServer\Models\User;
use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;

class ResetController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->contentType() !== 'application/json') {
            return JsonResponse::make(['errors' => ['title' => 'Unsupported Media Type']], 415);
        }

        return $request->validate([
            'email' => ['required', 'email', 'exists:users,email']
        ], [
            'email.not-exists' => 'An account with that email address does not exist.'
        ])->then(function ($validation) {
            [$data, $errors] = $validation;
            if ($errors) {
                return JsonResponse::make(['errors' => $errors], 422);
            }

            return UserRepository::getForLogin($data['email'])->then(function ($User) {
                // $attributes = [
                //     'verification_code' => User::generateVerificationCode(),
                //     // 'verified_at' => null  // ?
                // ];
                $token = new Jwt(['user_id' => $User->id], 5);
                $Token = new PasswordResetToken([
                    'user_id' => $User->id,
                    'token' => (string) $token,
                    'token_expiry' => $token->claims->exp
                ]);

                return $Token->save()->then(function ($User) {
                    Log::info(sprintf('Issued password reset token for User id: %d', $User->id));

                    // // @todo - Move to separate Controller/endpoint? Perhaps the client app should request an email be sent
                    // $Email = Email::make(
                    //     'Welcome New User',
                    //     'Please verify your account here: ' . $_ENV['APP_URL'] . '/auth/verify?code=' . $User->verification_code,
                    //     'allenmccabe@gmail.com',
                    //     'API Team'
                    // )->to($User->email, $User->name_first . ' ' . $User->name_last);

                    // return $Email->save()->then(function ($Email) use ($User) {
                    //     $process = new \React\ChildProcess\Process('php ' . SERVER_SCRIPT . ' mail:send ' . $Email->id, ROOT_PATH);
                    //     $process->start();
                        
                    //     $process->stdout->on('data', function ($chunk) {
                    //         Log::info($chunk);
                    //     });

                    //     $process->on('exit', function ($exitCode, $termSignal) {
                    //         if ($exitCode) Log::error('Process exited with code ' . $exitCode);
                    //     });

                        return JsonResponse::make(['data' => [
                            'type' => 'User',
                            'id' => $User->id,
                            'verification_code' => $User->verification_code
                        ]], 201);
                    // });
                });
            });
        });
    }
}