<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Security;

/**
 * Generador de tokens seguros para diferentes propósitos
 * Utiliza funciones criptográficamente seguras
 */
class TokenGenerator {
   // Longitudes por defecto para diferentes tipos de tokens
   private const PASSWORD_RESET_TOKEN_LENGTH = 64;
   private const EMAIL_VERIFICATION_TOKEN_LENGTH = 32;
   private const API_TOKEN_LENGTH = 40;
   private const SESSION_TOKEN_LENGTH = 32;

   // Alfabetos para diferentes tipos de tokens
   private const ALPHA_NUMERIC = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
   private const ALPHA_NUMERIC_SAFE = '23456789ABCDEFGHJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz'; // Sin 0,O,1,l,I
   private const HEX_CHARS = '0123456789abcdef';
   private const URL_SAFE_CHARS = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-_';

   /**
    * Genera un token para recuperación de contraseña
    */
   public function generatePasswordResetToken(): string {
      return $this->generateSecureToken(self::PASSWORD_RESET_TOKEN_LENGTH, self::ALPHA_NUMERIC);
   }

   /**
    * Genera un token para verificación de email
    */
   public function generateEmailVerificationToken(): string {
      return $this->generateSecureToken(self::EMAIL_VERIFICATION_TOKEN_LENGTH, self::ALPHA_NUMERIC_SAFE);
   }

   /**
    * Genera un token de API
    */
   public function generateApiToken(): string {
      return $this->generateSecureToken(self::API_TOKEN_LENGTH, self::URL_SAFE_CHARS);
   }

   /**
    * Genera un token de sesión
    */
   public function generateSessionToken(): string {
      return $this->generateSecureToken(self::SESSION_TOKEN_LENGTH, self::HEX_CHARS);
   }

   /**
    * Genera un token de propósito general
    */
   public function generateToken(int $length = 32, string $alphabet = self::ALPHA_NUMERIC): string {
      return $this->generateSecureToken($length, $alphabet);
   }

   /**
    * Genera un token hexadecimal
    */
   public function generateHexToken(int $length = 32): string {
      if ($length % 2 !== 0) {
         throw new \InvalidArgumentException('La longitud del token hexadecimal debe ser par');
      }

      $bytes = random_bytes($length / 2);
      return bin2hex($bytes);
   }

   /**
    * Genera un token URL-safe (base64url)
    */
   public function generateUrlSafeToken(int $byteLength = 32): string {
      $bytes = random_bytes($byteLength);
      return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
   }

   /**
    * Genera un UUID v4
    */
   public function generateUuid(): string {
      $bytes = random_bytes(16);

      // Establecer bits de versión (4) y variante
      $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // Version 4
      $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // Variant 10

      return sprintf(
         '%08s-%04s-%04s-%04s-%12s',
         bin2hex(substr($bytes, 0, 4)),
         bin2hex(substr($bytes, 4, 2)),
         bin2hex(substr($bytes, 6, 2)),
         bin2hex(substr($bytes, 8, 2)),
         bin2hex(substr($bytes, 10, 6))
      );
   }

   /**
    * Genera un código numérico (PIN) de verificación
    */
   public function generateNumericCode(int $length = 6): string {
      if ($length < 4 || $length > 10) {
         throw new \InvalidArgumentException('La longitud del código debe estar entre 4 y 10 dígitos');
      }

      $code = '';
      for ($i = 0; $i < $length; $i++) {
         $code .= random_int(0, 9);
      }

      return $code;
   }

   /**
    * Genera un token con tiempo de expiración embebido
    */
   public function generateTimestampedToken(int $expirationMinutes = 60): array {
      $expiresAt = time() + ($expirationMinutes * 60);
      $payload = $expiresAt . '|' . $this->generateSecureToken(32, self::ALPHA_NUMERIC);
      $signature = hash_hmac('sha256', $payload, $this->getSigningKey());

      $token = base64_encode($payload . '|' . $signature);

      return [
         'token' => $token,
         'expiresAt' => $expiresAt,
         'expiresAtFormatted' => date('Y-m-d H:i:s', $expiresAt)
      ];
   }

   /**
    * Valida un token con timestamp embebido
    */
   public function validateTimestampedToken(string $token): array {
      try {
         $decoded = base64_decode($token, true);
         if ($decoded === false) {
            return ['valid' => false, 'reason' => 'Token malformado'];
         }

         $parts = explode('|', $decoded);
         if (count($parts) !== 3) {
            return ['valid' => false, 'reason' => 'Estructura de token inválida'];
         }

         [$timestamp, $randomPart, $signature] = $parts;
         $payload = $timestamp . '|' . $randomPart;
         $expectedSignature = hash_hmac('sha256', $payload, $this->getSigningKey());

         // Verificación de firma con comparación segura
         if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'reason' => 'Firma inválida'];
         }

         $expiresAt = (int) $timestamp;
         if (time() > $expiresAt) {
            return [
               'valid' => false,
               'reason' => 'Token expirado',
               'expiredAt' => date('Y-m-d H:i:s', $expiresAt)
            ];
         }

         return [
            'valid' => true,
            'expiresAt' => $expiresAt,
            'timeRemaining' => $expiresAt - time()
         ];

      } catch (\Exception $e) {
         return ['valid' => false, 'reason' => 'Error al validar token: ' . $e->getMessage()];
      }
   }

   /**
    * Genera un token de invitación con metadata
    */
   public function generateInvitationToken(array $metadata = []): string {
      $tokenData = [
         'type' => 'invitation',
         'created' => time(),
         'metadata' => $metadata,
         'nonce' => $this->generateSecureToken(16, self::ALPHA_NUMERIC)
      ];

      $payload = base64_encode(json_encode($tokenData, JSON_THROW_ON_ERROR));
      $signature = hash_hmac('sha256', $payload, $this->getSigningKey());

      return $payload . '.' . base64_encode($signature);
   }

   /**
    * Valida y extrae metadata de un token de invitación
    */
   public function validateInvitationToken(string $token): array {
      try {
         $parts = explode('.', $token);
         if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'Formato de token inválido'];
         }

         [$payload, $encodedSignature] = $parts;
         $signature = base64_decode($encodedSignature, true);
         if ($signature === false) {
            return ['valid' => false, 'reason' => 'Firma malformada'];
         }

         $expectedSignature = hash_hmac('sha256', $payload, $this->getSigningKey());
         if (!hash_equals($expectedSignature, $signature)) {
            return ['valid' => false, 'reason' => 'Firma inválida'];
         }

         $tokenData = json_decode(base64_decode($payload, true), true, 512, JSON_THROW_ON_ERROR);

         return [
            'valid' => true,
            'type' => $tokenData['type'] ?? 'unknown',
            'created' => $tokenData['created'] ?? 0,
            'metadata' => $tokenData['metadata'] ?? []
         ];

      } catch (\Exception $e) {
         return ['valid' => false, 'reason' => 'Error al procesar token: ' . $e->getMessage()];
      }
   }

   /**
    * Genera múltiples tokens únicos en lote
    */
   public function generateBatchTokens(int $count, int $length = 32, string $alphabet = self::ALPHA_NUMERIC): array {
      if ($count <= 0 || $count > 1000) {
         throw new \InvalidArgumentException('El count debe estar entre 1 y 1000');
      }

      $tokens = [];
      $attempts = 0;
      $maxAttempts = $count * 10;

      while (count($tokens) < $count && $attempts < $maxAttempts) {
         $token = $this->generateSecureToken($length, $alphabet);

         // Asegurar unicidad
         if (!in_array($token, $tokens, true)) {
            $tokens[] = $token;
         }

         $attempts++;
      }

      if (count($tokens) < $count) {
         throw new \RuntimeException('No se pudieron generar suficientes tokens únicos');
      }

      return $tokens;
   }

   /**
    * Genera un hash del token para almacenamiento seguro
    */
   public function hashToken(string $token): string {
      return hash('sha256', $token);
   }

   /**
    * Verifica un token contra su hash almacenado
    */
   public function verifyTokenHash(string $token, string $hash): bool {
      return hash_equals($hash, $this->hashToken($token));
   }

   /**
    * Método principal para generar tokens seguros
    */
   private function generateSecureToken(int $length, string $alphabet): string {
      if ($length <= 0) {
         throw new \InvalidArgumentException('La longitud debe ser mayor a 0');
      }

      if (empty($alphabet)) {
         throw new \InvalidArgumentException('El alfabeto no puede estar vacío');
      }

      $alphabetLength = strlen($alphabet);
      $token = '';

      for ($i = 0; $i < $length; $i++) {
         $token .= $alphabet[random_int(0, $alphabetLength - 1)];
      }

      return $token;
   }

   /**
    * Obtiene la clave de firma para tokens con signature
    */
   private function getSigningKey(): string {
      // En producción, esto debería venir de una variable de entorno
      // o archivo de configuración seguro
      $key = $_ENV['TOKEN_SIGNING_KEY'] ?? 'default_development_key_change_in_production';

      if ($key === 'default_development_key_change_in_production' &&
         ($_ENV['APP_ENV'] ?? 'development') === 'production') {
         throw new \RuntimeException('TOKEN_SIGNING_KEY debe configurarse en producción');
      }

      return $key;
   }

   /**
    * Genera estadísticas sobre la entropía del token
    */
   public function getTokenEntropy(int $length, string $alphabet): array {
      $alphabetSize = strlen($alphabet);
      $entropy = $length * log($alphabetSize, 2);

      return [
         'length' => $length,
         'alphabetSize' => $alphabetSize,
         'entropy' => round($entropy, 2),
         'possibleCombinations' => bcpow((string) $alphabetSize, (string) $length),
         'security' => $this->getSecurityLevel($entropy)
      ];
   }

   /**
    * Determina el nivel de seguridad basado en la entropía
    */
   private function getSecurityLevel(float $entropy): string {
      if ($entropy < 32)
         return 'Muy Bajo';
      if ($entropy < 64)
         return 'Bajo';
      if ($entropy < 96)
         return 'Medio';
      if ($entropy < 128)
         return 'Alto';
      return 'Muy Alto';
   }

   /**
    * Valida la fortaleza de un token existente
    */
   public function validateTokenStrength(string $token): array {
      $length = strlen($token);
      $uniqueChars = count(array_unique(str_split($token)));
      $hasNumbers = preg_match('/[0-9]/', $token);
      $hasLowercase = preg_match('/[a-z]/', $token);
      $hasUppercase = preg_match('/[A-Z]/', $token);
      $hasSpecialChars = preg_match('/[^A-Za-z0-9]/', $token);

      $score = 0;
      $issues = [];

      // Longitud
      if ($length >= 32)
         $score += 25;
      elseif ($length >= 24)
         $score += 20;
      elseif ($length >= 16)
         $score += 15;
      else
         $issues[] = 'Token demasiado corto (mínimo recomendado: 16 caracteres)';

      // Diversidad de caracteres
      if ($hasNumbers)
         $score += 15;
      if ($hasLowercase)
         $score += 15;
      if ($hasUppercase)
         $score += 15;
      if ($hasSpecialChars)
         $score += 10;

      // Unicidad de caracteres
      $uniquenessRatio = $uniqueChars / $length;
      if ($uniquenessRatio > 0.8)
         $score += 20;
      elseif ($uniquenessRatio > 0.6)
         $score += 15;
      elseif ($uniquenessRatio > 0.4)
         $score += 10;
      else
         $issues[] = 'Demasiadas repeticiones de caracteres';

      return [
         'score' => min(100, $score),
         'level' => $this->getScoreLevel($score),
         'issues' => $issues,
         'metrics' => [
            'length' => $length,
            'uniqueChars' => $uniqueChars,
            'uniquenessRatio' => round($uniquenessRatio, 2),
            'hasNumbers' => $hasNumbers,
            'hasLowercase' => $hasLowercase,
            'hasUppercase' => $hasUppercase,
            'hasSpecialChars' => $hasSpecialChars
         ]
      ];
   }

   /**
    * Convierte score numérico a nivel descriptivo
    */
   private function getScoreLevel(int $score): string {
      if ($score >= 90)
         return 'Excelente';
      if ($score >= 75)
         return 'Bueno';
      if ($score >= 60)
         return 'Aceptable';
      if ($score >= 40)
         return 'Débil';
      return 'Muy Débil';
   }
}
