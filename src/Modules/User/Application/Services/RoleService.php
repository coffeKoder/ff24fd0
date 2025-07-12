<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\{UserGroup, UserUserGroup};
use Viex\Modules\User\Domain\Repositories\{
   UserRepositoryInterface,
   UserGroupRepositoryInterface,
   UserUserGroupRepositoryInterface,
   PermissionRepositoryInterface
};
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de roles y asignaciones contextuales RBAC
 * Maneja la creación, asignación y gestión de roles de usuario
 */
class RoleService {
   private UserRepositoryInterface $userRepository;
   private UserGroupRepositoryInterface $userGroupRepository;
   private UserUserGroupRepositoryInterface $userUserGroupRepository;
   private PermissionRepositoryInterface $permissionRepository;
   private ?LoggerInterface $logger;

   public function __construct(
      UserRepositoryInterface $userRepository,
      UserGroupRepositoryInterface $userGroupRepository,
      UserUserGroupRepositoryInterface $userUserGroupRepository,
      PermissionRepositoryInterface $permissionRepository,
      ?LoggerInterface $logger = null
   ) {
      $this->userRepository = $userRepository;
      $this->userGroupRepository = $userGroupRepository;
      $this->userUserGroupRepository = $userUserGroupRepository;
      $this->permissionRepository = $permissionRepository;
      $this->logger = $logger;
   }

   /**
    * Crea un nuevo grupo/rol de usuario
    */
   public function createUserGroup(string $name, string $description): UserGroup {
      // Verificar que el nombre no existe
      $existingGroup = $this->userGroupRepository->findByName($name);
      if ($existingGroup) {
         throw new \InvalidArgumentException("Ya existe un grupo con el nombre: {$name}");
      }

      // Crear nuevo grupo
      $userGroup = new UserGroup($name, $description);

      $this->userGroupRepository->save($userGroup);

      $this->logger?->info('Grupo de usuario creado', [
         'group_id' => $userGroup->getId(),
         'name' => $name
      ]);

      return $userGroup;
   }

   /**
    * Asigna un usuario a un grupo en una unidad organizacional específica
    */
   public function assignUserToGroup(int $userId, int $groupId, int $organizationalUnitId): UserUserGroup {
      // Verificar que el usuario existe
      $user = $this->userRepository->findById($userId);
      if (!$user) {
         throw new UserNotFoundException("Usuario no encontrado con ID: {$userId}");
      }

      // Verificar que el grupo existe
      $group = $this->userGroupRepository->findById($groupId);
      if (!$group) {
         throw new \InvalidArgumentException("Grupo no encontrado con ID: {$groupId}");
      }

      // Verificar si ya existe la asignación
      $existingAssignment = $this->userUserGroupRepository->findByUserAndGroup($userId, $groupId);
      if ($existingAssignment) {
         throw new \InvalidArgumentException("El usuario ya está asignado a este grupo");
      }

      // Crear nueva asignación
      $userUserGroup = new UserUserGroup($userId, $groupId, $organizationalUnitId);

      $this->userUserGroupRepository->save($userUserGroup);

      $this->logger?->info('Usuario asignado a grupo', [
         'user_id' => $userId,
         'group_id' => $groupId,
         'organizational_unit_id' => $organizationalUnitId
      ]);

      return $userUserGroup;
   }

   /**
    * Remueve un usuario de un grupo en una unidad organizacional específica
    */
   public function removeUserFromGroup(int $userId, int $groupId, int $organizationalUnitId): void {
      $assignment = $this->userUserGroupRepository->findByUserAndGroup($userId, $groupId);

      if (!$assignment) {
         throw new \InvalidArgumentException("No existe asignación entre usuario y grupo");
      }

      $this->userUserGroupRepository->delete($assignment);

      $this->logger?->info('Usuario removido del grupo', [
         'user_id' => $userId,
         'group_id' => $groupId,
         'organizational_unit_id' => $organizationalUnitId
      ]);
   }

   /**
    * Obtiene todos los roles/grupos de un usuario
    */
   public function getUserRoles(int $userId): array {
      try {
         $userGroups = $this->userGroupRepository->findGroupsByUserId($userId);

         $roles = [];
         foreach ($userGroups as $group) {
            $roles[] = [
               'id' => $group['id'] ?? null,
               'name' => $group['name'] ?? null,
               'description' => $group['description'] ?? null,
               'organizational_unit_id' => $group['organizational_unit_id'] ?? null,
               'organizational_unit_name' => $group['organizational_unit_name'] ?? null
            ];
         }

         return $roles;
      } catch (\Exception $e) {
         $this->logger?->error('Error obteniendo roles de usuario', [
            'user_id' => $userId,
            'error' => $e->getMessage()
         ]);
         return [];
      }
   }

   /**
    * Verifica si un usuario tiene un rol en una unidad organizacional específica
    */
   public function userHasRoleInUnit(int $userId, string $roleName, int $organizationalUnitId): bool {
      try {
         $userRoles = $this->getUserRoles($userId);

         foreach ($userRoles as $role) {
            if (isset($role['name']) && $role['name'] === $roleName &&
               isset($role['organizational_unit_id']) && $role['organizational_unit_id'] === $organizationalUnitId) {
               return true;
            }
         }

         return false;
      } catch (\Exception $e) {
         $this->logger?->error('Error verificando rol de usuario en unidad', [
            'user_id' => $userId,
            'role_name' => $roleName,
            'organizational_unit_id' => $organizationalUnitId,
            'error' => $e->getMessage()
         ]);
         return false;
      }
   }

   /**
    * Obtiene todos los grupos/roles disponibles
    */
   public function getAllRoles(): array {
      return $this->userGroupRepository->findActiveGroups();
   }

   /**
    * Obtiene un grupo por su nombre
    */
   public function getRoleByName(string $name): ?UserGroup {
      return $this->userGroupRepository->findByName($name);
   }

   /**
    * Obtiene un grupo por su ID
    */
   public function getRoleById(int $id): ?UserGroup {
      return $this->userGroupRepository->findById($id);
   }

   /**
    * Actualiza la información de un grupo
    */
   public function updateRole(int $groupId, ?string $name = null, ?string $description = null): UserGroup {
      $group = $this->userGroupRepository->findById($groupId);
      if (!$group) {
         throw new \InvalidArgumentException("Grupo no encontrado con ID: {$groupId}");
      }

      if ($name !== null) {
         // Verificar que el nuevo nombre no existe (si es diferente)
         if ($name !== $group->getName()) {
            $existingGroup = $this->userGroupRepository->findByName($name);
            if ($existingGroup) {
               throw new \InvalidArgumentException("Ya existe un grupo con el nombre: {$name}");
            }
         }
         $group->setName($name);
      }

      if ($description !== null) {
         $group->setDescription($description);
      }

      $this->userGroupRepository->save($group);

      $this->logger?->info('Grupo actualizado', [
         'group_id' => $groupId,
         'name' => $group->getName()
      ]);

      return $group;
   }

   /**
    * Elimina un grupo (solo si no tiene usuarios asignados)
    */
   public function deleteRole(int $groupId): void {
      $group = $this->userGroupRepository->findById($groupId);
      if (!$group) {
         throw new \InvalidArgumentException("Grupo no encontrado con ID: {$groupId}");
      }

      // Verificar que no tiene usuarios asignados
      $assignedUsers = $this->userUserGroupRepository->findByGroupId($groupId);
      if (!empty($assignedUsers)) {
         throw new \InvalidArgumentException("No se puede eliminar el grupo porque tiene usuarios asignados");
      }

      $this->userGroupRepository->delete($group);

      $this->logger?->info('Grupo eliminado', [
         'group_id' => $groupId,
         'name' => $group->getName()
      ]);
   }

   /**
    * Obtiene usuarios asignados a un grupo específico
    */
   public function getGroupUsers(int $groupId): array {
      return $this->userUserGroupRepository->findByGroupId($groupId);
   }

   /**
    * Cuenta usuarios en un grupo
    */
   public function countGroupUsers(int $groupId): int {
      return $this->userUserGroupRepository->countUsersByGroup($groupId);
   }

   /**
    * Cuenta grupos asignados a un usuario
    */
   public function countUserGroups(int $userId): int {
      return $this->userUserGroupRepository->countGroupsByUser($userId);
   }

   /**
    * Obtiene estadísticas de roles
    */
   public function getRoleStatistics(): array {
      $allGroups = $this->userGroupRepository->findAll();
      $activeGroups = $this->userGroupRepository->findActiveGroups();
      $totalAssignments = count($this->userUserGroupRepository->findActiveRelations());

      return [
         'total_roles' => count($allGroups),
         'active_roles' => count($activeGroups),
         'total_assignments' => $totalAssignments,
         'average_assignments_per_role' => count($activeGroups) > 0 ? round($totalAssignments / count($activeGroups), 2) : 0
      ];
   }

   /**
    * Obtiene roles comunes entre usuarios
    */
   public function getCommonRoles(array $userIds): array {
      if (empty($userIds)) {
         return [];
      }

      return $this->userUserGroupRepository->findCommonGroupsBetweenUsers($userIds);
   }

   /**
    * Copia roles de un usuario a otro
    */
   public function copyUserRoles(int $sourceUserId, int $targetUserId): int {
      $sourceRoles = $this->getUserRoles($sourceUserId);
      $copiedCount = 0;

      foreach ($sourceRoles as $role) {
         try {
            if (isset($role['id']) && isset($role['organizational_unit_id'])) {
               $this->assignUserToGroup($targetUserId, $role['id'], $role['organizational_unit_id']);
               $copiedCount++;
            }
         } catch (\Exception $e) {
            // Log el error pero continúa copiando otros roles
            $this->logger?->warning('Error copiando rol', [
               'source_user_id' => $sourceUserId,
               'target_user_id' => $targetUserId,
               'role_id' => $role['id'] ?? null,
               'error' => $e->getMessage()
            ]);
         }
      }

      $this->logger?->info('Roles copiados entre usuarios', [
         'source_user_id' => $sourceUserId,
         'target_user_id' => $targetUserId,
         'copied_count' => $copiedCount
      ]);

      return $copiedCount;
   }

   /**
    * Busca roles por criterios
    */
   public function searchRoles(string $searchTerm): array {
      try {
         $allGroups = $this->userGroupRepository->findActiveGroups();

         $results = [];
         foreach ($allGroups as $group) {
            if (isset($group['name']) && stripos($group['name'], $searchTerm) !== false ||
               isset($group['description']) && stripos($group['description'], $searchTerm) !== false) {
               $results[] = $group;
            }
         }

         return $results;
      } catch (\Exception $e) {
         $this->logger?->error('Error buscando roles', [
            'search_term' => $searchTerm,
            'error' => $e->getMessage()
         ]);
         return [];
      }
   }
}
