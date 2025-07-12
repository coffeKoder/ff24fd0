<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Responses;

/**
 * DTO para respuesta de autenticación
 */
final class AuthResponse {
   private string $status;
   private string $message;
   private array $userData;
   private ?string $token;

   public function __construct(
      string $status,
      string $message,
      array $userData,
      ?string $token = null
   ) {
      $this->status = $status;
      $this->message = $message;
      $this->userData = $userData;
      $this->token = $token;
   }

   /**
    * Crear respuesta exitosa
    */
   public static function success(array $userData, ?string $token = null): self {
      return new self('success', 'Autenticación exitosa', $userData, $token);
   }

   /**
    * Crear respuesta de error
    */
   public static function error(string $message): self {
      return new self('error', $message, []);
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
         $data['data'] = ['user' => $this->userData];

         if ($this->token) {
            $data['data']['token'] = $this->token;
         }
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
   public function getToken(): ?string {
      return $this->token;
   }
}
