<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http;

use Ajthenewguy\Php8ApiServer\Str;
use Psr\Http\Message\StreamInterface;
use React\Http\Message\Response as ReactResponse;
use React\Stream\ReadableStreamInterface;

class JsonResponse extends Response
{
    public static function make(
        string|ReadableStreamInterface|StreamInterface|array|\stdClass $body = '',
        int $status = 200,
        string|array $headers = [],
        string $version = '1.1',
        ?string $reason = null
    ): ReactResponse {

        if (is_string($body)) {
            $body = trim($body);

            if ($body[0] !== '{') {
                $body = json_encode($body, JSON_THROW_ON_ERROR);
            }
        } else {
            $body = json_encode($body, JSON_THROW_ON_ERROR);
        }

        if (!isset($headers['Content-Type']) || !Str::endsWith($headers['Content-Type'], 'json')) {
            $headers['Content-Type'] = 'application/vnd.api+json';
        }

        return parent::make($body, $status, $headers, $version, $reason);
    }
}
