<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\Repositories\{
   PermissionRepositoryInterface,
   UserUserGroupRepositoryInterface,
   UserGroupRepositoryInterface
};

/**
 * Sistema RBAC para verificación de permisos contextuales
 * Maneja permisos tanto globales como específicos por unidad organizacional
 */
class PermissionService {
   private PermissionRepositoryInterface $permissionRepository;
   private UserUserGroupRepositoryInterface $userUserGroupRepository;
   private UserGroupRepositoryInterface $userGroupRepository;

   public function __construct(
      PermissionRepositoryInterface $permissionRepository,
      UserUserGroupRepositoryInterface $userUserGroupRepository,
      UserGroupRepositoryInterface $userGroupRepository
   ) {
      $this->permissionRepository = $permissionRepository;
      $this->userUserGroupRepository = $userUserGroupRepository;
      $this->userGroupRepository = $userGroupRepository;
   }

   /**
    * Obtiene todos los permisos de un usuario (consolidados de todos sus roles)
    */
   public function getUserPermissions(User $user): array {
      try {
         $permissions = $this->permissionRepository->findByUserId($user->getId());

         // Extraer solo los nombres de permisos únicos
         $permissionNames = [];
         foreach ($permissions as $permission) {
            if (isset($permission['name']) && !in_array($permission['name'], $permissionNames)) {
               $permissionNames[] = $permission['name'];
            }
         }

         return $permissionNames;
      } catch (\Exception $e) {
         // Log error y retornar array vacío para mantener la aplicación funcionando
         error_log("Error obteniendo permisos de usuario {$user->getId()}: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Obtiene los grupos/roles de un usuario con información contextual
    */
   public function getUserGroups(User $user): array {
      try {
         $userGroups = $this->userGroupRepository->findGroupsByUserId($user->getId());

         $groupsData = [];
         foreach ($userGroups as $group) {
            $groupsData[] = [
               'id' => $group['id'] ?? null,
               'name' => $group['name'] ?? null,
               'description' => $group['description'] ?? null,
               'organizational_unit_id' => $group['organizational_unit_id'] ?? null,
               'organizational_unit_name' => $group['organizational_unit_name'] ?? null
            ];
         }

         return $groupsData;
      } catch (\Exception $e) {
         error_log("Error obteniendo grupos de usuario {$user->getId()}: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Obtiene permisos de un usuario en una unidad organizacional específica
    */
   public function getUserPermissionsInContext(User $user, int $organizationalUnitId): array {
      try {
         // Por ahora, devolvemos los permisos generales del usuario
         // TODO: Implementar lógica contextual cuando esté disponible en el repositorio
         $permissions = $this->permissionRepository->findByUserId($user->getId());

         $permissionNames = [];
         foreach ($permissions as $permission) {
            if (isset($permission['name']) && !in_array($permission['name'], $permissionNames)) {
               $permissionNames[] = $permission['name'];
            }
         }

         return $permissionNames;
      } catch (\Exception $e) {
         error_log("Error obteniendo permisos contextuales de usuario {$user->getId()} en unidad {$organizationalUnitId}: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Verifica si un usuario tiene un permiso específico (global)
    */
   public function userHasPermission(User $user, string $permission): bool {
      try {
         $userPermissions = $this->getUserPermissions($user);
         return in_array($permission, $userPermissions, true);
      } catch (\Exception $e) {
         error_log("Error verificando permiso '{$permission}' para usuario {$user->getId()}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifica si un usuario tiene un permiso en una unidad organizacional específica
    */
   public function userHasPermissionInContext(User $user, string $permission, int $organizationalUnitId): bool {
      try {
         $userPermissions = $this->getUserPermissionsInContext($user, $organizationalUnitId);
         return in_array($permission, $userPermissions, true);
      } catch (\Exception $e) {
         error_log("Error verificando permiso contextual '{$permission}' para usuario {$user->getId()} en unidad {$organizationalUnitId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifica si un usuario tiene un rol específico (global)
    */
   public function userHasRole(User $user, string $roleName): bool {
      try {
         $userGroups = $this->getUserGroups($user);

         foreach ($userGroups as $group) {
            if (isset($group['name']) && $group['name'] === $roleName) {
               return true;
            }
         }

         return false;
      } catch (\Exception $e) {
         error_log("Error verificando rol '{$roleName}' para usuario {$user->getId()}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifica si un usuario tiene un rol en una unidad organizacional específica
    */
   public function userHasRoleInUnit(User $user, string $roleName, int $organizationalUnitId): bool {
      try {
         $userGroups = $this->getUserGroups($user);

         foreach ($userGroups as $group) {
            if (isset($group['name']) && $group['name'] === $roleName &&
               isset($group['organizational_unit_id']) && $group['organizational_unit_id'] === $organizationalUnitId) {
               return true;
            }
         }

         return false;
      } catch (\Exception $e) {
         error_log("Error verificando rol contextual '{$roleName}' para usuario {$user->getId()} en unidad {$organizationalUnitId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifica si un usuario puede actuar en nombre de una unidad organizacional
    * (tiene permisos directos o heredados por jerarquía)
    */
   public function userCanActInUnit(User $user, string $roleName, int $targetUnitId): bool {
      try {
         // Por ahora verificamos solo si tiene el rol en la unidad específica
         // TODO: Implementar lógica de herencia jerárquica cuando esté disponible
         return $this->userHasRoleInUnit($user, $roleName, $targetUnitId);
      } catch (\Exception $e) {
         error_log("Error verificando capacidad de actuar en unidad {$targetUnitId} para usuario {$user->getId()}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Obtiene las unidades organizacionales donde un usuario tiene un rol específico
    */
   public function getUserUnitsWithRole(User $user, string $roleName): array {
      try {
         $userGroups = $this->getUserGroups($user);
         $units = [];

         foreach ($userGroups as $group) {
            if (isset($group['name']) && $group['name'] === $roleName &&
               isset($group['organizational_unit_id'])) {
               $units[] = [
                  'unit_id' => $group['organizational_unit_id'],
                  'unit_name' => $group['organizational_unit_name'] ?? null
               ];
            }
         }

         return $units;
      } catch (\Exception $e) {
         error_log("Error obteniendo unidades con rol '{$roleName}' para usuario {$user->getId()}: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Verifica múltiples permisos a la vez
    */
   public function userHasAnyPermission(User $user, array $permissions): bool {
      foreach ($permissions as $permission) {
         if ($this->userHasPermission($user, $permission)) {
            return true;
         }
      }

      return false;
   }

   /**
    * Verifica que el usuario tenga TODOS los permisos especificados
    */
   public function userHasAllPermissions(User $user, array $permissions): bool {
      foreach ($permissions as $permission) {
         if (!$this->userHasPermission($user, $permission)) {
            return false;
         }
      }

      return true;
   }

   /**
    * Verifica permisos con patrones (wildcards)
    * Ej: "extension.*" coincide con "extension.work.view", "extension.work.create", etc.
    */
   public function userHasPermissionPattern(User $user, string $pattern): bool {
      try {
         $userPermissions = $this->getUserPermissions($user);

         // Convertir pattern a regex
         $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';

         foreach ($userPermissions as $permission) {
            if (preg_match($regex, $permission)) {
               return true;
            }
         }

         return false;
      } catch (\Exception $e) {
         error_log("Error verificando pattern de permiso '{$pattern}' para usuario {$user->getId()}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Obtiene un resumen completo de permisos y roles de un usuario
    */
   public function getUserSecurityProfile(User $user): array {
      try {
         return [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'is_active' => $user->isActive(),
            'permissions' => $this->getUserPermissions($user),
            'groups' => $this->getUserGroups($user),
            'permission_count' => count($this->getUserPermissions($user)),
            'group_count' => count($this->getUserGroups($user)),
            'has_admin_access' => $this->userHasPermissionPattern($user, 'admin.*'),
            'has_extension_access' => $this->userHasPermissionPattern($user, 'extension.*'),
            'can_certify' => $this->userHasAnyPermission($user, [
               'extension.work.certify.viex',
               'extension.work.certify.coordinator'
            ])
         ];
      } catch (\Exception $e) {
         error_log("Error obteniendo perfil de seguridad para usuario {$user->getId()}: " . $e->getMessage());
         return [
            'user_id' => $user->getId(),
            'error' => 'Error obteniendo perfil de seguridad'
         ];
      }
   }

   /**
    * Valida que un usuario tenga el nivel de acceso mínimo requerido
    */
   public function validateMinimumAccess(User $user, array $requiredPermissions = [], array $requiredRoles = []): array {
      $errors = [];

      // Verificar que el usuario esté activo
      if (!$user->isActive()) {
         $errors[] = 'Usuario inactivo';
      }

      // Verificar permisos requeridos
      foreach ($requiredPermissions as $permission) {
         if (!$this->userHasPermission($user, $permission)) {
            $errors[] = "Falta permiso requerido: {$permission}";
         }
      }

      // Verificar roles requeridos
      foreach ($requiredRoles as $role) {
         if (!$this->userHasRole($user, $role)) {
            $errors[] = "Falta rol requerido: {$role}";
         }
      }

      return [
         'valid' => empty($errors),
         'errors' => $errors,
         'user_id' => $user->getId()
      ];
   }
}
