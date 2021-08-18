<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Traits;

use Ajthenewguy\Php8ApiServer\Application;

trait RequiresServiceContainer
{
    protected static Application $app;

    public static function app(Application $app = null)
    {
        if (isset($app)) {
            static::$app = $app;
        }

        if (isset(static::$app)) {
            return self::$app;
        }

        return null;
    }
}