<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Repositories;

use Ajthenewguy\Php8ApiServer\Models\User;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use React\Promise;

class UserRepository
{
    public function getById(int|string $id): Promise\PromiseInterface
    {
        return User::find($id);
    }

    public function create(array $attributes): Promise\PromiseInterface
    {
        if (!isset($attributes['password'])) {
            throw new \InvalidArgumentException('Users require a password to create.');
        }

        $attributes['password'] = AuthService::hash($attributes['password']);

        return User::create($attributes);
    }

    public function update(User $User, array $attributes): Promise\PromiseInterface
    {
        return $User->update($attributes);
    }

    public function delete(int|string $id): Promise\PromiseInterface
    {
        return User::find($id)->then(function (User $User) {
            return $User->delete();
        });
    }
}