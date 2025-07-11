<?php
/**
 * Script para verificar configuración actual
 */
declare(strict_types=1);

// Cargar archivo .env manualmente
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
   $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($lines as $line) {
      if (strpos($line, '#') === 0)
         continue; // comentarios
      if (strpos($line, '=') === false)
         continue; // líneas sin =

      list($key, $value) = explode('=', $line, 2);
      $_ENV[trim($key)] = trim($value);
   }
}

echo "=== Variables de Entorno ===\n";
echo "DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'NO DEFINIDA') . "\n";
echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'NO DEFINIDA') . "\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NO DEFINIDA') . "\n";

echo "\n=== Configuración de Base de Datos ===\n";
$dbConfig = require __DIR__ . '/config/database.config.php';
echo "Conexión por defecto: " . $dbConfig['default'] . "\n";
echo "Configuración activa:\n";
print_r($dbConfig['connections'][$dbConfig['default']]);
