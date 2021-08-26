<?php declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Filesystem\Directory;
use Ajthenewguy\Php8ApiServer\Reporting\Logger;
use Ajthenewguy\Php8ApiServer\Reporting\Drivers\StandardLogger;
use Tests\TestCase;

class LoggerTest extends TestCase
{
    public function testEnv()
    {
        $Logger = new Logger((object) [
            'env' => 'production'
        ]);

        $this->assertEquals('production', $Logger->env());
    }

    public function testLog()
    {
        $Logger = new Logger(new \stdClass);
        ob_start();
        $Logger->log('There was an error', Logger::ERROR);
        $out = ob_get_clean();

        $this->assertEquals(date('Y-m-d H:i:s').' - error: There was an error', $out);
    }

    public function testName()
    {
        $Logger = new Logger((object) [
            'name' => 'Stdout Logger'
        ]);

        $this->assertEquals('Stdout Logger', $Logger->name());
    }

    public function testCreate()
    {
        $Logger = Logger::create((object) [
            'env' => 'production',
            'name' => 'Stdout Logger'
        ]);

        $this->assertEquals(StandardLogger::class, get_debug_type($Logger));
        $this->assertEquals('Stdout Logger', $Logger->name());
        $this->assertEquals('production', $Logger->env());
    }

    public function testFacade()
    {
        $path = __DIR__.DIRECTORY_SEPARATOR.'logs';
        $app = Application::singleton();
        $app->defineProvider(Logger::class, function ($app) use ($path) {
            return Logger::create((object) [
                'driver' => 'file',
                'path' => $path
            ]);
        });

        Log::app($app);

        $expected = date('Y-m-d H:i:s').' - info: FileLogger resolved'."\n";
        Log::info('FileLogger resolved');
        $LogDir = new Directory($path);
        $out = $LogDir->files()[0]->read();

        foreach ($LogDir->files() as $File) {
            $File->delete();
        }
        $LogDir->delete();

        $this->assertEquals($expected, $out);
    }
}
