<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        UpdateOrganizationalUnit
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:32:36
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;

class UpdateOrganizationalUnit {
   private UnitManagementService $unitManagementService;

   public function __construct(UnitManagementService $unitManagementService) {
      $this->unitManagementService = $unitManagementService;
   }

   public function execute(int $unitId, string $name, string $type): OrganizationalUnitDTO {
      return $this->unitManagementService->updateUnit($unitId, $name, $type);
   }
}