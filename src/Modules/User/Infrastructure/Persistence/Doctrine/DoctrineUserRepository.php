<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\Repositories\UserRepositoryInterface;
use Viex\Modules\User\Domain\ValueObjects\Email;

/**
 * Implementación Doctrine del repositorio de usuarios
 */
class DoctrineUserRepository extends BaseRepository implements UserRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, User::class);
   }

   public function findByEmail(Email $email): ?User {
      return $this->findOneBy(['email' => $email->getValue()]);
   }

   public function findByEmployeeCode(string $employeeCode): ?User {
      return $this->findOneBy(['professorCode' => $employeeCode]);
   }

   public function findByCedula(string $cedula): ?User {
      return $this->findOneBy(['cedula' => $cedula]);
   }

   public function findByAcademicUnit(int $academicUnitId): array {
      return $this->findBy(['mainOrganizationalUnitId' => $academicUnitId]);
   }

   public function findByEmployeeType(string $employeeType): array {
      $qb = $this->createQueryBuilder('u');

      // Asumiendo que profesores tienen professorCode no nulo
      if ($employeeType === 'professor') {
         $qb->where('u.professorCode IS NOT NULL');
      } else {
         $qb->where('u.professorCode IS NULL');
      }

      return $qb->getQuery()->getResult();
   }

   public function findActiveUsers(): array {
      return $this->createQueryBuilder('u')
         ->where('u.isActive = 1')
         ->andWhere('u.softDeleted IS NULL')
         ->orderBy('u.firstName', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findInactiveUsers(): array {
      return $this->createQueryBuilder('u')
         ->where('u.isActive = 0')
         ->andWhere('u.softDeleted IS NULL')
         ->orderBy('u.firstName', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByStatus(string $status): array {
      $isActive = $status === 'active' ? 1 : 0;

      return $this->createQueryBuilder('u')
         ->where('u.isActive = :isActive')
         ->andWhere('u.softDeleted IS NULL')
         ->setParameter('isActive', $isActive)
         ->orderBy('u.firstName', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('u')
         ->where('u.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'u');
      $qb->orderBy('u.firstName', 'ASC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }

   public function existsByEmail(Email $email): bool {
      return $this->exists(['email' => $email->getValue()]);
   }

   public function existsByCedula(string $cedula): bool {
      return $this->exists(['cedula' => $cedula]);
   }

   public function existsByEmployeeCode(string $employeeCode): bool {
      return $this->exists(['professorCode' => $employeeCode]);
   }

   public function searchUsers(string $searchTerm): array {
      $searchPattern = '%' . $searchTerm . '%';

      return $this->createQueryBuilder('u')
         ->where('u.softDeleted IS NULL')
         ->andWhere('(u.firstName LIKE :search OR u.lastName LIKE :search OR u.email LIKE :search OR u.cedula LIKE :search)')
         ->setParameter('search', $searchPattern)
         ->orderBy('u.firstName', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function countByAcademicUnit(int $academicUnitId): int {
      return (int) $this->createQueryBuilder('u')
         ->select('COUNT(u.id)')
         ->where('u.mainOrganizationalUnitId = :unitId')
         ->andWhere('u.softDeleted IS NULL')
         ->setParameter('unitId', $academicUnitId)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function countActiveUsers(): int {
      return (int) $this->createQueryBuilder('u')
         ->select('COUNT(u.id)')
         ->where('u.isActive = 1')
         ->andWhere('u.softDeleted IS NULL')
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function findUsersWithGroups(): array {
      return $this->createQueryBuilder('u')
         ->leftJoin('u.userGroups', 'ug')
         ->addSelect('ug')
         ->where('u.softDeleted IS NULL')
         ->orderBy('u.firstName', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('u')
         ->where('u.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['name'])) {
         $namePattern = '%' . $filters['name'] . '%';
         $qb->andWhere('(u.firstName LIKE :name OR u.lastName LIKE :name)')
            ->setParameter('name', $namePattern);
      }

      if (isset($filters['email'])) {
         $qb->andWhere('u.email LIKE :email')
            ->setParameter('email', '%' . $filters['email'] . '%');
      }

      if (isset($filters['isActive'])) {
         $qb->andWhere('u.isActive = :isActive')
            ->setParameter('isActive', $filters['isActive'] ? 1 : 0);
      }

      if (isset($filters['hasProfessorCode'])) {
         if ($filters['hasProfessorCode']) {
            $qb->andWhere('u.professorCode IS NOT NULL');
         } else {
            $qb->andWhere('u.professorCode IS NULL');
         }
      }

      if (isset($filters['organizationalUnitId'])) {
         $qb->andWhere('u.mainOrganizationalUnitId = :unitId')
            ->setParameter('unitId', $filters['organizationalUnitId']);
      }

      if (isset($filters['emailVerified'])) {
         if ($filters['emailVerified']) {
            $qb->andWhere('u.emailVerifiedAt IS NOT NULL');
         } else {
            $qb->andWhere('u.emailVerifiedAt IS NULL');
         }
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'firstName';
      $orderDirection = $filters['orderDirection'] ?? 'ASC';
      $qb->orderBy("u.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }
}
