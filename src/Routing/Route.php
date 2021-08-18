<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Routing;

use Ajthenewguy\Php8ApiServer\Collection;
use Ajthenewguy\Php8ApiServer\Str;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;

class Route
{
    public static Collection $Table;

    private Collection $Parameters;

    public function __construct(
        private string $method,
        private string $uri,
        private $action,
        private ?Guard $Guard = null
    )
    {}

    public static function delete(string $uri, callable|array $action, ?Guard $Guard = null)
    {
        static::registerRoute('DELETE', $uri, $action, $Guard);
    }

    public static function get(string $uri, callable|array $action, ?Guard $Guard = null)
    {
        static::registerRoute('GET', $uri, $action, $Guard);
    }

    public static function lookup(string $method, string $url)
    {
        $Matches = static::$Table->filter(function (Route $Route) use ($method, $url) {
            return $Route->matches($method, $url);       
        });

        if (!$Matches->empty()) {
            $Matches = $Matches->sort(function (Route $RouteA, Route $RouteB) use ($url) {
                $compareA = Str::before($RouteA->getUri(), '{') ?: $RouteA->getUri();
                $compareB = Str::before($RouteB->getUri(), '{') ?: $RouteB->getUri();
                $levA = levenshtein($url, $compareA);
                $levB = levenshtein($url, $compareB);

                if ($levA === $levB) {
                    return 0;
                }
                return $levB > $levA ? -1 : 1;
            });
        } else {
            // throw new NotFoundException($url);
        }

        return $Matches->first();
    }

    public static function patch(string $uri, callable|array $action, ?Guard $Guard = null)
    {
        static::registerRoute('PATCH', $uri, $action, $Guard);
    }

    public static function post(string $uri, callable|array $action, ?Guard $Guard = null)
    {
        static::registerRoute('POST', $uri, $action, $Guard);
    }

    public static function put(string $uri, callable|array $action, ?Guard $Guard = null)
    {
        static::registerRoute('PUT', $uri, $action, $Guard);
    }

    public static function table(): Collection
    {
        return static::$Table;
    }

    public function dispatch(ServerRequestInterface $request, array $parameters): Response
    {
        $action = $this->getAction();
        $response = null;
        array_unshift($parameters, $request);

        if (is_array($action)) {
            $response = call_user_func_array($action, ...$parameters);
        } elseif (is_callable($action)) {
            $response = $action(...$parameters);
        }

        if ($response !== null) {
            if ($response instanceof Response) {
                return $response;
            }
            if (strlen($response) > 0) {
                return new Response(200, ['Content-Type' => 'application/json'], $response);
            }
            return new Response(204, ['Content-Type' => 'application/json'], $response);
        }

        return new Response(404, ['Content-Type' => 'application/json'], 'Not found');
    }

    public function getAction(): array|callable
    {
        return $this->action;
    }

    public function getGuard(): ?Guard
    {
        return $this->Guard;
    }

    public function getId(): string
    {
        return $this->method . ':' . $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameter(string $name): ?RouteParameter
    {
        return $this->getParameters()->first(function (RouteParameter $Parameter) use ($name) {
            return $Parameter->getName() === $name;
        });
    }

    public function getParameters(): Collection
    {
        if (!isset($this->Parameters)) {
            $this->Parameters = $this->parseParameters();
        }

        return $this->Parameters;
    }
    
    public function getUri(): string
    {
        return $this->uri;
    }

    public function hasParams(): bool
    {
        return $this->paramCount() > 0;
    }

    public function matches(string $method, string $url): bool
    {
        if ($this->getMethod() !== 'ANY' && $this->getMethod() !== strtoupper($method)) {
            return false;
        }

        if ($this->getUri() === $url && !$this->hasParams()) {
            return true;
        }

        $matches = $this->matchParameters($url);
        $Parameters = $this->getParameters();
        $RequiredParameters = $Parameters->filter(function (RouteParameter $Parameter) {
            return $Parameter->isRequired();
        });

        if (empty($matches) && $RequiredParameters->count() > 0) {
            return false;
        }

        $Parameters->each(function (RouteParameter $Parameter) use ($matches) {
            if ($Parameter->isRequired() && !isset($matches[$Parameter->getName()])) {
                return false;
            }
        });

        return true;
    }

    /**
     * Given a requested URL match and fill the values.
     * 
     * @param string $url
     * @return array
     */
    public function matchParameters(string $url)
    {
        $values = [];
        $routeUri = $this->getUri();
        $pattern = '#^' . $routeUri;

        $this->getParameters()->each(function ($Parameter) use (&$pattern) {
            $parameterName = $Parameter->getName();
            $search = '';
            $replace = '';
            if ($Parameter->isRequired()) {
                $search = '{' . $parameterName . '}';
                $replace = '(?P<' . $parameterName . '>[a-z0-9_-]+)';
            } else {
                $search = '/{' . $parameterName . '?}';
                $replace = '(?:/(?P<' . $parameterName . '>[a-z0-9_-]+))?';
            }
            $pattern = str_replace($search, $replace, $pattern);
        });
        $pattern  .= '#i';

        if (preg_match_all($pattern, $url, $matches, PREG_OFFSET_CAPTURE | PREG_PATTERN_ORDER)) {
            $this->getParameters()->each(function (RouteParameter $Parameter) use ($matches, &$values) {
                foreach ($matches as $key => $match) {
                    if ($key === $Parameter->getName() && strlen($match[0][0]) > 0) {
                        $values[$key] = $match[0][0];
                    }
                }
            });
        }

        return $values;
    }

    public function paramCount(): int
    {
        return $this->getParameters()->count();
    }

    /**
     * Parse the parameter placeholders in the URI.
     * 
     * @return Collection
     */
    private function parseParameters(): Collection
    {
        $offset = 0;
        $closePos = 0;
        $Parameters = new Collection();

        while ($openPos = strpos($this->uri, '{', $offset)) {
            if ($closePos = strpos($this->uri, '}', $openPos)) {
                $length = $closePos - $openPos - 1;
                $name = substr($this->uri, $openPos + 1, $length);
                $required = true;

                if (Str::endsWith($name, '?')) {
                    $required = false;
                    $name = rtrim($name, '?');
                }

                $Parameters->push(new RouteParameter($name, $required));
                $offset = $closePos;
            }
        }

        return $Parameters;
    }

    private static function registerRoute(string $method, string $uri, callable|array $action, ?Guard $Guard = null)
    {
        if (!isset(static::$Table)) {
            static::$Table = new Collection();
        }

        $method = strtoupper($method);
        $id = $method . ':' . $uri;

        static::$Table->set($id, new static($method, $uri, $action, $Guard));
    }
}