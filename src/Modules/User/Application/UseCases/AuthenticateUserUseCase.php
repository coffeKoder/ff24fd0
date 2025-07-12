<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\UseCases;

use Viex\Modules\User\Application\Services\LoginService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Application\Services\PermissionService;
use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\ValueObjects\Credentials;
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Viex\Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Viex\Modules\User\Domain\Exceptions\InactiveUserException;
use Psr\Log\LoggerInterface;

/**
 * Use Case para autenticar un usuario en el sistema
 * 
 * Coordina el proceso completo de autenticación:
 * - Valida credenciales
 * - Crea sesión
 * - Carga permisos
 * - Registra actividad
 */
final class AuthenticateUserUseCase {
   public function __construct(
      private LoginService $loginService,
      private SessionService $sessionService,
      private PermissionService $permissionService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Autentica un usuario y establece su sesión
    */
   public function execute(Credentials $credentials): AuthenticationResult {
      try {
         $this->logger->info('Iniciando proceso de autenticación', [
            'email' => $credentials->getEmail()->getValue()
         ]);

         // Verificar si el usuario puede intentar login
         $canLoginResult = $this->loginService->canLogin($credentials->getEmail()->getValue());
         if (!$canLoginResult['can_attempt']) {
            throw new InvalidCredentialsException(
               'Demasiados intentos de login. Intente más tarde.'
            );
         }

         // Autenticar usuario
         $user = $this->loginService->authenticate($credentials);

         // Crear sesión de usuario
         $this->sessionService->create($user);

         // Cargar permisos del usuario
         $permissions = $this->permissionService->getUserPermissions($user);

         $this->logger->info('Autenticación exitosa', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail()
         ]);

         return new AuthenticationResult(
            success: true,
            user: $user,
            permissions: $permissions,
            message: 'Autenticación exitosa'
         );

      } catch (UserNotFoundException $e) {
         $this->logger->warning('Intento de login con usuario inexistente', [
            'email' => $credentials->getEmail()->getValue()
         ]);

         throw new InvalidCredentialsException('Credenciales inválidas');

      } catch (InactiveUserException $e) {
         $this->logger->warning('Intento de login con usuario inactivo', [
            'email' => $credentials->getEmail()->getValue()
         ]);

         throw $e;

      } catch (\Exception $e) {
         $this->logger->error('Error durante autenticación', [
            'email' => $credentials->getEmail()->getValue(),
            'error' => $e->getMessage()
         ]);

         throw new InvalidCredentialsException('Error durante la autenticación');
      }
   }

   /**
    * Verifica si un usuario puede intentar hacer login
    */
   public function canAttemptLogin(string $email): bool {
      $result = $this->loginService->canLogin($email);
      return $result['can_attempt'] ?? false;
   }

   /**
    * Obtiene información de bloqueo para un usuario
    */
   public function getLoginAttemptInfo(string $email): array {
      return $this->loginService->canLogin($email);
   }
}

/**
 * Resultado del proceso de autenticación
 */
final class AuthenticationResult {
   public function __construct(
      private bool $success,
      private ?User $user = null,
      private array $permissions = [],
      private string $message = ''
   ) {
   }

   public function isSuccessful(): bool {
      return $this->success;
   }

   public function getUser(): ?User {
      return $this->user;
   }

   public function getPermissions(): array {
      return $this->permissions;
   }

   public function getMessage(): string {
      return $this->message;
   }

   public function hasPermission(string $permission): bool {
      return in_array($permission, $this->permissions, true);
   }

   public function toArray(): array {
      return [
         'success' => $this->success,
         'user_id' => $this->user?->getId(),
         'email' => $this->user?->getEmail(),
         'permissions' => $this->permissions,
         'message' => $this->message
      ];
   }
}
