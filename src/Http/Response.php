<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http;

use Ajthenewguy\Php8ApiServer\Application;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response as ReactResponse;
use React\Stream\ReadableStreamInterface;

class Response
{
    public static function make(
        string|ReadableStreamInterface|StreamInterface $body = '',
        int $status = 200,
        string|array $headers = [],
        string $version = '1.1',
        ?string $reason = null): ReactResponse
    {
        $App = Application::singleton();

        $headers = (array) $headers;
        $headers = array_merge((array) $App->config()->get('response-headers'), $headers);

        return new ReactResponse($status, $headers, $body, $version, $reason);
    }
}