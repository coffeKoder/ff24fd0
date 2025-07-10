<?php
/**
 * @package     Organizational/Application
 * @subpackage  Events
 * @file        HierarchyChanged
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:40:00
 * @version     1.0.0
 * @description Evento disparado cuando cambia la estructura jerárquica
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Events;

use DateTimeImmutable;

class HierarchyChanged {
   private string $changeType; // 'created', 'moved', 'updated', 'deleted'
   private int $affectedUnitId;
   private array $affectedUnitIds; // IDs de todas las unidades afectadas
   private DateTimeImmutable $occurredOn;

   public function __construct(string $changeType, int $affectedUnitId, array $affectedUnitIds = []) {
      $this->changeType = $changeType;
      $this->affectedUnitId = $affectedUnitId;
      $this->affectedUnitIds = array_unique(array_merge([$affectedUnitId], $affectedUnitIds));
      $this->occurredOn = new DateTimeImmutable();
   }

   public function getChangeType(): string {
      return $this->changeType;
   }

   public function getAffectedUnitId(): int {
      return $this->affectedUnitId;
   }

   public function getAffectedUnitIds(): array {
      return $this->affectedUnitIds;
   }

   public function getOccurredOn(): DateTimeImmutable {
      return $this->occurredOn;
   }

   public function requiresCacheInvalidation(): bool {
      return in_array($this->changeType, ['created', 'moved', 'updated', 'deleted'], true);
   }

   public function toArray(): array {
      return [
         'change_type' => $this->changeType,
         'affected_unit_id' => $this->affectedUnitId,
         'affected_unit_ids' => $this->affectedUnitIds,
         'requires_cache_invalidation' => $this->requiresCacheInvalidation(),
         'occurred_on' => $this->occurredOn->format('Y-m-d H:i:s')
      ];
   }
}
