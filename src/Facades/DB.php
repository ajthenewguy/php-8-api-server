<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Facades;

use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver;
use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Traits\RequiresServiceContainer;

class DB {

    use RequiresServiceContainer;

    public static function instance(string $name = null)
    {
        return self::app()->instance(Driver::class);
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([Query::class, $name], $arguments);
    }
}