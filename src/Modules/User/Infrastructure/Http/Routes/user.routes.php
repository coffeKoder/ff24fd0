<?php

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Viex\Modules\User\Infrastructure\Http\Controllers\AuthController;
use Viex\Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Viex\Modules\User\Infrastructure\Http\Controllers\UserManagementController;
use Viex\Modules\User\Infrastructure\Http\Middleware\AuthenticationMiddleware;

/**
 * Configuración de rutas para el módulo User
 * 
 * Este archivo define todas las rutas HTTP disponibles para el módulo de usuarios,
 * incluyendo autenticación, gestión de perfil y administración de usuarios.
 */
return function (App $app) {

   // Grupo de rutas para autenticación (públicas)
   $app->group('/api/auth', function (RouteCollectorProxy $group) {
      // Login
      $group->post('/login', [AuthController::class, 'login']);

      // Logout (requiere autenticación)
      $group->post('/logout', [AuthController::class, 'logout'])
         ->add(AuthenticationMiddleware::class);

      // Información del usuario autenticado
      $group->get('/me', [AuthController::class, 'me'])
         ->add(AuthenticationMiddleware::class);

      // Extender sesión
      $group->post('/extend-session', [AuthController::class, 'extendSession'])
         ->add(AuthenticationMiddleware::class);
   });

   // Grupo de rutas para perfil de usuario (requiere autenticación)
   $app->group('/api/profile', function (RouteCollectorProxy $group) {
      // Obtener perfil
      $group->get('', [ProfileController::class, 'getProfile']);

      // Actualizar perfil
      $group->put('', [ProfileController::class, 'updateProfile']);

      // Cambiar contraseña
      $group->put('/password', [ProfileController::class, 'changePassword']);

   })->add(AuthenticationMiddleware::class);

   // Grupo de rutas para administración de usuarios (requiere autenticación)
   $app->group('/api/users', function (RouteCollectorProxy $group) {

      // Listar usuarios
      $group->get('', [UserManagementController::class, 'list']);

      // Crear usuario
      $group->post('', [UserManagementController::class, 'create']);

      // Obtener usuario por ID
      $group->get('/{id:[0-9]+}', [UserManagementController::class, 'getById']);

      // Actualizar usuario
      $group->put('/{id:[0-9]+}', [UserManagementController::class, 'update']);

      // Activar usuario
      $group->post('/{id:[0-9]+}/activate', [UserManagementController::class, 'activate']);

      // Desactivar usuario
      $group->post('/{id:[0-9]+}/deactivate', [UserManagementController::class, 'deactivate']);

      // Cambiar contraseña de usuario
      $group->put('/{id:[0-9]+}/password', [UserManagementController::class, 'changePassword']);

   })->add(AuthenticationMiddleware::class);
};

/**
 * Ejemplos de uso de las rutas:
 * 
 * POST /api/auth/login
 * Body: {"identifier": "user@example.com", "password": "password123", "remember": false}
 * 
 * GET /api/auth/me
 * Headers: Cookie con sesión activa
 * 
 * GET /api/profile
 * Headers: Cookie con sesión activa
 * 
 * PUT /api/profile
 * Headers: Cookie con sesión activa
 * Body: {"first_name": "Juan", "last_name": "Pérez", "office_phone": "+1234567890"}
 * 
 * GET /api/users?limit=20&offset=0&search=juan&is_active=true
 * Headers: Cookie con sesión activa y permisos users.list
 * 
 * POST /api/users
 * Headers: Cookie con sesión activa y permisos users.create
 * Body: {
 *   "username": "newuser",
 *   "email": "newuser@example.com",
 *   "password": "securepassword123",
 *   "first_name": "Nuevo",
 *   "last_name": "Usuario",
 *   "cedula": "12345678",
 *   "is_active": true
 * }
 * 
 * GET /api/users/123
 * Headers: Cookie con sesión activa y permisos users.read
 * 
 * PUT /api/users/123
 * Headers: Cookie con sesión activa y permisos users.update
 * Body: {"first_name": "Juan Carlos", "last_name": "Pérez Gómez"}
 * 
 * POST /api/users/123/activate
 * Headers: Cookie con sesión activa y permisos users.manage_status
 * 
 * PUT /api/users/123/password
 * Headers: Cookie con sesión activa y permisos users.change_password
 * Body: {"current_password": "oldpass", "new_password": "newpass123"}
 */
