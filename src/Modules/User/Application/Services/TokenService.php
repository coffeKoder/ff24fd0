<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\{User, PasswordReset};
use Viex\Modules\User\Domain\Repositories\{UserRepositoryInterface, PasswordResetRepositoryInterface};
use Viex\Modules\User\Infrastructure\Security\TokenGenerator;
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * Servicio simplificado de generación y validación de tokens
 * Usa solo los métodos disponibles en las interfaces actuales
 */
class TokenService {
   private UserRepositoryInterface $userRepository;
   private PasswordResetRepositoryInterface $passwordResetRepository;
   private TokenGenerator $tokenGenerator;
   private ?LoggerInterface $logger;
   private int $defaultExpirationMinutes;

   public function __construct(
      UserRepositoryInterface $userRepository,
      PasswordResetRepositoryInterface $passwordResetRepository,
      TokenGenerator $tokenGenerator,
      ?LoggerInterface $logger = null,
      int $defaultExpirationMinutes = 60
   ) {
      $this->userRepository = $userRepository;
      $this->passwordResetRepository = $passwordResetRepository;
      $this->tokenGenerator = $tokenGenerator;
      $this->logger = $logger;
      $this->defaultExpirationMinutes = $defaultExpirationMinutes;
   }

   /**
    * Genera un token para reset de contraseña
    */
   public function generatePasswordResetToken(string $email, ?int $expirationMinutes = null): PasswordReset {
      $expirationMinutes = $expirationMinutes ?? $this->defaultExpirationMinutes;

      // Verificar que el usuario existe
      $emailVO = \Viex\Modules\User\Domain\ValueObjects\Email::fromString($email);
      $user = $this->userRepository->findByEmail($emailVO);

      if (!$user) {
         throw new UserNotFoundException("Usuario no encontrado con email: {$email}");
      }

      // Limpiar tokens existentes para este usuario
      $this->invalidateUserTokens($user->getId());

      // Generar nuevo token
      $token = $this->tokenGenerator->generatePasswordResetToken();
      $expiresAt = new \DateTimeImmutable('+' . $expirationMinutes . ' minutes');

      // Crear entidad PasswordReset
      $passwordReset = new PasswordReset(
         $email,
         $token,
         $expiresAt
      );

      // Persistir
      $this->passwordResetRepository->save($passwordReset);

      $this->logger?->info('Token de reset de contraseña generado', [
         'email' => $email,
         'user_id' => $user->getId(),
         'expires_at' => $expiresAt->format('Y-m-d H:i:s')
      ]);

      return $passwordReset;
   }

   /**
    * Valida un token de reset de contraseña
    */
   public function validatePasswordResetToken(string $email, string $token): ?PasswordReset {
      // Buscar token por valor
      $passwordReset = $this->passwordResetRepository->findByToken($token);

      if (!$passwordReset) {
         $this->logger?->warning('Token de reset no encontrado', [
            'email' => $email,
            'token' => substr($token, 0, 8) . '...'
         ]);
         return null;
      }

      // Verificar que el email coincide
      if ($passwordReset->getEmail() !== $email) {
         $this->logger?->warning('Token de reset no coincide con email', [
            'email' => $email,
            'token_email' => $passwordReset->getEmail()
         ]);
         return null;
      }

      // Verificar que no ha expirado
      if ($passwordReset->getExpiresAt() <= new \DateTimeImmutable()) {
         $this->logger?->warning('Token de reset expirado', [
            'email' => $email,
            'expired_at' => $passwordReset->getExpiresAt()->format('Y-m-d H:i:s')
         ]);
         return null;
      }

      return $passwordReset;
   }

   /**
    * Valida y consume un token de reset (lo elimina tras validar)
    */
   public function consumePasswordResetToken(string $email, string $token): ?PasswordReset {
      $passwordReset = $this->validatePasswordResetToken($email, $token);

      if (!$passwordReset) {
         return null;
      }

      // Eliminar token tras uso exitoso
      $this->passwordResetRepository->delete($passwordReset);

      $this->logger?->info('Token de reset consumido', [
         'email' => $email
      ]);

      return $passwordReset;
   }

   /**
    * Genera un token de verificación de email
    */
   public function generateEmailVerificationToken(): string {
      $token = $this->tokenGenerator->generateEmailVerificationToken();

      $this->logger?->info('Token de verificación de email generado');

      return $token;
   }

   /**
    * Genera un token de API
    */
   public function generateApiToken(): string {
      $token = $this->tokenGenerator->generateApiToken();

      $this->logger?->info('Token de API generado');

      return $token;
   }

   /**
    * Genera un token de sesión seguro
    */
   public function generateSessionToken(): string {
      return $this->tokenGenerator->generateSessionToken();
   }

   /**
    * Genera un código numérico de verificación
    */
   public function generateVerificationCode(int $length = 6): string {
      return $this->tokenGenerator->generateNumericCode($length);
   }

   /**
    * Genera un UUID único
    */
   public function generateUuid(): string {
      return $this->tokenGenerator->generateUuid();
   }

   /**
    * Genera un token genérico con longitud y alfabeto específicos
    */
   public function generateCustomToken(int $length = 32, string $alphabet = null): string {
      if ($alphabet) {
         return $this->tokenGenerator->generateToken($length, $alphabet);
      } else {
         return $this->tokenGenerator->generateToken($length);
      }
   }

   /**
    * Limpia tokens expirados de reset de contraseña
    */
   public function cleanupExpiredTokens(): int {
      $deletedCount = $this->passwordResetRepository->deleteExpiredResets();

      $this->logger?->info('Tokens expirados limpiados', [
         'deleted_count' => $deletedCount
      ]);

      return $deletedCount;
   }

   /**
    * Invalida todos los tokens de un usuario específico
    */
   public function invalidateUserTokens(int $userId): int {
      $deletedCount = $this->passwordResetRepository->invalidateAllUserResets($userId);

      $this->logger?->info('Tokens de usuario invalidados', [
         'user_id' => $userId,
         'deleted_count' => $deletedCount
      ]);

      return $deletedCount;
   }

   /**
    * Verifica si un usuario tiene tokens activos
    */
   public function hasActiveTokens(int $userId): bool {
      return $this->passwordResetRepository->hasValidTokenForUser($userId);
   }

   /**
    * Obtiene estadísticas de tokens
    */
   public function getTokenStatistics(): array {
      $allTokens = $this->passwordResetRepository->findAll();
      $activeTokens = $this->passwordResetRepository->findActiveResets();
      $expiredTokens = $this->passwordResetRepository->findExpiredResets();

      return [
         'total_tokens' => count($allTokens),
         'active_tokens' => count($activeTokens),
         'expired_tokens' => count($expiredTokens)
      ];
   }

   /**
    * Obtiene tokens activos de un usuario
    */
   public function getUserActiveTokens(int $userId): array {
      return $this->passwordResetRepository->findByUserId($userId);
   }

   /**
    * Cuenta tokens activos de un usuario
    */
   public function countUserActiveTokens(int $userId): int {
      return $this->passwordResetRepository->countActiveResetsByUser($userId);
   }

   /**
    * Hash de un token para almacenamiento seguro
    */
   public function hashToken(string $token): string {
      return $this->tokenGenerator->hashToken($token);
   }

   /**
    * Verifica un token contra su hash
    */
   public function verifyTokenHash(string $token, string $hash): bool {
      return $this->tokenGenerator->verifyTokenHash($token, $hash);
   }
}
