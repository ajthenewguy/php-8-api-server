<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Exceptions;

use Exception;

class SystemInterfaceException extends Exception
{
    public function __construct(string $message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}