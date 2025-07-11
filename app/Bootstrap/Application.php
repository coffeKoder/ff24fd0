<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Bootstrap
 * @file        Application
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 09:43:35
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace App\Bootstrap;

use DI\Container as DI;
use App\Bootstrap\Container;
use App\Contracts\SettingsInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use Dotenv\Dotenv;
use Slim\App;
use \Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use Viex\Shared\Application\Handlers\HttpErrorHandler;
use Viex\Shared\Application\Handlers\ShutdownHandler;
use Viex\Shared\Application\Handlers\WhoopsErrorHandler;
use Viex\Shared\Application\ResponseEmitter\ResponseEmitter;

class Application {
   private DI $container;
   private App $app;
   private $callableResolver;
   private SettingsInterface $settings;
   private ServerRequestInterface $request;
   private HttpErrorHandler $errorHandler;
   private WhoopsErrorHandler $whoopsHandler;



   public function __construct() {
      // Cargar variables de entorno
      if (file_exists(__DIR__ . '/../../.env')) {
         $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
         $dotenv->load();
      }

      $this->container = (new Container())->container();
      AppFactory::setContainer($this->container);
      $this->app = AppFactory::create();
      $this->callableResolver = $this->app->getCallableResolver();
      $this->settings = $this->container->get(SettingsInterface::class);
      $serverRequestCreator = ServerRequestCreatorFactory::create();
      $this->request = $serverRequestCreator->createServerRequestFromGlobals();
   }

   public function getContainer(): DI {
      return $this->container;
   }

   public function addMiddleware($middleware): void {
      $this->app->add($middleware);
   }

   public function addRoutes(): self {
      // recorre el directorio de app/Routes y agrega los archivos que terminan en .php
      $files = glob(__DIR__ . '/../Routes/*.php');

      foreach ($files as $file) {
         $definitions = require $file;
         if (!is_callable($definitions)) {
            throw new \InvalidArgumentException("El archivo de definiciones debe retornar una función.");
         }
         // el archivo debe retornar una función anoniima que recibe el contenedor y retorna un array de definiciones
         if (!is_callable($definitions)) {
            throw new \InvalidArgumentException("El archivo de definiciones debe retornar una función anónima.");
         }
         $definitions($this->app);
      }

      return $this;
   }

   private function handlerManager() {
      // Determinar si estamos en modo debug
      $isDebugMode = $this->settings->get('debug', false);
      $displayErrorDetails = $this->settings->get('logger.displayErrorDetails', false);

      // Inicializar el WhoopsErrorHandler
      $this->whoopsHandler = new WhoopsErrorHandler(
         $displayErrorDetails,
         $isDebugMode
      );

      // Agregar middleware de error con configuración
      $this->app->addErrorMiddleware(
         $displayErrorDetails,
         $this->settings->get('logger.logErrors', false),
         $this->settings->get('logger.logErrorDetails', false)
      );

      // Create Error Handler
      $this->errorHandler = new HttpErrorHandler(
         $this->callableResolver,
         $this->app->getResponseFactory()
      );

      // Set trusted proxies if configured
      $shutdownHandler = new ShutdownHandler(
         $this->request,
         $this->errorHandler,
         $displayErrorDetails
      );

      register_shutdown_function($shutdownHandler);

   }

   private function exceptionHandler() {
      // Aquí puedes agregar la lógica para manejar excepciones no capturadas
      set_exception_handler(function (\Throwable $exception) {
         // Manejo de la excepción
         error_log($exception->getMessage());
         http_response_code(500);
         echo 'Internal Server Error';
      });
   }

   public function run(): void {

      $this->addRoutes();
      $this->handlerManager();
      $this->exceptionHandler();

      $this->app->addRoutingMiddleware();
      $this->app->addBodyParsingMiddleware();

      $errorMiddleware = $this->app->addErrorMiddleware(
         $this->settings->get('logger.displayErrorDetails', false),
         $this->settings->get('logger.logErrors', false),
         $this->settings->get('logger.logErrorDetails', false)
      );

      // Usar WhoopsErrorHandler como manejador de errores predeterminado
      $errorMiddleware->setDefaultErrorHandler($this->whoopsHandler);

      $response = $this->app->handle($this->request);
      $responseEmitter = new ResponseEmitter();
      // Emit the response
      $responseEmitter->emit($response);

   }


}