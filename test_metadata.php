<?php
/**
 * Script para probar EntityManager sin conexión DB real
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

try {
   echo "=== Test de EntityManager (Sin DB) ===\n";

   // Cargar configuración
   $dbConfig = require __DIR__ . '/config/database.config.php';

   echo "✓ Configuración cargada\n";

   // Configurar Doctrine ORM
   $doctrineConfig = $dbConfig['doctrine'];

   echo "✓ Usando directorios: " . json_encode($doctrineConfig['metadata_dirs']) . "\n";

   // Crear configuración de ORM
   $config = ORMSetup::createAttributeMetadataConfiguration(
      paths: $doctrineConfig['metadata_dirs'],
      isDevMode: $doctrineConfig['dev_mode']
   );

   echo "✓ Configuración ORM creada\n";

   // Configurar proxy
   $config->setProxyDir($doctrineConfig['proxy_dir']);
   $config->setProxyNamespace('DoctrineProxies');

   echo "✓ Proxy configurado\n";

   // Crear EntityManager sin conexión real
   // Solo para verificar que los metadatos son válidos
   $connectionParams = [
      'driver' => 'pdo_sqlite',
      'memory' => true, // Base de datos en memoria
   ];

   $connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
   $entityManager = new EntityManager($connection, $config);

   echo "✓ EntityManager creado\n";

   // Obtener driver de metadata
   $metadataDriver = $config->getMetadataDriverImpl();

   if ($metadataDriver) {
      echo "✓ Driver de metadatos encontrado: " . get_class($metadataDriver) . "\n";

      // Obtener todas las clases
      $allClasses = $metadataDriver->getAllClassNames();
      echo "✓ Clases encontradas: " . count($allClasses) . "\n";

      foreach ($allClasses as $className) {
         echo "  - $className\n";

         try {
            $metadata = $entityManager->getClassMetadata($className);
            echo "    ✓ Metadatos válidos para $className\n";
            echo "    - Tabla: " . $metadata->getTableName() . "\n";
            echo "    - Campos: " . count($metadata->getFieldNames()) . "\n";
         } catch (Exception $e) {
            echo "    ✗ Error en metadatos para $className: " . $e->getMessage() . "\n";
         }
      }
   } else {
      echo "✗ No se encontró driver de metadatos\n";
   }

   echo "\n=== Test completado ===\n";

} catch (Exception $e) {
   echo "✗ Error: " . $e->getMessage() . "\n";
   echo "Línea: " . $e->getLine() . "\n";
   echo "Archivo: " . $e->getFile() . "\n";
   exit(1);
}
