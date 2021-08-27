<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Http\Request;

class VerifyCsrfMiddleware extends Middleware
{
    public function __invoke(Request $request, callable $next)
    {
        $requestMethod = $request->getMethod();

        if (!$request->expectsJson() && in_array(strtoupper($requestMethod), ['PATCH', 'POST', 'PUT', 'DELETE'])) {
            $presented = $request->input('_csrf');

            if (!$presented || $presented !== $request->Session()->token()) {
                return $request->redirectBackWithErrors(['token' => ['Form stale, please try again.']]);
            }
        }

        return $next($request);
    }
}
