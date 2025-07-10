<?php
/**
 * @package     Infrastructure/Persistence
 * @subpackage  Doctrine
 * @file        DoctrineBaseRepository
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:46:28
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Shared\Infrastructure\Persistence\Doctrine;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;



abstract class DoctrineBaseRepository implements BaseRepositoryInterface {
   protected EntityRepository $repository;


   public function __construct(
      protected EntityManagerInterface $entityManager,
      string $entityClass
   ) {
      $this->repository = $this->entityManager->getRepository($entityClass);
   }

   /**
    * {@inheritdoc}
    */
   public function findById($id): ?object {
      return $this->repository->find($id);
   }

   /**
    * {@inheritdoc}
    */
   public function findAll(): array {
      return $this->repository->findAll();
   }

   /**
    * {@inheritdoc}
    */
   public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
      return $this->repository->findBy($criteria, $orderBy, $limit, $offset);
   }

   /**
    * {@inheritdoc}
    */
   public function findOneBy(array $criteria): ?object {
      return $this->repository->findOneBy($criteria);
   }

   /**
    * {@inheritdoc}
    */
   public function save(object $entity): object {
      $this->entityManager->persist($entity);
      $this->entityManager->flush();

      return $entity;
   }

   /**
    * {@inheritdoc}
    */
   public function delete(object $entity): void {
      $this->entityManager->remove($entity);
      $this->entityManager->flush();
   }

   /**
    * {@inheritdoc}
    */
   public function count(array $criteria = []): int {
      return $this->repository->count($criteria);
   }

   /**
    * Obtener el EntityManager para operaciones más complejas
    * 
    * @return EntityManagerInterface
    */
   protected function getEntityManager(): EntityManagerInterface {
      return $this->entityManager;
   }

   /**
    * Obtener el repositorio de Doctrine para operaciones específicas
    * 
    * @return EntityRepository
    */
   protected function getRepository(): EntityRepository {
      return $this->repository;
   }
}
