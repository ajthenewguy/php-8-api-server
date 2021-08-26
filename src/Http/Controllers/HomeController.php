<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers;

use Ajthenewguy\Php8ApiServer\Facades\View;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        return $request->user()->then(function ($User) {
            return View::make('index', ['User' => $User]);
        }, function (\Exception $e) {
            return Response::redirect('/login');
        });
    }
}