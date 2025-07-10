<?php
/**
 * @package     ff24fd0/tests
 * @subpackage  Integration
 * @file        test_organizational_integration
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 20:00:00
 * @version     1.0.0
 * @description Script de prueba para verificar integración del módulo Organizational
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Psr\Container\ContainerInterface;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Infrastructure\Http\OrganizationalController;

try {
   echo "=== Prueba de Integración del Módulo Organizational ===\n\n";

   // Crear instancia de la aplicación
   $app = new Application();
   $container = $app->getContainer();

   echo "✓ Aplicación inicializada correctamente\n";

   // Verificar que los servicios del módulo están registrados
   $services = [
      OrganizationalHierarchyService::class => 'Servicio de Jerarquía',
      UnitManagementService::class => 'Servicio de Gestión de Unidades',
      OrganizationalController::class => 'Controlador de Unidades',
   ];

   echo "\n--- Verificando servicios registrados ---\n";
   foreach ($services as $serviceClass => $serviceName) {
      try {
         $service = $container->get($serviceClass);
         echo "✓ $serviceName ($serviceClass) - Registrado\n";
      } catch (Exception $e) {
         echo "✗ $serviceName ($serviceClass) - Error: " . $e->getMessage() . "\n";
      }
   }

   // Verificar servicios manuales si están disponibles
   echo "\n--- Verificando servicios manuales ---\n";
   try {
      $manualServices = $container->get('OrganizationalModule_Services');
      echo "✓ Servicios manuales disponibles\n";

      if (isset($manualServices['hierarchyService'])) {
         echo "✓ Servicio de jerarquía manual disponible\n";
      }

      if (isset($manualServices['useCases'])) {
         echo "✓ Casos de uso disponibles: " . count($manualServices['useCases']) . "\n";
      }
   } catch (Exception $e) {
      echo "✗ Servicios manuales - Error: " . $e->getMessage() . "\n";
   }

   // Verificar configuración
   echo "\n--- Verificando configuración ---\n";
   try {
      $configPath = __DIR__ . '/config/organizational.config.php';
      if (file_exists($configPath)) {
         $config = require $configPath;
         echo "✓ Configuración del módulo cargada\n";
         echo "  - Caché habilitado: " . ($config['organizational']['cache']['enabled'] ? 'Sí' : 'No') . "\n";
         echo "  - Tipos de unidades permitidos: " . count($config['organizational']['validation']['allowed_unit_types']) . "\n";
      } else {
         echo "✗ Configuración del módulo no encontrada\n";
      }
   } catch (Exception $e) {
      echo "✗ Error al cargar configuración: " . $e->getMessage() . "\n";
   }

   echo "\n=== Prueba de integración completada ===\n";

} catch (Exception $e) {
   echo "✗ Error crítico: " . $e->getMessage() . "\n";
   echo "Trace: " . $e->getTraceAsString() . "\n";
   exit(1);
}
