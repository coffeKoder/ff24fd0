<?php
/**
 * @package     Organizational/Domain
 * @subpackage  Repositories
 * @file        OrganizationalUnitRepositoryInterface
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:41:45
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\Repository;

use Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit;

interface OrganizationalUnitRepositoryInterface {
   /**
    * Buscar unidad por ID
    */
   public function findById(int $id): ?OrganizationalUnit;

   /**
    * Buscar unidad por nombre
    */
   public function findByName(string $name): ?OrganizationalUnit;

   /**
    * Buscar unidades por tipo
    * 
    * @return OrganizationalUnit[]
    */
   public function findByType(string $type): array;

   /**
    * Obtener todas las unidades activas
    * 
    * @return OrganizationalUnit[]
    */
   public function findActiveUnits(): array;

   /**
    * Obtener todas las unidades raíz (sin padre)
    * 
    * @return OrganizationalUnit[]
    */
   public function findRootUnits(): array;

   /**
    * Obtener unidades hijas de una unidad específica
    * 
    * @return OrganizationalUnit[]
    */
   public function findByParent(int $parentId): array;

   /**
    * Obtener unidades por nivel de profundidad
    * 
    * @return OrganizationalUnit[]
    */
   public function findByDepthLevel(int $level): array;

   /**
    * Obtener el árbol jerárquico completo
    * 
    * @return OrganizationalUnit[]
    */
   public function findHierarchyTree(): array;

   /**
    * Buscar unidades por texto de búsqueda
    * 
    * @return OrganizationalUnit[]
    */
   public function search(string $searchTerm): array;

   /**
    * Obtener unidades paginadas
    * 
    * @return array{units: OrganizationalUnit[], total: int}
    */
   public function findPaginated(int $page = 1, int $limit = 20, ?string $search = null, ?string $type = null): array;

   /**
    * Verificar si existe una unidad con el nombre especificado
    */
   public function existsByName(string $name): bool;

   /**
    * Verificar si existe una unidad con el nombre en un tipo específico
    */
   public function existsByNameAndType(string $name, string $type): bool;

   /**
    * Guardar unidad (crear o actualizar)
    */
   public function save(OrganizationalUnit $unit): void;

   /**
    * Eliminar unidad permanentemente
    */
   public function delete(OrganizationalUnit $unit): void;

   /**
    * Obtener estadísticas por tipo
    * 
    * @return array<string, int>
    */
   public function getStatisticsByType(): array;

   /**
    * Obtener todos los tipos únicos
    * 
    * @return string[]
    */
   public function getUniqueTypes(): array;
}