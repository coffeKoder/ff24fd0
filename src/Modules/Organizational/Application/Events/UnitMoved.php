<?php
/**
 * @package     Organizational/Application
 * @subpackage  Events
 * @file        UnitMoved
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:40:00
 * @version     1.0.0
 * @description Evento disparado cuando se mueve una unidad en la jerarquía
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Events;

use DateTimeImmutable;

class UnitMoved {
   private int $unitId;
   private string $unitName;
   private ?int $oldParentId;
   private ?int $newParentId;
   private DateTimeImmutable $occurredOn;

   public function __construct(int $unitId, string $unitName, ?int $oldParentId, ?int $newParentId) {
      $this->unitId = $unitId;
      $this->unitName = $unitName;
      $this->oldParentId = $oldParentId;
      $this->newParentId = $newParentId;
      $this->occurredOn = new DateTimeImmutable();
   }

   public function getUnitId(): int {
      return $this->unitId;
   }

   public function getUnitName(): string {
      return $this->unitName;
   }

   public function getOldParentId(): ?int {
      return $this->oldParentId;
   }

   public function getNewParentId(): ?int {
      return $this->newParentId;
   }

   public function getOccurredOn(): DateTimeImmutable {
      return $this->occurredOn;
   }

   public function toArray(): array {
      return [
         'unit_id' => $this->unitId,
         'unit_name' => $this->unitName,
         'old_parent_id' => $this->oldParentId,
         'new_parent_id' => $this->newParentId,
         'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s')
      ];
   }
}
