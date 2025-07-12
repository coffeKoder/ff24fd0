<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\Repositories\UserRepositoryInterface;
use Viex\Modules\User\Domain\ValueObjects\{Email, Password};
use Viex\Modules\User\Domain\Exceptions\{
   UserNotFoundException,
   UserAlreadyExistsException,
   InvalidCredentialsException
};
use Viex\Modules\User\Infrastructure\Security\PasswordHasher;
use Viex\Modules\User\Application\Events\{UserCreated, PasswordChanged, UserUpdated};
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión completa del ciclo de vida de usuarios
 * Maneja CRUD de usuarios con validaciones de negocio
 */
class UserService {
   private UserRepositoryInterface $userRepository;
   private PasswordHasher $passwordHasher;
   private ?LoggerInterface $logger;

   public function __construct(
      UserRepositoryInterface $userRepository,
      PasswordHasher $passwordHasher,
      ?LoggerInterface $logger = null
   ) {
      $this->userRepository = $userRepository;
      $this->passwordHasher = $passwordHasher;
      $this->logger = $logger;
   }

   /**
    * Crea un nuevo usuario con validaciones completas
    */
   public function createUser(
      string $email,
      string $plainPassword,
      string $firstName,
      string $lastName,
      string $cedula,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null
   ): User {
      // 1. Validar reglas de negocio
      $this->validateUserCreation($email, $cedula, $plainPassword);

      // 2. Hash de la contraseña
      $hashedPassword = $this->passwordHasher->hash($plainPassword);

      // 3. Crear entidad User
      $user = new User(
         $email,      // username (por ahora igual que email)
         $email,      // email
         $hashedPassword,
         $firstName,
         $lastName,
         $cedula,
         $professorCode,
         $mainOrganizationalUnitId
      );

      // 4. Persistir
      $this->userRepository->save($user);

      // 5. Log y evento
      $this->logUserCreation($user);
      $this->dispatchUserCreatedEvent($user);

      return $user;
   }

   /**
    * Actualiza el perfil de un usuario
    */
   public function updateUserProfile(
      int $userId,
      ?string $firstName = null,
      ?string $lastName = null,
      ?string $officePhone = null,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null
   ): User {
      $user = $this->findUserById($userId);

      // Actualizar perfil básico si se proporcionan datos
      if ($firstName !== null || $lastName !== null || $officePhone !== null) {
         $user->updateProfile(
            $firstName ?? $user->getFirstName(),
            $lastName ?? $user->getLastName(),
            $officePhone ?? $user->getOfficePhone()
         );
      }

      // Actualizar código de profesor si se proporciona
      if ($professorCode !== null) {
         $user->setProfessorCode($professorCode);
      }

      // Actualizar unidad organizacional si se proporciona
      if ($mainOrganizationalUnitId !== null) {
         $user->assignToOrganizationalUnit($mainOrganizationalUnitId);
      }

      $this->userRepository->save($user);

      $this->logUserUpdate($user);
      $this->dispatchUserUpdatedEvent($user);

      return $user;
   }

   /**
    * Cambia la contraseña de un usuario con verificación
    */
   public function changePassword(int $userId, string $currentPassword, string $newPassword): void {
      $user = $this->findUserById($userId);

      // Verificar contraseña actual
      if (!$this->passwordHasher->verify($currentPassword, $user->getPasswordHash())) {
         throw new InvalidCredentialsException('Contraseña actual incorrecta');
      }

      // Validar nueva contraseña
      $validation = $this->passwordHasher->validatePasswordStrength($newPassword);
      if (!$validation['isValid']) {
         throw new \InvalidArgumentException('Contraseña no cumple los requisitos: ' . implode(', ', $validation['messages']));
      }

      // Cambiar contraseña
      $hashedPassword = $this->passwordHasher->hash($newPassword);
      $user->changePassword($hashedPassword);

      $this->userRepository->save($user);

      $this->logPasswordChange($user);
      $this->dispatchPasswordChangedEvent($user);
   }

   /**
    * Resetea la contraseña de un usuario (sin verificación - para admins)
    */
   public function resetPassword(int $userId, string $newPassword): User {
      $user = $this->findUserById($userId);

      // Validar nueva contraseña
      $validation = $this->passwordHasher->validatePasswordStrength($newPassword);
      if (!$validation['isValid']) {
         throw new \InvalidArgumentException('Contraseña no cumple los requisitos: ' . implode(', ', $validation['messages']));
      }

      // Resetear contraseña
      $hashedPassword = $this->passwordHasher->hash($newPassword);
      $user->changePassword($hashedPassword);

      $this->userRepository->save($user);

      $this->logPasswordReset($user);

      return $user;
   }

   /**
    * Activa un usuario
    */
   public function activateUser(int $userId): User {
      $user = $this->findUserById($userId);

      if ($user->isActive()) {
         return $user; // Ya está activo
      }

      $user->activate();
      $this->userRepository->save($user);

      $this->logger?->info('Usuario activado', [
         'user_id' => $userId,
         'email' => $user->getEmail()
      ]);

      return $user;
   }

   /**
    * Desactiva un usuario
    */
   public function deactivateUser(int $userId): User {
      $user = $this->findUserById($userId);

      if (!$user->isActive()) {
         return $user; // Ya está inactivo
      }

      $user->deactivate();
      $this->userRepository->save($user);

      $this->logger?->info('Usuario desactivado', [
         'user_id' => $userId,
         'email' => $user->getEmail()
      ]);

      return $user;
   }

   /**
    * Busca un usuario por ID
    */
   public function findUserById(int $userId): User {
      $user = $this->userRepository->findById($userId);

      if (!$user) {
         throw new UserNotFoundException("Usuario no encontrado con ID: {$userId}");
      }

      return $user;
   }

   /**
    * Busca un usuario por email
    */
   public function findUserByEmail(string $email): User {
      $emailVO = Email::fromString($email);
      $user = $this->userRepository->findByEmail($emailVO);

      if (!$user) {
         throw new UserNotFoundException("Usuario no encontrado con email: {$email}");
      }

      return $user;
   }

   /**
    * Busca un usuario por cédula
    */
   public function findUserByCedula(string $cedula): User {
      $user = $this->userRepository->findByCedula($cedula);

      if (!$user) {
         throw new UserNotFoundException("Usuario no encontrado con cédula: {$cedula}");
      }

      return $user;
   }

   /**
    * Verifica si un usuario existe por email
    */
   public function userExistsByEmail(string $email, ?int $excludeUserId = null): bool {
      $emailVO = Email::fromString($email);
      $user = $this->userRepository->findByEmail($emailVO);

      if (!$user) {
         return false;
      }

      // Si se especifica un ID a excluir (para updates)
      if ($excludeUserId !== null && $user->getId() === $excludeUserId) {
         return false;
      }

      return true;
   }

   /**
    * Verifica si un usuario existe por cédula
    */
   public function userExistsByCedula(string $cedula, ?int $excludeUserId = null): bool {
      $user = $this->userRepository->findByCedula($cedula);

      if (!$user) {
         return false;
      }

      // Si se especifica un ID a excluir (para updates)
      if ($excludeUserId !== null && $user->getId() === $excludeUserId) {
         return false;
      }

      return true;
   }

   /**
    * Obtiene lista de usuarios con criterios
    */
   public function getUsersList(array $criteria = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array {
      return $this->userRepository->findBy($criteria, $orderBy, $limit, $offset);
   }

   /**
    * Genera una contraseña temporal segura
    */
   public function generateTemporaryPassword(int $length = 12): string {
      return $this->passwordHasher->generateSecurePassword($length);
   }

   /**
    * Valida la fortaleza de una contraseña
    */
   public function validatePasswordStrength(string $password): array {
      return $this->passwordHasher->validatePasswordStrength($password);
   }

   /**
    * Obtiene estadísticas de usuarios
    */
   public function getUserStatistics(): array {
      $totalUsers = count($this->userRepository->findAll());
      $activeUsers = count($this->userRepository->findBy(['isActive' => true]));
      $inactiveUsers = $totalUsers - $activeUsers;

      return [
         'total_users' => $totalUsers,
         'active_users' => $activeUsers,
         'inactive_users' => $inactiveUsers,
         'percentage_active' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0
      ];
   }

   /**
    * Valida reglas de negocio para creación de usuario
    */
   private function validateUserCreation(string $email, string $cedula, string $password): void {
      // Verificar email único
      if ($this->userExistsByEmail($email)) {
         throw new UserAlreadyExistsException("Ya existe un usuario con el email: {$email}");
      }

      // Verificar cédula única
      if ($this->userExistsByCedula($cedula)) {
         throw new UserAlreadyExistsException("Ya existe un usuario con la cédula: {$cedula}");
      }

      // Validar fortaleza de contraseña
      $validation = $this->passwordHasher->validatePasswordStrength($password);
      if (!$validation['isValid']) {
         throw new \InvalidArgumentException('Contraseña no cumple los requisitos: ' . implode(', ', $validation['messages']));
      }
   }

   /**
    * Registra creación de usuario en logs
    */
   private function logUserCreation(User $user): void {
      $this->logger?->info('Usuario creado', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail(),
         'cedula' => $user->getCedula()
      ]);
   }

   /**
    * Registra actualización de usuario en logs
    */
   private function logUserUpdate(User $user): void {
      $this->logger?->info('Usuario actualizado', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail()
      ]);
   }

   /**
    * Registra cambio de contraseña en logs
    */
   private function logPasswordChange(User $user): void {
      $this->logger?->info('Contraseña cambiada', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail()
      ]);
   }

   /**
    * Registra reset de contraseña en logs
    */
   private function logPasswordReset(User $user): void {
      $this->logger?->warning('Contraseña reseteada por administrador', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail()
      ]);
   }

   /**
    * Dispara evento de usuario creado
    */
   private function dispatchUserCreatedEvent(User $user): void {
      // TODO: Implementar event dispatcher cuando esté disponible
      // EventDispatcher::dispatch(new UserCreated($user));
   }

   /**
    * Dispara evento de usuario actualizado
    */
   private function dispatchUserUpdatedEvent(User $user): void {
      // TODO: Implementar event dispatcher cuando esté disponible
      // EventDispatcher::dispatch(new UserUpdated($user));
   }

   /**
    * Dispara evento de contraseña cambiada
    */
   private function dispatchPasswordChangedEvent(User $user): void {
      // TODO: Implementar event dispatcher cuando esté disponible
      // EventDispatcher::dispatch(new PasswordChanged($user));
   }
}
