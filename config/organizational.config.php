<?php
/**
 * @package     ff24fd0/config
 * @subpackage  modules
 * @file        organizational.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 20:00:00
 * @version     1.0.0
 * @description Configuración del módulo Organizational
 */

declare(strict_types=1);

return [
   /*
   |--------------------------------------------------------------------------
   | Configuración del Módulo Organizational
   |--------------------------------------------------------------------------
   */
   'organizational' => [

      // Configuración de caché para jerarquías
      'cache' => [
         'hierarchy_ttl' => 3600, // 1 hora
         'unit_context_ttl' => 1800, // 30 minutos
         'statistics_ttl' => 7200, // 2 horas
         'enabled' => true,
      ],

      // Configuración de validaciones
      'validation' => [
         'unit_name_min_length' => 3,
         'unit_name_max_length' => 255,
         'max_hierarchy_depth' => 10,
         'allowed_unit_types' => [
            'headquarters', // Sede
            'faculty',      // Facultad
            'department',   // Departamento
            'school',       // Escuela
            'center',       // Centro
            'institute',    // Instituto
            'unit',         // Unidad general
         ],
      ],

      // Configuración de la jerarquía
      'hierarchy' => [
         'root_units' => ['headquarters'], // Tipos que pueden ser raíz
         'valid_parent_child_relationships' => [
            'headquarters' => ['faculty', 'center', 'institute'],
            'faculty' => ['department', 'school'],
            'department' => ['school'],
            'center' => ['unit'],
            'institute' => ['unit'],
            'school' => [],
            'unit' => [],
         ],
      ],

      // Configuración de paginación
      'pagination' => [
         'default_page_size' => 20,
         'max_page_size' => 100,
      ],

      // Configuración de logging
      'logging' => [
         'enabled' => true,
         'level' => 'info',
         'log_hierarchy_changes' => true,
         'log_unit_operations' => true,
      ],

      // Configuración de eventos
      'events' => [
         'enabled' => true,
         'async' => false, // Para futuras implementaciones con colas
      ],

      // URLs y rutas
      'routes' => [
         'api_prefix' => '/api/organizational',
         'admin_prefix' => '/admin/organizational',
      ],

      // Configuración de seguridad
      'security' => [
         'require_authentication' => true,
         'require_authorization' => true,
         'allowed_roles' => [
            'admin',
            'organizational_admin',
            'unit_coordinator',
            'dean',
            'director',
         ],
      ],
   ],
];
