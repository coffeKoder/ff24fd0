<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        security.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:33:22
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);


return [
   /*
  |--------------------------------------------------------------------------
  | Cross-Origin Resource Sharing (CORS)
  |--------------------------------------------------------------------------
  |
  | Configura qué orígenes, métodos y cabeceras están permitidos para las
  | peticiones de otros dominios.
  |
  */
   'cors' => [
      'allowed_origins' => array_filter(array_map('trim', explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? ''))),
      'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
      'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-TOKEN'],
      'max_age' => 86400, // 24 horas
   ],

   /*
   |--------------------------------------------------------------------------
   | HTTP Strict Transport Security (HSTS)
   |--------------------------------------------------------------------------
   |
   | Fuerza a los navegadores a comunicarse con tu servidor solo a través de HTTPS.
   | Solo habilítalo si tu sitio está completamente servido sobre HTTPS.
   |
   */
   'hsts' => [
      'enabled' => filter_var($_ENV['HSTS_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),
      'max_age' => 31536000, // 1 año
      'include_subdomains' => true,
      'preload' => false,
   ],

   /*
   |--------------------------------------------------------------------------
   | Política de Seguridad de Contenido (CSP)
   |--------------------------------------------------------------------------
   |
   | Ayuda a prevenir ataques XSS. Es compleja de configurar pero muy efectiva.
   | Dejarla vacía por defecto para no romper la aplicación.
   |
   */
   'csp' => [
      'enabled' => false,
      'policy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self';",
   ],
];