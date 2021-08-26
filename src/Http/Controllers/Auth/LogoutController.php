<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Auth;

use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;

class LogoutController extends Controller
{
    public static string $redirectTo = '/login';

    public function __invoke(Request $request)
    {
        return $request->user()->then(function ($User) use ($request) {
            $request->Session()->end();
            return Response::redirect(static::$redirectTo);
        }, function (\Exception $e) {
            return Response::redirect(static::$redirectTo);
        });
    }
}