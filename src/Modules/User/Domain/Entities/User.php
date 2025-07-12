<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Entities;

use App\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'users_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'username', type: 'string', length: 100, unique: true)]
   private string $username;

   #[ORM\Column(name: 'email', type: 'string', length: 255, unique: true)]
   private string $email;

   #[ORM\Column(name: 'password_hash', type: 'string', length: 255)]
   private string $passwordHash;

   #[ORM\Column(name: 'first_name', type: 'string', length: 100)]
   private string $firstName;

   #[ORM\Column(name: 'last_name', type: 'string', length: 100)]
   private string $lastName;

   #[ORM\Column(name: 'cedula', type: 'string', length: 20, unique: true)]
   private string $cedula;

   #[ORM\Column(name: 'professor_code', type: 'string', length: 50, nullable: true)]
   private ?string $professorCode = null;

   #[ORM\Column(name: 'office_phone', type: 'string', length: 20, nullable: true)]
   private ?string $officePhone = null;

   #[ORM\Column(name: 'main_organizational_unit_id', type: 'integer', nullable: true)]
   private ?int $mainOrganizationalUnitId = null;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   #[ORM\Column(name: 'last_login_at', type: 'datetime', nullable: true)]
   private ?\DateTimeInterface $lastLoginAt = null;

   #[ORM\Column(name: 'email_verified_at', type: 'datetime', nullable: true)]
   private ?\DateTimeInterface $emailVerifiedAt = null;

   /**
    * Relación Many-to-Many con UserGroup a través de user_user_groups
    * NO cargamos la colección para evitar redundancia cíclica
    */
   private Collection $userGroups;

   public function __construct(
      string $username,
      string $email,
      string $passwordHash,
      string $firstName,
      string $lastName,
      string $cedula,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null
   ) {
      parent::__construct();
      $this->username = $username;
      $this->email = $email;
      $this->passwordHash = $passwordHash;
      $this->firstName = $firstName;
      $this->lastName = $lastName;
      $this->cedula = $cedula;
      $this->professorCode = $professorCode;
      $this->mainOrganizationalUnitId = $mainOrganizationalUnitId;
      $this->userGroups = new ArrayCollection();
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getUsername(): string {
      return $this->username;
   }

   public function getEmail(): string {
      return $this->email;
   }

   public function getPasswordHash(): string {
      return $this->passwordHash;
   }

   public function getFirstName(): string {
      return $this->firstName;
   }

   public function getLastName(): string {
      return $this->lastName;
   }

   public function getFullName(): string {
      return trim($this->firstName . ' ' . $this->lastName);
   }

   public function getCedula(): string {
      return $this->cedula;
   }

   public function getProfessorCode(): ?string {
      return $this->professorCode;
   }

   public function getOfficePhone(): ?string {
      return $this->officePhone;
   }

   public function getMainOrganizationalUnitId(): ?int {
      return $this->mainOrganizationalUnitId;
   }

   public function isActive(): bool {
      return $this->isActive === 1;
   }

   public function getLastLoginAt(): ?\DateTimeInterface {
      return $this->lastLoginAt;
   }

   public function getEmailVerifiedAt(): ?\DateTimeInterface {
      return $this->emailVerifiedAt;
   }

   public function isEmailVerified(): bool {
      return $this->emailVerifiedAt !== null;
   }

   // Business Methods
   public function changePassword(string $newPasswordHash): void {
      $this->passwordHash = $newPasswordHash;
      $this->touch();
   }

   public function updateProfile(
      string $firstName,
      string $lastName,
      ?string $officePhone = null
   ): void {
      $this->firstName = $firstName;
      $this->lastName = $lastName;
      $this->officePhone = $officePhone;
      $this->touch();
   }

   public function updateEmail(string $newEmail): void {
      $this->email = $newEmail;
      $this->emailVerifiedAt = null; // Reset verification when email changes
      $this->touch();
   }

   public function verifyEmail(): void {
      $this->emailVerifiedAt = new \DateTimeImmutable();
      $this->touch();
   }

   public function recordLogin(): void {
      $this->lastLoginAt = new \DateTimeImmutable();
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

   public function assignToOrganizationalUnit(int $organizationalUnitId): void {
      $this->mainOrganizationalUnitId = $organizationalUnitId;
      $this->touch();
   }

   public function setProfessorCode(string $professorCode): void {
      $this->professorCode = $professorCode;
      $this->touch();
   }

   // Collection Management (sin lazy loading para evitar redundancia)
   public function getUserGroups(): Collection {
      return $this->userGroups;
   }

   // Validation Methods
   public function canLogin(): bool {
      return $this->isActive() && $this->isEmailVerified();
   }

   public function isProfessor(): bool {
      return $this->professorCode !== null;
   }

   // String representation
   public function __toString(): string {
      return $this->getFullName() . ' (' . $this->email . ')';
   }
}
