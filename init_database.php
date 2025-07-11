<?php
/**
 * Script para inicializar la base de datos SQLite con las tablas necesarias
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Application;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

try {
   echo "=== INICIALIZACIÓN DE BASE DE DATOS SQLite ===\n\n";

   // 1. Obtener EntityManager
   echo "1. Obteniendo EntityManager...\n";
   $app = new Application();
   $container = $app->getContainer();
   $entityManager = $container->get(EntityManagerInterface::class);
   echo "   ✓ EntityManager obtenido\n";

   // 2. Verificar conexión
   echo "\n2. Verificando conexión a la base de datos...\n";
   $connection = $entityManager->getConnection();
   echo "   - Driver: " . $connection->getDriver()::class . "\n";
   echo "   - Plataforma: " . $connection->getDatabasePlatform()::class . "\n";

   // Verificar parámetros de conexión
   $params = $connection->getParams();
   echo "   - Archivo DB: " . ($params['path'] ?? 'NO DEFINIDO') . "\n";

   // 3. Obtener metadatos de entidades
   echo "\n3. Obteniendo metadatos de entidades...\n";
   $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
   echo "   ✓ Entidades encontradas: " . count($metadatas) . "\n";

   foreach ($metadatas as $metadata) {
      echo "     - " . $metadata->getName() . " -> tabla: " . $metadata->getTableName() . "\n";
   }

   // 4. Crear esquema de base de datos
   echo "\n4. Creando esquema de base de datos...\n";
   $schemaTool = new SchemaTool($entityManager);

   // Verificar si ya existen tablas
   $schemaManager = $connection->createSchemaManager();
   $existingTables = $schemaManager->listTableNames();

   echo "   - Tablas existentes: " . count($existingTables) . "\n";
   if (count($existingTables) > 0) {
      echo "     * " . implode(', ', $existingTables) . "\n";

      // Actualizar esquema si ya existe
      echo "   - Actualizando esquema existente...\n";
      $sqls = $schemaTool->getUpdateSchemaSql($metadatas);
      if (count($sqls) > 0) {
         echo "     * Ejecutando " . count($sqls) . " sentencias SQL...\n";
         $schemaTool->updateSchema($metadatas);
         echo "     ✓ Esquema actualizado\n";
      } else {
         echo "     ✓ Esquema ya está actualizado\n";
      }
   } else {
      // Crear esquema desde cero
      echo "   - Creando esquema desde cero...\n";
      $schemaTool->createSchema($metadatas);
      echo "     ✓ Esquema creado\n";
   }

   // 5. Verificar que las tablas se crearon
   echo "\n5. Verificando tablas creadas...\n";
   $newTables = $connection->createSchemaManager()->listTableNames();
   echo "   ✓ Tablas en la base de datos: " . count($newTables) . "\n";
   foreach ($newTables as $table) {
      echo "     - $table\n";
   }

   // 6. Verificar que podemos hacer consultas básicas
   echo "\n6. Probando consultas básicas...\n";
   try {
      $query = $entityManager->createQuery('SELECT COUNT(u.id) FROM Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit u');
      $count = $query->getSingleScalarResult();
      echo "   ✓ Consulta ejecutada - Registros en OrganizationalUnit: $count\n";
   } catch (Exception $e) {
      echo "   ✗ Error en consulta: " . $e->getMessage() . "\n";
   }

   echo "\n=== INICIALIZACIÓN COMPLETADA ===\n";

} catch (Exception $e) {
   echo "\n✗ ERROR: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
   echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
   exit(1);
}
