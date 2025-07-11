<?php
/**
 * Script para probar solo la configuración de Doctrine
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

try {
   echo "=== Test de Configuración Doctrine ===\n";

   // Cargar configuración
   $config = require __DIR__ . '/config/database.config.php';

   echo "✓ Configuración cargada\n";
   echo "Directorio de entidades: " . json_encode($config['doctrine']['metadata_dirs']) . "\n";
   echo "Directorio de proxies: " . $config['doctrine']['proxy_dir'] . "\n";
   echo "Modo desarrollo: " . ($config['doctrine']['dev_mode'] ? 'SI' : 'NO') . "\n";

   // Verificar que los directorios existen
   foreach ($config['doctrine']['metadata_dirs'] as $dir) {
      if (is_dir($dir)) {
         echo "✓ Directorio de entidades existe: $dir\n";
         $files = glob($dir . '/*.php');
         echo "  - Archivos encontrados: " . count($files) . "\n";
         foreach ($files as $file) {
            echo "    * " . basename($file) . "\n";
         }
      } else {
         echo "✗ Directorio no existe: $dir\n";
      }
   }

   if (is_dir($config['doctrine']['proxy_dir'])) {
      echo "✓ Directorio de proxies existe: " . $config['doctrine']['proxy_dir'] . "\n";
   } else {
      echo "✗ Directorio de proxies no existe: " . $config['doctrine']['proxy_dir'] . "\n";
   }

   echo "\n=== Test completado ===\n";

} catch (Exception $e) {
   echo "✗ Error: " . $e->getMessage() . "\n";
   exit(1);
}
