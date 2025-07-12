<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Entities;

use Viex\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_groups')]
class UserGroup extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'user_groups_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'name', type: 'string', length: 100, unique: true)]
   private string $name;

   #[ORM\Column(name: 'description', type: 'string', length: 500)]
   private string $description;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   /**
    * Relación Many-to-Many con Permission a través de user_group_permissions
    * NO cargamos la colección para evitar redundancia cíclica
    */
   private Collection $permissions;

   /**
    * Relación Many-to-Many con User a través de user_user_groups  
    * NO cargamos la colección para evitar redundancia cíclica
    */
   private Collection $users;

   public function __construct(
      string $name,
      string $description
   ) {
      parent::__construct();
      $this->name = $name;
      $this->description = $description;
      $this->permissions = new ArrayCollection();
      $this->users = new ArrayCollection();
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

   public function isActive(): bool {
      return $this->isActive === 1;
   }

   // Business Methods
   public function updateDetails(string $name, string $description): void {
      $this->name = $name;
      $this->description = $description;
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
   public function getPermissions(): Collection {
      return $this->permissions;
   }

   public function getUsers(): Collection {
      return $this->users;
   }

   // Business Logic
   public function isSystemRole(): bool {
      return in_array($this->name, [
         'Administrador',
         'Staff VIEX',
         'Coordinador Extensión',
         'Decano',
         'Director'
      ]);
   }

   public function canBeDeleted(): bool {
      return !$this->isSystemRole() && $this->isActive();
   }

   // String representation
   public function __toString(): string {
      return $this->name;
   }
}
