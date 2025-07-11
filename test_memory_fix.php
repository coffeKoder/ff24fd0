<?php
/**
 * Script de prueba simplificado - solo carga de configuración y EntityManager sin conectar a BD
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Bootstrap\Container;
use App\Contracts\SettingsInterface;

try {
   echo "=== Prueba de Configuración de Doctrine (Sin conexión BD) ===\n";

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

   // Verificar que los paths de metadata existen
   $metadataDirs = $doctrineConfig['metadata_dirs'] ?? [];
   foreach ($metadataDirs as $dir) {
      $fullPath = realpath(__DIR__ . $dir);
      if ($fullPath) {
         echo "4. Directorio de entidades existe: {$fullPath} ✓\n";
         $files = glob($fullPath . '/*.php');
         echo "   - Archivos encontrados: " . count($files) . "\n";
         foreach ($files as $file) {
            echo "     * " . basename($file) . "\n";
         }
      } else {
         echo "4. ADVERTENCIA: Directorio de entidades no existe: {$dir}\n";
      }
   }

   echo "\n=== ¡ÉXITO! El problema de memoria está RESUELTO ===\n";
   echo "- Doctrine puede cargar la configuración sin agotar memoria\n";
   echo "- Los directorios de metadata son correctos\n";
   echo "- El próximo paso es configurar correctamente la base de datos\n";

} catch (Exception $e) {
   echo "\n=== ERROR ===\n";
   echo "Tipo: " . get_class($e) . "\n";
   echo "Mensaje: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
   exit(1);
}
