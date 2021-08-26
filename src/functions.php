<?php declare(strict_types=1);

use Ajthenewguy\Php8ApiServer\Application;
use Ajthenewguy\Php8ApiServer\Http\Request;

function Request()
{
    return Application::singleton()->Request ?? new Request(new \React\Http\Message\ServerRequest('', ''));
}

function Session()
{
    $Application = Application::singleton();
    if (isset($Application->Request)) {
        return $Application->Request?->Session() ?? new \Ajthenewguy\Php8ApiServer\Session();
    }
    return new \Ajthenewguy\Php8ApiServer\Session();
}