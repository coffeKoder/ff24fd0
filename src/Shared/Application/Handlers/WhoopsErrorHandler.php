<?php
/**
 * @package     Shared/Application
 * @subpackage  Handlers
 * @file        WhoopsErrorHandler
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 21:00:00
 * @version     1.0.0
 * @description Manejo de errores con Whoops para mejor debugging
 */

declare(strict_types=1);

namespace Viex\Shared\Application\Handlers;

use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;
use Throwable;

class WhoopsErrorHandler {
   private Run $whoops;
   private bool $displayDetails;
   private bool $isDebugMode;

   public function __construct(bool $displayDetails = false, bool $isDebugMode = false) {
      $this->displayDetails = $displayDetails;
      $this->isDebugMode = $isDebugMode;
      $this->setupWhoops();
   }

   private function setupWhoops(): void {
      $this->whoops = new Run();

      if ($this->isDebugMode) {
         // Handler para páginas HTML (desarrollo)
         $prettyHandler = new PrettyPageHandler();
         $prettyHandler->setPageTitle('VIEX - Error de Aplicación');
         $prettyHandler->setApplicationPaths([
            dirname(__DIR__, 4) . '/src',
            dirname(__DIR__, 4) . '/app',
         ]);

         // Agregar información extra del contexto
         $prettyHandler->addDataTable('Información del Sistema', [
            'PHP Version' => PHP_VERSION,
            'Environment' => $_ENV['APP_ENV'] ?? 'development',
            'Debug Mode' => $this->isDebugMode ? 'Enabled' : 'Disabled',
            'Memory Usage' => $this->formatBytes(memory_get_usage(true)),
            'Memory Peak' => $this->formatBytes(memory_get_peak_usage(true)),
            'Execution Time' => $this->getExecutionTime() . 'ms',
         ]);

         $this->whoops->pushHandler($prettyHandler);
      } else {
         // Handler para producción (JSON)
         $jsonHandler = new JsonResponseHandler();
         $jsonHandler->setJsonApi(true);
         $this->whoops->pushHandler($jsonHandler);
      }

      // Handler para CLI
      if (php_sapi_name() === 'cli') {
         $this->whoops->pushHandler(new PlainTextHandler());
      }
   }

   public function __invoke(
      ServerRequestInterface $request,
      Throwable $exception,
      bool $displayErrorDetails = false,
      bool $logErrors = false,
      bool $logErrorDetails = false
   ): ResponseInterface {
      $response = new Response();

      // Determinar el tipo de contenido esperado
      $contentType = $this->determineContentType($request);

      // Si es una excepción HTTP de Slim, mantener el código de estado
      $statusCode = $exception instanceof HttpException
         ? $exception->getCode()
         : 500;

      if ($statusCode < 400) {
         $statusCode = 500;
      }

      // En modo debug, usar Whoops
      if ($this->isDebugMode || $displayErrorDetails) {
         // Configurar el handler apropiado según el tipo de contenido
         $this->configureHandlerForContentType($contentType, $request);

         // Obtener la salida de Whoops
         $whoopsOutput = $this->whoops->handleException($exception);

         $response->getBody()->write($whoopsOutput);

         return $response
            ->withHeader('Content-Type', $contentType)
            ->withStatus($statusCode);
      }

      // En producción, respuesta simple
      $errorData = [
         'error' => true,
         'message' => $this->getProductionErrorMessage($statusCode),
         'code' => $statusCode,
         'timestamp' => date('c'),
      ];

      if ($contentType === 'application/json') {
         $response->getBody()->write(json_encode($errorData, JSON_PRETTY_PRINT));
      } else {
         $response->getBody()->write($this->getHtmlErrorPage($errorData));
      }

      return $response
         ->withHeader('Content-Type', $contentType)
         ->withStatus($statusCode);
   }

   private function determineContentType(ServerRequestInterface $request): string {
      $acceptHeader = $request->getHeaderLine('Accept');
      $contentType = $request->getHeaderLine('Content-Type');

      // Si es una petición AJAX o API
      if (strpos($acceptHeader, 'application/json') !== false ||
         strpos($contentType, 'application/json') !== false ||
         strpos($request->getUri()->getPath(), '/api/') !== false) {
         return 'application/json';
      }

      return 'text/html';
   }

   private function configureHandlerForContentType(string $contentType, ServerRequestInterface $request): void {
      // Limpiar handlers existentes
      $this->whoops->clearHandlers();

      if ($contentType === 'application/json') {
         $jsonHandler = new JsonResponseHandler();
         $jsonHandler->setJsonApi(true);
         $jsonHandler->addTraceToOutput(true);
         $this->whoops->pushHandler($jsonHandler);
      } else {
         $prettyHandler = new PrettyPageHandler();
         $prettyHandler->setPageTitle('VIEX - Error de Aplicación');
         $prettyHandler->setApplicationPaths([
            dirname(__DIR__, 4) . '/src',
            dirname(__DIR__, 4) . '/app',
         ]);

         // Información de la petición
         $prettyHandler->addDataTable('Información de la Petición', [
            'Method' => $request->getMethod(),
            'URI' => (string) $request->getUri(),
            'Headers' => $request->getHeaders(),
            'Query Params' => $request->getQueryParams(),
            'Server Params' => $request->getServerParams(),
         ]);

         $this->whoops->pushHandler($prettyHandler);
      }
   }

   private function getProductionErrorMessage(int $statusCode): string {
      $messages = [
         400 => 'Solicitud incorrecta',
         401 => 'No autorizado',
         403 => 'Acceso prohibido',
         404 => 'Recurso no encontrado',
         405 => 'Método no permitido',
         422 => 'Entidad no procesable',
         500 => 'Error interno del servidor',
         503 => 'Servicio no disponible',
      ];

      return $messages[$statusCode] ?? 'Error desconocido';
   }

   private function getHtmlErrorPage(array $errorData): string {
      return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIEX - Error {$errorData['code']}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .error-container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .error-code { font-size: 72px; font-weight: bold; color: #e74c3c; margin: 0; }
        .error-message { font-size: 24px; color: #333; margin: 20px 0; }
        .error-details { color: #666; font-size: 14px; margin-top: 20px; }
        .back-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">{$errorData['code']}</div>
        <div class="error-message">{$errorData['message']}</div>
        <div class="error-details">
            <p>Ha ocurrido un error en el sistema VIEX. Si el problema persiste, contacte al administrador.</p>
            <p><strong>Timestamp:</strong> {$errorData['timestamp']}</p>
        </div>
        <a href="/" class="back-link">Volver al inicio</a>
    </div>
</body>
</html>
HTML;
   }

   private function formatBytes(int $bytes, int $precision = 2): string {
      $units = ['B', 'KB', 'MB', 'GB', 'TB'];

      for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
         $bytes /= 1024;
      }

      return round($bytes, $precision) . ' ' . $units[$i];
   }

   private function getExecutionTime(): float {
      return round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
   }
}
