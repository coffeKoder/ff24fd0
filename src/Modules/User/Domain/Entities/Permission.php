<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Entities;

use Viex\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'permissions')]
class Permission extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'permissions_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'name', type: 'string', length: 100, unique: true)]
   private string $name;

   #[ORM\Column(name: 'description', type: 'string', length: 500)]
   private string $description;

   #[ORM\Column(name: 'module', type: 'string', length: 50)]
   private string $module;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   /**
    * Relación Many-to-Many con UserGroup a través de user_group_permissions
    * NO cargamos la colección para evitar redundancia cíclica
    */
   private Collection $userGroups;

   public function __construct(
      string $name,
      string $description,
      string $module
   ) {
      parent::__construct();
      $this->name = $name;
      $this->description = $description;
      $this->module = $module;
      $this->userGroups = new ArrayCollection();
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getName(): string {
      return $this->name;
   }

   public function getDescription(): string {
      return $this->description;
   }

   public function getModule(): string {
      return $this->module;
   }

   public function isActive(): bool {
      return $this->isActive === 1;
   }

   // Business Methods
   public function updateDetails(string $name, string $description, string $module): void {
      $this->name = $name;
      $this->description = $description;
      $this->module = $module;
      $this->touch();
   }

   public function activate(): void {
      $this->isActive = 1;
      $this->touch();
   }

   public function deactivate(): void {
      $this->isActive = 0;
      $this->touch();
   }

   // Collection Management (sin lazy loading para evitar redundancia)
   public function getUserGroups(): Collection {
      return $this->userGroups;
   }

   // Business Logic
   public function isSystemPermission(): bool {
      return str_starts_with($this->name, 'admin.') ||
         str_starts_with($this->name, 'system.');
   }

   public function canBeDeleted(): bool {
      return !$this->isSystemPermission() && $this->isActive();
   }

   public function belongsToModule(string $module): bool {
      return $this->module === $module;
   }

   // Helper methods for permission checking
   public function matches(string $permissionName): bool {
      // Exact match
      if ($this->name === $permissionName) {
         return true;
      }

      // Wildcard match (e.g., 'admin.*' matches 'admin.users.manage')
      if (str_ends_with($this->name, '.*')) {
         $prefix = substr($this->name, 0, -2);
         return str_starts_with($permissionName, $prefix . '.');
      }

      return false;
   }

   // String representation
   public function __toString(): string {
      return $this->name . ' (' . $this->module . ')';
   }
}
