<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\UserUserGroup;
use Viex\Modules\User\Domain\Repositories\UserUserGroupRepositoryInterface;

/**
 * Implementación Doctrine del repositorio de relaciones usuario-grupo
 */
class DoctrineUserUserGroupRepository extends BaseRepository implements UserUserGroupRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, UserUserGroup::class);
   }

   public function findByUserId(int $userId): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.userId = :userId')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByGroupId(int $groupId): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.userGroupId = :groupId')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByUserAndGroup(int $userId, int $groupId): ?UserUserGroup {
      return $this->createQueryBuilder('uug')
         ->where('uug.userId = :userId')
         ->andWhere('uug.userGroupId = :groupId')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('groupId', $groupId)
         ->orderBy('uug.assignedAt', 'DESC')
         ->setMaxResults(1)
         ->getQuery()
         ->getOneOrNullResult();
   }

   public function findActiveRelations(): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findInactiveRelations(): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.isActive = 0 OR uug.revokedAt IS NOT NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->orderBy('uug.revokedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByStatus(string $status): array {
      $qb = $this->createQueryBuilder('uug')
         ->where('uug.softDeleted IS NULL');

      if ($status === 'active') {
         $qb->andWhere('uug.isActive = 1')
            ->andWhere('uug.revokedAt IS NULL');
      } else {
         $qb->andWhere('uug.isActive = 0 OR uug.revokedAt IS NOT NULL');
      }

      return $qb->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAssignedBy(int $assignedByUserId): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.assignedBy = :assignedBy')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('assignedBy', $assignedByUserId)
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAssignedDateRange(\DateTime $startDate, \DateTime $endDate): array {
      return $this->createQueryBuilder('uug')
         ->where('uug.assignedAt BETWEEN :startDate AND :endDate')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('startDate', $startDate)
         ->setParameter('endDate', $endDate)
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function existsUserGroupRelation(int $userId, int $groupId): bool {
      return $this->createQueryBuilder('uug')
         ->select('COUNT(uug.id)')
         ->where('uug.userId = :userId')
         ->andWhere('uug.userGroupId = :groupId')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('groupId', $groupId)
         ->getQuery()
         ->getSingleScalarResult() > 0;
   }

   public function countGroupsByUser(int $userId): int {
      return (int) $this->createQueryBuilder('uug')
         ->select('COUNT(DISTINCT uug.userGroupId)')
         ->where('uug.userId = :userId')
         ->andWhere('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function countUsersByGroup(int $groupId): int {
      return (int) $this->createQueryBuilder('uug')
         ->select('COUNT(DISTINCT uug.userId)')
         ->where('uug.userGroupId = :groupId')
         ->andWhere('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function findWithUserAndGroupInfo(): array {
      return $this->createQueryBuilder('uug')
         ->leftJoin('uug.user', 'u')
         ->leftJoin('uug.userGroup', 'ug')
         ->addSelect('u', 'ug')
         ->where('uug.softDeleted IS NULL')
         ->orderBy('uug.assignedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findUsersInMultipleGroups(array $groupIds): array {
      return $this->createQueryBuilder('uug')
         ->select('uug.userId, COUNT(DISTINCT uug.userGroupId) as groupCount')
         ->where('uug.userGroupId IN (:groupIds)')
         ->andWhere('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('groupIds', $groupIds)
         ->groupBy('uug.userId')
         ->having('COUNT(DISTINCT uug.userGroupId) > 1')
         ->getQuery()
         ->getResult();
   }

   public function findCommonGroupsBetweenUsers(array $userIds): array {
      return $this->createQueryBuilder('uug')
         ->select('uug.userGroupId, COUNT(DISTINCT uug.userId) as userCount')
         ->where('uug.userId IN (:userIds)')
         ->andWhere('uug.isActive = 1')
         ->andWhere('uug.revokedAt IS NULL')
         ->andWhere('uug.softDeleted IS NULL')
         ->setParameter('userIds', $userIds)
         ->groupBy('uug.userGroupId')
         ->having('COUNT(DISTINCT uug.userId) = :expectedCount')
         ->setParameter('expectedCount', count($userIds))
         ->getQuery()
         ->getResult();
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('uug')
         ->where('uug.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['userId'])) {
         $qb->andWhere('uug.userId = :userId')
            ->setParameter('userId', $filters['userId']);
      }

      if (isset($filters['userGroupId'])) {
         $qb->andWhere('uug.userGroupId = :userGroupId')
            ->setParameter('userGroupId', $filters['userGroupId']);
      }

      if (isset($filters['organizationalUnitId'])) {
         $qb->andWhere('uug.organizationalUnitId = :organizationalUnitId')
            ->setParameter('organizationalUnitId', $filters['organizationalUnitId']);
      }

      if (isset($filters['isActive'])) {
         if ($filters['isActive']) {
            $qb->andWhere('uug.isActive = 1')
               ->andWhere('uug.revokedAt IS NULL');
         } else {
            $qb->andWhere('uug.isActive = 0 OR uug.revokedAt IS NOT NULL');
         }
      }

      if (isset($filters['assignedBy'])) {
         $qb->andWhere('uug.assignedBy = :assignedBy')
            ->setParameter('assignedBy', $filters['assignedBy']);
      }

      if (isset($filters['assignedAfter'])) {
         $qb->andWhere('uug.assignedAt >= :assignedAfter')
            ->setParameter('assignedAfter', $filters['assignedAfter']);
      }

      if (isset($filters['assignedBefore'])) {
         $qb->andWhere('uug.assignedAt <= :assignedBefore')
            ->setParameter('assignedBefore', $filters['assignedBefore']);
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'assignedAt';
      $orderDirection = $filters['orderDirection'] ?? 'DESC';
      $qb->orderBy("uug.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('uug')
         ->where('uug.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'uug');
      $qb->orderBy('uug.assignedAt', 'DESC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }
}
