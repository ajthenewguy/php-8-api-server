<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Attr;

#[\Attribute]
class ConfigAttribute
{
    public function __construct(
        private string $key,
        private mixed $default = null
    ) {}
}