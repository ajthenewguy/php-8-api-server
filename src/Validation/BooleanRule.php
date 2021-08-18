<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Validation;

class BooleanRule extends RegexRule
{
    protected string $name = 'boolean';

    /**
     * @return string
     */
    public function message(): string
    {
        return 'The ":attribute" must a boolean.';
    }

    /**
     * @param string $name
     * @param mixed $input
     * @return bool
     */
    public function passes(string $name, $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN) !== false;
    }
}