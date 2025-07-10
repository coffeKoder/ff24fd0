<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        GetHierarchyStatistics
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:15:00
 * @version     1.0.0
 * @description Caso de uso para obtener estadísticas de la jerarquía organizacional
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;

class GetHierarchyStatistics {
   private OrganizationalHierarchyService $hierarchyService;

   public function __construct(OrganizationalHierarchyService $hierarchyService) {
      $this->hierarchyService = $hierarchyService;
   }

   /**
    * Obtener estadísticas generales de la jerarquía
    * @return array
    */
   public function execute(): array {
      return $this->hierarchyService->getHierarchyStatistics();
   }

   /**
    * Obtener contexto de una unidad específica
    * @param int $unitId
    * @return array
    */
   public function getUnitContext(int $unitId): array {
      return $this->hierarchyService->getUnitContext($unitId);
   }

   /**
    * Obtener línea de ascendencia para una unidad
    * @param int $unitId
    * @return array
    */
   public function getLineage(int $unitId): array {
      return $this->hierarchyService->getLineageForUnit($unitId);
   }

   /**
    * Obtener descendientes de una unidad
    * @param int $unitId
    * @return array
    */
   public function getDescendants(int $unitId): array {
      return $this->hierarchyService->getDescendantsForUnit($unitId);
   }
}
