<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer;

use Ajthenewguy\Php8ApiServer\Config\Json;
use Ajthenewguy\Php8ApiServer\Database\Drivers\Driver as DatabaseDriver;
use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Exceptions\FileNotFoundException;
use Ajthenewguy\Php8ApiServer\Facades\Log;
use Ajthenewguy\Php8ApiServer\Filesystem\File;
use Ajthenewguy\Php8ApiServer\Http\Middleware\Middleware;
use Ajthenewguy\Php8ApiServer\Reporting\Logger;
use Ajthenewguy\Php8ApiServer\Traits\HasConfig;
use Ajthenewguy\Php8ApiServer\Traits\RequiresBinary;
use Ajthenewguy\Php8ApiServer\Traits\SystemInterface;
use Psr\Http\Message\ServerRequestInterface;

class Application
{
    use HasConfig, RequiresBinary, SystemInterface;

    protected static Application $instance;

    protected static array $commands;

    protected array $instances;

    protected Collection $Middlewares;

    protected array $providers;

    protected function __construct(\Dotenv\Dotenv $dotenv = null)
    {
        if ($dotenv) {
            $dotenv->load();
        }

        $this->configure();
    }

    public static function singleton(?\Dotenv\Dotenv $dotenv = null): static
    {
        if (!isset(static::$instance)) {
            static::$instance = new static($dotenv);
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
    public function runCommand(string $name, array $arguments = [])
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
            $File = new File($path);

            if (!$File->exists()) {
                throw new FileNotFoundException($path);
            }

            switch ($File->extension) {
                case 'json':
                    $this->setConfig(new Json($path));
                    break;
                default:
                    $this->setConfig(include($path));
                    break;
            }
        } else {
            $this->setConfig();
        }

        $this->configureSecurityKeys();
        $this->configureDatabase();
        $this->configureLogging();

        // Set the token lifetime
        if (isset($_ENV['APP_TOKEN_LIFETIME_MINS']) && !empty($_ENV['APP_TOKEN_LIFETIME_MINS'])) {
            $this->config()->set('security.tokenLifetime', $_ENV['APP_TOKEN_LIFETIME_MINS']);
        } else {
            $this->config()->set('security.tokenLifetime', 15);
        }
    }

    /**
     * Scan configuration and ENV vars for database configuration information.
     */
    protected function configureDatabase(): bool
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
                Query::app($this);

                try {
                    $this->db()->exec('SELECT 1 FROM migrations');
                } catch (\Throwable $e) {
                    $this->db()->exec('CREATE TABLE IF NOT EXISTS migrations (
                        id INTEGER PRIMARY KEY,
                        migration VARCHAR (128) NOT NULL,
                        batch INTEGER NOT NULL DEFAULT 1
                    )');
                }

                return true;
            }
        }

        return false;
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

        Log::app($this);
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

    public function close()
    {
        if ($this->db()) {
            $this->db()->quit();
        }
    }
}