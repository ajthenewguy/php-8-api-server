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

    protected function setUpDatabase(): \PDO
    {
        if (!isset($this->app)) {
            $this->app = Application::singleton();
        }

        $this->db = new \PDO(sprintf('sqlite:%s', $this->getDatabaseFile()->getPath()), '', '', array(
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ));

        $this->app->bindInstance(Driver::class, $this->db);
        Query::app($this->app);

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