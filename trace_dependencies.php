<?php
/**
 * Análisis de dependencias circulares - Trazado inverso
 * Desde el error de memoria hasta el repositorio
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;

echo "=== TRAZADO INVERSO DE DEPENDENCIAS ===\n\n";

try {
   // 1. PUNTO DE INICIO: Crear aplicación
   echo "1. Creando aplicación Bootstrap...\n";
   $app = new Application();
   $container = $app->getContainer();
   echo "   ✓ Application creada\n";

   // 2. LEVEL 1: Intentar obtener EntityManager (servicios básicos)
   echo "\n2. Intentando obtener EntityManager...\n";
   echo "   - Ruta: Container -> DatabaseServiceProvider -> EntityManager\n";

   try {
      $entityManager = $container->get(EntityManagerInterface::class);
      echo "   ✓ EntityManager obtenido sin problemas\n";

      // 2.1 Verificar configuración del EntityManager
      echo "   - Configuración EntityManager:\n";
      $connection = $entityManager->getConnection();
      echo "     * Driver: " . $connection->getDriver()::class . "\n";
      echo "     * Plataforma: " . $connection->getDatabasePlatform()::class . "\n";

   } catch (Exception $e) {
      echo "   ✗ Error al obtener EntityManager: " . $e->getMessage() . "\n";
      echo "   ✗ Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
      return;
   }

   // 3. LEVEL 2: Intentar obtener Repository (aquí debe estar el problema)
   echo "\n3. Intentando obtener Repository...\n";
   echo "   - Ruta: Container -> OrganizationalServiceProvider -> DoctrineRepository\n";
   echo "   - DoctrineRepository necesita: EntityManager (ya creado)\n";

   try {
      echo "   - Llamando a container->get(OrganizationalUnitRepositoryInterface::class)...\n";
      $repository = $container->get(OrganizationalUnitRepositoryInterface::class);
      echo "   ✓ Repository obtenido sin problemas\n";

   } catch (Exception $e) {
      echo "   ✗ ERROR AQUÍ - Repository: " . $e->getMessage() . "\n";
      echo "   ✗ Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
      echo "   ✗ Línea específica que falla: " . $e->getLine() . "\n";

      // Analizar el stack trace
      echo "\n   STACK TRACE ANÁLISIS:\n";
      $trace = $e->getTrace();
      for ($i = 0; $i < min(10, count($trace)); $i++) {
         $item = $trace[$i];
         echo "   [{$i}] " . ($item['class'] ?? 'N/A') . "::" . ($item['function'] ?? 'N/A');
         echo " en " . ($item['file'] ?? 'N/A') . ":" . ($item['line'] ?? 'N/A') . "\n";
      }
      return;
   }

   echo "\n=== ANÁLISIS COMPLETADO SIN ERRORES ===\n";

} catch (Exception $e) {
   echo "\n✗ ERROR GENERAL: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";

   // Stack trace completo para análisis
   echo "\nSTACK TRACE COMPLETO:\n";
   echo $e->getTraceAsString() . "\n";
}
