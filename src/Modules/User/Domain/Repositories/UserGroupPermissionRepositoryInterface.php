<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\UserGroupPermission;

/**
 * Interface del repositorio para la entidad UserGroupPermission
 * Define métodos específicos para consultas y operaciones de relaciones grupo-permiso
 */
interface UserGroupPermissionRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar relaciones por grupo
    */
   public function findByGroupId(int $groupId): array;

   /**
    * Buscar relaciones por permiso
    */
   public function findByPermissionId(int $permissionId): array;

   /**
    * Buscar una relación específica grupo-permiso
    */
   public function findByGroupAndPermission(int $groupId, int $permissionId): ?UserGroupPermission;

   /**
    * Buscar relaciones activas
    */
   public function findActiveRelations(): array;

   /**
    * Buscar relaciones inactivas
    */
   public function findInactiveRelations(): array;

   /**
    * Buscar relaciones por estado
    */
   public function findByStatus(string $status): array;

   /**
    * Buscar relaciones asignadas por un usuario específico
    */
   public function findByAssignedBy(int $assignedByUserId): array;

   /**
    * Buscar relaciones con fecha de asignación en rango
    */
   public function findByAssignedDateRange(\DateTime $startDate, \DateTime $endDate): array;

   /**
    * Verificar si existe una relación grupo-permiso
    */
   public function existsGroupPermissionRelation(int $groupId, int $permissionId): bool;

   /**
    * Contar permisos de un grupo
    */
   public function countPermissionsByGroup(int $groupId): int;

   /**
    * Contar grupos que tienen un permiso específico
    */
   public function countGroupsByPermission(int $permissionId): int;

   /**
    * Buscar relaciones con información completa (grupo y permiso)
    */
   public function findWithGroupAndPermissionInfo(): array;

   /**
    * Buscar grupos que tienen múltiples permisos específicos
    */
   public function findGroupsWithMultiplePermissions(array $permissionIds): array;

   /**
    * Buscar permisos comunes entre grupos
    */
   public function findCommonPermissionsBetweenGroups(array $groupIds): array;

   /**
    * Buscar permisos de un usuario a través de sus grupos
    */
   public function findUserPermissionsThroughGroups(int $userId): array;

   /**
    * Buscar relaciones por módulo del permiso
    */
   public function findByPermissionModule(string $module): array;

   /**
    * Buscar relaciones por múltiples criterios
    */
   public function findByAdvancedCriteria(array $filters): array;

   /**
    * Buscar relaciones con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;
}
