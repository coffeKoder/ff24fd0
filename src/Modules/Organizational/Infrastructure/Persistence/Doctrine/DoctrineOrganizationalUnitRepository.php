<?php
/**
 * @package     Modules/Organizational
 * @subpackage  Infrastructure
 * @file        DoctrineOrganizationalUnitRepository
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:45:15
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine;

use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineOrganizationalUnitRepository implements OrganizationalUnitRepositoryInterface {
   private EntityManagerInterface $entityManager;
   private EntityRepository $repository;

   public function __construct(EntityManagerInterface $entityManager) {
      $this->entityManager = $entityManager;
      $this->repository = $this->entityManager->getRepository(OrganizationalUnit::class);
   }

   /**
    * {@inheritdoc}
    */
   public function findByName(string $name): ?OrganizationalUnit {
      return $this->findOneBy(['name' => $name, 'softDeleted' => false]);
   }

   /**
    * {@inheritdoc}
    */
   public function findByType(string $type): array {
      return $this->findBy([
         'type' => $type,
         'isActive' => true,
         'softDeleted' => false
      ], ['name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findActiveUnits(): array {
      return $this->findBy([
         'isActive' => true,
         'softDeleted' => false
      ], ['type' => 'ASC', 'name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findRootUnits(): array {
      return $this->findBy([
         'parent' => null,
         'isActive' => true,
         'softDeleted' => false
      ], ['name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findByParentId(int $parentId): array {
      return $this->findBy([
         'parent' => $parentId,
         'isActive' => true,
         'softDeleted' => false
      ], ['name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findByDepthLevel(int $level): array {
      $qb = $this->entityManager->createQueryBuilder();

      // Construir consulta recursiva basada en el nivel
      if ($level === 0) {
         // Nivel raíz
         return $this->findRootUnits();
      }

      // Para niveles más profundos, necesitamos una consulta más compleja
      $sql = "
            WITH RECURSIVE unit_hierarchy AS (
                -- Caso base: unidades raíz (nivel 0)
                SELECT id, name, type, parent_id, 0 as level
                FROM organizational_units
                WHERE parent_id IS NULL 
                    AND is_active = true 
                    AND soft_deleted = false
                
                UNION ALL
                
                -- Caso recursivo: unidades hijas
                SELECT ou.id, ou.name, ou.type, ou.parent_id, uh.level + 1
                FROM organizational_units ou
                INNER JOIN unit_hierarchy uh ON ou.parent_id = uh.id
                WHERE ou.is_active = true 
                    AND ou.soft_deleted = false
            )
            SELECT id FROM unit_hierarchy WHERE level = :level
            ORDER BY name ASC
        ";

      $conn = $this->entityManager->getConnection();
      $result = $conn->executeQuery($sql, ['level' => $level]);
      $ids = array_column($result->fetchAllAssociative(), 'id');

      if (empty($ids)) {
         return [];
      }

      return $this->findBy(['id' => $ids], ['name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findHierarchyTree(): array {
      // Obtener todas las unidades activas
      $allUnits = $this->findActiveUnits();

      // Construir el árbol jerárquico
      $tree = [];
      $unitMap = [];

      // Primero, crear un mapa de todas las unidades
      foreach ($allUnits as $unit) {
         $unitMap[$unit->getId()] = [
            'unit' => $unit,
            'children' => []
         ];
      }

      // Construir la jerarquía
      foreach ($allUnits as $unit) {
         $parent = $unit->getParent();
         if ($parent === null) {
            // Es una unidad raíz
            $tree[] = &$unitMap[$unit->getId()];
         } else {
            // Es una unidad hija, agregarla a su padre
            if (isset($unitMap[$parent->getId()])) {
               $unitMap[$parent->getId()]['children'][] = &$unitMap[$unit->getId()];
            }
         }
      }

      return $tree;
   }

   /**
    * {@inheritdoc}
    */
   public function search(string $searchTerm): array {
      $qb = $this->entityManager->createQueryBuilder();

      return $qb->select('ou')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.name LIKE :searchTerm')
         ->andWhere('ou.isActive = true')
         ->andWhere('ou.softDeleted = false')
         ->setParameter('searchTerm', '%' . $searchTerm . '%')
         ->orderBy('ou.type', 'ASC')
         ->addOrderBy('ou.name', 'ASC')
         ->getQuery()
         ->getResult();
   }

   /**
    * {@inheritdoc}
    */
   public function findAncestors(int $unitId): array {
      $unit = $this->findById($unitId);
      if (!$unit) {
         return [];
      }

      $ancestors = [];
      $current = $unit->getParent();

      while ($current !== null) {
         if ($current->isActive() && !$current->isSoftDeleted()) {
            array_unshift($ancestors, $current);
         }
         $current = $current->getParent();
      }

      return $ancestors;
   }

   /**
    * {@inheritdoc}
    */
   public function findDescendants(int $unitId): array {
      $sql = "
            WITH RECURSIVE unit_descendants AS (
                -- Caso base: la unidad especificada
                SELECT id, name, type, parent_id
                FROM organizational_units
                WHERE id = :unitId
                    AND is_active = true 
                    AND soft_deleted = false
                
                UNION ALL
                
                -- Caso recursivo: todas las unidades hijas
                SELECT ou.id, ou.name, ou.type, ou.parent_id
                FROM organizational_units ou
                INNER JOIN unit_descendants ud ON ou.parent_id = ud.id
                WHERE ou.is_active = true 
                    AND ou.soft_deleted = false
            )
            SELECT id FROM unit_descendants WHERE id != :unitId
            ORDER BY name ASC
        ";

      $conn = $this->entityManager->getConnection();
      $result = $conn->executeQuery($sql, ['unitId' => $unitId]);
      $ids = array_column($result->fetchAllAssociative(), 'id');

      if (empty($ids)) {
         return [];
      }

      return $this->findBy(['id' => $ids], ['type' => 'ASC', 'name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function existsByName(string $name, ?int $excludeId = null): bool {
      $qb = $this->entityManager->createQueryBuilder();

      $qb->select('COUNT(ou.id)')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.name = :name')
         ->andWhere('ou.softDeleted = false')
         ->setParameter('name', $name);

      if ($excludeId !== null) {
         $qb->andWhere('ou.id != :excludeId')
            ->setParameter('excludeId', $excludeId);
      }

      return (int) $qb->getQuery()->getSingleScalarResult() > 0;
   }

   /**
    * {@inheritdoc}
    */
   public function existsByNameAndType(string $name, string $type, ?int $excludeId = null): bool {
      $qb = $this->entityManager->createQueryBuilder();

      $qb->select('COUNT(ou.id)')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.name = :name')
         ->andWhere('ou.type = :type')
         ->andWhere('ou.softDeleted = false')
         ->setParameter('name', $name)
         ->setParameter('type', $type);

      if ($excludeId !== null) {
         $qb->andWhere('ou.id != :excludeId')
            ->setParameter('excludeId', $excludeId);
      }

      return (int) $qb->getQuery()->getSingleScalarResult() > 0;
   }

   /**
    * {@inheritdoc}
    */
   public function isAncestorOf(int $ancestorId, int $descendantId): bool {
      $sql = "
            WITH RECURSIVE unit_path AS (
                -- Caso base: el descendiente
                SELECT id, parent_id
                FROM organizational_units
                WHERE id = :descendantId
                    AND is_active = true 
                    AND soft_deleted = false
                
                UNION ALL
                
                -- Caso recursivo: seguir hacia arriba por los padres
                SELECT ou.id, ou.parent_id
                FROM organizational_units ou
                INNER JOIN unit_path up ON ou.id = up.parent_id
                WHERE ou.is_active = true 
                    AND ou.soft_deleted = false
            )
            SELECT COUNT(*) FROM unit_path WHERE id = :ancestorId
        ";

      $conn = $this->entityManager->getConnection();
      $result = $conn->executeQuery($sql, [
         'ancestorId' => $ancestorId,
         'descendantId' => $descendantId
      ]);

      return (int) $result->fetchOne() > 0;
   }

   /**
    * {@inheritdoc}
    */
   public function getStatisticsByType(): array {
      $qb = $this->entityManager->createQueryBuilder();

      $result = $qb->select('ou.type, COUNT(ou.id) as count')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.isActive = true')
         ->andWhere('ou.softDeleted = false')
         ->groupBy('ou.type')
         ->orderBy('count', 'DESC')
         ->getQuery()
         ->getResult();

      $statistics = [];
      foreach ($result as $row) {
         $statistics[$row['type']] = (int) $row['count'];
      }

      return $statistics;
   }

   /**
    * {@inheritdoc}
    */
   public function getUniqueTypes(): array {
      $qb = $this->entityManager->createQueryBuilder();

      $result = $qb->select('DISTINCT ou.type')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.isActive = true')
         ->andWhere('ou.softDeleted = false')
         ->orderBy('ou.type', 'ASC')
         ->getQuery()
         ->getResult();

      return array_column($result, 'type');
   }

   /**
    * {@inheritdoc}
    */
   public function countAssignedUsers(int $unitId): int {
      $qb = $this->entityManager->createQueryBuilder();

      return (int) $qb->select('COUNT(u.id)')
         ->from('App\User\Domain\Entities\User', 'u')
         ->where('u.mainOrganizationalUnit = :unitId')
         ->andWhere('u.isActive = true')
         ->andWhere('u.softDeleted = false')
         ->setParameter('unitId', $unitId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   /**
    * {@inheritdoc}
    */
   public function getStatistics(): array {
      $qb = $this->entityManager->createQueryBuilder();

      // Total units
      $total = $qb->select('COUNT(ou.id)')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.softDeleted = false')
         ->getQuery()
         ->getSingleScalarResult();

      // Active units
      $active = $qb->select('COUNT(ou.id)')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.isActive = true')
         ->andWhere('ou.softDeleted = false')
         ->getQuery()
         ->getSingleScalarResult();

      // By type
      $byType = $this->getStatisticsByType();

      // By level (aproximado usando depth)
      $byLevel = [];
      for ($level = 0; $level <= 3; $level++) {
         $units = $this->findByDepthLevel($level);
         $byLevel["level_$level"] = count($units);
      }

      return [
         'total' => (int) $total,
         'active' => (int) $active,
         'by_type' => $byType,
         'by_level' => $byLevel
      ];
   }

   /**
    * {@inheritdoc}
    */
   public function findByParent(int $parentId): array {
      return $this->findBy([
         'parent' => $parentId,
         'isActive' => true,
         'softDeleted' => false
      ], ['name' => 'ASC']);
   }

   /**
    * {@inheritdoc}
    */
   public function findPaginated(int $page = 1, int $limit = 20, ?string $search = null, ?string $type = null): array {
      $qb = $this->entityManager->createQueryBuilder();
      $qb->select('ou')
         ->from(OrganizationalUnit::class, 'ou')
         ->where('ou.softDeleted = false');

      if ($search !== null && $search !== '') {
         $qb->andWhere('ou.name LIKE :search')
            ->setParameter('search', '%' . $search . '%');
      }
      if ($type !== null && $type !== '') {
         $qb->andWhere('ou.type = :type')
            ->setParameter('type', $type);
      }

      $qb->orderBy('ou.type', 'ASC')
         ->addOrderBy('ou.name', 'ASC')
         ->setFirstResult(($page - 1) * $limit)
         ->setMaxResults($limit);

      return $qb->getQuery()->getResult();
   }

   public function findById(int $id): ?OrganizationalUnit {
      return $this->repository->find($id);
   }

   public function findAll(): array {
      return $this->repository->findAll();
   }

   public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
      return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
   }

   public function findOneBy(array $criteria): ?OrganizationalUnit {
      return $this->repository->findOneBy($criteria);
   }

   public function save(OrganizationalUnit $unit): void {
      $this->entityManager->persist($unit);
      $this->entityManager->flush();
   }

   public function delete(OrganizationalUnit $unit): void {
      $this->entityManager->remove($unit);
      $this->entityManager->flush();
   }

}
