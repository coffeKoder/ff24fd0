<?php
/**
 * @package     Organizational/Application
 * @subpackage  Events
 * @file        UnitCreated
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:40:00
 * @version     1.0.0
 * @description Evento disparado cuando se crea una unidad organizacional
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Events;

use DateTimeImmutable;

class UnitCreated {
   private int $unitId;
   private string $unitName;
   private string $unitType;
   private ?int $parentId;
   private DateTimeImmutable $occurredOn;

   public function __construct(int $unitId, string $unitName, string $unitType, ?int $parentId = null) {
      $this->unitId = $unitId;
      $this->unitName = $unitName;
      $this->unitType = $unitType;
      $this->parentId = $parentId;
      $this->occurredOn = new DateTimeImmutable();
   }

   public function getUnitId(): int {
      return $this->unitId;
   }

   public function getUnitName(): string {
      return $this->unitName;
   }

   public function getUnitType(): string {
      return $this->unitType;
   }

   public function getParentId(): ?int {
      return $this->parentId;
   }

   public function getOccurredOn(): DateTimeImmutable {
      return $this->occurredOn;
   }

   public function toArray(): array {
      return [
         'unit_id' => $this->unitId,
         'unit_name' => $this->unitName,
         'unit_type' => $this->unitType,
         'parent_id' => $this->parentId,
         'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s')
      ];
   }
}
