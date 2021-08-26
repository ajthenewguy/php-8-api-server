<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http;

use Ajthenewguy\Php8ApiServer\Repositories\UserRepository;
use Ajthenewguy\Php8ApiServer\Services\AuthService;
use Ajthenewguy\Php8ApiServer\Session;
use Ajthenewguy\Php8ApiServer\Traits\MagicProxy;
use Ajthenewguy\Php8ApiServer\Validation\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use React\Promise;
use WyriHaximus\React\Http\Middleware\SessionMiddleware;

class Request extends \RingCentral\Psr7\MessageTrait implements ServerRequestInterface
{
    use MagicProxy;

    private array $GET;

    private array $POST;

    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $this->proxied = $request;
        static::$proxiedClass = get_debug_type($request);

        $current = $request->getRequestTarget();

        if ($current !== $this->getSession('current')) {
            $this->putSession('previous', $this->getSession('current'));
        }
        $this->putSession('current', $current);
    }

    public static function redirect(string $location, int $statusCode = 302)
    {
        return Response::redirect($location, $statusCode);
    }

    public static function remoteAddress(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '';
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

    public function getSession(string $key, mixed $default = null)
    {
        if ($session = $this->session()) {
            $contents = $session->getContents();
        
            return $contents[$key] ?? $default;
        }
        return $default;
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

    public function is(string $uriPath): bool
    {
        $uriPath = '/' . ltrim($uriPath, '/');

        return $this->getUri()->getPath() === strtolower($uriPath);
    }

    public function post(?string $key = null): mixed
    {
        if (!isset($this->POST)) {
            $this->POST = [];

            if (in_array($this->contentType(), ['application/x-www-form-urlencoded', 'multipart/form-data'])) {
                $this->POST = $this->request->getParsedBody();
            } else {
                $this->POST = json_decode((string) $this->request->getBody(), true) ?? [];
            }
        }

        if ($key) {
            return $this->POST[$key];
        }

        return $this->POST;
    }

    public function pushSession(string $key, array ...$values): self
    {
        if ($this->session()) {
            $contents = $this->session()->getContents();
            if (!isset($contents[$key])) {
                $contents[$key] = [];
            }

            foreach ($values as $value) {
                $contents[$key][] = $value;
            }

            $this->session()->setContents($contents);
        }

        return $this;
    }

    public function putSession(string $key, mixed $value = null): self
    {
        if ($this->session()) {
            if ($contents = $this->session()->getContents()) {
                if ($value === null) {
                    unset($contents[$key]);
                    $this->session()->setContents($contents);
                } else {
                    $this->session()->setContents(array_merge($contents, [
                        $key => $value
                    ]));
                }
            }
        }

        return $this;
    }

    public function redirectBack($statusCode = 302)
    {
        $location = '/';
        if ($previous = $this->getSession('current')) {
            $location = $previous;
        }

        return Response::redirect($location, $statusCode);
    }

    public function redirectBackWithErrors(array $errors, $statusCode = 302)
    {
        $this->putSession('errors', $errors);

        return $this->redirectBack($statusCode);
    }

    public function Session()
    {
        return new Session($this->request->getAttribute(SessionMiddleware::ATTRIBUTE_NAME));
    }

    public function authenticatedApiUser(): Promise\PromiseInterface
    {
        if ($claims = AuthService::getClaims($this->httpRequest())) {
            return UserRepository::getById($claims->user_id)->then(function ($User) {
                return Promise\resolve($User);
            }, function () {
                return Promise\reject(new \Exception('User not logged in.'));
            });
        }

        return Promise\reject(new \Exception('User not logged in.'));
    }

    public function authenticatedSessionUser(): Promise\PromiseInterface
    {
        if ($session = $this->session()) {
            $contents = $session->getContents();

            if (isset($contents['User'])) {
                return Promise\resolve($contents['User']);
            } elseif (isset($contents['user_id'])) {
                return UserRepository::getById($contents['user_id']);
            }
        }
        return Promise\reject(new \Exception('User not logged in.'));
    }

    public function validate(array $rules, array $messages = []): Promise\PromiseInterface
    {
        $input = $this->input();

        $Validator = new Validator($rules, $messages);

        return $Validator->validate($input);
    }


    public function getServerParams()
    {
        return $this->request->getServerParams();
    }

    public function getCookieParams()
    {
        return $this->request->getCookieParams();
    }

    public function withCookieParams(array $cookies)
    {
        return $this->request->withCookieParams($cookies);
    }

    public function getQueryParams()
    {
        return $this->request->getQueryParams();
    }

    public function withQueryParams(array $query)
    {
        return $this->request->withQueryParams($query);
    }

    public function getUploadedFiles()
    {
        return $this->request->getUploadedFiles();
    }

    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->request->withUploadedFiles($uploadedFiles);
    }

    public function getParsedBody()
    {
        return $this->request->getParsedBody();
    }

    public function withParsedBody($data)
    {
        return $this->request->withParsedBody($data);
    }

    public function getAttributes()
    {
        return $this->request->getAttributes();
    }

    public function getAttribute($name, $default = null)
    {
        return $this->request->getAttribute($name, $default);
    }

    public function withAttribute($name, $value)
    {
        return $this->request->withAttribute($name, $value);
    }

    public function withoutAttribute($name)
    {
        return $this->request->withoutAttribute($name);
    }

    public function getRequestTarget()
    {
        return $this->request->getRequestTarget();
    }

    public function withRequestTarget($requestTarget)
    {
        return $this->request->withRequestTarget($requestTarget);
    }

    public function getMethod()
    {
        return $this->request->getMethod();
    }

    public function withMethod($method)
    {
        return $this->request->withMethod($method);
    }

    public function getUri()
    {
        return $this->request->getUri();
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->request->withUri($uri, $preserveHost);
    }

    public function withHeader($header, $value)
    {
        return $this->request->withHeader($header, $value);
    }


    public static function __callStatic($name, $args = [])
    {
        if ($name === 'ip') {
            return static::remoteAddress();
        }
    }

    public function __call($name, $args = [])
    {
        if ($name === 'ip') {
            return static::remoteAddress();
        } elseif ($name === 'user') {
            if ($this->contentType() === 'application/json') {
                return $this->authenticatedApiUser();
            } else {
                return $this->authenticatedSessionUser();
            }
        }

        return call_user_func_array([$this->request, $name], $args);
    }

    public function __get(string $name)
    {
        if ($name === 'ip') {
            return static::remoteAddress();
        }
    }
}