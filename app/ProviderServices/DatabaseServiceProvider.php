<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        DatabaseServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:05:36
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use App\Contracts\SettingsInterface;
use DI\ContainerBuilder;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Psr\Cache\CacheItemPoolInterface; // <-- Importante, usaremos la interfaz
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
      EntityManagerInterface::class => function (ContainerInterface $container): EntityManagerInterface {
         /** @var SettingsInterface $settings */
         $settings = $container->get(SettingsInterface::class);
         $isDevMode = $settings->get('database.connections.oracle.dev_mode', false);

         // --- 1. Crear la instancia de Caché PSR-6 ---
         // Ya no necesitamos 'DoctrineProvider'. Creamos la instancia de Symfony Cache directamente.
         /** @var CacheItemPoolInterface $cache */
         $cache = $isDevMode
            ? new ArrayAdapter() // Perfecto para desarrollo, no deja archivos.
            : new FilesystemAdapter(
               'doctrine_cache', // Un namespace simple para evitar colisiones.
               0, // Lifetime 0 por defecto para forzar un TTL explícito.
               $settings->get('database.connections.oracle.cache_dir', 'storage/cache/doctrine')
            );

         // --- 2. Configuración de Doctrine ORM ---
         // Pasamos la instancia de caché PSR-6 directamente a `ORMSetup`.
         // Doctrine ORM v3 sabe cómo usar cualquier objeto que implemente CacheItemPoolInterface.
         $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: $settings->get('database.connections.oracle.metadata_dirs', ['src/Entity']),
            isDevMode: $isDevMode,
            proxyDir: $settings->get('database.connections.oracle.proxy_dir'),
            cache: $cache // <-- ¡Punto clave! El caché se pasa aquí.
         );

         // En las versiones más recientes de ORMSetup, pasar el caché en el constructor es la forma preferida.
         // Los métodos set*Cache() todavía existen por retrocompatibilidad, pero esto es más limpio.
   
         // La autogeneración de proxies debe controlarse según el entorno.
         // 'true' en desarrollo para comodidad, 'false' en producción para rendimiento.
         // En producción, los proxies deben generarse mediante un comando en la fase de deploy.
         $config->setAutoGenerateProxyClasses($isDevMode);
         if (!$isDevMode) {
            $config->setProxyNamespace('DoctrineProxies');
         }

         // --- 3. Crear la Conexión a la Base de Datos ---
         $connectionParams = $settings->get('database.connections.oracle.connection');
         $connection = DriverManager::getConnection($connectionParams, $config);

         // --- 4. Crear y Devolver el EntityManager ---
         return new EntityManager($connection, $config);
      }
   ]);
};