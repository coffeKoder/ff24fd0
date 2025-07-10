<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        MoveUnit
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:32:32
 * @version     1.0.0
 * @description Caso de uso para mover una unidad organizacional en la jerarquía
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;

class MoveUnit {
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

   public function execute(int $unitId, ?int $newParentId): OrganizationalUnitDTO {
      // Validar que el movimiento es válido
      if (!$this->hierarchyService->validateUnitMove($unitId, $newParentId)) {
         throw InvalidHierarchyException::invalidHierarchyStructure(
            'El movimiento de la unidad crearía una estructura jerárquica inválida'
         );
      }

      return $this->unitManagementService->moveUnit($unitId, $newParentId);
   }
}