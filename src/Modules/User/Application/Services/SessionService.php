<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\Services;

use Viex\Modules\User\Domain\Entities\User;
use Aura\Session\Session;
use Aura\Session\SessionFactory;

/**
 * Servicio de gestión de sesiones nativas de PHP con seguridad y timeouts
 */
class SessionService {
   private Session $session;
   private int $sessionTimeout;
   private string $sessionName;

   public function __construct(?Session $session = null, int $sessionTimeout = 3600, string $sessionName = 'VIEX_SESSION') {
      $this->session = $session ?? (new SessionFactory())->newInstance($_COOKIE);
      $this->sessionTimeout = $sessionTimeout;
      $this->sessionName = $sessionName;

      $this->configureSession();
   }

   /**
    * Crea una nueva sesión para un usuario
    */
   public function create(User $user): void {
      // Regenerar ID de sesión por seguridad
      $this->regenerate();

      $segment = $this->session->getSegment('viex_user');

      $segment->set('user_id', $user->getId());
      $segment->set('user_email', $user->getEmail());
      $segment->set('login_time', time());
      $segment->set('last_activity', time());
      $segment->set('ip_address', $this->getClientIp());
      $segment->set('user_agent', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');

      // Inicializar arrays de permisos y grupos
      $segment->set('permissions', []);
      $segment->set('user_groups', []);
   }

   /**
    * Destruye la sesión actual
    */
   public function destroy(): void {
      $this->session->destroy();
   }

   /**
    * Verifica si hay una sesión activa y válida
    */
   public function isActive(): bool {
      $segment = $this->session->getSegment('viex_user');

      if (!$segment->get('user_id')) {
         return false;
      }

      // Verificar timeout de sesión
      $lastActivity = $segment->get('last_activity', 0);
      if (time() - $lastActivity > $this->sessionTimeout) {
         $this->destroy();
         return false;
      }

      // Actualizar último tiempo de actividad
      $segment->set('last_activity', time());

      return true;
   }

   /**
    * Obtiene el ID del usuario actual
    */
   public function getCurrentUserId(): ?int {
      if (!$this->isActive()) {
         return null;
      }

      $segment = $this->session->getSegment('viex_user');
      return $segment->get('user_id');
   }

   /**
    * Obtiene el email del usuario actual
    */
   public function getCurrentUserEmail(): ?string {
      if (!$this->isActive()) {
         return null;
      }

      $segment = $this->session->getSegment('viex_user');
      return $segment->get('user_email');
   }

   /**
    * Establece los permisos del usuario en la sesión
    */
   public function setPermissions(array $permissions): void {
      if (!$this->isActive()) {
         return;
      }

      $segment = $this->session->getSegment('viex_user');
      $segment->set('permissions', $permissions);
   }

   /**
    * Obtiene los permisos del usuario desde la sesión
    */
   public function getPermissions(): array {
      if (!$this->isActive()) {
         return [];
      }

      $segment = $this->session->getSegment('viex_user');
      return $segment->get('permissions', []);
   }

   /**
    * Verifica si el usuario tiene un permiso específico
    */
   public function hasPermission(string $permission): bool {
      $permissions = $this->getPermissions();
      return in_array($permission, $permissions, true);
   }

   /**
    * Establece los grupos/roles del usuario en la sesión
    */
   public function setUserGroups(array $userGroups): void {
      if (!$this->isActive()) {
         return;
      }

      $segment = $this->session->getSegment('viex_user');
      $segment->set('user_groups', $userGroups);
   }

   /**
    * Obtiene los grupos del usuario desde la sesión
    */
   public function getUserGroups(): array {
      if (!$this->isActive()) {
         return [];
      }

      $segment = $this->session->getSegment('viex_user');
      return $segment->get('user_groups', []);
   }

   /**
    * Verifica si el usuario tiene un rol específico
    */
   public function hasRole(string $role): bool {
      $userGroups = $this->getUserGroups();

      foreach ($userGroups as $group) {
         if (isset($group['name']) && $group['name'] === $role) {
            return true;
         }
      }

      return false;
   }

   /**
    * Verifica si el usuario tiene un rol en una unidad organizacional específica
    */
   public function hasRoleInUnit(string $role, int $organizationalUnitId): bool {
      $userGroups = $this->getUserGroups();

      foreach ($userGroups as $group) {
         if (isset($group['name']) && $group['name'] === $role &&
            isset($group['organizational_unit_id']) && $group['organizational_unit_id'] === $organizationalUnitId) {
            return true;
         }
      }

      return false;
   }

   /**
    * Regenera el ID de sesión por seguridad
    */
   public function regenerate(): void {
      session_regenerate_id(true);
   }

   /**
    * Obtiene información de la sesión para auditoría
    */
   public function getSessionInfo(): array {
      if (!$this->isActive()) {
         return [];
      }

      $segment = $this->session->getSegment('viex_user');

      return [
         'user_id' => $segment->get('user_id'),
         'user_email' => $segment->get('user_email'),
         'login_time' => $segment->get('login_time'),
         'last_activity' => $segment->get('last_activity'),
         'ip_address' => $segment->get('ip_address'),
         'user_agent' => $segment->get('user_agent'),
         'session_duration' => time() - $segment->get('login_time', time()),
         'time_remaining' => $this->sessionTimeout - (time() - $segment->get('last_activity', time()))
      ];
   }

   /**
    * Extiende el timeout de la sesión actual
    */
   public function extendSession(int $additionalMinutes = 30): void {
      if (!$this->isActive()) {
         return;
      }

      $segment = $this->session->getSegment('viex_user');
      $segment->set('last_activity', time());

      // Opcional: registrar extensión para auditoría
   }

   /**
    * Configura parámetros de seguridad de la sesión
    */
   private function configureSession(): void {
      // Configurar solo si no se ha iniciado ya la sesión
      if (session_status() === PHP_SESSION_NONE) {
         // Configuraciones de seguridad
         ini_set('session.cookie_httponly', '1');
         ini_set('session.cookie_secure', $this->isHttps() ? '1' : '0');
         ini_set('session.cookie_samesite', 'Strict');
         ini_set('session.use_only_cookies', '1');
         ini_set('session.cookie_lifetime', '0'); // Session cookie

         // Nombre de sesión personalizado
         session_name($this->sessionName);
      }
   }

   /**
    * Detecta si la conexión es HTTPS
    */
   private function isHttps(): bool {
      return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
         $_SERVER['SERVER_PORT'] == 443 ||
         (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
   }

   /**
    * Obtiene la IP del cliente
    */
   private function getClientIp(): string {
      $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

      foreach ($ipKeys as $key) {
         if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if (strpos($ip, ',') !== false) {
               $ip = trim(explode(',', $ip)[0]);
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
               return $ip;
            }
         }
      }

      return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
   }

   /**
    * Limpia sesiones expiradas (para uso en cronjobs)
    */
   public static function cleanupExpiredSessions(): int {
      // Esta implementación dependería del handler de sesión usado
      // Por ahora retornamos 0 como placeholder
      return 0;
   }
}
