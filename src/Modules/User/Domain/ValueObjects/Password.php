<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para representar una contraseña
 */
final class Password {
   private string $value;

   private function __construct(string $value) {
      $this->validate($value);
      $this->value = $value;
   }

   public static function fromString(string $password): self {
      return new self($password);
   }

   public static function fromHash(string $hashedPassword): self {
      // Para contraseñas ya hasheadas, no validamos longitud
      $instance = new self('dummy');
      $instance->value = $hashedPassword;
      return $instance;
   }

   public function getValue(): string {
      return $this->value;
   }

   public function equals(Password $other): bool {
      return hash_equals($this->value, $other->value);
   }

   public function isHashed(): bool {
      // Verificar si ya está hasheada (formato típico de password_hash)
      return strlen($this->value) >= 60 && str_starts_with($this->value, '$');
   }

   public function getStrength(): string {
      if ($this->isHashed()) {
         return 'hashed';
      }

      $score = 0;
      $length = strlen($this->value);

      // Longitud
      if ($length >= 8)
         $score++;
      if ($length >= 12)
         $score++;
      if ($length >= 16)
         $score++;

      // Caracteres
      if (preg_match('/[a-z]/', $this->value))
         $score++;
      if (preg_match('/[A-Z]/', $this->value))
         $score++;
      if (preg_match('/\d/', $this->value))
         $score++;
      if (preg_match('/[^a-zA-Z\d]/', $this->value))
         $score++;

      if ($score <= 3)
         return 'débil';
      if ($score <= 5)
         return 'media';
      return 'fuerte';
   }

   public function isSecure(): bool {
      if ($this->isHashed()) {
         return true; // Asumimos que las contraseñas hasheadas son seguras
      }

      return $this->getStrength() !== 'débil';
   }

   private function validate(string $password): void {
      if (empty($password)) {
         throw new InvalidArgumentException('La contraseña no puede estar vacía');
      }

      // Solo validamos contraseñas en texto plano
      if (!str_starts_with($password, '$') && strlen($password) < 60) {
         if (strlen($password) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres');
         }

         if (strlen($password) > 128) {
            throw new InvalidArgumentException('La contraseña es demasiado larga (máximo 128 caracteres)');
         }

         // Verificar que no sea una contraseña común
         $commonPasswords = [
            'password', '123456', '12345678', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', '123123'
         ];

         if (in_array(strtolower($password), $commonPasswords, true)) {
            throw new InvalidArgumentException('La contraseña es demasiado común');
         }
      }
   }

   // No implementamos __toString() por seguridad
}
