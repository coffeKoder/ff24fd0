<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        UserServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-12 11:00:00
 * @version     1.0.0
 * @description Provider de servicios para el módulo User - Core de autenticación y gestión de usuarios
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Aura\Session\Session;

// Domain Repository Interfaces
use Viex\Modules\User\Domain\Repositories\UserRepositoryInterface;
use Viex\Modules\User\Domain\Repositories\UserGroupRepositoryInterface;
use Viex\Modules\User\Domain\Repositories\PermissionRepositoryInterface;
use Viex\Modules\User\Domain\Repositories\UserUserGroupRepositoryInterface;
use Viex\Modules\User\Domain\Repositories\UserGroupPermissionRepositoryInterface;
use Viex\Modules\User\Domain\Repositories\PasswordResetRepositoryInterface;

// Infrastructure Repository Implementations
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrineUserRepository;
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrineUserGroupRepository;
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrinePermissionRepository;
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrineUserUserGroupRepository;
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrineUserGroupPermissionRepository;
use Viex\Modules\User\Infrastructure\Persistence\Doctrine\DoctrinePasswordResetRepository;

// Application Services
use Viex\Modules\User\Application\Services\LoginService;
use Viex\Modules\User\Application\Services\UserService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Application\Services\TokenService;
use Viex\Modules\User\Application\Services\PermissionService;
use Viex\Modules\User\Application\Services\RoleService;

// Infrastructure Security Services
use Viex\Modules\User\Infrastructure\Security\PasswordHasher;
use Viex\Modules\User\Infrastructure\Security\TokenGenerator;
use Viex\Modules\User\Infrastructure\Security\RateLimiter;

// HTTP Controllers
use Viex\Modules\User\Infrastructure\Http\Controllers\AuthController;
use Viex\Modules\User\Infrastructure\Http\Controllers\ProfileController;
use Viex\Modules\User\Infrastructure\Http\Controllers\UserManagementController;

// HTTP Middleware
use Viex\Modules\User\Infrastructure\Http\Middleware\AuthenticationMiddleware;
use Viex\Modules\User\Infrastructure\Http\Middleware\AuthorizationMiddleware;

return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([

         // ===========================
         // REPOSITORY INTERFACES
         // ===========================

         // Usuario - Repositorio principal
      UserRepositoryInterface::class => \DI\autowire(DoctrineUserRepository::class),

         // Grupos de usuarios (Roles)
      UserGroupRepositoryInterface::class => \DI\autowire(DoctrineUserGroupRepository::class),

         // Permisos del sistema
      PermissionRepositoryInterface::class => \DI\autowire(DoctrinePermissionRepository::class),

         // Asignaciones contextuales de roles (RBAC)
      UserUserGroupRepositoryInterface::class => \DI\autowire(DoctrineUserUserGroupRepository::class),

         // Permisos de grupos
      UserGroupPermissionRepositoryInterface::class => \DI\autowire(DoctrineUserGroupPermissionRepository::class),

         // Tokens de recuperación de contraseñas
      PasswordResetRepositoryInterface::class => \DI\autowire(DoctrinePasswordResetRepository::class),

         // ===========================
         // INFRASTRUCTURE SECURITY
         // ===========================

         // Password hashing - Sin dependencias
      PasswordHasher::class => \DI\autowire(PasswordHasher::class),

         // Token generation - Sin dependencias
      TokenGenerator::class => \DI\autowire(TokenGenerator::class),

         // Rate limiting para login attempts
      RateLimiter::class => \DI\autowire(RateLimiter::class),        // ===========================
        // APPLICATION SERVICES
        // ===========================
        
        // Session Service - Gestión de sesiones nativas PHP con Aura
        SessionService::class => function (ContainerInterface $container) {
            return new SessionService(
                $container->get(Session::class)
            );
        },
        
        // Permission Service - Sistema RBAC
        PermissionService::class => function (ContainerInterface $container) {
            return new PermissionService(
                $container->get(PermissionRepositoryInterface::class),
                $container->get(UserUserGroupRepositoryInterface::class),
                $container->get(UserGroupRepositoryInterface::class)
            );
        },

         // Token Service - Generación y validación de tokens
      TokenService::class => function (ContainerInterface $container) {
         return new TokenService(
            $container->get(PasswordResetRepositoryInterface::class),
            $container->get(TokenGenerator::class),
            $container->get(LoggerInterface::class)
         );
      },

         // Role Service - Gestión de roles y asignaciones
      RoleService::class => function (ContainerInterface $container) {
         return new RoleService(
            $container->get(UserGroupRepositoryInterface::class),
            $container->get(UserUserGroupRepositoryInterface::class),
            $container->get(UserGroupPermissionRepositoryInterface::class),
            $container->get(LoggerInterface::class)
         );
      },        // User Service - Gestión completa de usuarios
        UserService::class => function (ContainerInterface $container) {
            return new UserService(
                $container->get(UserRepositoryInterface::class),
                $container->get(PasswordHasher::class),
                $container->get(LoggerInterface::class)
            );
        },
        
        // Login Service - Servicio central de autenticación
        LoginService::class => function (ContainerInterface $container) {
            return new LoginService(
                $container->get(UserRepositoryInterface::class),
                $container->get(PasswordHasher::class),
                $container->get(SessionService::class),
                $container->get(PermissionService::class),
                $container->get(RateLimiter::class),
                $container->get(LoggerInterface::class)
            );
        },

         // ===========================
         // HTTP MIDDLEWARE
         // ===========================

         // Authentication Middleware - Verificación de sesiones
      AuthenticationMiddleware::class => function (ContainerInterface $container) {
         return new AuthenticationMiddleware(
            $container->get(SessionService::class),
            $container->get(LoggerInterface::class)
         );
      },

         // Authorization Middleware - Verificación de permisos RBAC
      AuthorizationMiddleware::class => function (ContainerInterface $container) {
         return new AuthorizationMiddleware(
            $container->get(SessionService::class),
            $container->get(PermissionService::class),
            $container->get(LoggerInterface::class)
         );
      },

         // ===========================
         // HTTP CONTROLLERS
         // ===========================

         // Auth Controller - Autenticación, logout, me, extend-session
      AuthController::class => function (ContainerInterface $container) {
         return new AuthController(
            $container->get(LoginService::class),
            $container->get(SessionService::class),
            $container->get(UserService::class),
            $container->get(LoggerInterface::class)
         );
      },

         // Profile Controller - Gestión del perfil del usuario autenticado
      ProfileController::class => function (ContainerInterface $container) {
         return new ProfileController(
            $container->get(UserService::class),
            $container->get(SessionService::class),
            $container->get(LoggerInterface::class)
         );
      },

         // User Management Controller - Administración de usuarios
      UserManagementController::class => function (ContainerInterface $container) {
         return new UserManagementController(
            $container->get(UserService::class),
            $container->get(SessionService::class),
            $container->get(PermissionService::class),
            $container->get(LoggerInterface::class)
         );
      },

   ]);
};
