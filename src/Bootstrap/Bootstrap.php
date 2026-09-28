<?php

namespace Src\Bootstrap;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use Src\Helpers\Http;
use Throwable;

/**
 * Arranca la aplicación Slim y atiende la petición actual.
 *
 * Orden de arranque (ver run()):
 *   1. Carga el autoload de Composer (vendor/autoload.php).
 *   2. Lee el archivo .env (LoadEnv).
 *   3. Crea la app de Slim.
 *   4. Registra las rutas de cada "Router Model" de $Models (LoadRoutes).
 *   5. Agrega los middleware globales (SetupMiddleware).
 *   6. Atiende la petición y envía la respuesta ($App->run()).
 *
 * La app de Slim se guarda en una propiedad estática para que los traits de
 * rutas puedan obtenerla con Bootstrap::getBootstrapApp().
 */
class Bootstrap
{

    /** Instancia de Slim\App compartida por toda la aplicación. */
    private static $App;

    /**
     * Clases que registran rutas. Para agregar un grupo de rutas nuevo, crea su
     * clase en src/Router/Models/<Nombre>/ y agrégala a esta lista.
     */
    private static $Models = [
        \Src\Router\Models\Pages\Pages::class,
        \Src\Router\Models\Users\Users::class,
        \Src\Router\Models\Children\Children::class,
        \Src\Router\Models\Locations\Locations::class,
        \Src\Router\Models\Zones\Zones::class,
    ];

    /** Punto de entrada; lo llama index.php en cada petición. */
    public static function run()
    {
        require dirname(__DIR__, 2) . '/vendor/autoload.php';

        Bootstrap::LoadEnv();

        Bootstrap::$App = AppFactory::create();

        Bootstrap::LoadRoutes();
        Bootstrap::SetupMiddleware();

        Bootstrap::$App->run();
    }


    /**
     * Carga las variables del archivo .env en $_ENV.
     *
     * safeLoad() no falla si no existe el .env. "Immutable" significa que no pisa
     * variables que ya estén definidas en el entorno del servidor.
     */
    private static function LoadEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->safeLoad();
    }

    /**
     * Registra los middleware globales.
     *
     * Un middleware es una función que envuelve a la petición: puede hacer algo
     * antes de que llegue a la ruta y/o cambiar la respuesta después. En Slim el
     * ÚLTIMO middleware agregado es el que se ejecuta PRIMERO (queda más afuera),
     * por eso el de errores se agrega al final: así envuelve a todos los demás y
     * atrapa cualquier excepción. El recorrido queda así:
     *
     *   petición → errores → routing → cabeceras de seguridad → middleware de la ruta → ruta
     */
    private static function SetupMiddleware()
    {
        // Cabeceras de seguridad en todas las respuestas: que el navegador no adivine
        // tipos de archivo (nosniff), que ningún sitio nos meta en un <iframe> (DENY)
        // y que no se filtre nuestra URL completa a otros sitios (Referrer-Policy).
        Bootstrap::$App->add(function (Request $request, RequestHandler $handler): Response {
            return $handler->handle($request)
                ->withHeader('X-Content-Type-Options', 'nosniff')
                ->withHeader('X-Frame-Options', 'DENY')
                ->withHeader('Referrer-Policy', 'same-origin');
        });

        // Decide qué ruta corresponde a la URL y al método HTTP de la petición.
        Bootstrap::$App->addRoutingMiddleware();

        // Con APP_DEBUG=true el mensaje de error incluye el detalle técnico (sólo para desarrollo).
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

    /** Crea cada Router Model de $Models y le pide que registre sus rutas. */
    private static function LoadRoutes()
    {
        foreach(Bootstrap::$Models as $model){
            (new $model())->addRoutes();
        }
    }

    /** Devuelve la app de Slim; los traits de rutas la usan para registrar get/post/delete. */
    public static function getBootstrapApp()
    {
        return Bootstrap::$App;
    }

}
