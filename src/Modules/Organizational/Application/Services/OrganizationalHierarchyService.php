<?php
/**
 * @package     Organizational/Application
 * @subpackage  Services
 * @file        OrganizationalHierarchyService
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:45:00
 * @version     1.0.0
 * @description Servicio para navegación y validación jerárquica
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Services;

use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;
use Viex\Modules\Organizational\Application\DTOs\HierarchyTreeDTO;
use Viex\Modules\Organizational\Infrastructure\Cache\HierarchyCacheService;

class OrganizationalHierarchyService {
   private OrganizationalUnitRepositoryInterface $repository;
   private HierarchyCacheService $cacheService;
   private ?array $cachedHierarchy = null;
   private int $cacheTimeout = 3600; // 1 hora

   public function __construct(
      OrganizationalUnitRepositoryInterface $repository,
      HierarchyCacheService $cacheService
   ) {
      $this->repository = $repository;
      $this->cacheService = $cacheService;
   }

   /**
    * Obtener el árbol jerárquico completo
    */
   public function getFullHierarchy(): array {
      // Intentar obtener desde cache simple
      if ($this->cachedHierarchy !== null) {
         return $this->cachedHierarchy;
      }

      // Si no está en cache, obtener desde repositorio
      $hierarchyData = $this->repository->findHierarchyTree();
      $this->cachedHierarchy = [];

      foreach ($hierarchyData as $item) {
         if (is_array($item) && isset($item['unit'])) {
            $this->cachedHierarchy[] = $this->buildTreeFromHierarchy($item);
         }
      }

      return $this->cachedHierarchy;
   }

   /**
    * Limpiar caché del árbol jerárquico
    */
   public function clearHierarchyCache(): void {
      $this->cachedHierarchy = null;
      $this->cacheService->flushHierarchy();
   }

   /**
    * Obtener subárbol para una unidad específica
    */
   public function getSubTreeForUnit(int $unitId): HierarchyTreeDTO {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      return $this->buildSubTree($unit);
   }

   /**
    * Obtener la ruta desde una unidad hasta la raíz
    */
   public function getLineageForUnit(int $unitId): array {
      $ancestors = $this->repository->findAncestors($unitId);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $ancestors
      );
   }

   /**
    * Obtener todas las unidades descendientes de una unidad
    */
   public function getDescendantsForUnit(int $unitId): array {
      $descendants = $this->repository->findDescendants($unitId);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $descendants
      );
   }

   /**
    * Verificar si una unidad es ancestro de otra
    */
   public function isAncestorOf(int $ancestorId, int $descendantId): bool {
      if ($ancestorId === $descendantId) {
         return false;
      }

      return $this->repository->isAncestorOf($ancestorId, $descendantId);
   }

   /**
    * Obtener unidades por tipo específico
    */
   public function getUnitsByType(string $type): array {
      $units = $this->repository->findByType($type);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Obtener unidades raíz
    */
   public function getRootUnits(): array {
      $units = $this->repository->findRootUnits();

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Obtener unidades por nivel de profundidad
    */
   public function getUnitsByLevel(int $level): array {
      $units = $this->repository->findByDepthLevel($level);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Buscar unidades por texto
    */
   public function searchUnits(string $searchTerm): array {
      $units = $this->repository->search($searchTerm);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Validar que el movimiento de una unidad es válido
    */
   public function validateUnitMove(int $unitId, ?int $newParentId): bool {
      // No se puede mover una unidad a sí misma
      if ($unitId === $newParentId) {
         return false;
      }

      // Si no hay nuevo padre, es válido (mover a raíz)
      if ($newParentId === null) {
         return true;
      }

      // Verificar que el nuevo padre exista
      $newParent = $this->repository->findById($newParentId);
      if (!$newParent) {
         return false;
      }

      // Verificar que la unidad no sea ancestro del nuevo padre
      // (esto evitaría ciclos)
      return !$this->isAncestorOf($unitId, $newParentId);
   }

   /**
    * Obtener estadísticas de la jerarquía
    */
   public function getHierarchyStatistics(): array {
      return $this->repository->getStatistics();
   }

   /**
    * Invalidar caché
    */
   public function flushCache(): void {
      $this->cachedHierarchy = null;
   }

   /**
    * Obtener el árbol jerárquico como DTO
    */
   public function getHierarchyTree(?int $rootId = null): HierarchyTreeDTO {
      if ($rootId === null) {
         // Obtener todas las unidades raíz
         $rootUnits = $this->getRootUnits();

         if (empty($rootUnits)) {
            // Si no hay unidades, crear un DTO vacío con una unidad virtual
            $virtualUnit = new OrganizationalUnitDTO(
               0,
               'Sistema',
               'SYSTEM',
               null,
               null,
               '/0',
               0,
               0,
               true,
               false,
               false,
               false,
               new \DateTimeImmutable(),
               new \DateTimeImmutable(),
               []
            );
            return new HierarchyTreeDTO($virtualUnit, []);
         }

         // Si hay múltiples raíces, crear un nodo virtual que las contenga
         if (count($rootUnits) > 1) {
            $virtualUnit = new OrganizationalUnitDTO(
               0,
               'Sistema',
               'SYSTEM',
               null,
               null,
               '/0',
               0,
               count($rootUnits),
               true,
               false,
               false,
               false,
               new \DateTimeImmutable(),
               new \DateTimeImmutable(),
               []
            );
            $children = [];
            foreach ($rootUnits as $rootUnit) {
               $children[] = $this->getSubTreeForUnit($rootUnit->getId());
            }
            return new HierarchyTreeDTO($virtualUnit, $children);
         }

         // Si hay una sola raíz, devolverla
         return $this->getSubTreeForUnit($rootUnits[0]->getId());
      } else {
         // Obtener subárbol para una unidad específica
         return $this->getSubTreeForUnit($rootId);
      }
   }

   /**
    * Construir subárbol para una unidad específica
    */
   private function buildSubTree(OrganizationalUnit $unit): HierarchyTreeDTO {
      $unitDTO = OrganizationalUnitDTO::fromEntity($unit);
      $children = [];

      foreach ($unit->getChildren() as $child) {
         if ($child->isActive() && !$child->isSoftDeleted()) {
            $children[] = $this->buildSubTree($child);
         }
      }

      return new HierarchyTreeDTO($unitDTO, $children);
   }

   /**
    * Construir árbol desde la estructura devuelta por el repositorio
    */
   private function buildTreeFromHierarchy(array $item): HierarchyTreeDTO {
      $unit = OrganizationalUnitDTO::fromEntity($item['unit']);
      $children = [];

      if (isset($item['children']) && is_array($item['children'])) {
         foreach ($item['children'] as $childItem) {
            $children[] = $this->buildTreeFromHierarchy($childItem);
         }
      }

      return new HierarchyTreeDTO($unit, $children);
   }

   /**
    * Verificar si una unidad puede ser eliminada
    */
   public function canDeleteUnit(int $unitId): bool {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         return false;
      }

      // No se puede eliminar si tiene hijos activos
      if ($unit->hasChildren()) {
         return false;
      }

      // Verificar si tiene usuarios asignados
      $assignedUsersCount = $this->repository->countAssignedUsers($unitId);
      if ($assignedUsersCount > 0) {
         return false;
      }

      return true;
   }

   /**
    * Obtener información de contexto para una unidad
    */
   public function getUnitContext(int $unitId): array {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      $ancestors = $this->getLineageForUnit($unitId);
      $descendants = $this->getDescendantsForUnit($unitId);

      return [
         'unit' => OrganizationalUnitDTO::fromEntity($unit),
         'ancestors' => $ancestors,
         'descendants' => $descendants,
         'children_count' => count($descendants),
         'depth_level' => $unit->getDepthLevel(),
         'hierarchy_path' => $unit->getHierarchyPath()
      ];
   }
}
