<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para representar una dirección de email válida
 */
final class Email {
   private string $value;

   private function __construct(string $value) {
      $this->validate($value);
      $this->value = strtolower(trim($value));
   }

   public static function fromString(string $email): self {
      return new self($email);
   }

   public function getValue(): string {
      return $this->value;
   }

   public function equals(Email $other): bool {
      return $this->value === $other->value;
   }

   public function getDomain(): string {
      return substr($this->value, strpos($this->value, '@') + 1);
   }

   public function getLocalPart(): string {
      return substr($this->value, 0, strpos($this->value, '@'));
   }

   public function isUniversityEmail(): bool {
      $universitaryDomains = [
         'up.ac.pa',
         'estudiante.up.ac.pa',
         'docente.up.ac.pa'
      ];

      return in_array($this->getDomain(), $universitaryDomains, true);
   }

   private function validate(string $email): void {
      if (empty($email)) {
         throw new InvalidArgumentException('El email no puede estar vacío');
      }

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
         throw new InvalidArgumentException('El formato del email no es válido');
      }

      if (strlen($email) > 255) {
         throw new InvalidArgumentException('El email es demasiado largo (máximo 255 caracteres)');
      }
   }

   public function __toString(): string {
      return $this->value;
   }
}
