<?php

declare(strict_types=1);

namespace Viex\Modules\Shared\Infrastructure\Persistence;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;

/**
 * Repositorio base abstracto que implementa BaseRepositoryInterface
 * Proporciona funcionalidades comunes para todos los repositorios
 */
abstract class BaseRepository implements BaseRepositoryInterface {
   protected EntityManagerInterface $entityManager;
   protected EntityRepository $repository;
   protected string $entityClass;

   public function __construct(EntityManagerInterface $entityManager, string $entityClass) {
      $this->entityManager = $entityManager;
      $this->entityClass = $entityClass;
      $this->repository = $this->entityManager->getRepository($entityClass);
   }

   public function findById($id): ?object {
      return $this->repository->find($id);
   }

   public function findAll(): array {
      return $this->repository->findAll();
   }

   public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
      return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
   }

   public function findOneBy(array $criteria): ?object {
      return $this->repository->findOneBy($criteria);
   }

   public function save(object $entity): object {
      $this->entityManager->persist($entity);
      $this->entityManager->flush();
      return $entity;
   }

   public function delete(object $entity): void {
      $this->entityManager->remove($entity);
      $this->entityManager->flush();
   }

   public function count(array $criteria = []): int {
      $qb = $this->createQueryBuilder('e');

      foreach ($criteria as $field => $value) {
         if (is_array($value)) {
            $qb->andWhere("e.{$field} IN (:{$field})")
               ->setParameter($field, $value);
         } else {
            $qb->andWhere("e.{$field} = :{$field}")
               ->setParameter($field, $value);
         }
      }

      return (int) $qb->select('COUNT(e.id)')
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function exists(array $criteria): bool {
      return $this->findOneBy($criteria) !== null;
   }

   public function flush(): void {
      $this->entityManager->flush();
   }

   public function refresh(object $entity): void {
      $this->entityManager->refresh($entity);
   }

   public function detach(object $entity): void {
      $this->entityManager->detach($entity);
   }

   public function merge(object $entity): object {
      // En Doctrine 3.x no hay merge(), usamos persist()
      $this->entityManager->persist($entity);
      return $entity;
   }

   /**
    * Crear QueryBuilder para consultas personalizadas
    */
   protected function createQueryBuilder(string $alias): QueryBuilder {
      return $this->repository->createQueryBuilder($alias);
   }

   /**
    * Ejecutar consulta con paginación
    */
   protected function executePaginatedQuery(QueryBuilder $qb, int $page = 1, int $limit = 10): array {
      $offset = ($page - 1) * $limit;

      return $qb->setFirstResult($offset)
         ->setMaxResults($limit)
         ->getQuery()
         ->getResult();
   }

   /**
    * Aplicar filtros a un QueryBuilder
    */
   protected function applyFilters(QueryBuilder $qb, array $filters, string $alias = 'e'): QueryBuilder {
      foreach ($filters as $field => $value) {
         if ($value === null) {
            continue;
         }

         $paramName = str_replace('.', '_', $field);

         if (is_array($value)) {
            $qb->andWhere("{$alias}.{$field} IN (:{$paramName})")
               ->setParameter($paramName, $value);
         } elseif (is_string($value) && str_contains($value, '%')) {
            $qb->andWhere("{$alias}.{$field} LIKE :{$paramName}")
               ->setParameter($paramName, $value);
         } else {
            $qb->andWhere("{$alias}.{$field} = :{$paramName}")
               ->setParameter($paramName, $value);
         }
      }

      return $qb;
   }

   /**
    * Aplicar ordenamiento a un QueryBuilder
    */
   protected function applyOrdering(QueryBuilder $qb, ?array $orderBy, string $alias = 'e'): QueryBuilder {
      if ($orderBy) {
         foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("{$alias}.{$field}", $direction);
         }
      }

      return $qb;
   }

   /**
    * Buscar entidades activas (no soft deleted)
    */
   public function findActive(): array {
      return $this->createQueryBuilder('e')
         ->where('e.softDeleted IS NULL')
         ->getQuery()
         ->getResult();
   }

   /**
    * Contar entidades activas
    */
   public function countActive(): int {
      return (int) $this->createQueryBuilder('e')
         ->select('COUNT(e.id)')
         ->where('e.softDeleted IS NULL')
         ->getQuery()
         ->getSingleScalarResult();
   }
}
