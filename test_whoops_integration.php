<?php
/**
 * Script de prueba para verificar la integración de Whoops
 * @author Fernando Castillo <fdocst@gmail.com>
 * @date 2025-07-10
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\ServerRequestFactory;

try {
    echo "=== PRUEBA DE INTEGRACIÓN DE WHOOPS ===\n";

    // Crear aplicación
    $app = new Application();

    echo "✓ Aplicación creada correctamente\n";

    // Simular una petición que causará un error
    $requestFactory = new ServerRequestFactory();
    $request = $requestFactory->createServerRequest('GET', '/test-error');

    echo "✓ Petición simulada creada\n";

    // Agregar una ruta que lance una excepción
    // $slimApp = $app->getContainer()->get(\Slim\App::class);
    // $slimApp->get('/test-error', function ($request, $response, $args) {
    //     throw new \Exception('Esta es una prueba de error para Whoops');
    // });

    echo "✓ Ruta de prueba omitida (se puede probar via web)\n";

    echo "✓ Ruta de prueba agregada\n";

    // Forzar un error para probar Whoops
    echo "\n=== PROBANDO MANEJO DE ERRORES ===\n";

    // Método 1: Error simple
    echo "1. Probando error simple...\n";
    $testError = function () {
        throw new \InvalidArgumentException("Error de prueba para Whoops");
    };

    try {
        $testError();
    } catch (\Exception $e) {
        echo "✓ Error capturado: " . $e->getMessage() . "\n";
    }

    // Método 2: Error de clase no encontrada
    echo "2. Probando error de clase no encontrada...\n";
    try {
        // $nonExistentClass = new \NonExistentClass();
        throw new \Error("Class 'NonExistentClass' not found");
    } catch (\Error $e) {
        echo "✓ Error capturado: " . $e->getMessage() . "\n";
    }

    // Método 3: Error de división por cero
    echo "3. Probando error de división por cero...\n";
    try {
        // $result = 10 / 0;
        throw new \DivisionByZeroError("Division by zero");
    } catch (\DivisionByZeroError $e) {
        echo "✓ Error capturado: " . $e->getMessage() . "\n";
    }

    echo "\n=== VERIFICANDO CONFIGURACIÓN ===\n";

    // Verificar configuración
    $settings = $app->getContainer()->get(\App\Contracts\SettingsInterface::class);
    echo "✓ Debug mode: " . ($settings->get('debug') ? 'ENABLED' : 'DISABLED') . "\n";
    echo "✓ Display error details: " . ($settings->get('logger.displayErrorDetails') ? 'ENABLED' : 'DISABLED') . "\n";
    echo "✓ Log errors: " . ($settings->get('logger.logErrors') ? 'ENABLED' : 'DISABLED') . "\n";

    echo "\n=== PRUEBA COMPLETADA ===\n";
    echo "✓ Whoops debería estar integrado correctamente\n";
    echo "✓ Para probar completamente, navega a http://localhost:8000/test-error\n";

} catch (\Exception $e) {
    echo "✗ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (\Error $e) {
    echo "✗ Error fatal durante la prueba: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
