<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viex\Modules\User\Application\Services\LoginService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Application\Services\UserService;
use Viex\Modules\User\Domain\ValueObjects\Credentials;
use Viex\Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Viex\Modules\User\Domain\Exceptions\InactiveUserException;

/**
 * Controlador para autenticación de usuarios
 */
final class AuthController {
   public function __construct(
      private LoginService $loginService,
      private SessionService $sessionService,
      private UserService $userService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Iniciar sesión de usuario
    */
   public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         $data = $request->getParsedBody();

         if (!isset($data['identifier']) || !isset($data['password'])) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Credenciales requeridas',
               'errors' => [
                     'identifier' => 'El campo identificador es requerido',
                     'password' => 'El campo contraseña es requerido'
                  ]
            ], 400);
         }

         // Crear credenciales desde los datos del request
         $credentials = Credentials::fromStrings(
            (string) $data['identifier'],
            (string) $data['password']
         );

         $user = $this->loginService->authenticate($credentials);

         $this->logger->info('Usuario autenticado exitosamente', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Autenticación exitosa',
            'data' => [
                  'user' => [
                     'id' => $user->getId(),
                     'username' => $user->getUsername(),
                     'email' => $user->getEmail(),
                     'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
                     'is_active' => $user->isActive()
                  ]
               ]
         ]);

      } catch (InvalidCredentialsException | UserNotFoundException $e) {
         $this->logger->warning('Intento de login fallido', [
            'identifier' => $data['identifier'] ?? 'unknown',
            'error' => $e->getMessage()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Credenciales inválidas'
         ], 401);

      } catch (InactiveUserException $e) {
         $this->logger->warning('Intento de login con usuario inactivo', [
            'identifier' => $data['identifier'] ?? 'unknown'
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario inactivo. Contacte al administrador.'
         ], 403);

      } catch (\Exception $e) {
         $this->logger->error('Error interno durante autenticación', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Cerrar sesión del usuario
    */
   public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->sessionService->isActive()) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'No hay sesión activa'
            ], 401);
         }

         $this->sessionService->destroy();

         $this->logger->info('Sesión cerrada exitosamente');

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Sesión cerrada exitosamente'
         ]);

      } catch (\Exception $e) {
         $this->logger->error('Error durante el logout', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Obtener información del usuario autenticado
    */
   public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->sessionService->isActive()) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'No hay sesión activa'
            ], 401);
         }

         $userId = $this->sessionService->getCurrentUserId();

         if (!$userId) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Usuario no encontrado en sesión'
            ], 401);
         }

         $user = $this->userService->findUserById($userId);

         if (!$user) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Usuario no encontrado'
            ], 404);
         }

         $sessionInfo = $this->sessionService->getSessionInfo();

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'data' => [
               'user' => [
                  'id' => $user->getId(),
                  'username' => $user->getUsername(),
                  'email' => $user->getEmail(),
                  'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
                  'is_active' => $user->isActive(),
                  'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
                  'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
               ],
               'session' => [
                  'login_time' => date('Y-m-d H:i:s', $sessionInfo['login_time']),
                  'last_activity' => date('Y-m-d H:i:s', $sessionInfo['last_activity']),
                  'ip_address' => $sessionInfo['ip_address'],
                  'user_agent' => $sessionInfo['user_agent']
               ],
               'permissions' => $this->sessionService->getPermissions(),
               'user_groups' => $this->sessionService->getUserGroups()
            ]
         ]);

      } catch (\Exception $e) {
         $this->logger->error('Error obteniendo información del usuario', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Extender sesión activa
    */
   public function extendSession(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->sessionService->isActive()) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'No hay sesión activa'
            ], 401);
         }

         $data = $request->getParsedBody();
         $additionalMinutes = (int) ($data['minutes'] ?? 30);

         $this->sessionService->extendSession($additionalMinutes);

         $this->logger->info('Sesión extendida exitosamente', [
            'additional_minutes' => $additionalMinutes,
            'user_id' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Sesión extendida exitosamente',
            'data' => [
               'extended_minutes' => $additionalMinutes,
               'session_info' => $this->sessionService->getSessionInfo()
            ]
         ]);

      } catch (\Exception $e) {
         $this->logger->error('Error extendiendo sesión', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Crear respuesta JSON
    */
   private function createJsonResponse(
      ResponseInterface $response,
      array $data,
      int $status = 200
   ): ResponseInterface {
      $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));

      return $response
         ->withHeader('Content-Type', 'application/json')
         ->withStatus($status);
   }
}
