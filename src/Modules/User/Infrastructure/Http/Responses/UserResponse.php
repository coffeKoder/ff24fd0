<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Responses;

/**
 * DTO para respuesta de usuario
 */
final class UserResponse {
   private string $status;
   private string $message;
   private array $userData;
   private array $errors;

   public function __construct(
      string $status,
      string $message,
      array $userData = [],
      array $errors = []
   ) {
      $this->status = $status;
      $this->message = $message;
      $this->userData = $userData;
      $this->errors = $errors;
   }

   /**
    * Crear respuesta exitosa
    */
   public static function success(string $message, array $userData = []): self {
      return new self('success', $message, $userData);
   }

   /**
    * Crear respuesta de error
    */
   public static function error(string $message, array $errors = []): self {
      return new self('error', $message, [], $errors);
   }

   /**
    * Convertir a array
    */
   public function toArray(): array {
      $data = [
         'status' => $this->status,
         'message' => $this->message
      ];

      if (!empty($this->userData)) {
         $data['data'] = $this->userData;
      }

      if (!empty($this->errors)) {
         $data['errors'] = $this->errors;
      }

      return $data;
   }

   // Getters
   public function getStatus(): string {
      return $this->status;
   }
   public function getMessage(): string {
      return $this->message;
   }
   public function getUserData(): array {
      return $this->userData;
   }
   public function getErrors(): array {
      return $this->errors;
   }
}
