<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        filesistem.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:33:16
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);


return [
   /*
  |--------------------------------------------------------------------------
  | Disco de Sistema de Archivos por Defecto
  |--------------------------------------------------------------------------
  */
   'default' => $_ENV['FILESYSTEM_DISK'] ?? 'local',

   /*
   |--------------------------------------------------------------------------
   | Discos de Sistema de Archivos
   |--------------------------------------------------------------------------
   |
   | Aquí puedes configurar tantos "discos" como necesites.
   |
   */
   'disks' => [

      'local' => [
         'driver' => 'local',
         // La ruta base para todos los archivos guardados en este disco.
         'root' => dirname(__DIR__) . '/storage/app',
         // La URL base para acceder a los archivos públicamente.
         'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/') . '/storage',
      ],

      'public' => [
         'driver' => 'local',
         'root' => dirname(__DIR__) . '/public/storage',
         'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/') . '/storage',
         'visibility' => 'public',
      ],

      's3' => [
         'driver' => 's3',
         'key' => $_ENV['AWS_ACCESS_KEY_ID'],
         'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
         'region' => $_ENV['AWS_DEFAULT_REGION'],
         'bucket' => $_ENV['AWS_BUCKET'],
         'url' => $_ENV['AWS_URL'],
      ],
   ],
];