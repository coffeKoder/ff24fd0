<?php
/**
 * Script de prueba para verificar que la configuración de Doctrine funciona
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Container;
use App\Contracts\SettingsInterface;
use Doctrine\ORM\EntityManagerInterface;

try {
   echo "=== Prueba de configuración de Doctrine ===\n";

   // Cargar variables de entorno
   if (file_exists(__DIR__ . '/.env')) {
      $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
      $dotenv->load();
   }

   // Crear contenedor
   $container = (new Container())->container();

   // Obtener configuración
   $settings = $container->get(SettingsInterface::class);

   echo "1. Configuración cargada correctamente ✓\n";

   // Verificar configuración de Doctrine
   $doctrineConfig = $settings->get('database.doctrine', []);
   echo "2. Configuración de Doctrine:\n";
   echo "   - dev_mode: " . ($doctrineConfig['dev_mode'] ? 'true' : 'false') . "\n";
   echo "   - cache_dir: " . ($doctrineConfig['cache_dir'] ?? 'N/A') . "\n";
   echo "   - proxy_dir: " . ($doctrineConfig['proxy_dir'] ?? 'N/A') . "\n";
   echo "   - metadata_dirs: " . json_encode($doctrineConfig['metadata_dirs'] ?? []) . "\n";

   // Verificar conexión por defecto
   $defaultConnection = $settings->get('database.default', 'mysql');
   echo "3. Conexión por defecto: {$defaultConnection}\n";

   // Intentar crear EntityManager
   echo "4. Creando EntityManager...\n";
   $entityManager = $container->get(EntityManagerInterface::class);

   echo "5. EntityManager creado exitosamente ✓\n";

   // Verificar que puede obtener metadata
   echo "6. Verificando metadata de entidades...\n";
   $metadataFactory = $entityManager->getMetadataFactory();

   // Intentar obtener metadata de la entidad OrganizationalUnit
   $metadata = $metadataFactory->getMetadataFor('Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit');
   echo "7. Metadata de OrganizationalUnit obtenida exitosamente ✓\n";
   echo "   - Tabla: " . $metadata->table['name'] . "\n";
   echo "   - Campos: " . implode(', ', $metadata->getFieldNames()) . "\n";

   echo "\n=== ¡Prueba exitosa! La configuración de Doctrine funciona correctamente ===\n";

} catch (Exception $e) {
   echo "\n=== ERROR ===\n";
   echo "Tipo: " . get_class($e) . "\n";
   echo "Mensaje: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
   echo "\n=== TRACE ===\n";
   echo $e->getTraceAsString() . "\n";
   exit(1);
}
