<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\DTOs;

use Viex\Modules\User\Domain\Entities\User;

/**
 * DTO para transferir información de usuario entre capas
 * 
 * Representa la información pública de un usuario sin exponer
 * detalles internos del dominio
 */
final class UserDTO {
   public int $id;
   public string $username;
   public string $email;
   public string $firstName;
   public string $lastName;
   public string $cedula;
   public bool $isActive;
   public ?string $lastLoginDate;
   public bool $emailVerified;
   public ?string $emailVerifiedAt;
   public ?string $professorCode;
   public ?int $mainOrganizationalUnitId;
   public ?\DateTimeImmutable $createdAt;
   public ?\DateTimeImmutable $updatedAt;

   public function __construct(
      int $id,
      string $username,
      string $email,
      string $firstName,
      string $lastName,
      string $cedula,
      bool $isActive,
      ?string $lastLoginDate = null,
      bool $emailVerified = false,
      ?string $emailVerifiedAt = null,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null,
      ?\DateTimeImmutable $createdAt = null,
      ?\DateTimeImmutable $updatedAt = null
   ) {
      $this->id = $id;
      $this->username = $username;
      $this->email = $email;
      $this->firstName = $firstName;
      $this->lastName = $lastName;
      $this->cedula = $cedula;
      $this->isActive = $isActive;
      $this->lastLoginDate = $lastLoginDate;
      $this->emailVerified = $emailVerified;
      $this->emailVerifiedAt = $emailVerifiedAt;
      $this->professorCode = $professorCode;
      $this->mainOrganizationalUnitId = $mainOrganizationalUnitId;
      $this->createdAt = $createdAt;
      $this->updatedAt = $updatedAt;
   }

   /**
    * Crea un UserDTO desde una entidad User
    */
   public static function fromEntity(User $user): self {
      return new self(
         $user->getId(),
         $user->getUsername(),
         $user->getEmail(),
         $user->getFirstName(),
         $user->getLastName(),
         $user->getCedula(),
         $user->isActive(),
         $user->getLastLoginAt()?->format('Y-m-d H:i:s'),
         $user->isEmailVerified(),
         $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
         $user->getProfessorCode(),
         $user->getMainOrganizationalUnitId(),
         $user->getCreatedAt(),
         $user->getUpdatedAt()
      );
   }

   /**
    * Convierte múltiples entidades User en DTOs
    */
   public static function fromEntities(array $users): array {
      return array_map(
         fn(User $user) => self::fromEntity($user),
         $users
      );
   }

   /**
    * Convierte el DTO a array
    */
   public function toArray(): array {
      return [
         'id' => $this->id,
         'username' => $this->username,
         'email' => $this->email,
         'firstName' => $this->firstName,
         'lastName' => $this->lastName,
         'cedula' => $this->cedula,
         'isActive' => $this->isActive,
         'lastLoginDate' => $this->lastLoginDate,
         'emailVerified' => $this->emailVerified,
         'emailVerifiedAt' => $this->emailVerifiedAt,
         'professorCode' => $this->professorCode,
         'mainOrganizationalUnitId' => $this->mainOrganizationalUnitId,
         'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
         'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s')
      ];
   }

   /**
    * Obtiene el nombre completo del usuario
    */
   public function getFullName(): string {
      return trim($this->firstName . ' ' . $this->lastName);
   }

   /**
    * Obtiene información pública básica del usuario
    */
   public function getPublicInfo(): array {
      return [
         'id' => $this->id,
         'username' => $this->username,
         'fullName' => $this->getFullName(),
         'isActive' => $this->isActive,
         'professorCode' => $this->professorCode
      ];
   }

   /**
    * Verifica si el usuario es un profesor
    */
   public function isProfessor(): bool {
      return !empty($this->professorCode);
   }

   /**
    * Verifica si el email del usuario está verificado
    */
   public function hasVerifiedEmail(): bool {
      return $this->emailVerified && !empty($this->emailVerifiedAt);
   }

   /**
    * Obtiene el tiempo desde el último login
    */
   public function getTimeSinceLastLogin(): ?string {
      if (!$this->lastLoginDate) {
         return null;
      }

      $lastLogin = new \DateTimeImmutable($this->lastLoginDate);
      $now = new \DateTimeImmutable();
      $interval = $now->diff($lastLogin);

      if ($interval->days > 0) {
         return $interval->days . ' días';
      } elseif ($interval->h > 0) {
         return $interval->h . ' horas';
      } elseif ($interval->i > 0) {
         return $interval->i . ' minutos';
      } else {
         return 'Hace un momento';
      }
   }
}
