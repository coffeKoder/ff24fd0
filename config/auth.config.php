<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        auth.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:33:02
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);


return [
   /*
   |--------------------------------------------------------------------------
   | Configuración de Sesión de Autenticación
   |--------------------------------------------------------------------------
   |
   | Nombre de la sesión para autenticación y configuraciones relacionadas
   |
   */
   'session_name' => $_ENV['AUTH_SESSION_NAME'] ?? 'viex_session',
   'csrf_token_name' => 'csrf_token',
   'login_redirect' => '/',
   'logout_redirect' => '/auth/login',

   /*
  |--------------------------------------------------------------------------
  | Autenticación por Defecto
  |--------------------------------------------------------------------------
  |
  | Aquí puedes especificar el guard de autenticación por defecto que
  | se usará en tu aplicación. Puedes cambiarlo según necesites.
  |
  */
   'defaults' => [
      'guard' => 'web',
   ],

   /*
   |--------------------------------------------------------------------------
   | Guards de Autenticación
   |--------------------------------------------------------------------------
   |
   | Aquí se definen todos los guards de autenticación para tu aplicación.
   | Cada guard tiene un 'driver' y un 'provider'. El driver define cómo
   | se autentica el usuario (sesión, token) y el provider define cómo
   | se recupera el usuario de la base de datos.
   |
   */
   'guards' => [
      'web' => [
         'driver' => 'session',
         'provider' => 'users',
      ],

      'api' => [
         'driver' => 'token',
         'provider' => 'users',
      ],
   ],

   /*
   |--------------------------------------------------------------------------
   | Proveedores de Usuarios
   |--------------------------------------------------------------------------
   |
   | Aquí se definen los "providers" de usuarios. Por ahora, solo tenemos
   | uno que usa nuestro modelo User, pero en el futuro podrías tener
   | providers para LDAP, etc.
   |
   */
   'providers' => [
      'users' => [
         'driver' => 'eloquent', // O 'model'
         // --- ¡ESTA ES LA LÍNEA MÁS IMPORTANTE! ---
         // Apunta al FQCN (Fully Qualified Class Name) de tu modelo User.
         // 'model' => Phast\App\Modules\Users\Models\User::class,
      ],
   ],
];