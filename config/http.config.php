<?php
/**
 * @package     config
 * @file        http.config.php
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-08 10:05:00
 * @version     1.0.0
 * @description HTTP configuration for VIEX framework
 */

declare(strict_types=1);

return [
   /*
   |--------------------------------------------------------------------------
   | HTTP Configuration
   |--------------------------------------------------------------------------
   |
   | Configuration options for VIEX HTTP components including Request,
   | Response, and general HTTP handling settings.
   |
   */

   'request' => [
      /*
      |--------------------------------------------------------------------------
      | Request Settings
      |--------------------------------------------------------------------------
      */

      // Maximum input variables
      'max_input_vars' => 1000,

      // Default validation rules
      'default_validation_rules' => [
         'email' => 'required|email',
         'password' => 'required|string|min:8',
         'name' => 'required|string|min:2|max:50'
      ],

      // Input sanitization
      'auto_sanitize' => true,
      'sanitize_html' => true,

      // File upload settings
      'file_upload' => [
         'max_size' => 5 * 1024 * 1024, // 5MB
         'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
         ],
         'upload_path' => '/srv/http/projects/viex-app/storage/uploads'
      ]
   ],

   'response' => [
      /*
      |--------------------------------------------------------------------------
      | Response Settings
      |--------------------------------------------------------------------------
      */

      // Default headers
      'default_headers' => [
         'X-Frame-Options' => 'DENY',
         'X-Content-Type-Options' => 'nosniff',
         'X-XSS-Protection' => '1; mode=block',
         'Referrer-Policy' => 'strict-origin-when-cross-origin'
      ],

      // JSON response settings
      'json' => [
         'flags' => JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
         'depth' => 512
      ],

      // Cache settings
      'cache' => [
         'default_max_age' => 3600, // 1 hour
         'static_max_age' => 86400,  // 24 hours
         'no_cache_routes' => [
            '/api/*',
            '/admin/*'
         ]
      ],

      // CORS settings
      'cors' => [
         'enabled' => true,
         'allowed_origins' => ['*'],
         'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
         'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
         'max_age' => 86400
      ]
   ],

   'session' => [
      /*
      |--------------------------------------------------------------------------
      | Session Settings
      |--------------------------------------------------------------------------
      */

      // Flash message settings
      'flash' => [
         'key' => 'flash',
         'types' => ['success', 'error', 'warning', 'info']
      ],

      // Old input settings
      'old_input' => [
         'key' => 'old_input',
         'exclude_fields' => ['password', 'password_confirmation', '_token']
      ]
   ],

   'validation' => [
      /*
      |--------------------------------------------------------------------------
      | Validation Settings
      |--------------------------------------------------------------------------
      */

      // Custom validation messages in Spanish
      'messages' => [
         'required' => 'El campo :field es requerido.',
         'string' => 'El campo :field debe ser una cadena de texto.',
         'integer' => 'El campo :field debe ser un número entero.',
         'email' => 'El campo :field debe ser un email válido.',
         'min' => 'El campo :field debe tener al menos :min caracteres.',
         'max' => 'El campo :field no puede tener más de :max caracteres.',
         'in' => 'El campo :field debe ser uno de: :values',
         'numeric' => 'El campo :field debe ser numérico.',
         'alpha' => 'El campo :field solo puede contener letras.',
         'alpha_num' => 'El campo :field solo puede contener letras y números.',
         'url' => 'El campo :field debe ser una URL válida.',
         'confirmed' => 'La confirmación del campo :field no coincide.',
         'unique' => 'El valor del campo :field ya existe.',
         'exists' => 'El valor del campo :field no existe.'
      ],

      // Custom validation rules
      'custom_rules' => [
         'cedula_panama' => [
            'rule' => '/^[0-9]{1,2}-[0-9]{1,4}-[0-9]{1,6}$/',
            'message' => 'El campo :field debe tener el formato válido de cédula panameña (ej: 8-123-456).'
         ]
      ]
   ],

   'security' => [
      /*
      |--------------------------------------------------------------------------
      | Security Settings
      |--------------------------------------------------------------------------
      */

      // CSRF protection
      'csrf' => [
         'enabled' => true,
         'token_name' => '_token',
         'regenerate_token' => true
      ],

      // Rate limiting
      'rate_limit' => [
         'enabled' => true,
         'max_requests' => 100,
         'window' => 3600, // 1 hour
         'by_ip' => true
      ],

      // Input filtering
      'input_filter' => [
         'strip_tags' => true,
         'trim_whitespace' => true,
         'remove_null_bytes' => true
      ]
   ],

   'middleware' => [
      /*
      |--------------------------------------------------------------------------
      | Middleware Settings
      |--------------------------------------------------------------------------
      */

      // Global middleware (applied to all routes)
      'global' => [
         \Viex\Framework\Http\RequestMiddleware::class
      ],

      // Route-specific middleware
      'route' => [
         // 'auth' => \Viex\Framework\Http\AuthMiddleware::class,
         // 'csrf' => \Viex\Framework\Http\CsrfMiddleware::class,
         // 'rate_limit' => \Viex\Framework\Http\RateLimitMiddleware::class
      ]
   ]
];
