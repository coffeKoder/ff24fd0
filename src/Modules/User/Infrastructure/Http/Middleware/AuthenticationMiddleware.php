<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Viex\Modules\User\Application\Services\SessionService;

/**
 * Middleware de autenticación para verificar sesiones activas
 */
final class AuthenticationMiddleware implements MiddlewareInterface {
   public function __construct(
      private SessionService $sessionService,
      private LoggerInterface $logger
   ) {
   }

   public function process(
      ServerRequestInterface $request,
      RequestHandlerInterface $handler
   ): ResponseInterface {

      // Verificar si hay una sesión activa
      if (!$this->sessionService->isActive()) {
         $this->logger->warning('Acceso denegado: sesión no activa', [
            'uri' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp($request)
         ]);

         return $this->createUnauthorizedResponse();
      }

      // Verificar si la sesión no ha expirado
      $userId = $this->sessionService->getCurrentUserId();
      if (!$userId) {
         $this->logger->warning('Acceso denegado: usuario no encontrado en sesión', [
            'uri' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'ip' => $this->getClientIp($request)
         ]);

         return $this->createUnauthorizedResponse();
      }

      // Agregar información del usuario al request
      $request = $request->withAttribute('user_id', $userId);
      $request = $request->withAttribute('user_email', $this->sessionService->getCurrentUserEmail());

      // Log de acceso autorizado
      $this->logger->info('Acceso autorizado', [
         'user_id' => $userId,
         'uri' => $request->getUri()->getPath(),
         'method' => $request->getMethod(),
         'ip' => $this->getClientIp($request)
      ]);

      return $handler->handle($request);
   }

   /**
    * Crear respuesta de no autorizado
    */
   private function createUnauthorizedResponse(): ResponseInterface {
      $response = new \Slim\Psr7\Response();

      $data = [
         'status' => 'error',
         'message' => 'Acceso no autorizado. Sesión requerida.',
         'code' => 401
      ];

      $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

      return $response
         ->withHeader('Content-Type', 'application/json')
         ->withStatus(401);
   }

   /**
    * Obtener IP del cliente
    */
   private function getClientIp(ServerRequestInterface $request): string {
      $serverParams = $request->getServerParams();

      // Verificar headers de proxy
      $ipHeaders = [
         'HTTP_X_FORWARDED_FOR',
         'HTTP_X_REAL_IP',
         'HTTP_CLIENT_IP',
         'REMOTE_ADDR'
      ];

      foreach ($ipHeaders as $header) {
         if (!empty($serverParams[$header])) {
            $ip = $serverParams[$header];
            // Si es una lista de IPs, tomar la primera
            if (strpos($ip, ',') !== false) {
               $ip = trim(explode(',', $ip)[0]);
            }
            return $ip;
         }
      }

      return 'unknown';
   }
}
