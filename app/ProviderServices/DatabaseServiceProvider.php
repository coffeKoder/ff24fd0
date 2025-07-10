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


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Dom\Entity;
use PhpParser\Comment\Doc;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Psr\Cache\CacheItemPoolInterface;


return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
      EntityManagerInterface::class => function (ContainerInterface $container) {
         $settings = $container->get(SettingsInterface::class);

         $cache = $settings->get('database.dev_mode', false)
            ? new ArrayAdapter()
            : new FilesystemAdapter(
               $settings->get('database.cache_namespace', 'storage/cache'),
               $settings->get('database.cache_lifetime', 3600)
            );


         $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: $settings->get('database.paths', ['src/Entity']),
            isDevMode: $settings->get('database.dev_mode', false),
            cache: $cache,
         );

         $connection = $settings->get('database.connection', [
            'driver' => 'pdo_sqlite',
            'path' => $settings->get('database.path', 'storage/database.sqlite'),
         ]);

         return new EntityManager($connection, $config);
      }
   ]);

};