<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Exceptions;

use Exception;

class ConfigNotFoundException extends FileNotFoundException
{
    public function __construct($path, Exception $previous = null)
    {
        $message = sprintf('Config file "%s" not found', $path);
        parent::__construct($message, $previous);
    }
}