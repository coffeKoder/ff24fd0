<?php
/**
 * Script para probar creación del EntityManager directamente
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;

try {
   echo "=== Test de EntityManager ===\n";

   // Cargar configuración
   $dbConfig = require __DIR__ . '/config/database.config.php';

   echo "✓ Configuración cargada\n";

   // Configurar Doctrine ORM
   $doctrineConfig = $dbConfig['doctrine'];
   $connectionParams = $dbConfig['connections'][$dbConfig['default']];

   echo "✓ Usando conexión: " . $dbConfig['default'] . "\n";

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

   // Crear conexión
   $connection = DriverManager::getConnection($connectionParams);
   echo "✓ Conexión DBAL creada\n";

   // Crear EntityManager
   $entityManager = new EntityManager($connection, $config);
   echo "✓ EntityManager creado exitosamente\n";

   // Verificar que puede cargar metadatos
   $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
   echo "✓ Metadatos cargados: " . count($metadata) . " entidades\n";

   foreach ($metadata as $meta) {
      echo "  - " . $meta->getName() . "\n";
   }

   echo "\n=== Test completado exitosamente ===\n";

} catch (Exception $e) {
   echo "✗ Error: " . $e->getMessage() . "\n";
   echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
   exit(1);
}
