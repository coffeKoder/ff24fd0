<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        GetHierarchyTree
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:32:28
 * @version     1.0.0
 * @description Caso de uso para obtener el árbol jerárquico de unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\DTOs\HierarchyTreeDTO;

class GetHierarchyTree {
   private OrganizationalHierarchyService $hierarchyService;

   public function __construct(OrganizationalHierarchyService $hierarchyService) {
      $this->hierarchyService = $hierarchyService;
   }

   public function execute(?int $rootId = null): HierarchyTreeDTO {
      return $this->hierarchyService->getHierarchyTree($rootId);
   }
}