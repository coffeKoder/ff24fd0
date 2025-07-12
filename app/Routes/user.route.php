<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Routes
 * @file        user.route
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-12 11:30:00
 * @version     1.0.0
 * @description Rutas del módulo User - Autenticación y gestión de usuarios
 */

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Viex\Modules\User\Infrastructure\Http\Controllers\AuthController;
use Viex\Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Viex\Modules\User\Infrastructure\Http\Controllers\UserManagementController;
use Viex\Modules\User\Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Viex\Modules\User\Infrastructure\Http\Middleware\AuthorizationMiddleware;

/**
 * Configuración de rutas del módulo User
 * 
 * Define todas las rutas HTTP para:
 * - Autenticación (login, logout, sesiones)
 * - Gestión de perfil de usuario
 * - Administración de usuarios (CRUD, roles, permisos)
 * 
 * Integra middleware de autenticación y autorización RBAC
 */
return function (App $app) {

    // ===========================
    // RUTAS DE AUTENTICACIÓN
    // ===========================
    
    $app->group('/api/auth', function (RouteCollectorProxy $group) {
        
        // Login - Ruta pública
        $group->post('/login', [AuthController::class, 'login'])
            ->setName('auth.login');

        // Logout - Requiere autenticación
        $group->post('/logout', [AuthController::class, 'logout'])
            ->add(AuthenticationMiddleware::class)
            ->setName('auth.logout');

        // Información del usuario autenticado
        $group->get('/me', [AuthController::class, 'me'])
            ->add(AuthenticationMiddleware::class)
            ->setName('auth.me');

        // Extender sesión activa
        $group->post('/extend-session', [AuthController::class, 'extendSession'])
            ->add(AuthenticationMiddleware::class)
            ->setName('auth.extend-session');
    });

    // ===========================
    // RUTAS DE PERFIL DE USUARIO
    // ===========================
    
    $app->group('/api/profile', function (RouteCollectorProxy $group) {
        
        // Obtener perfil del usuario autenticado
        $group->get('', [ProfileController::class, 'getProfile'])
            ->setName('profile.get');

        // Actualizar perfil del usuario autenticado
        $group->put('', [ProfileController::class, 'updateProfile'])
            ->setName('profile.update');

        // Cambiar contraseña del usuario autenticado
        $group->put('/password', [ProfileController::class, 'changePassword'])
            ->setName('profile.change-password');

    })->add(AuthenticationMiddleware::class); // Todas las rutas de perfil requieren autenticación

    // ===========================
    // RUTAS DE ADMINISTRACIÓN DE USUARIOS
    // ===========================
    
    $app->group('/api/users', function (RouteCollectorProxy $group) {

        // Listar usuarios con filtros y paginación
        $group->get('', [UserManagementController::class, 'list'])
            ->setName('users.list');

        // Crear nuevo usuario
        $group->post('', [UserManagementController::class, 'create'])
            ->setName('users.create');

        // Obtener usuario específico por ID
        $group->get('/{id:[0-9]+}', [UserManagementController::class, 'getById'])
            ->setName('users.get');

        // Actualizar datos de usuario específico
        $group->put('/{id:[0-9]+}', [UserManagementController::class, 'update'])
            ->setName('users.update');

        // Activar usuario específico
        $group->post('/{id:[0-9]+}/activate', [UserManagementController::class, 'activate'])
            ->setName('users.activate');

        // Desactivar usuario específico
        $group->post('/{id:[0-9]+}/deactivate', [UserManagementController::class, 'deactivate'])
            ->setName('users.deactivate');

        // Cambiar contraseña de usuario específico (admin)
        $group->put('/{id:[0-9]+}/password', [UserManagementController::class, 'changePassword'])
            ->setName('users.change-password');

    })->add(AuthenticationMiddleware::class); // Todas las rutas de administración requieren autenticación

    // ===========================
    // RUTAS CON MIDDLEWARE DE AUTORIZACIÓN
    // ===========================
    
    // Nota: Se pueden agregar middleware de autorización específicos para operaciones sensibles
    // Ejemplo para futuras implementaciones:
    
    /*
    $app->group('/api/admin/users', function (RouteCollectorProxy $group) {
        
        // Operaciones que requieren permisos específicos
        $group->delete('/{id:[0-9]+}', [UserManagementController::class, 'delete'])
            ->add((new AuthorizationMiddleware(/* dependencies *//*))
                ->withPermissions(['users.delete']));
                
        $group->post('/{id:[0-9]+}/assign-role', [UserManagementController::class, 'assignRole'])
            ->add((new AuthorizationMiddleware(/* dependencies *//*))
                ->withPermissions(['users.assign_roles']));
                
    })->add(AuthenticationMiddleware::class);
    */
};

/* 
 * ===========================
 * DOCUMENTACIÓN DE USO
 * ===========================
 * 
 * RUTAS DE AUTENTICACIÓN:
 * 
 * POST /api/auth/login
 * Body: {"identifier": "user@example.com", "password": "password123", "remember": false}
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * POST /api/auth/logout  
 * Headers: Cookie con sesión activa
 * Response: {"status": "success", "message": "Sesión cerrada exitosamente"}
 * 
 * GET /api/auth/me
 * Headers: Cookie con sesión activa
 * Response: {"status": "success", "data": {"user": {...}, "session": {...}, "permissions": [...]}}
 * 
 * POST /api/auth/extend-session
 * Headers: Cookie con sesión activa
 * Body: {"minutes": 30}
 * Response: {"status": "success", "message": "Sesión extendida exitosamente"}
 * 
 * 
 * RUTAS DE PERFIL:
 * 
 * GET /api/profile
 * Headers: Cookie con sesión activa
 * Response: {"status": "success", "data": {"profile": {...}}}
 * 
 * PUT /api/profile
 * Headers: Cookie con sesión activa
 * Body: {"first_name": "Juan", "last_name": "Pérez", "office_phone": "+1234567890"}
 * Response: {"status": "success", "data": {"profile": {...}}}
 * 
 * PUT /api/profile/password
 * Headers: Cookie con sesión activa
 * Body: {"current_password": "oldpass", "new_password": "newpass123"}
 * Response: {"status": "success", "message": "Contraseña cambiada exitosamente"}
 * 
 * 
 * RUTAS DE ADMINISTRACIÓN:
 * 
 * GET /api/users?limit=20&offset=0&search=juan&is_active=true
 * Headers: Cookie con sesión activa y permisos users.list
 * Response: {"status": "success", "data": {"users": [...], "pagination": {...}}}
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
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * GET /api/users/123
 * Headers: Cookie con sesión activa y permisos users.read
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * PUT /api/users/123
 * Headers: Cookie con sesión activa y permisos users.update
 * Body: {"first_name": "Juan Carlos", "last_name": "Pérez Gómez"}
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * POST /api/users/123/activate
 * Headers: Cookie con sesión activa y permisos users.manage_status
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * POST /api/users/123/deactivate
 * Headers: Cookie con sesión activa y permisos users.manage_status
 * Response: {"status": "success", "data": {"user": {...}}}
 * 
 * PUT /api/users/123/password
 * Headers: Cookie con sesión activa y permisos users.change_password
 * Body: {"current_password": "oldpass", "new_password": "newpass123"}
 * Response: {"status": "success", "message": "Contraseña cambiada exitosamente"}
 */
