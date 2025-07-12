<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Repositories;

use Viex\Modules\Shared\Domain\Repository\BaseRepositoryInterface;
use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\ValueObjects\Email;

/**
 * Interface del repositorio para la entidad User
 * Define métodos específicos para consultas y operaciones con usuarios
 */
interface UserRepositoryInterface extends BaseRepositoryInterface {
   /**
    * Buscar un usuario por su email
    */
   public function findByEmail(Email $email): ?User;

   /**
    * Buscar un usuario por su código de empleado
    */
   public function findByEmployeeCode(string $employeeCode): ?User;

   /**
    * Buscar un usuario por su cédula
    */
   public function findByCedula(string $cedula): ?User;

   /**
    * Buscar usuarios por unidad académica
    */
   public function findByAcademicUnit(int $academicUnitId): array;

   /**
    * Buscar usuarios por tipo de empleado
    */
   public function findByEmployeeType(string $employeeType): array;

   /**
    * Buscar usuarios activos
    */
   public function findActiveUsers(): array;

   /**
    * Buscar usuarios inactivos
    */
   public function findInactiveUsers(): array;

   /**
    * Buscar usuarios por estado
    */
   public function findByStatus(string $status): array;

   /**
    * Buscar usuarios con paginación
    */
   public function findWithPagination(array $criteria = [], int $page = 1, int $limit = 10): array;

   /**
    * Verificar si existe un usuario con el email dado
    */
   public function existsByEmail(Email $email): bool;

   /**
    * Verificar si existe un usuario con la cédula dada
    */
   public function existsByCedula(string $cedula): bool;

   /**
    * Verificar si existe un usuario con el código de empleado dado
    */
   public function existsByEmployeeCode(string $employeeCode): bool;

   /**
    * Buscar usuarios por término de búsqueda (nombre, email, etc.)
    */
   public function searchUsers(string $searchTerm): array;

   /**
    * Contar usuarios por unidad académica
    */
   public function countByAcademicUnit(int $academicUnitId): int;

   /**
    * Contar usuarios activos
    */
   public function countActiveUsers(): int;

   /**
    * Obtener usuarios con sus grupos
    */
   public function findUsersWithGroups(): array;

   /**
    * Buscar usuarios por múltiples criterios con filtros avanzados
    */
   public function findByAdvancedCriteria(array $filters): array;
}
