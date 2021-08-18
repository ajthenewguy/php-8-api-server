<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Routing;

use Ajthenewguy\Php8ApiServer\Auth\Jwt;
use Psr\Http\Message\ServerRequestInterface;

class Guard
{
    public function __construct(
        private array $requiredClaims = []
    ) {}

    public function getClaims(ServerRequestInterface $request): ?\stdClass
    {
        if ($token = $this->extractToken($request)) {
            return Jwt::decodeToken($token);
        }

        return null;
    }

    public function validate(ServerRequestInterface $request)
    {
        if ($claims = $this->getClaims($request)) {
            // validate public claims
            if (isset($claims->exp)) {
                if (time() + 60 > intval($claims->exp)) {
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

            return true;
        }

        return false;
    }

    /**
     * Get the JWT token from the Authorization header.
     */
    protected function extractToken(ServerRequestInterface $request): ?string
    {
        $authHeader = $request->getHeader('Authorization');

        if (!empty($authHeader) && preg_match("/Bearer\s+(.*)$/i", $authHeader[0], $matches)) {
            return $matches[1];
        }

        return null;
    }
}