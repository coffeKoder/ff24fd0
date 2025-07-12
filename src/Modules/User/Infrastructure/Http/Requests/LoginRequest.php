<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Requests;

/**
 * DTO para request de login
 */
final class LoginRequest {
   private string $identifier;
   private string $password;
   private bool $remember;

   public function __construct(string $identifier, string $password, bool $remember = false) {
      $this->identifier = $identifier;
      $this->password = $password;
      $this->remember = $remember;
   }

   public function getIdentifier(): string {
      return $this->identifier;
   }

   public function getPassword(): string {
      return $this->password;
   }

   public function isRemember(): bool {
      return $this->remember;
   }

   /**
    * Crear desde array de datos
    */
   public static function fromArray(array $data): self {
      return new self(
         (string) ($data['identifier'] ?? ''),
         (string) ($data['password'] ?? ''),
         (bool) ($data['remember'] ?? false)
      );
   }

   /**
    * Validar datos del request
    */
   public function validate(): array {
      $errors = [];

      if (empty($this->identifier)) {
         $errors['identifier'] = 'El identificador es requerido';
      }

      if (empty($this->password)) {
         $errors['password'] = 'La contraseña es requerida';
      }

      return $errors;
   }

   /**
    * Verificar si los datos son válidos
    */
   public function isValid(): bool {
      return empty($this->validate());
   }
}
