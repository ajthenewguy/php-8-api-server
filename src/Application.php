<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer;

use Ajthenewguy\Php8ApiServer\Config;
use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver as DatabaseDriver;
use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Exceptions\ConfigurationException;
use Ajthenewguy\Php8ApiServer\Exceptions\FileNotFoundException;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Filesystem;
use Ajthenewguy\Php8ApiServer\Http\Middleware\Middleware;
use Ajthenewguy\Php8ApiServer\Reporting\Logger;
use Ajthenewguy\Php8ApiServer\Traits\HasConfig;
use Ajthenewguy\Php8ApiServer\Traits\RequiresBinary;
use Ajthenewguy\Php8ApiServer\Traits\SystemInterface;
use Clue\React\Stdio\Stdio;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\Promise;

class Application
{
    use HasConfig, RequiresBinary, SystemInterface;

    protected static Application $instance;

    protected static array $commands;

    protected array $instances;

    protected Collection $Middlewares;

    protected array $providers;

    protected bool $inCommand = false;

    protected function __construct(\Dotenv\Dotenv $dotenv = null, bool $inCommand)
    {
        if ($dotenv) {
            $dotenv->load();
        }

        $this->inCommand = $inCommand;
        $this->configure();
    }

    public static function singleton(?\Dotenv\Dotenv $dotenv = null, bool $inCommand = false): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($dotenv, $inCommand);
        }

        return static::$instance;
    }

    public function bindCommand(string $name, callable $callback)
    {
        if (!isset(static::$commands)) {
            static::$commands = [];
        }

        static::$commands[$name] = $callback;
    }

    /**
     * @param string $class
     * @param mixed $instance
     * @return void
     */
    public function bindInstance(string $class, mixed $instance): void
    {
        if (!isset($this->instances)) {
            $this->instances = [];
        }

        $this->instances[$class] = $instance;
    }

    public function db()
    {
        return $this->instance(DatabaseDriver::class) ?? null;
    }

    /**
     * @param string $class
     * @param mixed $provider
     * @return void
     */
    public function defineProvider(string $class, callable $provider): void
    {
        if (!isset($this->providers)) {
            $this->providers = [];
        }

        $this->providers[$class] = $provider;
    }

    public function getConfigDirectoryPath(): string
    {
        return is_dir($_ENV['CONFIG_PATH'] ?? '') ? $_ENV['CONFIG_PATH'] : ROOT_PATH . '/config';
    }

    public function getMigrationsDirectoryPath(): string
    {
        return is_dir($_ENV['CONFIG_PATH'] ?? '') ? $_ENV['CONFIG_PATH'] : ROOT_PATH . '/migrations';
    }

    public function getPublicDirectoryPath(): string
    {
        return is_dir($_ENV['CONFIG_PATH'] ?? '') ? $_ENV['CONFIG_PATH'] : ROOT_PATH . '/public';
    }

    public function getPendingDatabaseMigrations(): Promise\PromiseInterface
    {
        $MigrationsDirectory = new Filesystem\Directory($this->getMigrationsDirectoryPath(ROOT_PATH));
        $migrationFiles = $MigrationsDirectory->files();
        $names = array_map(function (Filesystem\File $Migration) {
            return Str::rprune($Migration->getFilename(), '.php');
        }, $migrationFiles);

        return Query::table('migrations')->whereIn('migration', $names)->get()->then(
            function ($Migrations) use ($migrationFiles) {
                $toBeRun = [];
                if ($Migrations) {
                    $alreadyRun = [];
                    
                    if (!$Migrations->empty()) {
                        $alreadyRun = $Migrations->column('migration')->toArray();
                    }

                    foreach ($migrationFiles as $Migration) {
                        $migrationName = Str::rprune($Migration->getFilename(), '.php');
                        if (!in_array($migrationName, $alreadyRun)) {
                            $toBeRun[$migrationName] = $Migration;
                        }
                    }
                    
                    return $toBeRun;
                }
            }
        );
    }

    /**
     * Return the middleware stack.
     */
    public function handleRequest(): array
    {
        $Middleware = new Collection(null, Middleware::class);
        
        $this->Middlewares->each(function ($middleware) use ($Middleware) {
            $Middleware->push(new $middleware());
        });
        
        return $Middleware->toArray();
    }

    public function handleNext(ServerRequestInterface $request, callable $next, string $name)
    {
        if ($middleware = $this->Middlewares->get($name)) {
            $Middleware = new $middleware();
            return $Middleware($request, $next);
        }

        return $next($request);
    }

    /**
     * Check if a command is defined.
     * 
     * @param string $name
     * @return bool
     */
    public function hasCommand(string $name): bool
    {
        return isset(static::$commands[$name]);
    }

    /**
     * Attempt to resolve an instance of the provided class name.
     * 
     * @param string $class
     * @return mixed
     */
    public function instance(string $class): mixed
    {
        if (isset($this->instances) && isset($this->instances[$class])) {
            return $this->instances[$class];
        }

        return null;
    }

    /**
     * Resolve an instance of class.
     * 
     * @param string $class
     * @return mixed
     */
    public function make(string $class): mixed
    {
        if (isset($this->providers) && isset($this->providers[$class])) {
            // create new instance
            if (is_callable($this->providers[$class])) {
                return $this->providers[$class]($this);
            }
        }

        return null;
    }

    /**
     * Register a middleware.
     */
    public function registerMiddleware(string $name, string $middleware): void
    {
        if (!isset($this->Middlewares)) {
            $this->Middlewares = new Collection();
        }

        $this->Middlewares->set($name, $middleware);
    }

    /**
     * Run a command.
     */
    public function runCommand(string $name, array $arguments = []): Promise\PromiseInterface
    {
        if (!$this->hasCommand($name)) {
            throw new \InvalidArgumentException(sprintf('Command "%s" does not exist.', $name));
        }

        $callback = static::$commands[$name];

        return $callback($this, ...$arguments);
    }

    /**
     * Scan the .env file for a file path in "APP_CONFIG" and load into memory.
     */
    protected function configure(): void
    {
        if (isset($_ENV['APP_CONFIG'])) {
            $path = $_ENV['APP_CONFIG'];
            $File = new Filesystem\File($path);

            if (!$File->exists()) {
                throw new FileNotFoundException($path);
            }

            switch ($File->extension) {
                case 'json':
                    $this->setConfig(new Config\Json($path));
                    break;
                case 'php':
                    $this->setConfig(include($path));
                    break;
                default:
                    throw new ConfigurationException(sprintf("'%s': unsupported configuration file format.", $path));
                    break;
            }
        } else {
            $this->setConfig();
        }

        $this->configureLogging();
        $this->configureSecurityKeys();
        $this->configureDatabase()->then(function ($databaseInitialized) {
            $MigrationsDirectory = new Filesystem\Directory($this->getMigrationsDirectoryPath(ROOT_PATH));

            if ($databaseInitialized && !$MigrationsDirectory->empty()) {
                Log::info(sprintf('[ok] Database initialized - ready to run database migrations (%s db:migrate)', SCRIPT_NAME));
            } elseif ($databaseInitialized === false) {
                if (!$this->inCommand) {
                    $this->getPendingDatabaseMigrations()->then(function ($toBeRun) {
                        if (count($toBeRun) > 0) {
                            $FileList = array_map(function (Filesystem\File $File) {
                                return $File->filename;
                            }, $toBeRun);
                            Log::warning(sprintf("[warning] Found %d pending database migrations:\n\t - %s\n", count($toBeRun), implode("\n\t - ", $FileList)));
                            Log::info(sprintf("[info] run '%s db:migrate' before starting server.\n", SCRIPT_NAME));
                            // $stdio = new Stdio(Loop::get());
                            // $stdio->write(sprintf('[warning] Found %d pending database migrations (run %s db:migrate)', count($toBeRun), SCRIPT_NAME));
                            // $stdio->setPrompt('Would you like to run them now? [y/N] > ');

                            // $stdio->on('data', function ($line) use ($stdio) {
                            //     $line = rtrim($line, "\r\n");
                            //     // $stdio->write('Your input: ' . $line . PHP_EOL);
                            //     if (strlen($line) > 0 && strtoupper($line[0]) === 'Y') {
                            //         $Command = new Commands\DbMigrateCommand();
                            //         $Command->run($this)->done();
                            //     }
                            // });
                        }
                    })->done();
                }
            }
        }, function (\Throwable $e) {
            echo $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
        })->done();

        // Set the token lifetime
        if (isset($_ENV['APP_TOKEN_LIFETIME_MINS']) && !empty($_ENV['APP_TOKEN_LIFETIME_MINS'])) {
            $this->config()->set('security.tokenLifetime', $_ENV['APP_TOKEN_LIFETIME_MINS']);
        } else {
            $this->config()->set('security.tokenLifetime', 15);
        }

        $this->configureEmail();
    }

    /**
     * Scan configuration and ENV vars for database configuration information.
     */
    protected function configureDatabase(): Promise\PromiseInterface
    {
        $configuration = (array) $this->config()->get('database');
        $env = function ($key, $default = null) {
            $value = $default;
            if (isset($_ENV[$key])) {
                $value = $_ENV[$key];
                if ($value === '') {
                    $value = $default;
                }
            }
            
            return $value;
        };

        // If .env values exist they take priority over a configuration file
        if (isset($_ENV['DB_CONNECTION'], $_ENV['DB_DATABASE'])) {
            $configuration['driver'] =   $env('DB_DRIVER')   ?? $configuration['driver'];
            $configuration['username'] = $env('DB_USERNAME') ?? $configuration['username'] ?? null;
            $configuration['password'] = $env('DB_PASSWORD') ?? $configuration['password'] ?? null;

            switch ($configuration['driver']) {
                case 'mysql':
                    $configuration['host'] =        $env('DB_HOST')     ?? $configuration['host'];
                    $configuration['unix_socket'] = $env('DB_SOCKET')   ?? $configuration['unix_socket'];
                    $configuration['port'] =        $env('DB_PORT')     ?? $configuration['port'];
                    $configuration['dbname'] =      $env('DB_DATABASE') ?? $configuration['dbname'];
                    $configuration['charset'] =     $env('DB_CHARSET')  ?? $configuration['charset'];
                    break;
                case 'pgsql':
                case 'postgres':
                    $configuration['host'] =   $env('DB_HOST')     ?? $configuration['host'] ?? $configuration['hostaddr'];
                    $configuration['port'] =   $env('DB_PORT')     ?? $configuration['port'];
                    $configuration['dbname'] = $env('DB_DATABASE') ?? $configuration['dbname'];
                    break;
                case 'sqlite':
                case 'sqlite3':
                    $configuration['path'] = $env('DB_DATABASE') ?? $configuration['path'];
                    break;
                case 'sqlsrv':
                    $port = $env('DB_PORT') ?? $configuration['port'];
                    $configuration['Server'] =  ($env('DB_HOST')     ?? $configuration['host']) . ($port ? ',' . $port : '');
                    $configuration['Database'] = $env('DB_DATABASE') ?? $configuration['Database'];
                    break;
            }
        }

        if ($configuration) {
            $configuration = array_filter($configuration);

            if (!empty($configuration)) {
                $this->bindInstance(DatabaseDriver::class, DatabaseDriver::create($configuration));
                // Query::app($this);

                return $this->db()->query('SELECT 1 FROM migrations')->then(function () {
                    return false;
                }, function (\Throwable $e) {
                    echo $e->getMessage() . PHP_EOL;
                    $this->db()->exec('CREATE TABLE IF NOT EXISTS migrations (
                        id INTEGER PRIMARY KEY,
                        migration VARCHAR (128) NOT NULL,
                        batch INTEGER NOT NULL DEFAULT 1
                    )')->done();
                    return true;
                });
            }
        }

        return Promise\resolve(null);
    }

    public function configureEmail()
    {
        $env = function ($key, $default = null) {
            $value = $default;
            if (isset($_ENV[$key])) {
                $value = $_ENV[$key];
                if ($value === '') {
                    $value = $default;
                }
            }

            return $value;
        };

        if (isset($_ENV['MAIL_HOST'])) {
            $this->config()->set('mail.host', $env('MAIL_HOST', 'localhost'));
            $this->config()->set('mail.port', $env('MAIL_PORT', 25));
            $this->config()->set('mail.encryption', $env('MAIL_ENCRYPTION'));
            $this->config()->set('mail.username', $env('MAIL_USERNAME'));
            $this->config()->set('mail.password', $env('MAIL_PASSWORD'));
            $this->config()->set('mail.from.name', $env('MAIL_FROM_NAME'));
            $this->config()->set('mail.from.email', $env('MAIL_FROM_EMAIL'));
        }
    }

    /**
     * Scan configuration for logging configuration information.
     */
    protected function configureLogging()
    {
        $configuration = $this->config()->get('logger');
        $this->defineProvider(Logger::class, function ($app) use ($configuration) {
            return Logger::create($configuration);
        });

        // Log::app($this);
    }

    protected function configureSecurityKeys()
    {
        if (isset($_ENV['APP_PRIVATE_KEY']) && !empty($_ENV['APP_PRIVATE_KEY'])) {
            $privateKey = $_ENV['APP_PRIVATE_KEY'];
            if (file_exists($privateKey)) {
                $privateKey = file_get_contents($privateKey);
            }
            $this->config()->set('security.privateKey', $privateKey);
        }

        if (isset($_ENV['APP_PUBLIC_KEY']) && !empty($_ENV['APP_PUBLIC_KEY'])) {
            $publicKey = $_ENV['APP_PUBLIC_KEY'];
            if (file_exists($publicKey)) {
                $publicKey = file_get_contents($publicKey);
            }
            $this->config()->set('security.publicKey', $publicKey);
        }

        if (isset($_ENV['APP_KEY_ALGORITHM']) && !empty($_ENV['APP_KEY_ALGORITHM'])) {
            $this->config()->set('security.keyAlgorithm', $_ENV['APP_KEY_ALGORITHM']);
        }
    }
}