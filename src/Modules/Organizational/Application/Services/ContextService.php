<?php
/**
 * @package     Organizational/Application
 * @subpackage  Services
 * @file        ContextService
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:10:00
 * @version     1.0.0
 * @description Servicio para resolución de contextos organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Services;

use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;

class ContextService {
   private OrganizationalUnitRepositoryInterface $repository;
   private OrganizationalHierarchyService $hierarchyService;

   public function __construct(
      OrganizationalUnitRepositoryInterface $repository,
      OrganizationalHierarchyService $hierarchyService
   ) {
      $this->repository = $repository;
      $this->hierarchyService = $hierarchyService;
   }

   /**
    * Obtener unidades organizacionales para el contexto de un usuario
    * TODO: Implementar cuando tengamos el módulo User completo
    */
   public function getUnitsForUserContext($user): array {
      // Placeholder - implementar cuando tengamos User entity
      return [];
   }

   /**
    * Verificar si un usuario tiene autoridad sobre una unidad específica
    * TODO: Implementar cuando tengamos el módulo User completo
    */
   public function isUserInUnitHierarchy($user, int $targetUnitId): bool {
      // Placeholder - implementar cuando tengamos User entity
      return false;
   }

   /**
    * Resolver contexto organizacional por tipo de unidad
    */
   public function resolveContextByType(string $unitType): array {
      $units = $this->repository->findByType($unitType);

      return array_map(
         fn($unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Obtener árbol de unidades para selección contextual
    */
   public function getUnitsTreeForSelection(?string $filterType = null): array {
      $fullTree = $this->hierarchyService->getFullHierarchy();

      if ($filterType === null) {
         return $fullTree;
      }

      // Filtrar por tipo
      $filteredTree = [];
      foreach ($fullTree as $treeNode) {
         $filtered = $treeNode->filterByType($filterType);
         if ($filtered !== null) {
            $filteredTree[] = $filtered;
         }
      }

      return $filteredTree;
   }

   /**
    * Obtener opciones para dropdown/select de unidades
    */
   public function getUnitsForDropdown(?string $parentType = null, bool $activeOnly = true): array {
      $units = $activeOnly ? $this->repository->findActiveUnits() : $this->repository->findAll();
      $options = [];

      foreach ($units as $unit) {
         // Filtrar por tipo de padre si se especifica
         if ($parentType !== null) {
            $parent = $unit->getParent();
            if (!$parent || $parent->getType() !== $parentType) {
               continue;
            }
         }

         $options[] = [
            'value' => $unit->getId(),
            'label' => $unit->getName(),
            'type' => $unit->getType(),
            'hierarchy_path' => $unit->getHierarchyPath(),
            'depth_level' => $unit->getDepthLevel(),
            'is_academic' => $unit->isAcademicUnit(),
            'is_administrative' => $unit->isAdministrativeUnit(),
            'is_teaching' => $unit->isTeachingUnit()
         ];
      }

      // Ordenar por ruta jerárquica
      usort($options, fn($a, $b) => strcmp($a['hierarchy_path'], $b['hierarchy_path']));

      return $options;
   }

   /**
    * Resolver contexto para roles específicos
    */
   public function resolveContextForRole(string $role): array {
      switch ($role) {
         case 'decano':
         case 'director':
            return $this->resolveContextByType('Facultad');

         case 'coordinador_extension':
            return $this->getUnitsTreeForSelection();

         case 'jefe_departamento':
            return $this->resolveContextByType('Departamento');

         case 'director_escuela':
            return $this->resolveContextByType('Escuela');

         default:
            return [];
      }
   }

   /**
    * Obtener contexto jerárquico completo para una unidad
    */
   public function getUnitHierarchyContext(int $unitId): array {
      return $this->hierarchyService->getUnitContext($unitId);
   }

   /**
    * Verificar si una unidad pertenece a una jerarquía específica
    */
   public function unitBelongsToHierarchy(int $unitId, int $rootUnitId): bool {
      return $this->hierarchyService->isAncestorOf($rootUnitId, $unitId);
   }

   /**
    * Obtener unidades hermanas (mismo padre)
    */
   public function getSiblingUnits(int $unitId): array {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      $parent = $unit->getParent();
      if (!$parent) {
         // Es unidad raíz, obtener otras unidades raíz
         $siblings = $this->repository->findRootUnits();
      } else {
         // Obtener unidades hijas del mismo padre
         $siblings = $this->repository->findByParent($parent->getId());
      }

      // Excluir la unidad actual
      $siblings = array_filter($siblings, fn($sibling) => $sibling->getId() !== $unitId);

      return array_map(
         fn($unit) => OrganizationalUnitDTO::fromEntity($unit),
         $siblings
      );
   }

   /**
    * Obtener breadcrumb para navegación
    */
   public function getBreadcrumbForUnit(int $unitId): array {
      $ancestors = $this->hierarchyService->getLineageForUnit($unitId);
      $unit = $this->repository->findById($unitId);

      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      // Agregar la unidad actual al final
      $ancestors[] = OrganizationalUnitDTO::fromEntity($unit);

      return array_map(fn($unitDto) => [
         'id' => $unitDto->getId(),
         'name' => $unitDto->getName(),
         'type' => $unitDto->getType()
      ], $ancestors);
   }

   /**
    * Obtener estadísticas de contexto
    */
   public function getContextStatistics(): array {
      return $this->hierarchyService->getHierarchyStatistics();
   }
}
