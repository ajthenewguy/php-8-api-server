<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Routing;

use Ajthenewguy\Php8ApiServer\Validation\Validator;

class Guard
{
    public function __construct(
        private array $requiredClaims = []
    ) {}

    public function validate(?\stdClass $claims)
    {
        if ($claims) {
            // validate public claims
            if (isset($claims->exp)) {
                if (time() - 60 > intval($claims->exp)) {
                    // @todo - throw exception with message token is expired?
                    return false;
                }
            }

            if (isset($claims->nbf)) {
                if (time() - 60 < intval($claims->nbf)) {
                    // @todo - throw exception with message token is not yet valid?
                    return false;
                }
            }

            // extend this class to validate private claims
            if (!empty($this->requiredClaims)) {
                $Validator = new Validator($this->requiredClaims);
                
                return $Validator->passes((array) $claims);
            }

            return true;
        }

        return false;
    }
}