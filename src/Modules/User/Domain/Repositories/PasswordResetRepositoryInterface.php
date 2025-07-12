<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\PasswordReset;

/**
 * Interface del repositorio para la entidad PasswordReset
 * Define métodos específicos para consultas y operaciones de recuperación de contraseña
 */
interface PasswordResetRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar un token de reset por su valor
    */
   public function findByToken(string $token): ?PasswordReset;

   /**
    * Buscar resets por usuario
    */
   public function findByUserId(int $userId): array;

   /**
    * Buscar resets activos (no expirados y no utilizados)
    */
   public function findActiveResets(): array;

   /**
    * Buscar resets expirados
    */
   public function findExpiredResets(): array;

   /**
    * Buscar resets utilizados
    */
   public function findUsedResets(): array;

   /**
    * Buscar resets por estado
    */
   public function findByStatus(string $status): array;

   /**
    * Buscar resets por rango de fechas de creación
    */
   public function findByCreatedDateRange(\DateTime $startDate, \DateTime $endDate): array;

   /**
    * Buscar resets por rango de fechas de expiración
    */
   public function findByExpirationDateRange(\DateTime $startDate, \DateTime $endDate): array;

   /**
    * Verificar si existe un token válido para un usuario
    */
   public function hasValidTokenForUser(int $userId): bool;

   /**
    * Buscar el último reset activo de un usuario
    */
   public function findLatestActiveResetByUser(int $userId): ?PasswordReset;

   /**
    * Buscar resets que expiran pronto
    */
   public function findResetsExpiringWithin(\DateInterval $interval): array;

   /**
    * Contar resets activos de un usuario
    */
   public function countActiveResetsByUser(int $userId): int;

   /**
    * Contar resets por usuario en un período
    */
   public function countResetsByUserInPeriod(int $userId, \DateTime $startDate, \DateTime $endDate): int;

   /**
    * Limpiar resets expirados
    */
   public function deleteExpiredResets(): int;

   /**
    * Limpiar resets antiguos utilizados
    */
   public function deleteOldUsedResets(\DateTime $beforeDate): int;

   /**
    * Invalidar todos los resets activos de un usuario
    */
   public function invalidateAllUserResets(int $userId): int;

   /**
    * Buscar resets con información del usuario
    */
   public function findWithUserInfo(): array;

   /**
    * Buscar resets por múltiples criterios
    */
   public function findByAdvancedCriteria(array $filters): array;

   /**
    * Buscar resets con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;
}
