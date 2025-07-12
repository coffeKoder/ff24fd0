<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\UserGroup;
use Viex\Modules\User\Domain\Repositories\UserGroupRepositoryInterface;

/**
 * Implementación Doctrine del repositorio de grupos de usuarios
 */
class DoctrineUserGroupRepository extends BaseRepository implements UserGroupRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, UserGroup::class);
   }

   public function findByName(string $name): ?UserGroup {
      return $this->findOneBy(['name' => $name]);
   }

   public function findByStatus(string $status): array {
      $isActive = $status === 'active' ? 1 : 0;

      return $this->createQueryBuilder('ug')
         ->where('ug.isActive = :isActive')
         ->andWhere('ug.softDeleted IS NULL')
         ->setParameter('isActive', $isActive)
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findActiveGroups(): array {
      return $this->createQueryBuilder('ug')
         ->where('ug.isActive = 1')
         ->andWhere('ug.softDeleted IS NULL')
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findInactiveGroups(): array {
      return $this->createQueryBuilder('ug')
         ->where('ug.isActive = 0')
         ->andWhere('ug.softDeleted IS NULL')
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findGroupsWithUsers(): array {
      return $this->createQueryBuilder('ug')
         ->leftJoin('ug.users', 'u')
         ->addSelect('u')
         ->where('ug.softDeleted IS NULL')
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findGroupsWithPermissions(): array {
      return $this->createQueryBuilder('ug')
         ->leftJoin('ug.permissions', 'p')
         ->addSelect('p')
         ->where('ug.softDeleted IS NULL')
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findGroupsByUserId(int $userId): array {
      return $this->createQueryBuilder('ug')
         ->join('Viex\Modules\User\Domain\Entities\UserUserGroup', 'uug', 'WITH', 'uug.userGroupId = ug.id')
         ->where('uug.userId = :userId')
         ->andWhere('uug.isActive = 1')
         ->andWhere('ug.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findGroupsByPermissionId(int $permissionId): array {
      return $this->createQueryBuilder('ug')
         ->join('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.userGroupId = ug.id')
         ->where('ugp.permissionId = :permissionId')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('ug.softDeleted IS NULL')
         ->setParameter('permissionId', $permissionId)
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function existsByName(string $name): bool {
      return $this->exists(['name' => $name]);
   }

   public function searchGroups(string $searchTerm): array {
      $searchPattern = '%' . $searchTerm . '%';

      return $this->createQueryBuilder('ug')
         ->where('ug.softDeleted IS NULL')
         ->andWhere('(ug.name LIKE :search OR ug.description LIKE :search)')
         ->setParameter('search', $searchPattern)
         ->orderBy('ug.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function countUsersInGroup(int $groupId): int {
      return (int) $this->entityManager->createQueryBuilder()
         ->select('COUNT(uug.id)')
         ->from('Viex\Modules\User\Domain\Entities\UserUserGroup', 'uug')
         ->where('uug.userGroupId = :groupId')
         ->andWhere('uug.isActive = 1')
         ->setParameter('groupId', $groupId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function countPermissionsInGroup(int $groupId): int {
      return (int) $this->entityManager->createQueryBuilder()
         ->select('COUNT(ugp.id)')
         ->from('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.isActive = 1')
         ->setParameter('groupId', $groupId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('ug')
         ->where('ug.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'ug');
      $qb->orderBy('ug.name', 'ASC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('ug')
         ->where('ug.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['name'])) {
         $qb->andWhere('ug.name LIKE :name')
            ->setParameter('name', '%' . $filters['name'] . '%');
      }

      if (isset($filters['description'])) {
         $qb->andWhere('ug.description LIKE :description')
            ->setParameter('description', '%' . $filters['description'] . '%');
      }

      if (isset($filters['isActive'])) {
         $qb->andWhere('ug.isActive = :isActive')
            ->setParameter('isActive', $filters['isActive'] ? 1 : 0);
      }

      if (isset($filters['hasUsers'])) {
         if ($filters['hasUsers']) {
            $qb->join('Viex\Modules\User\Domain\Entities\UserUserGroup', 'uug', 'WITH', 'uug.userGroupId = ug.id')
               ->andWhere('uug.isActive = 1');
         }
      }

      if (isset($filters['hasPermissions'])) {
         if ($filters['hasPermissions']) {
            $qb->join('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.userGroupId = ug.id')
               ->andWhere('ugp.isActive = 1');
         }
      }

      if (isset($filters['isSystemRole'])) {
         $systemRoles = ['Administrador', 'Staff VIEX', 'Coordinador Extensión', 'Decano', 'Director'];
         if ($filters['isSystemRole']) {
            $qb->andWhere('ug.name IN (:systemRoles)')
               ->setParameter('systemRoles', $systemRoles);
         } else {
            $qb->andWhere('ug.name NOT IN (:systemRoles)')
               ->setParameter('systemRoles', $systemRoles);
         }
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'name';
      $orderDirection = $filters['orderDirection'] ?? 'ASC';
      $qb->orderBy("ug.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }
}
