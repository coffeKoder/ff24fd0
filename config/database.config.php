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
    | Conexión de Base de Datos por Defecto
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar cuál de las conexiones de abajo usar
    | por defecto en toda tu aplicación.
    |
    */
   'default' => $_ENV['DB_CONNECTION'] ?? 'mysql',

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
         'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
         'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
         'port' => $_ENV['DB_PORT'] ?? '3306',
         'database' => $_ENV['DB_DATABASE'] ?? 'phast_db',
         'username' => $_ENV['DB_USERNAME'] ?? 'root',
         'password' => $_ENV['DB_PASSWORD'] ?? '',
         'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
      ],

      'pgsql' => [

         'driver' => $_ENV['DB_CONNECTION_PGSQL'] ?? 'pgsql',
         'host' => $_ENV['DB_HOST_PGSQL'] ?? '127.0.0.1',
         'port' => $_ENV['DB_PORT_PGSQL'] ?? '5432',
         'dbname' => $_ENV['DB_DATABASE_PGSQL'] ?? 'phast_db',
         'user' => $_ENV['DB_USERNAME_PGSQL'] ?? 'root',
         'password' => $_ENV['DB_PASSWORD_PGSQL'] ?? '',
         'charset' => $_ENV['DB_CHARSET_PGSQL'] ?? 'AL32UTF8',

      ],
      'oracle' => [
         'dev_mode' => true, // Cambiar a false en producción
         'cache_dir' => __DIR__ . '/../storage/cache/doctrine', // Directorio de caché de Doctrine
         'proxy_dir' => __DIR__ . '/../storage/cache/doctrine/proxies', // Directorio para las clases proxy
         'metadata_dirs' => [
            __DIR__ . '/../src/Modules/Organizational/Domain/Entities/', // <-- ¡MUCHO MÁS ESPECÍFICO!
         ],
         'connection' => [
            'driver' => $_ENV['DB_CONNECTION_ORACLE'] ?? 'oci8',
            'host' => $_ENV['DB_HOST_ORACLE'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT_ORACLE'] ?? '1521',
            'dbname' => $_ENV['DB_NAME_ORACLE'] ?? 'phast_db',
            'user' => $_ENV['DB_USER_ORACLE'] ?? 'root',
            'password' => $_ENV['DB_PASS_ORACLE'] ?? '',
            'charset' => $_ENV['DB_CHARSET_ORACLE'] ?? 'AL32UTF8',
         ]
      ],

      'sqlite' => [
         'driver' => $_ENV['DB_CONNECTION_SQLITE'] ?? 'sqlite',
         // La ruta es relativa al directorio raíz del proyecto.
         'database' => $_ENV['DB_DATABASE_SQLITE'] ?? dirname(__DIR__) . '/storage/database.sqlite',
      ],
   ],
];
