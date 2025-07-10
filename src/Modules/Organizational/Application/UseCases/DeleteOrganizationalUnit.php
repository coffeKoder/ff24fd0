<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        DeleteOrganizationalUnit
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:00:00
 * @version     1.0.0
 * @description Caso de uso para eliminar una unidad organizacional
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;

class DeleteOrganizationalUnit {
   private UnitManagementService $unitManagementService;
   private OrganizationalHierarchyService $hierarchyService;
   private EventDispatcherInterface $eventDispatcher;

   public function __construct(
      UnitManagementService $unitManagementService,
      OrganizationalHierarchyService $hierarchyService,
      EventDispatcherInterface $eventDispatcher
   ) {
      $this->unitManagementService = $unitManagementService;
      $this->hierarchyService = $hierarchyService;
      $this->eventDispatcher = $eventDispatcher;
   }

   public function execute(int $unitId, bool $forceDelete = false): void {
      // Validar que la unidad se puede eliminar
      if (!$this->hierarchyService->canDeleteUnit($unitId)) {
         throw InvalidHierarchyException::unitCannotBeDeleted($unitId, 'La unidad tiene dependientes');
      }

      // Verificar si tiene hijos y si no se fuerza la eliminación
      $descendants = $this->hierarchyService->getDescendantsForUnit($unitId);
      if (!empty($descendants) && !$forceDelete) {
         throw InvalidHierarchyException::unitCannotBeDeleted($unitId, 'La unidad tiene unidades dependientes');
      }

      $this->unitManagementService->deleteUnit($unitId, $forceDelete);
   }
}
