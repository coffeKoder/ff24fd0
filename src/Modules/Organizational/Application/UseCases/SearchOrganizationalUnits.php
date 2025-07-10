<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        SearchOrganizationalUnits
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:10:00
 * @version     1.0.0
 * @description Caso de uso para buscar unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;

class SearchOrganizationalUnits {
   private OrganizationalHierarchyService $hierarchyService;

   public function __construct(OrganizationalHierarchyService $hierarchyService) {
      $this->hierarchyService = $hierarchyService;
   }

   /**
    * Buscar unidades por término de búsqueda
    * @param string $searchTerm
    * @return OrganizationalUnitDTO[]
    */
   public function execute(string $searchTerm): array {
      return $this->hierarchyService->searchUnits($searchTerm);
   }

   /**
    * Obtener unidades por tipo
    * @param string $type
    * @return OrganizationalUnitDTO[]
    */
   public function getByType(string $type): array {
      return $this->hierarchyService->getUnitsByType($type);
   }

   /**
    * Obtener unidades por nivel jerárquico
    * @param int $level
    * @return OrganizationalUnitDTO[]
    */
   public function getByLevel(int $level): array {
      return $this->hierarchyService->getUnitsByLevel($level);
   }

   /**
    * Obtener unidades raíz
    * @return OrganizationalUnitDTO[]
    */
   public function getRootUnits(): array {
      return $this->hierarchyService->getRootUnits();
   }
}
