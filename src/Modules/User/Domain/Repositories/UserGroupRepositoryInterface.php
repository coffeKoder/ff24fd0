<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\UserGroup;

/**
 * Interface del repositorio para la entidad UserGroup
 * Define métodos específicos para consultas y operaciones con grupos de usuarios
 */
interface UserGroupRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar un grupo por su nombre
    */
   public function findByName(string $name): ?UserGroup;

   /**
    * Buscar grupos por estado
    */
   public function findByStatus(string $status): array;

   /**
    * Buscar grupos activos
    */
   public function findActiveGroups(): array;

   /**
    * Buscar grupos inactivos
    */
   public function findInactiveGroups(): array;

   /**
    * Buscar grupos con sus usuarios
    */
   public function findGroupsWithUsers(): array;

   /**
    * Buscar grupos con sus permisos
    */
   public function findGroupsWithPermissions(): array;

   /**
    * Buscar grupos que contienen un usuario específico
    */
   public function findGroupsByUserId(int $userId): array;

   /**
    * Buscar grupos que tienen un permiso específico
    */
   public function findGroupsByPermissionId(int $permissionId): array;

   /**
    * Verificar si existe un grupo con el nombre dado
    */
   public function existsByName(string $name): bool;

   /**
    * Buscar grupos por término de búsqueda
    */
   public function searchGroups(string $searchTerm): array;

   /**
    * Contar usuarios en un grupo específico
    */
   public function countUsersInGroup(int $groupId): int;

   /**
    * Contar permisos en un grupo específico
    */
   public function countPermissionsInGroup(int $groupId): int;

   /**
    * Buscar grupos con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;

   /**
    * Buscar grupos por múltiples criterios
    */
   public function findByAdvancedCriteria(array $filters): array;
}
