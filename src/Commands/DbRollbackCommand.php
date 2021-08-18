<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Commands;

use Ajthenewguy\Php8ApiServer\Database\Query;
use Ajthenewguy\Php8ApiServer\Filesystem\File;
use Ajthenewguy\Php8ApiServer\Filesystem\Directory;

final class DbRollbackCommand extends Command
{
    public function run()
    {
        $args = func_get_args();
        $app = array_shift($args);
        $batches = array_shift($args) ?? 0;

        if (!($app instanceof \Ajthenewguy\Php8ApiServer\Application)) {
            throw new \InvalidArgumentException();
        }

        if (!$app->db()) {
            throw new \Exception('Database not configured.');
        }

        $stdio = $this->stdio();
        try {
            Query::transaction(function () use ($stdio, $batches) {
                $MigrationsDirectory = new Directory(dirname(dirname(__DIR__)) . '/migrations');
                $Migrations = Query::table('migrations')->orderBy('batch', 'desc')->get();

                if ($Migrations->empty()) {
                    $stdio->write('Nothing to roll back.' . PHP_EOL);
                }

                $batchStop = -1;
                foreach ($Migrations as $Migration) {
                    $batch = intval($Migration->batch);

                    if ($batches > 0 && $batchStop < 0) {
                        $batchStop = max($batch - $batches, 0);
                    }

                    if ($batch <= $batchStop) {
                        break;
                    }

                    $filename = $Migration->migration . '.php';
                    $MigrationFile = new File($MigrationsDirectory->path($filename));

                    if (!$MigrationFile->exists()) {
                        $stdio->write('Error: file for migration ' . $Migration->migration . ' not found.' . PHP_EOL);
                        $stdio->end();
                        return;
                    }

                    $stdio->write('Rolling back ' . $MigrationFile->getFilename() . '...' . PHP_EOL);

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
                    $MigrationClass->down();

                    Query::table('migrations')->where('id', $Migration->id)->delete();

                    $stdio->write('Rolled back ' . $MigrationFile->getFilename() . '.' . PHP_EOL);
                }
            });
        } catch (\Throwable $e) {
            $stdio->write('Migration error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL);
        }

        $stdio->end();
        return;
    }
}