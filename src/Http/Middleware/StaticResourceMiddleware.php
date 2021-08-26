<?php declare(strict_types=1);

namespace Ajthenewguy\Php8ApiServer\Http\Middleware;

use Ajthenewguy\Php8ApiServer\Attr\ConfigAttribute;
use Ajthenewguy\Php8ApiServer\Http\Request;
use Ajthenewguy\Php8ApiServer\Http\Response;
use Ajthenewguy\Php8ApiServer\Str;
use Psr\Http\Message\ServerRequestInterface;

class StaticResourceMiddleware extends Middleware
{
    public function __construct(
        #[ConfigAttribute('static-files.path', 'public')]
        private string $path
    ) {}

    public function __invoke(ServerRequestInterface $request, $next)
    {   
        $rootPath = $this->path;
        $filePath = $request->getUri()->getPath();

        if (!Str::startsWith($rootPath, DIRECTORY_SEPARATOR)) {
            $rootPath = ROOT_PATH . DIRECTORY_SEPARATOR . $rootPath;
        }

        $file = $rootPath . $filePath;

        if (file_exists($file) && !is_dir($file)) {
            $fileExt = pathinfo($file, PATHINFO_EXTENSION);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $fileType = finfo_file($finfo, $file);
            finfo_close($finfo);
            $fileContents = file_get_contents($file);

            // Fix for incorrect mime types
            switch ($fileExt) {
                case 'css':
                    $fileType = 'text/css';

                    break;
                case 'js':
                    $fileType = 'application/javascript';

                    break;
            }

            return Response::make($fileContents, 200, ['Content-Type' => $fileType]);
        }
        
        $request = new Request($request);
        

        return $next($request);
    }
}