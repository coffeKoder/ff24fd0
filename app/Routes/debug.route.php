<?php
/**
 * @package     app/Routes
 * @file        debug.route.php
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10
 * @description Rutas de debug para probar Whoops
 */

declare(strict_types=1);

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

return function (App $app) {
    // Ruta para probar error simple
    $app->get('/debug/test-error', function (Request $request, Response $response, array $args) {
        throw new \Exception('Esta es una prueba de error para Whoops - Error simple');
    });

    // Ruta para probar error de clase no encontrada
    $app->get('/debug/test-class-error', function (Request $request, Response $response, array $args) {
        // Intentar usar una clase que no existe
        $nonExistentClass = new \NonExistentClass();
        return $response;
    });

    // Ruta para probar error de tipo
    $app->get('/debug/test-type-error', function (Request $request, Response $response, array $args) {
        $func = function (string $param) {
            return $param;
        };

        // Pasar un tipo incorrecto
        return $func(12345);
    });

    // Ruta para probar error de división por cero
    $app->get('/debug/test-division-error', function (Request $request, Response $response, array $args) {
        $result = 10 / 0;
        $response->getBody()->write("Resultado: " . $result);
        return $response;
    });

    // Ruta para probar error de memoria
    $app->get('/debug/test-memory-error', function (Request $request, Response $response, array $args) {
        $array = [];
        while (true) {
            $array[] = str_repeat('a', 1000000);
        }
        return $response;
    });

    // Ruta para probar error de archivo no encontrado
    $app->get('/debug/test-file-error', function (Request $request, Response $response, array $args) {
        $content = file_get_contents('/archivo/que/no/existe.txt');
        $response->getBody()->write($content);
        return $response;
    });

    // Ruta para probar error de JSON
    $app->get('/debug/test-json-error', function (Request $request, Response $response, array $args) {
        $malformedJson = '{"key": "value",}';
        $decoded = json_decode($malformedJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Error de JSON: ' . json_last_error_msg());
        }

        $response->getBody()->write(json_encode($decoded));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Ruta para mostrar información de debug
    $app->get('/debug/info', function (Request $request, Response $response, array $args) {
        $debugInfo = [
            'whoops_installed' => class_exists('\Whoops\Run'),
            'debug_mode' => $_ENV['APP_DEBUG'] ?? false,
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'loaded_extensions' => get_loaded_extensions(),
            'whoops_version' => class_exists('\Whoops\Run') ? 'Available' : 'Not Available',
        ];

        $response->getBody()->write(json_encode($debugInfo, JSON_PRETTY_PRINT));
        return $response->withHeader('Content-Type', 'application/json');
    });

    // Ruta para probar error con contexto personalizado
    $app->get('/debug/test-context-error', function (Request $request, Response $response, array $args) {
        $userData = [
            'id' => 123,
            'name' => 'Usuario de Prueba',
            'email' => 'test@example.com'
        ];

        $requestData = [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
            'query' => $request->getQueryParams()
        ];

        // Crear una excepción con contexto
        $exception = new \RuntimeException('Error con contexto personalizado para debugging');

        // Agregar datos adicionales al contexto (esto se mostrará en Whoops)
        throw $exception;
    });
};
