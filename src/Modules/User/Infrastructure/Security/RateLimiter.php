<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Security;

/**
 * Implementa rate limiting para prevenir ataques de fuerza bruta
 * y abuso del sistema usando almacenamiento en archivos
 */
class RateLimiter {
   private string $cacheDir;
   private int $defaultMaxAttempts;
   private int $defaultDecayMinutes;
   private string $keyPrefix;

   public function __construct(
      ?string $cacheDir = null,
      int $defaultMaxAttempts = 5,
      int $defaultDecayMinutes = 15,
      string $keyPrefix = 'rate_limit:'
   ) {
      $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/viex_rate_limit';
      $this->defaultMaxAttempts = $defaultMaxAttempts;
      $this->defaultDecayMinutes = $defaultDecayMinutes;
      $this->keyPrefix = $keyPrefix;

      // Crear directorio si no existe
      if (!is_dir($this->cacheDir)) {
         mkdir($this->cacheDir, 0755, true);
      }
   }

   /**
    * Verifica si una clave está siendo rate limited
    */
   public function tooManyAttempts(string $key, ?int $maxAttempts = null): bool {
      $maxAttempts = $maxAttempts ?? $this->defaultMaxAttempts;
      $attempts = $this->attempts($key);

      return $attempts >= $maxAttempts;
   }

   /**
    * Incrementa el contador de intentos para una clave
    */
   public function hit(string $key, ?int $decayMinutes = null): int {
      $decayMinutes = $decayMinutes ?? $this->defaultDecayMinutes;
      $cacheKey = $this->resolveRequestSignature($key);

      $data = $this->getCacheData($cacheKey);
      $attempts = ($data['attempts'] ?? 0) + 1;
      $expiresAt = time() + ($decayMinutes * 60);

      $this->setCacheData($cacheKey, [
         'attempts' => $attempts,
         'expires_at' => $expiresAt
      ]);

      return $attempts;
   }

   /**
    * Obtiene el número de intentos actuales para una clave
    */
   public function attempts(string $key): int {
      $cacheKey = $this->resolveRequestSignature($key);
      $data = $this->getCacheData($cacheKey);

      // Verificar si ha expirado
      if (isset($data['expires_at']) && time() > $data['expires_at']) {
         $this->deleteCacheData($cacheKey);
         return 0;
      }

      return (int) ($data['attempts'] ?? 0);
   }

   /**
    * Resetea el contador de intentos para una clave
    */
   public function clear(string $key): bool {
      $cacheKey = $this->resolveRequestSignature($key);
      return $this->deleteCacheData($cacheKey);
   }

   /**
    * Obtiene el tiempo restante hasta que se resetee el rate limit (en segundos)
    */
   public function availableIn(string $key): int {
      $cacheKey = $this->resolveRequestSignature($key);
      $data = $this->getCacheData($cacheKey);

      if (!isset($data['expires_at'])) {
         return 0;
      }

      return max(0, $data['expires_at'] - time());
   }

   /**
    * Rate limiting específico para intentos de login
    */
   public function loginAttempts(string $identifier): array {
      $key = "login:{$identifier}";
      $maxAttempts = 5;
      $decayMinutes = 15;

      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0
      ];
   }

   /**
    * Rate limiting para recuperación de contraseña
    */
   public function passwordResetAttempts(string $email): array {
      $key = "password_reset:{$email}";
      $maxAttempts = 3;
      $decayMinutes = 60; // 1 hora

      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0
      ];
   }

   /**
    * Rate limiting para verificación de email
    */
   public function emailVerificationAttempts(string $email): array {
      $key = "email_verification:{$email}";
      $maxAttempts = 3;
      $decayMinutes = 30;

      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0
      ];
   }

   /**
    * Rate limiting para API calls
    */
   public function apiAttempts(string $apiKey, ?string $endpoint = null): array {
      $key = $endpoint ? "api:{$apiKey}:{$endpoint}" : "api:{$apiKey}";
      $maxAttempts = 100; // Por hora
      $decayMinutes = 60;

      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0
      ];
   }

   /**
    * Rate limiting por IP
    */
   public function ipAttempts(string $ip, string $action = 'general'): array {
      $key = "ip:{$ip}:{$action}";
      $maxAttempts = $this->getMaxAttemptsForAction($action);
      $decayMinutes = $this->getDecayMinutesForAction($action);

      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0,
         'action' => $action
      ];
   }

   /**
    * Rate limiting con escalamiento (aumenta el tiempo de bloqueo)
    */
   public function escalatingAttempts(string $key, ?array $thresholds = null): array {
      $thresholds = $thresholds ?? [
         5 => 15,   // 5 intentos = 15 minutos
         10 => 60,  // 10 intentos = 1 hora
         15 => 240, // 15 intentos = 4 horas
         20 => 1440 // 20 intentos = 24 horas
      ];

      $attempts = $this->attempts($key);
      $decayMinutes = $this->calculateEscalatingDecay($attempts, $thresholds);
      $maxAttempts = max(array_keys($thresholds));

      return [
         'attempts' => $attempts,
         'blocked' => $attempts > 0 && $this->tooManyAttempts($key, 1),
         'decayMinutes' => $decayMinutes,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $this->availableIn($key),
         'nextThreshold' => $this->getNextThreshold($attempts, $thresholds)
      ];
   }

   /**
    * Incrementa con escalamiento
    */
   public function hitEscalating(string $key, ?array $thresholds = null): int {
      $thresholds = $thresholds ?? [
         5 => 15,
         10 => 60,
         15 => 240,
         20 => 1440
      ];

      $attempts = $this->attempts($key) + 1;
      $decayMinutes = $this->calculateEscalatingDecay($attempts, $thresholds);

      $cacheKey = $this->resolveRequestSignature($key);
      $expiresAt = time() + ($decayMinutes * 60);

      $this->setCacheData($cacheKey, [
         'attempts' => $attempts,
         'expires_at' => $expiresAt
      ]);

      return $attempts;
   }

   /**
    * Obtiene información de rate limit sin incrementar
    */
   public function status(string $key, ?int $maxAttempts = null): array {
      $maxAttempts = $maxAttempts ?? $this->defaultMaxAttempts;
      $attempts = $this->attempts($key);
      $remaining = max(0, $maxAttempts - $attempts);
      $blocked = $this->tooManyAttempts($key, $maxAttempts);

      return [
         'key' => $key,
         'attempts' => $attempts,
         'remaining' => $remaining,
         'blocked' => $blocked,
         'maxAttempts' => $maxAttempts,
         'resetIn' => $blocked ? $this->availableIn($key) : 0
      ];
   }

   /**
    * Middleware helper para verificar rate limit
    */
   public function checkLimit(string $key, ?int $maxAttempts = null, int $decayMinutes = null): void {
      if ($this->tooManyAttempts($key, $maxAttempts)) {
         $resetIn = $this->availableIn($key);
         throw new \RuntimeException(
            "Demasiados intentos. Intente nuevamente en {$resetIn} segundos.",
            429
         );
      }
   }

   /**
    * Helper para obtener clave de IP + User Agent
    */
   public function getRequestSignature(?string $ip = null, ?string $userAgent = null): string {
      $ip = $ip ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
      $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

      return hash('sha256', $ip . '|' . $userAgent);
   }

   /**
    * Rate limiting temporal (se resetea automáticamente)
    */
   public function temporaryBlock(string $key, int $minutes): void {
      $cacheKey = $this->resolveRequestSignature($key . ':temp_block');
      $expiresAt = time() + ($minutes * 60);

      $this->setCacheData($cacheKey, [
         'blocked_until' => $expiresAt,
         'expires_at' => $expiresAt
      ]);
   }

   /**
    * Verifica si hay un bloqueo temporal
    */
   public function isTemporarilyBlocked(string $key): bool {
      $cacheKey = $this->resolveRequestSignature($key . ':temp_block');
      $data = $this->getCacheData($cacheKey);

      if (!isset($data['blocked_until'])) {
         return false;
      }

      return time() < $data['blocked_until'];
   }

   /**
    * Limpia archivos de cache expirados
    */
   public function cleanupExpired(): int {
      $cleaned = 0;
      $files = glob($this->cacheDir . '/*.cache');

      foreach ($files as $file) {
         $data = $this->getCacheDataFromFile($file);
         if (isset($data['expires_at']) && time() > $data['expires_at']) {
            unlink($file);
            $cleaned++;
         }
      }

      return $cleaned;
   }

   /**
    * Resuelve la signature única para una clave
    */
   private function resolveRequestSignature(string $key): string {
      return $this->keyPrefix . sha1($key);
   }

   /**
    * Obtiene el max attempts según la acción
    */
   private function getMaxAttemptsForAction(string $action): int {
      $limits = [
         'login' => 5,
         'password_reset' => 3,
         'email_verification' => 3,
         'api' => 100,
         'general' => 10
      ];

      return $limits[$action] ?? $this->defaultMaxAttempts;
   }

   /**
    * Obtiene los minutos de decay según la acción
    */
   private function getDecayMinutesForAction(string $action): int {
      $decays = [
         'login' => 15,
         'password_reset' => 60,
         'email_verification' => 30,
         'api' => 60,
         'general' => 15
      ];

      return $decays[$action] ?? $this->defaultDecayMinutes;
   }

   /**
    * Calcula el tiempo de decay basado en escalamiento
    */
   private function calculateEscalatingDecay(int $attempts, array $thresholds): int {
      $decay = $this->defaultDecayMinutes;

      foreach ($thresholds as $threshold => $minutes) {
         if ($attempts >= $threshold) {
            $decay = $minutes;
         } else {
            break;
         }
      }

      return $decay;
   }

   /**
    * Obtiene el siguiente threshold
    */
   private function getNextThreshold(int $attempts, array $thresholds): ?array {
      foreach ($thresholds as $threshold => $minutes) {
         if ($attempts < $threshold) {
            return [
               'threshold' => $threshold,
               'remaining' => $threshold - $attempts,
               'penaltyMinutes' => $minutes
            ];
         }
      }

      return null; // Ya alcanzó el máximo threshold
   }

   /**
    * Obtiene datos del cache desde archivo
    */
   private function getCacheData(string $key): array {
      $filename = $this->cacheDir . '/' . $key . '.cache';
      return $this->getCacheDataFromFile($filename);
   }

   /**
    * Obtiene datos del cache desde un archivo específico
    */
   private function getCacheDataFromFile(string $filename): array {
      if (!file_exists($filename)) {
         return [];
      }

      $content = file_get_contents($filename);
      if ($content === false) {
         return [];
      }

      $data = json_decode($content, true);
      if (!is_array($data)) {
         return [];
      }

      return $data;
   }

   /**
    * Guarda datos en el cache
    */
   private function setCacheData(string $key, array $data): bool {
      $filename = $this->cacheDir . '/' . $key . '.cache';
      $content = json_encode($data, JSON_THROW_ON_ERROR);

      return file_put_contents($filename, $content, LOCK_EX) !== false;
   }

   /**
    * Elimina datos del cache
    */
   private function deleteCacheData(string $key): bool {
      $filename = $this->cacheDir . '/' . $key . '.cache';

      if (!file_exists($filename)) {
         return true;
      }

      return unlink($filename);
   }
}
