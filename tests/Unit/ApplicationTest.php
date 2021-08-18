<?php declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Reporting\Logger;
use Ajthenewguy\Php8ApiServer\Reporting\Drivers\NullLogger;

final class ApplicationTest extends TestCase
{
    public $app;

    public function setUp(): void
    {
        $this->app = Application::singleton();
    }

    public function testInstance()
    {
        $this->app->bindInstance(Logger::class, NullLogger::create());

        $Logger = $this->app->instance(Logger::class);

        $this->assertInstanceOf(NullLogger::class, $Logger);
    }
}