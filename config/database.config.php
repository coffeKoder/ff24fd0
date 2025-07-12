<?php

/**
 * @package     projects/viex-app
 * @subpackag      'oracle' => [
 * @file        database.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:33:09
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);


return [

   'dev_mode' => ($_ENV['APP_ENV'] ?? 'local') === 'local',

   'cache_namespace' => 'viex_database', // Espacio de nombres para la caché de la base de datos

   /*
    |--------------------------------------------------------------------------
    | Configuración de Doctrine ORM (Global)
    |--------------------------------------------------------------------------
    |
    | Esta configuración es independiente de la conexión de base de datos
    |
    */
   'doctrine' => [
      'dev_mode' => ($_ENV['APP_ENV'] ?? 'local') === 'local',
      'cache_dir' => __DIR__ . '/../storage/cache/doctrine',
      'proxy_dir' => __DIR__ . '/../storage/cache/doctrine/proxies',
      'metadata_dirs' => [
         __DIR__ . '/../src/Modules/Organizational/Domain/Entities',
         __DIR__ . '/../src/Modules/User/Domain/Entities',
      ],
   ],

   /*
    |--------------------------------------------------------------------------
    | Conexión de Base de Datos por Defecto
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar cuál de las conexiones de abajo usar
    | por defecto en toda tu aplicación.
    |
    */
   'default' => $_ENV['DB_CONNECTION'] ?? 'oci8',

   /*
    |--------------------------------------------------------------------------
    | Conexiones de Base de Datos
    |--------------------------------------------------------------------------
    |
    | Aquí están todas las configuraciones de conexión para tu aplicación.
    | Soportamos MySQL, PostgreSQL y SQLite.
    |
    */
   'connections' => [
      'mysql' => [
         'driver' => 'pdo_mysql',
         'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
         'port' => $_ENV['DB_PORT'] ?? '3306',
         'dbname' => $_ENV['DB_DATABASE'] ?? 'phast_db',
         'user' => $_ENV['DB_USERNAME'] ?? 'root',
         'password' => $_ENV['DB_PASSWORD'] ?? '',
         'charset' => 'utf8',
      ],

      'pgsql' => [

         'driver' => $_ENV['DB_CONNECTION'] ?? 'pgsql',
         'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
         'port' => $_ENV['DB_PORT'] ?? '5432',
         'dbname' => $_ENV['DB_DATABASE'] ?? 'phast_db',
         'user' => $_ENV['DB_USERNAME'] ?? 'root',
         'password' => $_ENV['DB_PASSWORD'] ?? '',
         'charset' => $_ENV['DB_CHARSET'] ?? 'AL32UTF8',

      ],
      'oci8' => [
         'driver' => 'oci8',
         'dbname' => '//' . ($_ENV['DB_HOST'] ?? 'localhost') . ':' . ($_ENV['DB_PORT'] ?? '1521') . '/' . ($_ENV['DB_NAME'] ?? 'freepdb1'),
         'user' => $_ENV['DB_USER'] ?? 'SYSTEM',
         'password' => $_ENV['DB_PASS'] ?? '',
         'charset' => $_ENV['DB_CHARSET'] ?? 'AL32UTF8',
      ],

      'sqlite' => [
         'driver' => 'pdo_sqlite',
         'path' => $_ENV['DB_DATABASE'] ?? dirname(__DIR__) . '/storage/viex_dev.sqlite',
      ],
   ],
];
