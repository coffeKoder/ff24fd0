<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity class que provee funcionalidades comunes para todas las entidades
 */
abstract class BaseEntity {
   #[ORM\Column(name: 'created_at', type: 'datetime')]
   protected \DateTimeInterface $createdAt;

   #[ORM\Column(name: 'updated_at', type: 'datetime')]
   protected \DateTimeInterface $updatedAt;

   #[ORM\Column(name: 'soft_deleted', type: 'datetime', nullable: true)]
   protected ?\DateTimeInterface $softDeleted = null;

   public function __construct() {
      $now = new \DateTimeImmutable();
      $this->createdAt = $now;
      $this->updatedAt = $now;
   }

   public function getCreatedAt(): \DateTimeInterface {
      return $this->createdAt;
   }

   public function getUpdatedAt(): \DateTimeInterface {
      return $this->updatedAt;
   }

   public function getSoftDeleted(): ?\DateTimeInterface {
      return $this->softDeleted;
   }

   public function isDeleted(): bool {
      return $this->softDeleted !== null;
   }

   /**
    * Actualiza el timestamp de updated_at
    */
   public function touch(): void {
      $this->updatedAt = new \DateTimeImmutable();
   }

   /**
    * Marca la entidad como eliminada (soft delete)
    */
   public function softDelete(): void {
      $this->softDeleted = new \DateTimeImmutable();
      $this->touch();
   }

   /**
    * Restaura una entidad eliminada
    */
   public function restore(): void {
      $this->softDeleted = null;
      $this->touch();
   }

   /**
    * Verifica si la entidad está activa (no eliminada)
    */
   public function isActive(): bool {
      return !$this->isDeleted();
   }
}
