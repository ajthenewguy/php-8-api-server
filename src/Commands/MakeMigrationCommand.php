<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Commands;

use Ajthenewguy\Php8ApiServer\Filesystem\File;
use Ajthenewguy\Php8ApiServer\Str;

final class MakeMigrationCommand extends Command
{
    public function run()
    {
        $args = func_get_args();
        $app = array_shift($args);

        if (!($app instanceof \Ajthenewguy\Php8ApiServer\Application)) {
            throw new \InvalidArgumentException();
        }

        if (!$app->db()) {
            throw new \Exception('Database not configured.');
        }

        $stdio = $this->stdio();
        $stdio->setPrompt('Name of migration > ');

        $stdio->on('data', function ($line) use ($stdio) {
            $line = rtrim($line);

            if ($line === 'quit' || empty($line)) {
                $stdio->end();
                return;
            }

            $filename = preg_replace('/\s+/', '_', trim($line));

            $filename = 'Migration_' . date('Y_m_d_') . $filename . '.php';
            $destination = dirname(dirname(__DIR__)) . '/migrations/';
            $stdio->write('Wrting: ' . $filename . ' to ' . $destination . PHP_EOL);

            $Migration = new File($destination . $filename);

            if ($Migration->exists()) {
                $stdio->write($Migration->getPath() . ' already exists.' . PHP_EOL);
            } elseif ($Migration->create()) {
                $parts = explode('_', Str::prune($filename, '.php'));
                $className = implode('_', array_map('ucfirst', $parts));
                $body = <<<EOT
<?php declare(strict_types=1);

use Ajthenewguy\Php8ApiServer\Database\Migration;
use Ajthenewguy\Php8ApiServer\Database\Query;

class $className extends Migration
{
    public function up()
    {
        Query::driver()->exec("");
    }

    public function down()
    {
        Query::driver()->exec("");
    }
}

EOT;
                $Migration->write($body);
                $stdio->write($Migration->getPath() . ' written.' . PHP_EOL);
            } else {
                $stdio->write('Error writing ' . $Migration->getPath() . PHP_EOL);
            }

            $stdio->end();
            return;
        });
    }
}