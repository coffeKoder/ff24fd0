<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Application\Services\PermissionService;

/**
 * Middleware de autorización para verificar permisos
 */
final class AuthorizationMiddleware implements MiddlewareInterface {
   private array $requiredPermissions;

   public function __construct(
      private SessionService $sessionService,
      private PermissionService $permissionService,
      private LoggerInterface $logger,
      array $requiredPermissions = []
   ) {
      $this->requiredPermissions = $requiredPermissions;
   }

   /**
    * Configurar permisos requeridos
    */
   public function withPermissions(array $permissions): self {
      $clone = clone $this;
      $clone->requiredPermissions = $permissions;
      return $clone;
   }

   public function process(
      ServerRequestInterface $request,
      RequestHandlerInterface $handler
   ): ResponseInterface {

      // Si no hay permisos requeridos, continuar
      if (empty($this->requiredPermissions)) {
         return $handler->handle($request);
      }

      $userId = $this->sessionService->getCurrentUserId();

      if (!$userId) {
         $this->logger->warning('Autorización denegada: usuario no encontrado', [
            'uri' => $request->getUri()->getPath(),
            'required_permissions' => $this->requiredPermissions
         ]);

         return $this->createForbiddenResponse('Usuario no autenticado');
      }

      // Verificar cada permiso requerido
      foreach ($this->requiredPermissions as $permission) {
         if (!$this->sessionService->hasPermission($permission)) {
            $this->logger->warning('Autorización denegada: permiso insuficiente', [
               'user_id' => $userId,
               'uri' => $request->getUri()->getPath(),
               'required_permission' => $permission,
               'user_permissions' => $this->sessionService->getPermissions()
            ]);

            return $this->createForbiddenResponse("Permiso requerido: {$permission}");
         }
      }

      // Log de autorización exitosa
      $this->logger->info('Autorización exitosa', [
         'user_id' => $userId,
         'uri' => $request->getUri()->getPath(),
         'verified_permissions' => $this->requiredPermissions
      ]);

      return $handler->handle($request);
   }

   /**
    * Crear respuesta de prohibido
    */
   private function createForbiddenResponse(string $message): ResponseInterface {
      $response = new \Slim\Psr7\Response();

      $data = [
         'status' => 'error',
         'message' => 'Acceso prohibido. ' . $message,
         'code' => 403
      ];

      $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

      return $response
         ->withHeader('Content-Type', 'application/json')
         ->withStatus(403);
   }
}
