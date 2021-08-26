<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer;

use Ajthenewguy\Php8ApiServer\Traits\MagicProxy;
use WyriHaximus\React\Http\Middleware\Session as BaseSession;

class Session
{
    use MagicProxy;

    public function __construct(BaseSession $Session = null)
    {
        if ($Session) {
            $this->proxied = $Session;
        }
    }

    public function all(): array
    {
        if (isset($this->proxied)) {
            return $this->proxied->getContents();
        }
        return [];
    }

    public function get(string $name, $default = null)
    {
        $contents = $this->all();

        return $contents[$name] ?? $default;
    }

    public function has(string $name): bool
    {
        $contents = $this->all();

        return isset($contents[$name]);
    }

    public function set(string $name, $value): static
    {
        $contents = $this->all();

        if ($value === null) {
            unset($contents[$name]);
            $this->proxied->setContents($contents);
        } else {
            $this->proxied->setContents(array_merge($contents, [
                $name => $value
            ]));
        }

        return $this;
    }
}