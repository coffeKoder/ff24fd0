<?php
/**
 * @package     Organizational/Application
 * @subpackage  DTOs
 * @file        HierarchyTreeDTO
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:35:00
 * @version     1.0.0
 * @description DTO para representar árboles jerárquicos de unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\DTOs;

class HierarchyTreeDTO {
   private OrganizationalUnitDTO $unit;
   private array $children;

   public function __construct(OrganizationalUnitDTO $unit, array $children = []) {
      $this->unit = $unit;
      $this->children = $children;
   }

   public function getUnit(): OrganizationalUnitDTO {
      return $this->unit;
   }

   public function getChildren(): array {
      return $this->children;
   }

   public function addChild(HierarchyTreeDTO $child): void {
      $this->children[] = $child;
   }

   public function hasChildren(): bool {
      return !empty($this->children);
   }

   public function getChildrenCount(): int {
      return count($this->children);
   }

   public function toArray(): array {
      return [
         'unit' => $this->unit->toArray(),
         'children' => array_map(fn(HierarchyTreeDTO $child) => $child->toArray(), $this->children)
      ];
   }

   public function toFlatArray(): array {
      $result = [$this->unit->toArray()];

      foreach ($this->children as $child) {
         $result = array_merge($result, $child->toFlatArray());
      }

      return $result;
   }

   /**
    * Crear árbol jerárquico desde array de entidades
    * 
    * @param array $hierarchyData Array con estructura [unit => OrganizationalUnit, children => [...]]
    */
   public static function fromHierarchyArray(array $hierarchyData): self {
      $unit = OrganizationalUnitDTO::fromEntity($hierarchyData['unit']);
      $children = [];

      if (isset($hierarchyData['children']) && is_array($hierarchyData['children'])) {
         foreach ($hierarchyData['children'] as $childData) {
            $children[] = self::fromHierarchyArray($childData);
         }
      }

      return new self($unit, $children);
   }

   /**
    * Buscar una unidad en el árbol por ID
    */
   public function findUnitById(int $unitId): ?OrganizationalUnitDTO {
      if ($this->unit->getId() === $unitId) {
         return $this->unit;
      }

      foreach ($this->children as $child) {
         $found = $child->findUnitById($unitId);
         if ($found !== null) {
            return $found;
         }
      }

      return null;
   }

   /**
    * Obtener la ruta hacia una unidad específica
    */
   public function getPathToUnit(int $unitId): ?array {
      if ($this->unit->getId() === $unitId) {
         return [$this->unit];
      }

      foreach ($this->children as $child) {
         $path = $child->getPathToUnit($unitId);
         if ($path !== null) {
            return array_merge([$this->unit], $path);
         }
      }

      return null;
   }

   /**
    * Filtrar el árbol por tipo de unidad
    */
   public function filterByType(string $type): ?self {
      $filteredChildren = [];

      foreach ($this->children as $child) {
         $filteredChild = $child->filterByType($type);
         if ($filteredChild !== null) {
            $filteredChildren[] = $filteredChild;
         }
      }

      // Si esta unidad es del tipo buscado o tiene hijos del tipo
      if ($this->unit->getType() === $type || !empty($filteredChildren)) {
         return new self($this->unit, $filteredChildren);
      }

      return null;
   }

   /**
    * Obtener estadísticas del árbol
    */
   public function getStatistics(): array {
      $stats = [
         'total_units' => 1,
         'active_units' => $this->unit->isActive() ? 1 : 0,
         'max_depth' => 1,
         'by_type' => [$this->unit->getType() => 1]
      ];

      foreach ($this->children as $child) {
         $childStats = $child->getStatistics();

         $stats['total_units'] += $childStats['total_units'];
         $stats['active_units'] += $childStats['active_units'];
         $stats['max_depth'] = max($stats['max_depth'], $childStats['max_depth'] + 1);

         foreach ($childStats['by_type'] as $type => $count) {
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + $count;
         }
      }

      return $stats;
   }
}
