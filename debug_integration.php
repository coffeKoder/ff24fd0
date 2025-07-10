<?php
/**
 * Script de prueba simplificado para debug
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Infrastructure\Cache\HierarchyCacheService;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;

try {
   echo "=== Debug de Integración ===\n";

   // Crear aplicación
   $app = new Application();
   $container = $app->getContainer();

   echo "✓ Aplicación inicializada\n";

   // Probar servicios básicos
   echo "\n--- Probando servicios básicos ---\n";   try {
      $entityManager = $container->get(EntityManagerInterface::class);
      echo "✓ EntityManager disponible\n";
   } catch (Exception $e) {
      echo "✗ EntityManager: " . $e->getMessage() . "\n";
   }

   // Probar servicios del módulo
   echo "\n--- Probando servicios del módulo ---\n";
   
   $testServices = [
      OrganizationalUnitRepositoryInterface::class => 'Repository',
      HierarchyCacheService::class => 'Cache Service',
      OrganizationalHierarchyService::class => 'Hierarchy Service',
   ];

   foreach ($testServices as $serviceClass => $serviceName) {
      try {
         $service = $container->get($serviceClass);
         echo "✓ $serviceName ($serviceClass)\n";
      } catch (Exception $e) {
         echo "✗ $serviceName ($serviceClass): " . $e->getMessage() . "\n";
      }
   }

   echo "\n=== Debug completado ===\n";

} catch (Exception $e) {
   echo "✗ Error crítico: " . $e->getMessage() . "\n";
   exit(1);
}
