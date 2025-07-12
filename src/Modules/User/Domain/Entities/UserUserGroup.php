<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Entities;

use App\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_user_groups')]
#[ORM\UniqueConstraint(columns: ['user_id', 'user_group_id', 'organizational_unit_id'])]
class UserUserGroup extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'user_user_groups_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'user_id', type: 'integer')]
   private int $userId;

   #[ORM\Column(name: 'user_group_id', type: 'integer')]
   private int $userGroupId;

   #[ORM\Column(name: 'organizational_unit_id', type: 'integer')]
   private int $organizationalUnitId;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   #[ORM\Column(name: 'assigned_at', type: 'datetime')]
   private \DateTimeInterface $assignedAt;

   #[ORM\Column(name: 'assigned_by', type: 'integer', nullable: true)]
   private ?int $assignedBy = null;

   #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
   private ?\DateTimeInterface $revokedAt = null;

   #[ORM\Column(name: 'revoked_by', type: 'integer', nullable: true)]
   private ?int $revokedBy = null;

   #[ORM\Column(name: 'notes', type: 'text', nullable: true)]
   private ?string $notes = null;

   /**
    * Referencias a entidades relacionadas (sin lazy loading)
    * Estas se cargan mediante repositorios cuando se necesiten
    */
   private ?User $user = null;
   private ?UserGroup $userGroup = null;

   public function __construct(
      int $userId,
      int $userGroupId,
      int $organizationalUnitId,
      ?int $assignedBy = null,
      ?string $notes = null
   ) {
      parent::__construct();
      $this->userId = $userId;
      $this->userGroupId = $userGroupId;
      $this->organizationalUnitId = $organizationalUnitId;
      $this->assignedAt = new \DateTimeImmutable();
      $this->assignedBy = $assignedBy;
      $this->notes = $notes;
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getUserId(): int {
      return $this->userId;
   }

   public function getUserGroupId(): int {
      return $this->userGroupId;
   }

   public function getOrganizationalUnitId(): int {
      return $this->organizationalUnitId;
   }

   public function isActive(): bool {
      return $this->isActive === 1 && $this->revokedAt === null;
   }

   public function getAssignedAt(): \DateTimeInterface {
      return $this->assignedAt;
   }

   public function getAssignedBy(): ?int {
      return $this->assignedBy;
   }

   public function getRevokedAt(): ?\DateTimeInterface {
      return $this->revokedAt;
   }

   public function getRevokedBy(): ?int {
      return $this->revokedBy;
   }

   public function getNotes(): ?string {
      return $this->notes;
   }

   // Business Methods
   public function revoke(?int $revokedBy = null, ?string $reason = null): void {
      $this->isActive = 0;
      $this->revokedAt = new \DateTimeImmutable();
      $this->revokedBy = $revokedBy;

      if ($reason) {
         $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Revocado: " . $reason;
      }

      $this->touch();
   }

   public function reactivate(?int $assignedBy = null, ?string $reason = null): void {
      $this->isActive = 1;
      $this->revokedAt = null;
      $this->revokedBy = null;
      $this->assignedBy = $assignedBy;

      if ($reason) {
         $this->notes = ($this->notes ? $this->notes . "\n" : '') . "Reactivado: " . $reason;
      }

      $this->touch();
   }

   public function updateNotes(string $notes): void {
      $this->notes = $notes;
      $this->touch();
   }

   public function addNote(string $note): void {
      $timestamp = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
      $formattedNote = "[{$timestamp}] {$note}";

      $this->notes = $this->notes
         ? $this->notes . "\n" . $formattedNote
         : $formattedNote;

      $this->touch();
   }

   // Entity references (loaded via repositories when needed)
   public function getUser(): ?User {
      return $this->user;
   }

   public function setUser(User $user): void {
      $this->user = $user;
   }

   public function getUserGroup(): ?UserGroup {
      return $this->userGroup;
   }

   public function setUserGroup(UserGroup $userGroup): void {
      $this->userGroup = $userGroup;
   }

   // Business Logic
   public function isValidAssignment(): bool {
      return $this->userId > 0 &&
         $this->userGroupId > 0 &&
         $this->organizationalUnitId > 0;
   }

   public function hasBeenRevoked(): bool {
      return $this->revokedAt !== null;
   }

   public function getAssignmentDuration(): ?\DateInterval {
      if ($this->revokedAt) {
         return $this->assignedAt->diff($this->revokedAt);
      }

      return $this->assignedAt->diff(new \DateTimeImmutable());
   }

   // String representation
   public function __toString(): string {
      $status = $this->isActive() ? 'Activo' : 'Inactivo';
      return "User:{$this->userId} -> Group:{$this->userGroupId} @ Unit:{$this->organizationalUnitId} ({$status})";
   }
}
