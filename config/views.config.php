<?php
/**
 * @package     VIEX
 * @subpackage  Config
 * @file        views.config.php
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-08 03:00:00
 * @version     1.0.0
 * @description Configuración del motor de vistas Plates
 */

declare(strict_types=1);

return [
   // Rutas de directorios de vistas
   'views_path' => dirname(__DIR__) . '/resources/views',
   'templates_path' => dirname(__DIR__) . '/resources/templates',

   // Configuración de archivos
   'file_extension' => 'phtml',

   // Cache de vistas
   'cache_enabled' => getenv('APP_ENV') === 'production',
   'cache_path' => dirname(__DIR__) . '/storage/cache/views',

   // Layout predeterminado
   'default_layout' => 'adminlte',

   // URL base para helpers
   'base_url' => getenv('APP_URL') ?: 'http://localhost:8000',

   // Debugging
   'debug' => getenv('APP_DEBUG') === 'true',

   // Configuración de seguridad
   'security' => [
      'strict_templates' => true,
      'escape_html' => true,
      'allow_php_functions' => false,
   ],

   // Data global compartida entre todas las vistas
   'shared_data' => [
      'app_name' => getenv('APP_NAME') ?: 'VIEX',
      'app_version' => '1.0.0',
      'environment' => getenv('APP_ENV') ?: 'development',
   ],

   // Configuración de helpers personalizados
   'helpers' => [
      'csrf_field_name' => '_token',
      'old_input_session_key' => 'old_input',
      'flash_messages_key' => 'flash_messages',
   ],
];
