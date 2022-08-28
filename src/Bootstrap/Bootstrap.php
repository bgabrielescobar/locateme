<?php

namespace Src\Bootstrap;

use Slim\Factory\AppFactory;
use DI\Container;
use Src\Helpers\DBConnection;

class Bootstrap 
{

    private static $App; 

    private static $Models = [
        \Src\Router\Models\Locations\Locations::class,
        \Src\Router\Models\Users\Users::class
    ];

    public static function run()
    {
        require dirname(__DIR__, 2) . '/vendor/autoload.php';

        Bootstrap::LoadContainer();

        Bootstrap::$App = AppFactory::create();

        Bootstrap::LoadEnv();
        Bootstrap::LoadRoutes();
        Bootstrap::SetupHeaders();    

        Bootstrap::$App->run();
    }


    private static function LoadEnv()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
        $dotenv->load();
    }

    private static function LoadContainer()
    {
        $container = new Container();
        AppFactory::setContainer($container);
        
        /*$container->set('db', function () {
            return new DBConnection();
          });*/
    }

    private static function SetupHeaders()
    {
        header("Access-Control-Allow-Origin: *");
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