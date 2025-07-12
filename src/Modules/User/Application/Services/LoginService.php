<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\Repositories\UserRepositoryInterface;
use Viex\Modules\User\Domain\ValueObjects\Credentials;
use Viex\Modules\User\Domain\Exceptions\{
   InvalidCredentialsException,
   InactiveUserException,
   UserNotFoundException
};
use Viex\Modules\User\Infrastructure\Security\{PasswordHasher, RateLimiter};
use Viex\Modules\User\Application\Events\UserLoggedIn;
use Psr\Log\LoggerInterface;

/**
 * Servicio central de autenticación usado por el módulo Auth
 * Maneja todo el flujo de login incluyendo validaciones de seguridad
 */
class LoginService {
   private UserRepositoryInterface $userRepository;
   private PasswordHasher $passwordHasher;
   private SessionService $sessionService;
   private PermissionService $permissionService;
   private RateLimiter $rateLimiter;
   private ?LoggerInterface $logger;

   public function __construct(
      UserRepositoryInterface $userRepository,
      PasswordHasher $passwordHasher,
      SessionService $sessionService,
      PermissionService $permissionService,
      RateLimiter $rateLimiter,
      ?LoggerInterface $logger = null
   ) {
      $this->userRepository = $userRepository;
      $this->passwordHasher = $passwordHasher;
      $this->sessionService = $sessionService;
      $this->permissionService = $permissionService;
      $this->rateLimiter = $rateLimiter;
      $this->logger = $logger;
   }

   /**
    * Autentica un usuario con credenciales
    */
   public function authenticate(Credentials $credentials): User {
      $emailValue = $credentials->getEmail()->getValue();
      $email = $credentials->getEmail();
      $password = $credentials->getPassword();

      // 1. Verificar rate limiting por email e IP
      $this->checkRateLimit($emailValue);

      try {
         // 2. Buscar usuario por email
         $user = $this->userRepository->findByEmail($email);
         if (!$user) {
            $this->handleFailedLogin($emailValue, 'Usuario no encontrado');
            throw new UserNotFoundException("Usuario no encontrado: {$emailValue}");
         }

         // 3. Verificar contraseña
         if (!$this->passwordHasher->verify($password->getValue(), $user->getPasswordHash())) {
            $this->handleFailedLogin($emailValue, 'Contraseña incorrecta');
            throw new InvalidCredentialsException('Credenciales inválidas');
         }

         // 4. Verificar estado del usuario
         if (!$user->isActive()) {
            $this->handleFailedLogin($emailValue, 'Usuario inactivo');
            throw new InactiveUserException("Usuario inactivo: {$emailValue}");
         }

         // 5. Login exitoso - limpiar rate limits
         $this->clearRateLimit($emailValue);

         // 6. Crear sesión
         $this->sessionService->create($user);

         // 7. Cargar permisos en sesión
         $this->loadUserPermissions($user);

         // 8. Log y evento de login exitoso
         $this->logSuccessfulLogin($user);
         $this->dispatchLoginEvent($user);

         return $user;

      } catch (\Exception $e) {
         // Registrar intento fallido si no se hizo antes
         if (!($e instanceof UserNotFoundException ||
            $e instanceof InvalidCredentialsException ||
            $e instanceof InactiveUserException)) {
            $this->handleFailedLogin($emailValue, 'Error interno: ' . $e->getMessage());
         }
         throw $e;
      }
   }

   /**
    * Verifica si un usuario puede hacer login (sin autenticar)
    */
   public function canLogin(string $email): array {
      $loginStatus = $this->rateLimiter->loginAttempts($email);
      $ipStatus = $this->rateLimiter->ipAttempts($this->getClientIp(), 'login');

      return [
         'allowed' => !$loginStatus['blocked'] && !$ipStatus['blocked'],
         'loginAttempts' => $loginStatus,
         'ipAttempts' => $ipStatus,
         'blockReason' => $this->getBlockReason($loginStatus, $ipStatus)
      ];
   }

   /**
    * Cierra la sesión de un usuario
    */
   public function logout(): void {
      $userId = $this->sessionService->getCurrentUserId();

      if ($userId) {
         $user = $this->userRepository->findById($userId);
         if ($user) {
            $this->logSuccessfulLogout($user);
         }
      }

      $this->sessionService->destroy();
   }

   /**
    * Verifica si hay una sesión activa
    */
   public function isAuthenticated(): bool {
      return $this->sessionService->isActive();
   }

   /**
    * Obtiene el usuario actualmente autenticado
    */
   public function getCurrentUser(): ?User {
      $userId = $this->sessionService->getCurrentUserId();

      if (!$userId) {
         return null;
      }

      try {
         return $this->userRepository->findById($userId);
      } catch (\Exception $e) {
         $this->logger?->warning('Error al obtener usuario actual', [
            'user_id' => $userId,
            'error' => $e->getMessage()
         ]);

         // Limpiar sesión corrupta
         $this->sessionService->destroy();
         return null;
      }
   }

   /**
    * Renueva la sesión actual (anti-session hijacking)
    */
   public function renewSession(): bool {
      if (!$this->isAuthenticated()) {
         return false;
      }

      $userId = $this->sessionService->getCurrentUserId();
      if (!$userId) {
         return false;
      }

      try {
         $user = $this->userRepository->findById($userId);
         if (!$user || !$user->isActive()) {
            $this->logout();
            return false;
         }

         // Renovar ID de sesión
         $this->sessionService->regenerate();

         // Recargar permisos por si cambiaron
         $this->loadUserPermissions($user);

         return true;
      } catch (\Exception $e) {
         $this->logger?->error('Error al renovar sesión', [
            'user_id' => $userId,
            'error' => $e->getMessage()
         ]);

         $this->logout();
         return false;
      }
   }

   /**
    * Verifica rate limiting antes del login
    */
   private function checkRateLimit(string $email): void {
      // Verificar intentos por email
      $loginStatus = $this->rateLimiter->loginAttempts($email);
      if ($loginStatus['blocked']) {
         $message = "Demasiados intentos de login para {$email}. " .
            "Intente nuevamente en {$loginStatus['resetIn']} segundos.";

         $this->logger?->warning('Login bloqueado por rate limit - email', [
            'email' => $email,
            'attempts' => $loginStatus['attempts'],
            'reset_in' => $loginStatus['resetIn']
         ]);

         throw new InvalidCredentialsException($message);
      }

      // Verificar intentos por IP
      $ip = $this->getClientIp();
      $ipStatus = $this->rateLimiter->ipAttempts($ip, 'login');
      if ($ipStatus['blocked']) {
         $message = "Demasiados intentos de login desde esta IP. " .
            "Intente nuevamente en {$ipStatus['resetIn']} segundos.";

         $this->logger?->warning('Login bloqueado por rate limit - IP', [
            'ip' => $ip,
            'attempts' => $ipStatus['attempts'],
            'reset_in' => $ipStatus['resetIn']
         ]);

         throw new InvalidCredentialsException($message);
      }
   }

   /**
    * Maneja un intento de login fallido
    */
   private function handleFailedLogin(string $email, string $reason): void {
      // Incrementar counters de rate limiting
      $this->rateLimiter->hit("login:{$email}");
      $this->rateLimiter->hit("ip:{$this->getClientIp()}:login");

      // Log del intento fallido
      $this->logger?->warning('Intento de login fallido', [
         'email' => $email,
         'reason' => $reason,
         'ip' => $this->getClientIp(),
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
      ]);
   }

   /**
    * Limpia rate limits tras login exitoso
    */
   private function clearRateLimit(string $email): void {
      $this->rateLimiter->clear("login:{$email}");
      // No limpiar IP rate limit para mantener protección general
   }

   /**
    * Carga permisos del usuario en la sesión
    */
   private function loadUserPermissions(User $user): void {
      try {
         $permissions = $this->permissionService->getUserPermissions($user);
         $userGroups = $this->permissionService->getUserGroups($user);

         $this->sessionService->setPermissions($permissions);
         $this->sessionService->setUserGroups($userGroups);

      } catch (\Exception $e) {
         $this->logger?->error('Error al cargar permisos de usuario', [
            'user_id' => $user->getId(),
            'error' => $e->getMessage()
         ]);

         // Continuar con permisos vacíos en lugar de fallar
         $this->sessionService->setPermissions([]);
         $this->sessionService->setUserGroups([]);
      }
   }

   /**
    * Registra login exitoso en logs
    */
   private function logSuccessfulLogin(User $user): void {
      $this->logger?->info('Login exitoso', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail(),
         'ip' => $this->getClientIp(),
         'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
      ]);
   }

   /**
    * Registra logout exitoso en logs
    */
   private function logSuccessfulLogout(User $user): void {
      $this->logger?->info('Logout exitoso', [
         'user_id' => $user->getId(),
         'email' => $user->getEmail(),
         'ip' => $this->getClientIp()
      ]);
   }

   /**
    * Dispara evento de login
    */
   private function dispatchLoginEvent(User $user): void {
      // TODO: Implementar event dispatcher cuando esté disponible
      // EventDispatcher::dispatch(new UserLoggedIn($user));
   }

   /**
    * Obtiene la IP del cliente
    */
   private function getClientIp(): string {
      // Verificar proxies y balanceadores de carga
      $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

      foreach ($ipKeys as $key) {
         if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            // Si hay múltiples IPs, tomar la primera
            if (strpos($ip, ',') !== false) {
               $ip = trim(explode(',', $ip)[0]);
            }

            // Validar que sea una IP válida
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
               return $ip;
            }
         }
      }

      return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
   }

   /**
    * Obtiene razón del bloqueo para mostrar al usuario
    */
   private function getBlockReason(array $loginStatus, array $ipStatus): ?string {
      if ($loginStatus['blocked']) {
         return "Demasiados intentos para esta cuenta. Reintentar en {$loginStatus['resetIn']} segundos.";
      }

      if ($ipStatus['blocked']) {
         return "Demasiados intentos desde esta ubicación. Reintentar en {$ipStatus['resetIn']} segundos.";
      }

      return null;
   }
}
