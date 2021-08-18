<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Exceptions\Http;

use Exception;

class ServerError extends Exception
{
    public function __construct($message = 'Server Error', int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}