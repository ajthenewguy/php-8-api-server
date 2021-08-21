<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Commands;

use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Filesystem\File;
use Ajthenewguy\Php8ApiServer\Filesystem\Directory;
use Ajthenewguy\Php8ApiServer\Str;

final class DbMigrateCommand extends Command
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
        $batch = Query::table('migrations')->addSelect('max(batch) AS batch')->value('batch') ?? 0;
        $MigrationsDirectory = new Directory(dirname(dirname(__DIR__)) . '/migrations');
        $migrationFiles = $MigrationsDirectory->files();
        $names = array_map(function (File $Migration) {
            return Str::rprune($Migration->getFilename(), '.php');
        }, $migrationFiles);
        $Migrations = Query::table('migrations')->whereIn('migration', $names)->get();
        $alreadyRun = $Migrations->column('migration')->toArray();
        $toBeRun = [];

        foreach ($migrationFiles as $Migration) {
            $migrationName = Str::rprune($Migration->getFilename(), '.php');
            if (!in_array($migrationName, $alreadyRun)) {
                $toBeRun[$migrationName] = $Migration;
            }
        }

        if (count($toBeRun) > 0) {
            try {
                foreach ($toBeRun as $migrationName => $MigrationFile) {
                    $stdio->write('Migrating ' . $MigrationFile->getFilename() . '...' . PHP_EOL);

                    require $MigrationFile->getPath();

                    $fp = fopen($MigrationFile->getPath(), 'r');
                    $class = $buffer = '';
                    $i = 0;
                    while (!$class) {
                        if (feof($fp)) break;

                        $buffer .= fread($fp, 512);
                        $tokens = token_get_all($buffer);

                        if (strpos($buffer, '{') === false) continue;

                        for (; $i < count($tokens); $i++) {
                            if ($tokens[$i][0] === T_CLASS) {
                                for ($j = $i + 1; $j < count($tokens); $j++) {
                                    if ($tokens[$j] === '{') {
                                        $class = $tokens[$i + 2][1];
                                    }
                                }
                            }
                        }
                    }

                    $class = '\\' . $class;

                    $MigrationClass = new $class;

                    $MigrationClass->up();

                    Query::table('migrations')->insert([
                        'migration' => $migrationName,
                        'batch' => $batch + 1
                    ]);

                    $stdio->write('Migrated ' . $MigrationFile->getFilename() . '.' . PHP_EOL);
                }
            } catch (\Throwable $e) {
                $stdio->write('Migration error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
            }
        } else {
            $stdio->write('Nothing to migrate.' . PHP_EOL);
        }

        $stdio->end();
        return;
    }
}