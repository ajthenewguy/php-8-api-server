<?php declare(strict_types=1);

namespace Tests;

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver;
use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Filesystem\File;

trait UsesDatabase
{
    public Application $app;

    public $database;

    public $db;

    protected function getDatabaseFile()
    {
        return new File(sprintf('%s/database.sqlite3', __DIR__));
    }

    protected function setUpDatabase(): Driver
    {
        if (!isset($this->app)) {
            $this->app = Application::singleton();
        }

        $this->tearDownDatabase();

        $this->app->bindInstance(Driver::class, Driver::create(['driver' => 'sqlite', 'path' => $this->getDatabaseFile()->getPath()]));
        // Query::app($this->app);
        $this->db = $this->app->instance(Driver::class);

        return $this->db;
    }

    protected function tearDownDatabase(): void
    {
        $File = $this->getDatabaseFile();
        if ($File->exists()) {
            $File->delete();
        }
    }
}