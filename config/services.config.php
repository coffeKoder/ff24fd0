<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        services.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:36:36
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

/**
 * Configuración de servicios externos utilizados por la aplicación.
 * 
 * Cada servicio debe tener su propio arreglo de configuración, 
 * utilizando variables de entorno para mantener seguras las credenciales.
 * 
 * Ejemplo de uso:
 * $stripeKey = $services['stripe']['key'];
 */

return [
   // Configuración para Stripe
   'stripe' => [
      // Clave pública de Stripe
      'key' => $_ENV['STRIPE_KEY'],
      // Clave secreta de Stripe
      'secret' => $_ENV['STRIPE_SECRET'],
   ],

   // Configuración para AWS
   'aws' => [
      // ID de clave de acceso de AWS
      'key' => $_ENV['AWS_ACCESS_KEY_ID'],
      // Clave secreta de AWS
      'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
      // Región por defecto de AWS
      'region' => $_ENV['AWS_DEFAULT_REGION'],
      // URL base de AWS
      'url' => $_ENV['AWS_URL'],
      // Configuración específica para S3
      's3' => [
         // Nombre del bucket de S3
         'bucket' => $_ENV['AWS_BUCKET'],
      ],
   ],

   // Configuración para autenticación social con GitHub
   'github' => [
      // ID de cliente de GitHub
      'client_id' => $_ENV['GITHUB_CLIENT_ID'],
      // Secreto de cliente de GitHub
      'client_secret' => $_ENV['GITHUB_CLIENT_SECRET'],
      // URL de redirección después de la autenticación
      'redirect' => $_ENV['APP_URL'] . '/auth/github/callback',
   ],
];

