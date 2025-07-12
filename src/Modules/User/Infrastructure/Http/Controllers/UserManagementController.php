<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viex\Modules\User\Application\Services\UserService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Application\Services\PermissionService;
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Viex\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use Viex\Modules\User\Domain\Exceptions\InvalidCredentialsException;

/**
 * Controlador para gestión de usuarios
 */
final class UserManagementController {
   public function __construct(
      private UserService $userService,
      private SessionService $sessionService,
      private PermissionService $permissionService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Crear nuevo usuario
    */
   public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         // Verificar permisos
         if (!$this->hasPermission('users.create')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para crear usuarios'
            ], 403);
         }

         $data = $request->getParsedBody();

         $validationErrors = $this->validateUserCreationData($data);
         if (!empty($validationErrors)) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Datos de entrada inválidos',
               'errors' => $validationErrors
            ], 400);
         }

         $user = $this->userService->createUser(
            $data['email'],
            $data['password'],
            $data['first_name'],
            $data['last_name'],
            $data['cedula'],
            $data['professor_code'] ?? null,
            $data['main_organizational_unit_id'] ?? null
         );

         $this->logger->info('Usuario creado exitosamente', [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'created_by' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Usuario creado exitosamente',
            'data' => [
                  'user' => $this->formatUserData($user)
               ]
         ], 201);

      } catch (UserAlreadyExistsException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'El usuario ya existe',
            'errors' => ['email' => 'Este email ya está en uso']
         ], 409);

      } catch (\Exception $e) {
         $this->logger->error('Error creando usuario', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'data' => $data ?? []
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Obtener usuario por ID
    */
   public function getById(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.read')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para ver usuarios'
            ], 403);
         }

         $userId = (int) $request->getAttribute('id');

         $user = $this->userService->findUserById($userId);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'data' => [
               'user' => $this->formatUserData($user)
            ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (\Exception $e) {
         $this->logger->error('Error obteniendo usuario', [
            'error' => $e->getMessage(),
            'user_id' => $userId ?? null
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Actualizar usuario
    */
   public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.update')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para actualizar usuarios'
            ], 403);
         }

         $userId = (int) $request->getAttribute('id');
         $data = $request->getParsedBody();

         $validationErrors = $this->validateUserUpdateData($data);
         if (!empty($validationErrors)) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Datos de entrada inválidos',
               'errors' => $validationErrors
            ], 400);
         }

         $user = $this->userService->updateUserProfile(
            $userId,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['office_phone'] ?? null,
            $data['professor_code'] ?? null,
            $data['main_organizational_unit_id'] ?? null
         );

         $this->logger->info('Usuario actualizado exitosamente', [
            'user_id' => $user->getId(),
            'updated_by' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Usuario actualizado exitosamente',
            'data' => [
               'user' => $this->formatUserData($user)
            ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (UserAlreadyExistsException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'El usuario ya existe',
            'errors' => ['email' => 'Este email ya está en uso']
         ], 409);

      } catch (\Exception $e) {
         $this->logger->error('Error actualizando usuario', [
            'error' => $e->getMessage(),
            'user_id' => $userId ?? null
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Activar usuario
    */
   public function activate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.manage_status')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para cambiar estado de usuarios'
            ], 403);
         }

         $userId = (int) $request->getAttribute('id');

         $user = $this->userService->activateUser($userId);

         $this->logger->info('Usuario activado', [
            'user_id' => $user->getId(),
            'activated_by' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Usuario activado exitosamente',
            'data' => [
               'user' => $this->formatUserData($user)
            ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (\Exception $e) {
         $this->logger->error('Error activando usuario', [
            'error' => $e->getMessage(),
            'user_id' => $userId ?? null
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Desactivar usuario
    */
   public function deactivate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.manage_status')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para cambiar estado de usuarios'
            ], 403);
         }

         $userId = (int) $request->getAttribute('id');

         $user = $this->userService->deactivateUser($userId);

         $this->logger->info('Usuario desactivado', [
            'user_id' => $user->getId(),
            'deactivated_by' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Usuario desactivado exitosamente',
            'data' => [
               'user' => $this->formatUserData($user)
            ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (\Exception $e) {
         $this->logger->error('Error desactivando usuario', [
            'error' => $e->getMessage(),
            'user_id' => $userId ?? null
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Cambiar contraseña de usuario
    */
   public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.change_password')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para cambiar contraseñas'
            ], 403);
         }

         $userId = (int) $request->getAttribute('id');
         $data = $request->getParsedBody();

         if (empty($data['current_password']) || empty($data['new_password'])) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Contraseña actual y nueva son requeridas',
               'errors' => [
                  'current_password' => 'La contraseña actual es requerida',
                  'new_password' => 'La nueva contraseña es requerida'
               ]
            ], 400);
         }

         $this->userService->changePassword(
            $userId,
            $data['current_password'],
            $data['new_password']
         );

         $this->logger->info('Contraseña cambiada exitosamente', [
            'user_id' => $userId,
            'changed_by' => $this->sessionService->getCurrentUserId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Contraseña cambiada exitosamente'
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (InvalidCredentialsException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Contraseña actual incorrecta'
         ], 400);

      } catch (\Exception $e) {
         $this->logger->error('Error cambiando contraseña', [
            'error' => $e->getMessage(),
            'user_id' => $userId ?? null
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Listar usuarios con filtros
    */
   public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
      try {
         if (!$this->hasPermission('users.list')) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'Sin permisos para listar usuarios'
            ], 403);
         }

         $params = $request->getQueryParams();

         $criteria = [];
         $orderBy = ['created_at' => 'DESC'];
         $limit = isset($params['limit']) ? (int) $params['limit'] : 50;
         $offset = isset($params['offset']) ? (int) $params['offset'] : 0;

         // Filtros
         if (!empty($params['search'])) {
            $criteria['search'] = $params['search'];
         }
         if (isset($params['is_active'])) {
            $criteria['is_active'] = filter_var($params['is_active'], FILTER_VALIDATE_BOOLEAN);
         }
         if (!empty($params['organizational_unit_id'])) {
            $criteria['organizational_unit_id'] = (int) $params['organizational_unit_id'];
         }

         // Ordenamiento
         if (!empty($params['sort_by'])) {
            $sortBy = $params['sort_by'];
            $sortDirection = strtoupper($params['sort_direction'] ?? 'ASC');
            if (in_array($sortDirection, ['ASC', 'DESC'])) {
               $orderBy = [$sortBy => $sortDirection];
            }
         }

         $users = $this->userService->getUsersList($criteria, $orderBy, $limit, $offset);

         $formattedUsers = array_map([$this, 'formatUserData'], $users);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'data' => [
               'users' => $formattedUsers,
               'pagination' => [
                  'limit' => $limit,
                  'offset' => $offset,
                  'count' => count($formattedUsers)
               ]
            ]
         ]);

      } catch (\Exception $e) {
         $this->logger->error('Error listando usuarios', [
            'error' => $e->getMessage(),
            'params' => $params ?? []
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Error interno del servidor'
         ], 500);
      }
   }

   /**
    * Verificar si el usuario actual tiene un permiso específico
    */
   private function hasPermission(string $permission): bool {
      if (!$this->sessionService->isActive()) {
         return false;
      }

      return $this->sessionService->hasPermission($permission);
   }

   /**
    * Validar datos para creación de usuario
    */
   private function validateUserCreationData(array $data): array {
      $errors = [];

      if (empty($data['username'])) {
         $errors['username'] = 'El nombre de usuario es requerido';
      }

      if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
         $errors['email'] = 'Email válido es requerido';
      }

      if (empty($data['password']) || strlen($data['password']) < 8) {
         $errors['password'] = 'Contraseña de al menos 8 caracteres es requerida';
      }

      if (empty($data['first_name'])) {
         $errors['first_name'] = 'El nombre es requerido';
      }

      if (empty($data['last_name'])) {
         $errors['last_name'] = 'El apellido es requerido';
      }

      if (empty($data['cedula'])) {
         $errors['cedula'] = 'La cédula es requerida';
      }

      return $errors;
   }

   /**
    * Validar datos para actualización de usuario
    */
   private function validateUserUpdateData(array $data): array {
      $errors = [];

      if (isset($data['email']) && (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
         $errors['email'] = 'Email válido es requerido';
      }

      if (isset($data['username']) && empty($data['username'])) {
         $errors['username'] = 'El nombre de usuario no puede estar vacío';
      }

      if (isset($data['first_name']) && empty($data['first_name'])) {
         $errors['first_name'] = 'El nombre no puede estar vacío';
      }

      if (isset($data['last_name']) && empty($data['last_name'])) {
         $errors['last_name'] = 'El apellido no puede estar vacío';
      }

      return $errors;
   }

   /**
    * Formatear datos de usuario para respuesta
    */
   private function formatUserData($user): array {
      return [
         'id' => $user->getId(),
         'username' => $user->getUsername(),
         'email' => $user->getEmail(),
         'first_name' => $user->getFirstName(),
         'last_name' => $user->getLastName(),
         'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
         'cedula' => $user->getCedula(),
         'is_active' => $user->isActive(),
         'email_verified' => $user->isEmailVerified(),
         'email_verified_at' => $user->getEmailVerifiedAt()?->format('Y-m-d H:i:s'),
         'professor_code' => $user->getProfessorCode(),
         'main_organizational_unit_id' => $user->getMainOrganizationalUnitId(),
         'last_login' => $user->getLastLogin()?->format('Y-m-d H:i:s'),
         'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s'),
         'updated_at' => $user->getUpdatedAt()->format('Y-m-d H:i:s')
      ];
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
