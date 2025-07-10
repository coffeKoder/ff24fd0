<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        app.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:32:48
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

return [

   /*
   |--------------------------------------------------------------------------
   | Nombre de la Aplicación
   |--------------------------------------------------------------------------
   */
   'name' => $_ENV['APP_NAME'] ?? 'Phast',

   /*
   |--------------------------------------------------------------------------
   | Versión de la Aplicación
   |--------------------------------------------------------------------------
   | Este valor es opcional y se puede usar para mostrar en la UI o en cabeceras.
   */
   'version' => '1.0.0',

   /*
   |--------------------------------------------------------------------------
   | Ruta Base de la Aplicación
   |--------------------------------------------------------------------------
   | Define la ruta base de la aplicación. En desarrollo, generalmente será '/',
   | pero en producción podría ser un subdirectorio como '/viex-app/'.
   */
   'base_path' => $_ENV['APP_BASE_PATH'] ?? '/',

   /*
   |--------------------------------------------------------------------------
   | Entorno de la Aplicación
   |--------------------------------------------------------------------------
   | Determina el entorno en el que se ejecuta la aplicación.
   | Puede ser 'local', 'staging', 'production'.
   */
   'env' => $_ENV['APP_ENV'] ?? 'production',

   /*
   |--------------------------------------------------------------------------
   | Modo de Depuración
   |--------------------------------------------------------------------------
   | Cuando está habilitado, se mostrarán errores detallados.
   | ¡NUNCA debe estar habilitado en producción!
   */
   'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),

   /*
   |--------------------------------------------------------------------------
   | URL de la Aplicación
   |--------------------------------------------------------------------------
   | Esta URL es utilizada por la consola y para generar URLs absolutas.
   | Asegúrate de que no tenga una barra al final.
   */
   'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),

   /*
   |--------------------------------------------------------------------------
   | Clave de Encriptación de la Aplicación
   |--------------------------------------------------------------------------
   | Esta clave se utiliza para la encriptación y debe ser una cadena
   | aleatoria de 32 caracteres. ¡Mantenla segura!
   */
   'key' => $_ENV['APP_KEY'] ?? 'SET_YOUR_APP_KEY_IN_ENV_FILE',

   /*
   |--------------------------------------------------------------------------
   | Zona Horaria y Localización
   |--------------------------------------------------------------------------
   */
   'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
   'locale' => $_ENV['APP_LOCALE'] ?? 'en',

   /*
   |--------------------------------------------------------------------------
   | Configuración de Caché de Rutas
   |--------------------------------------------------------------------------
   | Habilitar esto en producción mejorará drásticamente el rendimiento.
   */
   'route_cache_enabled' => filter_var($_ENV['ROUTE_CACHE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN),

   /*
   |--------------------------------------------------------------------------
   | Proveedores de Servicios
   |--------------------------------------------------------------------------
   |
   | Los proveedores de servicios son el lugar central para registrar
   | todas las dependencias de tu aplicación en el contenedor.
   | (Esta es una mejora futura sugerida para organizar los 'bindings').
   |
   */
   'providers' => [
      // App\Providers\DatabaseServiceProvider::class,
      // App\Providers\AuthServiceProvider::class,
   ],

   /*
   |--------------------------------------------------------------------------
   | Proxies de Confianza
   |--------------------------------------------------------------------------
   |
   | Especifica las direcciones IP de tus proxies de confianza (ej: balanceadores
   | de carga, Cloudflare, etc.). Puedes usar '*' para confiar en todos los
   | proxies (no recomendado para producción), o definir IPs específicas
   | o rangos CIDR.
   |
   */
   'trusted_proxies' => $_ENV['TRUSTED_PROXIES'] ?? 'localhost', // Lee desde .env: '192.168.1.1,10.0.0.0/8'

   /*
   |--------------------------------------------------------------------------
   | Encabezado del Proxy de Confianza
   |--------------------------------------------------------------------------
   |
   | Define qué encabezado usar para determinar la IP del cliente cuando la
   | petición viene de un proxy de confianza.
   |
   */
   'trusted_proxy_header' => 'X-Forwarded-For',

];