<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Requests;

/**
 * DTO para request de creación de usuario
 */
final class CreateUserRequest {
   private string $username;
   private string $email;
   private string $password;
   private string $firstName;
   private string $lastName;
   private string $cedula;
   private bool $isActive;
   private ?string $professorCode;
   private ?int $mainOrganizationalUnitId;

   public function __construct(
      string $username,
      string $email,
      string $password,
      string $firstName,
      string $lastName,
      string $cedula,
      bool $isActive = true,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null
   ) {
      $this->username = $username;
      $this->email = $email;
      $this->password = $password;
      $this->firstName = $firstName;
      $this->lastName = $lastName;
      $this->cedula = $cedula;
      $this->isActive = $isActive;
      $this->professorCode = $professorCode;
      $this->mainOrganizationalUnitId = $mainOrganizationalUnitId;
   }

   /**
    * Crear desde array de datos
    */
   public static function fromArray(array $data): self {
      return new self(
         (string) ($data['username'] ?? ''),
         (string) ($data['email'] ?? ''),
         (string) ($data['password'] ?? ''),
         (string) ($data['first_name'] ?? ''),
         (string) ($data['last_name'] ?? ''),
         (string) ($data['cedula'] ?? ''),
         (bool) ($data['is_active'] ?? true),
         isset($data['professor_code']) ? (string) $data['professor_code'] : null,
         isset($data['main_organizational_unit_id']) ? (int) $data['main_organizational_unit_id'] : null
      );
   }

   /**
    * Validar datos del request
    */
   public function validate(): array {
      $errors = [];

      if (empty($this->username)) {
         $errors['username'] = 'El nombre de usuario es requerido';
      }

      if (empty($this->email) || !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
         $errors['email'] = 'Email válido es requerido';
      }

      if (empty($this->password) || strlen($this->password) < 8) {
         $errors['password'] = 'Contraseña de al menos 8 caracteres es requerida';
      }

      if (empty($this->firstName)) {
         $errors['first_name'] = 'El nombre es requerido';
      }

      if (empty($this->lastName)) {
         $errors['last_name'] = 'El apellido es requerido';
      }

      if (empty($this->cedula)) {
         $errors['cedula'] = 'La cédula es requerida';
      }

      return $errors;
   }

   /**
    * Verificar si los datos son válidos
    */
   public function isValid(): bool {
      return empty($this->validate());
   }

   // Getters
   public function getUsername(): string {
      return $this->username;
   }
   public function getEmail(): string {
      return $this->email;
   }
   public function getPassword(): string {
      return $this->password;
   }
   public function getFirstName(): string {
      return $this->firstName;
   }
   public function getLastName(): string {
      return $this->lastName;
   }
   public function getCedula(): string {
      return $this->cedula;
   }
   public function isActive(): bool {
      return $this->isActive;
   }
   public function getProfessorCode(): ?string {
      return $this->professorCode;
   }
   public function getMainOrganizationalUnitId(): ?int {
      return $this->mainOrganizationalUnitId;
   }
}
