<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\ValueObjects;

/**
 * Value Object para representar credenciales de usuario
 */
final class Credentials {
   private Email $email;
   private Password $password;

   public function __construct(Email $email, Password $password) {
      $this->email = $email;
      $this->password = $password;
   }

   public static function fromStrings(string $email, string $password): self {
      return new self(
         Email::fromString($email),
         Password::fromString($password)
      );
   }

   public function getEmail(): Email {
      return $this->email;
   }

   public function getPassword(): Password {
      return $this->password;
   }

   public function equals(Credentials $other): bool {
      return $this->email->equals($other->email) &&
         $this->password->equals($other->password);
   }

   public function __toString(): string {
      return $this->email->getValue();
   }
}
