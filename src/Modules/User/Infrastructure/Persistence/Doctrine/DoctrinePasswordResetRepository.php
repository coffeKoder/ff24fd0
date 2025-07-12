<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Shared\Infrastructure\Persistence\BaseRepository;
use Viex\Modules\User\Domain\Entities\PasswordReset;
use Viex\Modules\User\Domain\Repositories\PasswordResetRepositoryInterface;

/**
 * Implementación Doctrine del repositorio de tokens de recuperación de contraseña
 */
class DoctrinePasswordResetRepository extends BaseRepository implements PasswordResetRepositoryInterface {
   public function __construct(EntityManagerInterface $entityManager) {
      parent::__construct($entityManager, PasswordReset::class);
   }

   public function findByToken(string $token): ?PasswordReset {
      return $this->findOneBy(['token' => $token]);
   }

   public function findByUserId(int $userId): array {
      // Nota: La entidad PasswordReset usa email, no userId directamente
      // Este método requeriría un join con la tabla users
      return $this->createQueryBuilder('pr')
         ->join('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->where('u.id = :userId')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->orderBy('pr.createdAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findActiveResets(): array {
      return $this->createQueryBuilder('pr')
         ->where('pr.isActive = 1')
         ->andWhere('pr.usedAt IS NULL')
         ->andWhere('pr.expiresAt > :now')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('now', new \DateTimeImmutable())
         ->orderBy('pr.createdAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findExpiredResets(): array {
      return $this->createQueryBuilder('pr')
         ->where('pr.expiresAt <= :now')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('now', new \DateTimeImmutable())
         ->orderBy('pr.expiresAt', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function findUsedResets(): array {
      return $this->createQueryBuilder('pr')
         ->where('pr.usedAt IS NOT NULL')
         ->andWhere('pr.softDeleted IS NULL')
         ->orderBy('pr.usedAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByStatus(string $status): array {
      $qb = $this->createQueryBuilder('pr')
         ->where('pr.softDeleted IS NULL');

      switch ($status) {
         case 'active':
            $qb->andWhere('pr.isActive = 1')
               ->andWhere('pr.usedAt IS NULL')
               ->andWhere('pr.expiresAt > :now')
               ->setParameter('now', new \DateTimeImmutable());
            break;
         case 'expired':
            $qb->andWhere('pr.expiresAt <= :now')
               ->setParameter('now', new \DateTimeImmutable());
            break;
         case 'used':
            $qb->andWhere('pr.usedAt IS NOT NULL');
            break;
         case 'inactive':
            $qb->andWhere('pr.isActive = 0');
            break;
      }

      return $qb->orderBy('pr.createdAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByCreatedDateRange(\DateTime $startDate, \DateTime $endDate): array {
      return $this->createQueryBuilder('pr')
         ->where('pr.createdAt BETWEEN :startDate AND :endDate')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('startDate', $startDate)
         ->setParameter('endDate', $endDate)
         ->orderBy('pr.createdAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByExpirationDateRange(\DateTime $startDate, \DateTime $endDate): array {
      return $this->createQueryBuilder('pr')
         ->where('pr.expiresAt BETWEEN :startDate AND :endDate')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('startDate', $startDate)
         ->setParameter('endDate', $endDate)
         ->orderBy('pr.expiresAt', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function hasValidTokenForUser(int $userId): bool {
      return $this->createQueryBuilder('pr')
         ->select('COUNT(pr.id)')
         ->join('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->where('u.id = :userId')
         ->andWhere('pr.isActive = 1')
         ->andWhere('pr.usedAt IS NULL')
         ->andWhere('pr.expiresAt > :now')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('now', new \DateTimeImmutable())
         ->getQuery()
         ->getSingleScalarResult() > 0;
   }

   public function findLatestActiveResetByUser(int $userId): ?PasswordReset {
      return $this->createQueryBuilder('pr')
         ->join('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->where('u.id = :userId')
         ->andWhere('pr.isActive = 1')
         ->andWhere('pr.usedAt IS NULL')
         ->andWhere('pr.expiresAt > :now')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('now', new \DateTimeImmutable())
         ->orderBy('pr.createdAt', 'DESC')
         ->setMaxResults(1)
         ->getQuery()
         ->getOneOrNullResult();
   }

   public function findResetsExpiringWithin(\DateInterval $interval): array {
      $now = new \DateTimeImmutable();
      $futureDate = $now->add($interval);

      return $this->createQueryBuilder('pr')
         ->where('pr.isActive = 1')
         ->andWhere('pr.usedAt IS NULL')
         ->andWhere('pr.expiresAt BETWEEN :now AND :futureDate')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('now', $now)
         ->setParameter('futureDate', $futureDate)
         ->orderBy('pr.expiresAt', 'ASC')
         ->getQuery()
         ->getResult();
   }

   public function countActiveResetsByUser(int $userId): int {
      return (int) $this->createQueryBuilder('pr')
         ->select('COUNT(pr.id)')
         ->join('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->where('u.id = :userId')
         ->andWhere('pr.isActive = 1')
         ->andWhere('pr.usedAt IS NULL')
         ->andWhere('pr.expiresAt > :now')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('now', new \DateTimeImmutable())
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function countResetsByUserInPeriod(int $userId, \DateTime $startDate, \DateTime $endDate): int {
      return (int) $this->createQueryBuilder('pr')
         ->select('COUNT(pr.id)')
         ->join('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->where('u.id = :userId')
         ->andWhere('pr.createdAt BETWEEN :startDate AND :endDate')
         ->andWhere('pr.softDeleted IS NULL')
         ->setParameter('userId', $userId)
         ->setParameter('startDate', $startDate)
         ->setParameter('endDate', $endDate)
         ->getQuery()
         ->getSingleScalarResult();
   }

   public function deleteExpiredResets(): int {
      return $this->entityManager->createQueryBuilder()
         ->delete(PasswordReset::class, 'pr')
         ->where('pr.expiresAt <= :now')
         ->setParameter('now', new \DateTimeImmutable())
         ->getQuery()
         ->execute();
   }

   public function deleteOldUsedResets(\DateTime $beforeDate): int {
      return $this->entityManager->createQueryBuilder()
         ->delete(PasswordReset::class, 'pr')
         ->where('pr.usedAt IS NOT NULL')
         ->andWhere('pr.usedAt <= :beforeDate')
         ->setParameter('beforeDate', $beforeDate)
         ->getQuery()
         ->execute();
   }

   public function invalidateAllUserResets(int $userId): int {
      return $this->entityManager->createQueryBuilder()
         ->update(PasswordReset::class, 'pr')
         ->set('pr.isActive', 0)
         ->where('pr.email = (SELECT u.email FROM Viex\Modules\User\Domain\Entities\User u WHERE u.id = :userId)')
         ->andWhere('pr.isActive = 1')
         ->setParameter('userId', $userId)
         ->getQuery()
         ->execute();
   }

   public function findWithUserInfo(): array {
      return $this->createQueryBuilder('pr')
         ->leftJoin('Viex\Modules\User\Domain\Entities\User', 'u', 'WITH', 'u.email = pr.email')
         ->addSelect('u')
         ->where('pr.softDeleted IS NULL')
         ->orderBy('pr.createdAt', 'DESC')
         ->getQuery()
         ->getResult();
   }

   public function findByAdvancedCriteria(array $filters): array {
      $qb = $this->createQueryBuilder('pr')
         ->where('pr.softDeleted IS NULL');

      // Filtros específicos avanzados
      if (isset($filters['email'])) {
         $qb->andWhere('pr.email = :email')
            ->setParameter('email', $filters['email']);
      }

      if (isset($filters['token'])) {
         $qb->andWhere('pr.token = :token')
            ->setParameter('token', $filters['token']);
      }

      if (isset($filters['isActive'])) {
         $qb->andWhere('pr.isActive = :isActive')
            ->setParameter('isActive', $filters['isActive'] ? 1 : 0);
      }

      if (isset($filters['isUsed'])) {
         if ($filters['isUsed']) {
            $qb->andWhere('pr.usedAt IS NOT NULL');
         } else {
            $qb->andWhere('pr.usedAt IS NULL');
         }
      }

      if (isset($filters['isExpired'])) {
         if ($filters['isExpired']) {
            $qb->andWhere('pr.expiresAt <= :now');
         } else {
            $qb->andWhere('pr.expiresAt > :now');
         }
         $qb->setParameter('now', new \DateTimeImmutable());
      }

      if (isset($filters['ipAddress'])) {
         $qb->andWhere('pr.ipAddress = :ipAddress')
            ->setParameter('ipAddress', $filters['ipAddress']);
      }

      if (isset($filters['createdAfter'])) {
         $qb->andWhere('pr.createdAt >= :createdAfter')
            ->setParameter('createdAfter', $filters['createdAfter']);
      }

      if (isset($filters['createdBefore'])) {
         $qb->andWhere('pr.createdAt <= :createdBefore')
            ->setParameter('createdBefore', $filters['createdBefore']);
      }

      // Ordenamiento
      $orderBy = $filters['orderBy'] ?? 'createdAt';
      $orderDirection = $filters['orderDirection'] ?? 'DESC';
      $qb->orderBy("pr.{$orderBy}", $orderDirection);

      return $qb->getQuery()->getResult();
   }

   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array {
      $qb = $this->createQueryBuilder('pr')
         ->where('pr.softDeleted IS NULL');

      $this->applyFilters($qb, $criteria, 'pr');
      $qb->orderBy('pr.createdAt', 'DESC');

      return $this->executePaginatedQuery($qb, $page, $limit);
   }
}
