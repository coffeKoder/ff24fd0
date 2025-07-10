<?php
/**
 * @package     Organizational/Application
 * @subpackage  UseCases
 * @file        GetOrganizationalUnit
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:05:00
 * @version     1.0.0
 * @description Caso de uso para obtener una unidad organizacional por ID
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\UseCases;

use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;

class GetOrganizationalUnit {
   private OrganizationalUnitRepositoryInterface $repository;

   public function __construct(OrganizationalUnitRepositoryInterface $repository) {
      $this->repository = $repository;
   }

   public function execute(int $unitId): OrganizationalUnitDTO {
      $unit = $this->repository->findById($unitId);
      
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      return OrganizationalUnitDTO::fromEntity($unit);
   }
}
