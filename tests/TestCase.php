<?php declare(strict_types=1);

namespace Tests;

use Ajthenewguy\Php8ApiServer\Application;
use PHPUnit\Framework\TestCase as BaseClass;

class TestCase extends BaseClass
{
    protected function setUp(): void
    {
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__));
            define('SCRIPT_NAME', __FILE__);
            define('CONFIG_PATH', Application::singleton()->getConfigDirectoryPath());
        }
    }

    public function exe(string $binary, array $args = [])
    {
        foreach ($args as $arg => $value) {
            $name = ltrim($arg, '-');

            if (strlen($name) > 1) {
                $binary .= ' --'.$name;
            } elseif (strlen($name) === 1) {
                $binary .= ' -'.$name;
            }
            if (!is_bool($value) && is_scalar($value)) {
                $binary .= ' '.$value;
            }
        }

        ob_start();
        passthru($binary);

        return ob_get_clean();
    }

    protected static function invoke($obj, $name, array $args = [])
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        if (is_string($obj)) {
            $obj = new $obj;
        }

        return $method->invokeArgs($obj, $args);
    }
}
