<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Entities;

use Viex\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_group_permissions')]
#[ORM\UniqueConstraint(columns: ['user_group_id', 'permission_id'])]
class UserGroupPermission extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'user_group_permissions_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'user_group_id', type: 'integer')]
   private int $userGroupId;

   #[ORM\Column(name: 'permission_id', type: 'integer')]
   private int $permissionId;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   #[ORM\Column(name: 'granted_at', type: 'datetime')]
   private \DateTimeInterface $grantedAt;

   #[ORM\Column(name: 'granted_by', type: 'integer', nullable: true)]
   private ?int $grantedBy = null;

   #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
   private ?\DateTimeInterface $revokedAt = null;

   #[ORM\Column(name: 'revoked_by', type: 'integer', nullable: true)]
   private ?int $revokedBy = null;

   /**
    * Referencias a entidades relacionadas (sin lazy loading)
    */
   private ?UserGroup $userGroup = null;
   private ?Permission $permission = null;

   public function __construct(
      int $userGroupId,
      int $permissionId,
      ?int $grantedBy = null
   ) {
      parent::__construct();
      $this->userGroupId = $userGroupId;
      $this->permissionId = $permissionId;
      $this->grantedAt = new \DateTimeImmutable();
      $this->grantedBy = $grantedBy;
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getUserGroupId(): int {
      return $this->userGroupId;
   }

   public function getPermissionId(): int {
      return $this->permissionId;
   }

   public function isActive(): bool {
      return $this->isActive === 1 && $this->revokedAt === null;
   }

   public function getGrantedAt(): \DateTimeInterface {
      return $this->grantedAt;
   }

   public function getGrantedBy(): ?int {
      return $this->grantedBy;
   }

   public function getRevokedAt(): ?\DateTimeInterface {
      return $this->revokedAt;
   }

   public function getRevokedBy(): ?int {
      return $this->revokedBy;
   }

   // Business Methods
   public function revoke(?int $revokedBy = null): void {
      $this->isActive = 0;
      $this->revokedAt = new \DateTimeImmutable();
      $this->revokedBy = $revokedBy;
      $this->touch();
   }

   public function restore(?int $grantedBy = null): void {
      $this->isActive = 1;
      $this->revokedAt = null;
      $this->revokedBy = null;
      $this->grantedBy = $grantedBy ?? $this->grantedBy;
      $this->touch();
   }

   // Entity references (loaded via repositories when needed)
   public function getUserGroup(): ?UserGroup {
      return $this->userGroup;
   }

   public function setUserGroup(UserGroup $userGroup): void {
      $this->userGroup = $userGroup;
   }

   public function getPermission(): ?Permission {
      return $this->permission;
   }

   public function setPermission(Permission $permission): void {
      $this->permission = $permission;
   }

   // Business Logic
   public function isValidGrant(): bool {
      return $this->userGroupId > 0 && $this->permissionId > 0;
   }

   public function hasBeenRevoked(): bool {
      return $this->revokedAt !== null;
   }

   public function getGrantDuration(): ?\DateInterval {
      if ($this->revokedAt) {
         return $this->grantedAt->diff($this->revokedAt);
      }

      return $this->grantedAt->diff(new \DateTimeImmutable());
   }

   // String representation
   public function __toString(): string {
      $status = $this->isActive() ? 'Activo' : 'Revocado';
      return "Group:{$this->userGroupId} -> Permission:{$this->permissionId} ({$status})";
   }
}
