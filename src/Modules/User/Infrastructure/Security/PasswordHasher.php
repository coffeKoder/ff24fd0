<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Security;

/**
 * Servicio para hash y verificación segura de contraseñas
 * Implementa las mejores prácticas de seguridad para passwords
 */
class PasswordHasher {
   private const MIN_PASSWORD_LENGTH = 8;
   private const MAX_PASSWORD_LENGTH = 128;

   private array $strengthCriteria = [
      'minLength' => 8,
      'requireUppercase' => true,
      'requireLowercase' => true,
      'requireNumbers' => true,
      'requireSpecialChars' => true,
      'blacklistedWords' => ['password', 'admin', 'user', 'viex', 'universidad']
   ];

   /**
    * Hash una contraseña usando PHP's password_hash con PASSWORD_DEFAULT
    */
   public function hash(string $password): string {
      if (empty(trim($password))) {
         throw new \InvalidArgumentException('La contraseña no puede estar vacía');
      }

      if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
         throw new \InvalidArgumentException(
            sprintf('La contraseña debe tener al menos %d caracteres', self::MIN_PASSWORD_LENGTH)
         );
      }

      if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
         throw new \InvalidArgumentException(
            sprintf('La contraseña no puede exceder %d caracteres', self::MAX_PASSWORD_LENGTH)
         );
      }

      // PHP's password_hash usa salt automático y seguro
      $hash = password_hash($password, PASSWORD_DEFAULT);

      if ($hash === false) {
         throw new \RuntimeException('Error al generar hash de contraseña');
      }

      return $hash;
   }

   /**
    * Verifica una contraseña contra su hash
    */
   public function verify(string $password, string $hash): bool {
      if (empty(trim($password)) || empty(trim($hash))) {
         return false;
      }

      return password_verify($password, $hash);
   }

   /**
    * Verifica si un hash necesita ser rehashed (por cambio de algoritmo)
    */
   public function needsRehash(string $hash): bool {
      return password_needs_rehash($hash, PASSWORD_DEFAULT);
   }

   /**
    * Valida la fortaleza de una contraseña
    */
   public function validatePasswordStrength(string $password): array {
      $errors = [];
      $warnings = [];

      // Longitud mínima
      if (strlen($password) < $this->strengthCriteria['minLength']) {
         $errors[] = sprintf(
            'La contraseña debe tener al menos %d caracteres',
            $this->strengthCriteria['minLength']
         );
      }

      // Longitud máxima
      if (strlen($password) > self::MAX_PASSWORD_LENGTH) {
         $errors[] = sprintf(
            'La contraseña no puede exceder %d caracteres',
            self::MAX_PASSWORD_LENGTH
         );
      }

      // Mayúsculas
      if ($this->strengthCriteria['requireUppercase'] && !preg_match('/[A-Z]/', $password)) {
         $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
      }

      // Minúsculas
      if ($this->strengthCriteria['requireLowercase'] && !preg_match('/[a-z]/', $password)) {
         $errors[] = 'La contraseña debe contener al menos una letra minúscula';
      }

      // Números
      if ($this->strengthCriteria['requireNumbers'] && !preg_match('/[0-9]/', $password)) {
         $errors[] = 'La contraseña debe contener al menos un número';
      }

      // Caracteres especiales
      if ($this->strengthCriteria['requireSpecialChars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
         $errors[] = 'La contraseña debe contener al menos un carácter especial (!@#$%^&*()_+-=[]{}|;:,.<>?)';
      }

      // Palabras prohibidas
      $passwordLower = strtolower($password);
      foreach ($this->strengthCriteria['blacklistedWords'] as $blacklisted) {
         if (str_contains($passwordLower, strtolower($blacklisted))) {
            $errors[] = 'La contraseña no puede contener palabras comunes o relacionadas al sistema';
            break;
         }
      }

      // Patrones débiles
      if (preg_match('/(.)\1{2,}/', $password)) {
         $warnings[] = 'La contraseña contiene caracteres repetidos consecutivos';
      }

      if (preg_match('/123|abc|qwerty|password/i', $password)) {
         $errors[] = 'La contraseña contiene secuencias comunes prohibidas';
      }

      // Verificar que no sea solo números o letras
      if (preg_match('/^[0-9]+$/', $password)) {
         $errors[] = 'La contraseña no puede ser solo números';
      }

      if (preg_match('/^[A-Za-z]+$/', $password)) {
         $warnings[] = 'La contraseña solo contiene letras, considere agregar números o símbolos';
      }

      return [
         'isValid' => empty($errors),
         'errors' => $errors,
         'warnings' => $warnings,
         'strength' => $this->calculatePasswordStrength($password)
      ];
   }

   /**
    * Calcula la fortaleza de una contraseña (0-100)
    */
   public function calculatePasswordStrength(string $password): int {
      $score = 0;
      $length = strlen($password);

      // Longitud (max 25 puntos)
      if ($length >= 8)
         $score += 10;
      if ($length >= 12)
         $score += 10;
      if ($length >= 16)
         $score += 5;

      // Variedad de caracteres (max 40 puntos)
      if (preg_match('/[a-z]/', $password))
         $score += 10;
      if (preg_match('/[A-Z]/', $password))
         $score += 10;
      if (preg_match('/[0-9]/', $password))
         $score += 10;
      if (preg_match('/[^A-Za-z0-9]/', $password))
         $score += 10;

      // Complejidad adicional (max 35 puntos)
      if ($length >= 20)
         $score += 10;
      if (preg_match('/[A-Z].*[A-Z]/', $password))
         $score += 5;
      if (preg_match('/[0-9].*[0-9]/', $password))
         $score += 5;
      if (preg_match('/[^A-Za-z0-9].*[^A-Za-z0-9]/', $password))
         $score += 5;

      // Variedad en posiciones
      $hasUpperInMiddle = preg_match('/^.+[A-Z].+$/', $password);
      $hasNumberInMiddle = preg_match('/^.+[0-9].+$/', $password);
      if ($hasUpperInMiddle || $hasNumberInMiddle)
         $score += 5;

      // Patrones mixtos
      if (preg_match('/[A-Za-z][0-9]/', $password) || preg_match('/[0-9][A-Za-z]/', $password)) {
         $score += 5;
      }

      // Penalizaciones
      if (preg_match('/(.)\1{2,}/', $password))
         $score -= 10; // Repeticiones
      if (preg_match('/123|abc|qwerty/i', $password))
         $score -= 15; // Secuencias
      if (preg_match('/^[0-9]+$/', $password))
         $score -= 20; // Solo números

      return max(0, min(100, $score));
   }

   /**
    * Genera una contraseña aleatoria segura
    */
   public function generateSecurePassword(int $length = 12): string {
      if ($length < self::MIN_PASSWORD_LENGTH) {
         $length = self::MIN_PASSWORD_LENGTH;
      }

      if ($length > self::MAX_PASSWORD_LENGTH) {
         $length = self::MAX_PASSWORD_LENGTH;
      }

      $lowercase = 'abcdefghijklmnopqrstuvwxyz';
      $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $numbers = '0123456789';
      $specialChars = '!@#$%^&*()_+-=[]{}|;:,.<>?';

      // Asegurar que tenga al menos un carácter de cada tipo
      $password = '';
      $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
      $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
      $password .= $numbers[random_int(0, strlen($numbers) - 1)];
      $password .= $specialChars[random_int(0, strlen($specialChars) - 1)];

      // Completar el resto de la longitud
      $allChars = $lowercase . $uppercase . $numbers . $specialChars;
      for ($i = 4; $i < $length; $i++) {
         $password .= $allChars[random_int(0, strlen($allChars) - 1)];
      }

      // Mezclar los caracteres para que no siga un patrón predecible
      return str_shuffle($password);
   }

   /**
    * Configura los criterios de fortaleza de contraseña
    */
   public function setStrengthCriteria(array $criteria): void {
      $this->strengthCriteria = array_merge($this->strengthCriteria, $criteria);
   }

   /**
    * Obtiene los criterios de fortaleza actuales
    */
   public function getStrengthCriteria(): array {
      return $this->strengthCriteria;
   }

   /**
    * Verifica si una contraseña cumple con los criterios mínimos
    */
   public function isSecurePassword(string $password): bool {
      $validation = $this->validatePasswordStrength($password);
      return $validation['isValid'] && $validation['strength'] >= 60;
   }

   /**
    * Obtiene información sobre el algoritmo de hash usado
    */
   public function getHashInfo(string $hash): array {
      $info = password_get_info($hash);

      return [
         'algorithm' => $info['algo'],
         'algorithmName' => $info['algoName'],
         'options' => $info['options'] ?? []
      ];
   }

   /**
    * Limpia los datos sensibles de memoria (best effort)
    */
   public function clearSensitiveData(string &$password): void {
      if (function_exists('sodium_memzero')) {
         sodium_memzero($password);
      } else {
         // Fallback: sobreescribir con datos aleatorios
         $length = strlen($password);
         for ($i = 0; $i < $length; $i++) {
            $password[$i] = chr(random_int(0, 255));
         }
         $password = '';
      }
   }
}
