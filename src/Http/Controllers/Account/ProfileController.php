<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Controllers\Account;

use Ajthenewguy\Php8ApiServer\Exceptions\ModelException;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Facades\View;
use Ajthenewguy\Php8ApiServer\Http\Controllers\Controller;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->then(function ($User) {
            return View::make('account.index');
        }/*, function () {
            return Response::redirect('/login');
        }*/);
    }

    public function getForm(Request $request)
    {
        return View::make('account.profile');
    }

    public function postForm(Request $request)
    {
        return $request->user()->then(function ($User) use ($request) {
            return $request->validate([
                'name_first' => ['required', 'string'],
                'name_last' => ['required', 'string']
            ])->then(function ($validated) use ($request, $User) {
                [$data, $errors] = $validated;
                if ($errors) {
                    return $request->redirectBackWithErrors($errors);
                }

                return $User->update($data)->then(function ($User) use ($request) {
                    $request->Session()->flash('notifications', [
                        ['type' => 'success', 'message' => 'Your profile has been updated.']
                    ]);

                    return Response::redirect('/account');
                }, function (ModelException $e) {
                    Log::error($e);
                    return Response::redirect('/account/profile');
                });
            });
        });
    }
}