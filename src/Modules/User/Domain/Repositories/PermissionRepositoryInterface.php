<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\Permission;

/**
 * Interface del repositorio para la entidad Permission
 * Define métodos específicos para consultas y operaciones con permisos
 */
interface PermissionRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar un permiso por su nombre
    */
   public function findByName(string $name): ?Permission;

   /**
    * Buscar permisos por módulo
    */
   public function findByModule(string $module): array;

   /**
    * Buscar permisos por acción
    */
   public function findByAction(string $action): array;

   /**
    * Buscar permisos por recurso
    */
   public function findByResource(string $resource): array;

   /**
    * Buscar permisos por estado
    */
   public function findByStatus(string $status): array;

   /**
    * Buscar permisos activos
    */
   public function findActivePermissions(): array;

   /**
    * Buscar permisos inactivos
    */
   public function findInactivePermissions(): array;

   /**
    * Buscar permisos de un grupo específico
    */
   public function findByGroupId(int $groupId): array;

   /**
    * Buscar permisos de un usuario específico (a través de sus grupos)
    */
   public function findByUserId(int $userId): array;

   /**
    * Verificar si existe un permiso con el nombre dado
    */
   public function existsByName(string $name): bool;

   /**
    * Buscar permisos por término de búsqueda
    */
   public function searchPermissions(string $searchTerm): array;

   /**
    * Buscar permisos con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;

   /**
    * Buscar permisos por múltiples criterios
    */
   public function findByAdvancedCriteria(array $filters): array;

   /**
    * Buscar permisos agrupados por módulo
    */
   public function findGroupedByModule(): array;

   /**
    * Contar permisos por módulo
    */
   public function countByModule(string $module): int;

   /**
    * Buscar permisos que no están asignados a ningún grupo
    */
   public function findUnassignedPermissions(): array;
}
