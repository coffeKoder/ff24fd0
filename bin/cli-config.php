<?php
/**
 * Doctrine CLI Configuration
 * 
 * Este archivo configura la consola de Doctrine para permitir el uso de comandos
 * como schema-tool, migrations, etc.
 * 
 * @package     VIEX
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-11
 * @version     1.0.0
 */

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use App\Bootstrap\Application;

require_once __DIR__ . '/../vendor/autoload.php';

try {
   // Crear la aplicación y obtener el container
   $app = new Application();
   $container = $app->getContainer();

   // Obtener el EntityManager desde el container
   $entityManager = $container->get(EntityManagerInterface::class);

   // Para Doctrine ORM 3.x, simplemente devolvemos el EntityManager
   // La consola lo detectará automáticamente
   return $entityManager;

} catch (Exception $e) {
   echo "Error al configurar la consola de Doctrine: " . $e->getMessage() . "\n";
   echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
   exit(1);
}
