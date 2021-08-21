<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Routing;

use Ajthenewguy\Php8ApiServer\Validation\Validator;
use React\Promise;
use React\Promise\PromiseInterface;

class Guard
{
    public function __construct(
        private array $requiredClaims = []
    ) {}

    public function validate(?\stdClass $claims): PromiseInterface
    {
        if ($claims) {
            // validate public claims
            if (isset($claims->exp)) {
                if (time() - 60 > intval($claims->exp)) {
                    // @todo - throw exception with message token is expired?
                    return Promise\resolve(false);
                }
            }

            if (isset($claims->nbf)) {
                if (time() - 60 < intval($claims->nbf)) {
                    // @todo - throw exception with message token is not yet valid?
                    return Promise\resolve(false);
                }
            }

            // extend this class to validate private claims
            if (!empty($this->requiredClaims)) {
                $Validator = new Validator($this->requiredClaims);
                
                return $Validator->validate((array) $claims);
            }

            return Promise\resolve(true);
        }

        return Promise\resolve(false);
    }
}