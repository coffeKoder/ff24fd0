<?php
/**
 * Análisis específico del constructor DoctrineOrganizationalUnitRepository
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine\DoctrineOrganizationalUnitRepository;
use Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit;

echo "=== ANÁLISIS DEL CONSTRUCTOR DEL REPOSITORY ===\n\n";

try {
   // 1. Obtener EntityManager funcionando
   echo "1. Obteniendo EntityManager...\n";
   $app = new Application();
   $container = $app->getContainer();
   $entityManager = $container->get(EntityManagerInterface::class);
   echo "   ✓ EntityManager obtenido\n";

   // 2. Analizar qué hace el constructor paso a paso
   echo "\n2. Simulando constructor DoctrineOrganizationalUnitRepository...\n";

   echo "   - Paso 1: Asignar EntityManager...\n";
   // Esto es lo que hace: $this->entityManager = $entityManager;
   echo "   ✓ EntityManager asignado (sin problemas)\n";

   echo "   - Paso 2: Llamar getRepository(OrganizationalUnit::class)...\n";
   echo "     * Esto es donde debe fallar: \$this->entityManager->getRepository(OrganizationalUnit::class)\n";

   try {
      $repository = $entityManager->getRepository(OrganizationalUnit::class);
      echo "   ✓ Repository obtenido sin problemas\n";
   } catch (Exception $e) {
      echo "   ✗ ERROR EN getRepository(): " . $e->getMessage() . "\n";
      echo "   ✗ Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";

      // Analizar qué metadatos está intentando cargar
      echo "\n   ANÁLISIS DE METADATOS:\n";
      try {
         echo "   - Intentando obtener metadatos de OrganizationalUnit...\n";
         $metadata = $entityManager->getClassMetadata(OrganizationalUnit::class);
         echo "   ✓ Metadatos obtenidos: " . $metadata->getName() . "\n";
      } catch (Exception $metaError) {
         echo "   ✗ ERROR EN METADATOS: " . $metaError->getMessage() . "\n";
         echo "   ✗ Línea: " . $metaError->getLine() . "\n";
         echo "   ✗ Archivo: " . $metaError->getFile() . "\n";
      }

      return;
   }

   // 3. Si llegamos aquí, el problema no está en getRepository
   echo "\n3. Creando instancia directa del Repository...\n";
   try {
      $directRepository = new DoctrineOrganizationalUnitRepository($entityManager);
      echo "   ✓ Repository creado directamente sin problemas\n";
   } catch (Exception $e) {
      echo "   ✗ ERROR EN CONSTRUCTOR DIRECTO: " . $e->getMessage() . "\n";
      echo "   ✗ Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
   }

   echo "\n=== ANÁLISIS COMPLETADO ===\n";

} catch (Exception $e) {
   echo "\n✗ ERROR GENERAL: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
