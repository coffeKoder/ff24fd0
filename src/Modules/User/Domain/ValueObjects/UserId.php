<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Value Object para representar un ID de usuario
 */
final class UserId {
   private int $value;

   private function __construct(int $value) {
      $this->validate($value);
      $this->value = $value;
   }

   public static function fromInt(int $id): self {
      return new self($id);
   }

   public static function fromString(string $id): self {
      if (!is_numeric($id)) {
         throw new InvalidArgumentException('El ID de usuario debe ser numérico');
      }

      return new self((int) $id);
   }

   public function getValue(): int {
      return $this->value;
   }

   public function equals(UserId $other): bool {
      return $this->value === $other->value;
   }

   public function isValid(): bool {
      return $this->value > 0;
   }

   private function validate(int $id): void {
      if ($id <= 0) {
         throw new InvalidArgumentException('El ID de usuario debe ser mayor que 0');
      }

      if ($id > PHP_INT_MAX) {
         throw new InvalidArgumentException('El ID de usuario es demasiado grande');
      }
   }

   public function __toString(): string {
      return (string) $this->value;
   }
}
