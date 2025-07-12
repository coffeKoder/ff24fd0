<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\DTOs;

/**
 * DTO para transferir información de sesión y autenticación
 */
final class SessionDTO {
   public string $sessionId;
   public ?UserDTO $user;
   public array $permissions;
   public array $roles;
   public bool $isAuthenticated;
   public bool $isActive;
   public ?\DateTimeImmutable $createdAt;
   public ?\DateTimeImmutable $lastActivity;
   public ?int $expiresIn; // Segundos hasta expiración

   public function __construct(
      string $sessionId,
      ?UserDTO $user = null,
      array $permissions = [],
      array $roles = [],
      bool $isAuthenticated = false,
      bool $isActive = false,
      ?\DateTimeImmutable $createdAt = null,
      ?\DateTimeImmutable $lastActivity = null,
      ?int $expiresIn = null
   ) {
      $this->sessionId = $sessionId;
      $this->user = $user;
      $this->permissions = $permissions;
      $this->roles = $roles;
      $this->isAuthenticated = $isAuthenticated;
      $this->isActive = $isActive;
      $this->createdAt = $createdAt;
      $this->lastActivity = $lastActivity;
      $this->expiresIn = $expiresIn;
   }

   /**
    * Crea una sesión activa con usuario
    */
   public static function createActive(
      string $sessionId,
      UserDTO $user,
      array $permissions = [],
      array $roles = []
   ): self {
      return new self(
         sessionId: $sessionId,
         user: $user,
         permissions: $permissions,
         roles: $roles,
         isAuthenticated: true,
         isActive: true,
         createdAt: new \DateTimeImmutable(),
         lastActivity: new \DateTimeImmutable()
      );
   }

   /**
    * Crea una sesión anónima
    */
   public static function createAnonymous(string $sessionId): self {
      return new self(
         sessionId: $sessionId,
         isAuthenticated: false,
         isActive: true,
         createdAt: new \DateTimeImmutable(),
         lastActivity: new \DateTimeImmutable()
      );
   }

   /**
    * Verifica si el usuario tiene un permiso específico
    */
   public function hasPermission(string $permission): bool {
      return in_array($permission, $this->permissions, true);
   }

   /**
    * Verifica si el usuario tiene cualquiera de los permisos dados
    */
   public function hasAnyPermission(array $permissions): bool {
      return !empty(array_intersect($permissions, $this->permissions));
   }

   /**
    * Verifica si el usuario tiene todos los permisos dados
    */
   public function hasAllPermissions(array $permissions): bool {
      return empty(array_diff($permissions, $this->permissions));
   }

   /**
    * Verifica si el usuario tiene un rol específico
    */
   public function hasRole(string $role): bool {
      return in_array($role, $this->roles, true);
   }

   /**
    * Verifica si el usuario tiene cualquiera de los roles dados
    */
   public function hasAnyRole(array $roles): bool {
      return !empty(array_intersect($roles, $this->roles));
   }

   /**
    * Obtiene información básica de la sesión
    */
   public function getBasicInfo(): array {
      return [
         'session_id' => $this->sessionId,
         'is_authenticated' => $this->isAuthenticated,
         'is_active' => $this->isActive,
         'user_id' => $this->user?->id,
         'username' => $this->user?->username,
         'permissions_count' => count($this->permissions),
         'roles_count' => count($this->roles)
      ];
   }

   /**
    * Obtiene información detallada de la sesión
    */
   public function getDetailedInfo(): array {
      return [
         'session_id' => $this->sessionId,
         'user' => $this->user?->toArray(),
         'permissions' => $this->permissions,
         'roles' => $this->roles,
         'is_authenticated' => $this->isAuthenticated,
         'is_active' => $this->isActive,
         'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
         'last_activity' => $this->lastActivity?->format('Y-m-d H:i:s'),
         'expires_in' => $this->expiresIn
      ];
   }

   /**
    * Verifica si la sesión está próxima a expirar
    */
   public function isExpiringSoon(int $warningMinutes = 5): bool {
      if ($this->expiresIn === null) {
         return false;
      }

      return $this->expiresIn <= ($warningMinutes * 60);
   }

   /**
    * Obtiene el tiempo de actividad de la sesión
    */
   public function getSessionDuration(): ?string {
      if (!$this->createdAt) {
         return null;
      }

      $now = new \DateTimeImmutable();
      $interval = $now->diff($this->createdAt);

      if ($interval->days > 0) {
         return $interval->days . ' días';
      } elseif ($interval->h > 0) {
         return $interval->h . ' horas';
      } elseif ($interval->i > 0) {
         return $interval->i . ' minutos';
      } else {
         return 'Menos de un minuto';
      }
   }

   /**
    * Obtiene el tiempo desde la última actividad
    */
   public function getTimeSinceLastActivity(): ?string {
      if (!$this->lastActivity) {
         return null;
      }

      $now = new \DateTimeImmutable();
      $interval = $now->diff($this->lastActivity);

      if ($interval->days > 0) {
         return $interval->days . ' días';
      } elseif ($interval->h > 0) {
         return $interval->h . ' horas';
      } elseif ($interval->i > 0) {
         return $interval->i . ' minutos';
      } else {
         return 'Activo ahora';
      }
   }

   /**
    * Convierte a array
    */
   public function toArray(): array {
      return $this->getDetailedInfo();
   }
}
