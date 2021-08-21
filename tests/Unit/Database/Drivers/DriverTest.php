<?php declare(strict_types=1);

namespace Tests\Unit\Database\Drivers;

use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver;
use Ajthenewguy\Php8ApiServer\Database\Drivers\Sqlite;
use Tests\TestCase;

final class DriverTest extends TestCase
{
    use \Tests\UsesDatabase;

    public function testCreate()
    {
        $db = Driver::create((object) [
            'driver' => 'sqlite',
            'path' => $this->getDatabaseFile()->getPath()
        ]);

        $this->assertEquals(Sqlite::class, get_debug_type($db));
    }

    protected function tearDown(): void
    {
        $File = $this->getDatabaseFile();
        if ($File->exists()) {
            $File->delete();
        }
    }
}