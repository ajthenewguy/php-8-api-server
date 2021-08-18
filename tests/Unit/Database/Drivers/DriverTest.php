<?php declare(strict_types=1);

namespace Tests\Unit\Database\Drivers;

use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver;
use Tests\TestCase;

final class DriverTest extends TestCase
{
    use \Tests\UsesDatabase;

    public function testCreate()
    {
        $PDO = Driver::create((object) [
            'driver' => 'sqlite',
            'path' => $this->getDatabaseFile()->getPath()
        ]);

        // $this->assertInstanceOf(\PDO::class, $PDO);
        $this->assertEquals(\PDO::class, get_debug_type($PDO));
    }

    protected function tearDown(): void
    {
        $File = $this->getDatabaseFile();
        if ($File->exists()) {
            $File->delete();
        }
    }
}