<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\UserGroupPermission;
use Viex\Modules\User\Domain\Repositories\UserGroupPermissionRepositoryInterface;

/**
 * Implementación Doctrine del repositorio de relaciones grupo-permiso
 */
class DoctrineUserGroupPermissionRepository extends BaseRepository implements UserGroupPermissionRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, UserGroupPermission::class);
   }

   public function findByGroupId(int $groupId): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByPermissionId(int $permissionId): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.permissionId = :permissionId')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('permissionId', $permissionId)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByGroupAndPermission(int $groupId, int $permissionId): ?UserGroupPermission {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.permissionId = :permissionId')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->setParameter('permissionId', $permissionId)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->setMaxResults(1)
         ->getQuery()
         ->getOneOrNullResult();
   }

   public function findActiveRelations(): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findInactiveRelations(): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.isActive = 0 OR ugp.revokedAt IS NOT NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->orderBy('ugp.revokedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByStatus(string $status): array {
      $qb = $this->createQueryBuilder('ugp')
         ->where('ugp.softDeleted IS NULL');

      if ($status === 'active') {
         $qb->andWhere('ugp.isActive = 1')
            ->andWhere('ugp.revokedAt IS NULL');
      } else {
         $qb->andWhere('ugp.isActive = 0 OR ugp.revokedAt IS NOT NULL');
      }

      return $qb->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAssignedBy(int $assignedByUserId): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.grantedBy = :grantedBy')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('grantedBy', $assignedByUserId)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAssignedDateRange(\DateTime $startDate, \DateTime $endDate): array {
      return $this->createQueryBuilder('ugp')
         ->where('ugp.grantedAt BETWEEN :startDate AND :endDate')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('startDate', $startDate)
         ->setParameter('endDate', $endDate)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function existsGroupPermissionRelation(int $groupId, int $permissionId): bool {
      return $this->createQueryBuilder('ugp')
         ->select('COUNT(ugp.id)')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.permissionId = :permissionId')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->setParameter('permissionId', $permissionId)
         ->getQuery()
         ->getSingleScalarResult() > 0;
   }

   public function countPermissionsByGroup(int $groupId): int {
      return (int) $this->createQueryBuilder('ugp')
         ->select('COUNT(DISTINCT ugp.permissionId)')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function countGroupsByPermission(int $permissionId): int {
      return (int) $this->createQueryBuilder('ugp')
         ->select('COUNT(DISTINCT ugp.userGroupId)')
         ->where('ugp.permissionId = :permissionId')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('permissionId', $permissionId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function findWithGroupAndPermissionInfo(): array {
      return $this->createQueryBuilder('ugp')
         ->leftJoin('ugp.userGroup', 'ug')
         ->leftJoin('ugp.permission', 'p')
         ->addSelect('ug', 'p')
         ->where('ugp.softDeleted IS NULL')
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findGroupsWithMultiplePermissions(array $permissionIds): array {
      return $this->createQueryBuilder('ugp')
         ->select('ugp.userGroupId, COUNT(DISTINCT ugp.permissionId) as permissionCount')
         ->where('ugp.permissionId IN (:permissionIds)')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('permissionIds', $permissionIds)
         ->groupBy('ugp.userGroupId')
         ->having('COUNT(DISTINCT ugp.permissionId) > 1')
         ->getQuery()
         ->getResult();
   }

   public function findCommonPermissionsBetweenGroups(array $groupIds): array {
      return $this->createQueryBuilder('ugp')
         ->select('ugp.permissionId, COUNT(DISTINCT ugp.userGroupId) as groupCount')
         ->where('ugp.userGroupId IN (:groupIds)')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('groupIds', $groupIds)
         ->groupBy('ugp.permissionId')
         ->having('COUNT(DISTINCT ugp.userGroupId) = :expectedCount')
         ->setParameter('expectedCount', count($groupIds))
         ->getQuery()
         ->getResult();
   }

   public function findUserPermissionsThroughGroups(int $userId): array {
      return $this->createQueryBuilder('ugp')
         ->join('Viex\Modules\User\Domain\Entities\UserUserGroup', 'uug', 'WITH', 'uug.userGroupId = ugp.userGroupId')
         ->where('uug.userId = :userId')
         ->andWhere('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ugp.revokedAt IS NULL')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByPermissionModule(string $module): array {
      return $this->createQueryBuilder('ugp')
         ->join('Viex\Modules\User\Domain\Entities\Permission', 'p', 'WITH', 'p.id = ugp.permissionId')
         ->where('p.module = :module')
         ->andWhere('ugp.softDeleted IS NULL')
         ->setParameter('module', $module)
         ->orderBy('ugp.grantedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('ugp')
         ->where('ugp.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['userGroupId'])) {
         $qb->andWhere('ugp.userGroupId = :userGroupId')
            ->setParameter('userGroupId', $filters['userGroupId']);
      }

      if (isset($filters['permissionId'])) {
         $qb->andWhere('ugp.permissionId = :permissionId')
            ->setParameter('permissionId', $filters['permissionId']);
      }

      if (isset($filters['isActive'])) {
         if ($filters['isActive']) {
            $qb->andWhere('ugp.isActive = 1')
               ->andWhere('ugp.revokedAt IS NULL');
         } else {
            $qb->andWhere('ugp.isActive = 0 OR ugp.revokedAt IS NOT NULL');
         }
      }

      if (isset($filters['grantedBy'])) {
         $qb->andWhere('ugp.grantedBy = :grantedBy')
            ->setParameter('grantedBy', $filters['grantedBy']);
      }

      if (isset($filters['grantedAfter'])) {
         $qb->andWhere('ugp.grantedAt >= :grantedAfter')
            ->setParameter('grantedAfter', $filters['grantedAfter']);
      }

      if (isset($filters['grantedBefore'])) {
         $qb->andWhere('ugp.grantedAt <= :grantedBefore')
            ->setParameter('grantedBefore', $filters['grantedBefore']);
      }

      if (isset($filters['permissionModule'])) {
         $qb->join('Viex\Modules\User\Domain\Entities\Permission', 'p', 'WITH', 'p.id = ugp.permissionId')
            ->andWhere('p.module = :module')
            ->setParameter('module', $filters['permissionModule']);
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'grantedAt';
      $orderDirection = $filters['orderDirection'] ?? 'DESC';
      $qb->orderBy("ugp.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('ugp')
         ->where('ugp.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'ugp');
      $qb->orderBy('ugp.grantedAt', 'DESC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }
}
