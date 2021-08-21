<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http;

use Ajthenewguy\Php8ApiServer\Traits\MagicProxy;
use Ajthenewguy\Php8ApiServer\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;

class Request
{
    use MagicProxy;

    private array $GET;

    private array $POST;

    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $this->proxied = $request;
        static::$proxiedClass = get_debug_type($request);
    }

    public function contentType(): string
    {
        return $this->request->getHeader('Content-Type')[0] ?? '';
    }

    public function files()
    {
        return $this->request->getUploadedFiles();
    }

    public function get(?string $key = null): array
    {
        if (!isset($this->GET)) {
            $this->GET = $this->request->getQueryParams();
        }

        if ($key) {
            return $this->GET[$key];
        }

        return $this->GET;
    }

    public function httpRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function input(?string $key = null): mixed
    {
        $input = array_merge($this->get(), $this->post());

        if ($key) {
            return $input[$key];
        }

        return $input;
    }

    public function post(?string $key = null): mixed
    {
        if (!isset($this->POST)) {
            $this->POST = [];

            if (in_array($this->contentType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
                $this->POST = $this->request->getParsedBody();
            } else {
                $this->POST = json_decode((string) $this->request->getBody(), true);
            }
        }

        if ($key) {
            return $this->POST[$key];
        }

        return $this->POST;
    }

    public function validate(array $rules, array $messages = []): PromiseInterface
    {
        $POST = $this->post();

        $Validator = new Validator($rules, $messages);

        return $Validator->validate($POST);
    }
}