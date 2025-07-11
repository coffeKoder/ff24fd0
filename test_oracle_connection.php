<?php
/**
 * Test de conexión a Oracle Database
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

echo "=== TEST DE CONEXIÓN ORACLE ===\n\n";

// Cargar variables de entorno
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
   $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
   foreach ($lines as $line) {
      if (strpos($line, '#') === 0)
         continue;
      if (strpos($line, '=') === false)
         continue;

      list($key, $value) = explode('=', $line, 2);
      $_ENV[trim($key)] = trim($value);
   }
}

// Mostrar configuración
echo "1. CONFIGURACIÓN DE ORACLE:\n";
echo "   - DB_CONNECTION: " . ($_ENV['DB_CONNECTION'] ?? 'NO DEFINIDA') . "\n";
echo "   - DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NO DEFINIDA') . "\n";
echo "   - DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NO DEFINIDA') . "\n";
echo "   - DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NO DEFINIDA') . "\n";
echo "   - DB_USER: " . ($_ENV['DB_USER'] ?? 'NO DEFINIDA') . "\n";
echo "   - DB_CHARSET: " . ($_ENV['DB_CHARSET'] ?? 'NO DEFINIDA') . "\n";

// Verificar extensión OCI8
echo "\n2. VERIFICACIÓN DE EXTENSIÓN OCI8:\n";
if (extension_loaded('oci8')) {
   echo "   ✓ Extensión OCI8 está cargada\n";
} else {
   echo "   ✗ Extensión OCI8 NO está cargada\n";
   echo "   ✗ Necesitas instalar php-oci8\n";
   exit(1);
}

// Test de conexión directa con OCI8
echo "\n3. TEST DE CONEXIÓN DIRECTA:\n";
try {
   $host = $_ENV['DB_HOST'] ?? 'localhost';
   $port = $_ENV['DB_PORT'] ?? '1521';
   $service_name = $_ENV['DB_NAME'] ?? 'FREEPDB1';
   $username = $_ENV['DB_USER'] ?? 'SYSTEM';
   $password = $_ENV['DB_PASS'] ?? 'viex1234';

   // Crear connection string para Oracle
   $connection_string = "//{$host}:{$port}/{$service_name}";
   echo "   - Connection String: $connection_string\n";
   echo "   - Usuario: $username\n";

   $connection = oci_connect($username, $password, $connection_string);

   if ($connection) {
      echo "   ✓ Conexión OCI8 exitosa\n";

      // Test simple de consulta
      $sql = "SELECT 1 FROM DUAL";
      $stmt = oci_parse($connection, $sql);

      if (oci_execute($stmt)) {
         echo "   ✓ Consulta de prueba exitosa\n";
      } else {
         echo "   ✗ Error en consulta de prueba\n";
      }

      oci_close($connection);
   } else {
      $error = oci_error();
      echo "   ✗ Error de conexión OCI8: " . $error['message'] . "\n";
      echo "   ✗ Código: " . $error['code'] . "\n";
   }

} catch (Exception $e) {
   echo "   ✗ Excepción: " . $e->getMessage() . "\n";
}

// Test con Doctrine DBAL
echo "\n4. TEST CON DOCTRINE DBAL:\n";
try {
   $dbConfig = require __DIR__ . '/config/database.config.php';
   $connectionParams = $dbConfig['connections'][$dbConfig['default']];

   echo "   - Configuración Doctrine:\n";
   echo "     * Driver: " . $connectionParams['driver'] . "\n";
   echo "     * Host: " . $connectionParams['host'] . "\n";
   echo "     * Port: " . $connectionParams['port'] . "\n";
   echo "     * Service: " . $connectionParams['dbname'] . "\n";
   echo "     * Usuario: " . $connectionParams['user'] . "\n";

   $connection = DriverManager::getConnection($connectionParams);
   echo "   ✓ Conexión Doctrine DBAL creada\n";

   // Test de conectividad
   $result = $connection->fetchOne("SELECT 1 FROM DUAL");
   echo "   ✓ Consulta Doctrine exitosa, resultado: $result\n";

} catch (Exception $e) {
   echo "   ✗ Error Doctrine DBAL: " . $e->getMessage() . "\n";
   echo "   ✗ Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== TEST COMPLETADO ===\n";
