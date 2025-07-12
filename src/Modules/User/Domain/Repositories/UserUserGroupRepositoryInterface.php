<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\UserUserGroup;

/**
 * Interface del repositorio para la entidad UserUserGroup
 * Define métodos específicos para consultas y operaciones de relaciones usuario-grupo
 */
interface UserUserGroupRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar relaciones por usuario
    */
   public function findByUserId(int $userId): array;

   /**
    * Buscar relaciones por grupo
    */
   public function findByGroupId(int $groupId): array;

   /**
    * Buscar una relación específica usuario-grupo
    */
   public function findByUserAndGroup(int $userId, int $groupId): ?UserUserGroup;

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
    * Verificar si existe una relación usuario-grupo
    */
   public function existsUserGroupRelation(int $userId, int $groupId): bool;

   /**
    * Contar grupos de un usuario
    */
   public function countGroupsByUser(int $userId): int;

   /**
    * Contar usuarios en un grupo
    */
   public function countUsersByGroup(int $groupId): int;

   /**
    * Buscar relaciones con información completa (usuario y grupo)
    */
   public function findWithUserAndGroupInfo(): array;

   /**
    * Buscar usuarios que pertenecen a múltiples grupos específicos
    */
   public function findUsersInMultipleGroups(array $groupIds): array;

   /**
    * Buscar grupos comunes entre usuarios
    */
   public function findCommonGroupsBetweenUsers(array $userIds): array;

   /**
    * Buscar relaciones por múltiples criterios
    */
   public function findByAdvancedCriteria(array $filters): array;

   /**
    * Buscar relaciones con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;
}
