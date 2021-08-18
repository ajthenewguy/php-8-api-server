<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Commands;

use Clue\React\Stdio\Stdio;
use React\EventLoop\Loop;

abstract class Command
{
    abstract public function run();

    /**
     * Get a input/output instance.
     */
    protected function stdio()
    {
        return new Stdio(Loop::get());
    }
    
    /**
     * Invoke the run method.
     */
    public function __invoke()
    {
        $args = func_get_args();

        return $this->run(...$args);
    }
}