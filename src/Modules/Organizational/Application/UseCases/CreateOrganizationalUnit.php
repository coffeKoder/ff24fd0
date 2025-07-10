<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        CreateOrganizationalUnit
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:32:16
 * @version     1.0.0
 * @description Caso de uso para crear una unidad organizacional
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;

class CreateOrganizationalUnit {
   private UnitManagementService $unitManagementService;
   private EventDispatcherInterface $eventDispatcher;

   public function __construct(
      UnitManagementService $unitManagementService,
      EventDispatcherInterface $eventDispatcher
   ) {
      $this->unitManagementService = $unitManagementService;
      $this->eventDispatcher = $eventDispatcher;
   }

   public function execute(string $name, string $type, ?int $parentId = null): OrganizationalUnitDTO {
      return $this->unitManagementService->createUnit($name, $type, $parentId);
   }
}