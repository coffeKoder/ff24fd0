<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Entities;

use Viex\Modules\Shared\Domain\Entities\BaseEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'password_reset_tokens')]
class PasswordReset extends BaseEntity {
   #[ORM\Id]
   #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
   #[ORM\SequenceGenerator(sequenceName: 'password_reset_tokens_seq', allocationSize: 1, initialValue: 1)]
   #[ORM\Column(name: 'id', type: 'integer')]
   private int $id;

   #[ORM\Column(name: 'email', type: 'string', length: 255)]
   private string $email;

   #[ORM\Column(name: 'token', type: 'string', length: 255, unique: true)]
   private string $token;

   #[ORM\Column(name: 'expires_at', type: 'datetime')]
   private \DateTimeInterface $expiresAt;

   #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
   private ?\DateTimeInterface $usedAt = null;

   #[ORM\Column(name: 'ip_address', type: 'string', length: 45, nullable: true)]
   private ?string $ipAddress = null;

   #[ORM\Column(name: 'user_agent', type: 'text', nullable: true)]
   private ?string $userAgent = null;

   #[ORM\Column(name: 'is_active', type: 'smallint')]
   private int $isActive = 1;

   public function __construct(
      string $email,
      string $token,
      \DateTimeInterface $expiresAt,
      ?string $ipAddress = null,
      ?string $userAgent = null
   ) {
      parent::__construct();
      $this->email = $email;
      $this->token = $token;
      $this->expiresAt = $expiresAt;
      $this->ipAddress = $ipAddress;
      $this->userAgent = $userAgent;
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getEmail(): string {
      return $this->email;
   }

   public function getToken(): string {
      return $this->token;
   }

   public function getExpiresAt(): \DateTimeInterface {
      return $this->expiresAt;
   }

   public function getUsedAt(): ?\DateTimeInterface {
      return $this->usedAt;
   }

   public function getIpAddress(): ?string {
      return $this->ipAddress;
   }

   public function getUserAgent(): ?string {
      return $this->userAgent;
   }

   public function isActive(): bool {
      return $this->isActive === 1;
   }

   // Business Methods
   public function isValid(): bool {
      return $this->isActive() &&
         !$this->hasBeenUsed() &&
         !$this->isExpired();
   }

   public function isExpired(): bool {
      return $this->expiresAt < new \DateTimeImmutable();
   }

   public function hasBeenUsed(): bool {
      return $this->usedAt !== null;
   }

   public function markAsUsed(): void {
      $this->usedAt = new \DateTimeImmutable();
      $this->isActive = 0;
      $this->touch();
   }

   public function invalidate(): void {
      $this->isActive = 0;
      $this->touch();
   }

   public function extendExpiration(int $minutes): void {
      $this->expiresAt = (new \DateTimeImmutable())->modify("+{$minutes} minutes");
      $this->touch();
   }

   // Business Logic
   public function getRemainingTime(): ?\DateInterval {
      if ($this->isExpired()) {
         return null;
      }

      return (new \DateTimeImmutable())->diff($this->expiresAt);
   }

   public function getRemainingMinutes(): int {
      $remaining = $this->getRemainingTime();

      if (!$remaining) {
         return 0;
      }

      return ($remaining->days * 24 * 60) + ($remaining->h * 60) + $remaining->i;
   }

   public function canBeUsedBy(string $email): bool {
      return $this->email === $email && $this->isValid();
   }

   public function getTokenAge(): \DateInterval {
      return $this->getCreatedAt()->diff(new \DateTimeImmutable());
   }

   public function isRecentlyCreated(int $minutes = 5): bool {
      $age = $this->getTokenAge();
      $totalMinutes = ($age->days * 24 * 60) + ($age->h * 60) + $age->i;

      return $totalMinutes <= $minutes;
   }

   // Security methods
   public function validateToken(string $providedToken): bool {
      return hash_equals($this->token, $providedToken);
   }

   public function updateSecurityInfo(?string $ipAddress = null, ?string $userAgent = null): void {
      if ($ipAddress) {
         $this->ipAddress = $ipAddress;
      }

      if ($userAgent) {
         $this->userAgent = $userAgent;
      }

      $this->touch();
   }

   // String representation
   public function __toString(): string {
      $status = $this->isValid() ? 'Válido' : 'Inválido';
      return "Token para {$this->email} ({$status})";
   }
}
