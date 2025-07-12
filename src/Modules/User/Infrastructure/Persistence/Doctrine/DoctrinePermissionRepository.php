<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\Permission;
use Viex\Modules\User\Domain\Repositories\PermissionRepositoryInterface;

/**
 * Implementación Doctrine del repositorio de permisos
 */
class DoctrinePermissionRepository extends BaseRepository implements PermissionRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, Permission::class);
   }

   public function findByName(string $name): ?Permission {
      return $this->findOneBy(['name' => $name]);
   }

   public function findByModule(string $module): array {
      return $this->createQueryBuilder('p')
         ->where('p.module = :module')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('module', $module)
         ->orderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByAction(string $action): array {
      return $this->createQueryBuilder('p')
         ->where('p.name LIKE :action')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('action', '%.' . $action . '.%')
         ->orderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByResource(string $resource): array {
      return $this->createQueryBuilder('p')
         ->where('p.name LIKE :resource')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('resource', '%' . $resource . '%')
         ->orderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByStatus(string $status): array {
      $isActive = $status === 'active' ? 1 : 0;

      return $this->createQueryBuilder('p')
         ->where('p.isActive = :isActive')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('isActive', $isActive)
         ->orderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findActivePermissions(): array {
      return $this->createQueryBuilder('p')
         ->where('p.isActive = 1')
         ->andWhere('p.softDeleted IS NULL')
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findInactivePermissions(): array {
      return $this->createQueryBuilder('p')
         ->where('p.isActive = 0')
         ->andWhere('p.softDeleted IS NULL')
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByGroupId(int $groupId): array {
      return $this->createQueryBuilder('p')
         ->join('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.permissionId = p.id')
         ->where('ugp.userGroupId = :groupId')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('groupId', $groupId)
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByUserId(int $userId): array {
      return $this->createQueryBuilder('p')
         ->join('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.permissionId = p.id')
         ->join('Viex\Modules\User\Domain\Entities\UserUserGroup', 'uug', 'WITH', 'uug.userGroupId = ugp.userGroupId')
         ->where('uug.userId = :userId')
         ->andWhere('uug.isActive = 1')
         ->andWhere('ugp.isActive = 1')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function existsByName(string $name): bool {
      return $this->exists(['name' => $name]);
   }

   public function searchPermissions(string $searchTerm): array {
      $searchPattern = '%' . $searchTerm . '%';

      return $this->createQueryBuilder('p')
         ->where('p.softDeleted IS NULL')
         ->andWhere('(p.name LIKE :search OR p.description LIKE :search OR p.module LIKE :search)')
         ->setParameter('search', $searchPattern)
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('p')
         ->where('p.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'p');
      $qb->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('p')
         ->where('p.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['name'])) {
         $qb->andWhere('p.name LIKE :name')
            ->setParameter('name', '%' . $filters['name'] . '%');
      }

      if (isset($filters['description'])) {
         $qb->andWhere('p.description LIKE :description')
            ->setParameter('description', '%' . $filters['description'] . '%');
      }

      if (isset($filters['module'])) {
         $qb->andWhere('p.module = :module')
            ->setParameter('module', $filters['module']);
      }

      if (isset($filters['isActive'])) {
         $qb->andWhere('p.isActive = :isActive')
            ->setParameter('isActive', $filters['isActive'] ? 1 : 0);
      }

      if (isset($filters['isSystemPermission'])) {
         if ($filters['isSystemPermission']) {
            $qb->andWhere('(p.name LIKE :admin OR p.name LIKE :system)')
               ->setParameter('admin', 'admin.%')
               ->setParameter('system', 'system.%');
         } else {
            $qb->andWhere('p.name NOT LIKE :admin')
               ->andWhere('p.name NOT LIKE :system')
               ->setParameter('admin', 'admin.%')
               ->setParameter('system', 'system.%');
         }
      }

      if (isset($filters['hasGroups'])) {
         if ($filters['hasGroups']) {
            $qb->join('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.permissionId = p.id')
               ->andWhere('ugp.isActive = 1');
         }
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'module';
      $orderDirection = $filters['orderDirection'] ?? 'ASC';
      $qb->orderBy("p.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }

   public function findGroupedByModule(): array {
      $permissions = $this->findActivePermissions();
      $grouped = [];

      foreach ($permissions as $permission) {
         $module = $permission->getModule();
         if (!isset($grouped[$module])) {
            $grouped[$module] = [];
         }
         $grouped[$module][] = $permission;
      }

      return $grouped;
   }

   public function countByModule(string $module): int {
      return (int) $this->createQueryBuilder('p')
         ->select('COUNT(p.id)')
         ->where('p.module = :module')
         ->andWhere('p.softDeleted IS NULL')
         ->setParameter('module', $module)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function findUnassignedPermissions(): array {
      return $this->createQueryBuilder('p')
         ->leftJoin('Viex\Modules\User\Domain\Entities\UserGroupPermission', 'ugp', 'WITH', 'ugp.permissionId = p.id AND ugp.isActive = 1')
         ->where('ugp.id IS NULL')
         ->andWhere('p.softDeleted IS NULL')
         ->orderBy('p.module', 'ASC')
         ->addOrderBy('p.name', 'ASC')
         ->getQuery()
         ->getResult();
   }
}
