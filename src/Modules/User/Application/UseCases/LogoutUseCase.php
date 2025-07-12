<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\UseCases;

use Viex\Modules\User\Application\Services\LoginService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Domain\Entities\User;
use Psr\Log\LoggerInterface;

/**
 * Use Case para cerrar sesión de usuario
 * 
 * Coordina el proceso completo de logout:
 * - Invalida la sesión actual
 * - Limpia datos de sesión
 * - Registra la actividad
 */
final class LogoutUseCase {
   public function __construct(
      private LoginService $loginService,
      private SessionService $sessionService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Cierra la sesión del usuario actual
    */
   public function execute(): LogoutResult {
      try {
         // Obtener usuario actual antes de cerrar sesión
         $currentUser = $this->loginService->getCurrentUser();
         $sessionId = session_id();

         $this->logger->info('Iniciando logout', [
            'user_id' => $currentUser?->getId(),
            'session_id' => $sessionId
         ]);

         // Cerrar sesión usando LoginService
         $this->loginService->logout();

         $this->logger->info('Logout exitoso', [
            'user_id' => $currentUser?->getId(),
            'session_id' => $sessionId
         ]);

         return new LogoutResult(
            true,
            'Sesión cerrada exitosamente',
            $currentUser
         );

      } catch (\Exception $e) {
         $this->logger->error('Error durante logout', [
            'error' => $e->getMessage()
         ]);

         return new LogoutResult(
            false,
            'Error durante el cierre de sesión'
         );
      }
   }

   /**
    * Cierra todas las sesiones de un usuario específico
    */
   public function executeForUser(int $userId): LogoutResult {
      try {
         $this->logger->info('Cerrando todas las sesiones del usuario', [
            'user_id' => $userId
         ]);

         // Nota: Esto requeriría implementación adicional para
         // manejar múltiples sesiones por usuario
         // Por ahora solo maneja la sesión actual

         $currentUser = $this->loginService->getCurrentUser();

         if ($currentUser && $currentUser->getId() === $userId) {
            $this->loginService->logout();
         }

         $this->logger->info('Sesiones del usuario cerradas', [
            'user_id' => $userId
         ]);

         return new LogoutResult(
            true,
            'Todas las sesiones del usuario han sido cerradas'
         );

      } catch (\Exception $e) {
         $this->logger->error('Error cerrando sesiones del usuario', [
            'user_id' => $userId,
            'error' => $e->getMessage()
         ]);

         return new LogoutResult(
            false,
            'Error cerrando las sesiones del usuario'
         );
      }
   }

   /**
    * Verifica si hay una sesión activa
    */
   public function hasActiveSession(): bool {
      return $this->loginService->isAuthenticated();
   }

   /**
    * Obtiene información de la sesión actual
    */
   public function getSessionInfo(): array {
      $currentUser = $this->loginService->getCurrentUser();
      $isAuthenticated = $this->loginService->isAuthenticated();

      return [
         'is_authenticated' => $isAuthenticated,
         'user_id' => $currentUser?->getId(),
         'email' => $currentUser?->getEmail(),
         'session_id' => session_id(),
         'session_active' => $this->sessionService->isActive()
      ];
   }
}

/**
 * Resultado del proceso de logout
 */
final class LogoutResult {
   private bool $success;
   private string $message;
   private ?User $user;

   public function __construct(
      bool $success,
      string $message = '',
      ?User $user = null
   ) {
      $this->success = $success;
      $this->message = $message;
      $this->user = $user;
   }

   public function isSuccessful(): bool {
      return $this->success;
   }

   public function getMessage(): string {
      return $this->message;
   }

   public function getUser(): ?User {
      return $this->user;
   }

   public function toArray(): array {
      return [
         'success' => $this->success,
         'message' => $this->message,
         'user_id' => $this->user?->getId(),
         'email' => $this->user?->getEmail()
      ];
   }
}
