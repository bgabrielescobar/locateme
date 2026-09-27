<?php

namespace Src\Bootstrap;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use Src\Helpers\Http;
use Throwable;

class Bootstrap
{

    private static $App;

    private static $Models = [
        \Src\Router\Models\Pages\Pages::class,
        \Src\Router\Models\Users\Users::class,
        \Src\Router\Models\Children\Children::class,
        \Src\Router\Models\Locations\Locations::class,
        \Src\Router\Models\Zones\Zones::class,
    ];

    public static function run()
    {
        require dirname(__DIR__, 2) . '/vendor/autoload.php';

        Bootstrap::LoadEnv();

        Bootstrap::$App = AppFactory::create();

        Bootstrap::LoadRoutes();
        Bootstrap::SetupMiddleware();

        Bootstrap::$App->run();
    }


    private static function LoadEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();
    }

    private static function SetupMiddleware()
    {
        Bootstrap::$App->add(function (Request $request, RequestHandler $handler): Response {
            return $handler->handle($request)
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('Referrer-Policy', 'same-origin');
        });

        Bootstrap::$App->addRoutingMiddleware();

        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $errorMiddleware = Bootstrap::$App->addErrorMiddleware($debug, true, true);

        // Errores siempre en JSON con un mensaje entendible; el detalle técnico va al log
        $errorMiddleware->setDefaultErrorHandler(function (Request $request, Throwable $exception) use ($debug) {
            $status = $exception instanceof HttpException ? $exception->getCode() : 500;

            if ($status >= 500) {
                error_log((string) $exception);
            }

            $message = match (true) {
                $status === 404 => 'No encontrado.',
                $status === 405 => 'Método no permitido.',
                $status >= 500  => 'Ocurrió un error en el servidor. Intenta de nuevo en un momento.',
                default         => $exception->getMessage(),
            };

            if ($debug && $status >= 500) {
                $message .= ' (' . $exception->getMessage() . ')';
            }

            return Http::error(Bootstrap::$App->getResponseFactory()->createResponse(), $message, $status);
        });
    }

    private static function LoadRoutes()
    {
        foreach(Bootstrap::$Models as $model){
            (new $model())->addRoutes();
        }
    }

    public static function getBootstrapApp()
    {
        return Bootstrap::$App;
    }

}
