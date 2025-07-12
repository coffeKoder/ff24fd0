<?php

declare(strict_types=1);

namespace Viex\Modules\User\Infrastructure\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Viex\Modules\User\Application\Services\UserService;
use Viex\Modules\User\Application\Services\SessionService;
use Viex\Modules\User\Domain\Exceptions\UserNotFoundException;
use Viex\Modules\User\Domain\Exceptions\InvalidCredentialsException;

/**
 * Controlador para gestión del perfil del usuario autenticado
 */
final class ProfileController {
   public function __construct(
      private UserService $userService,
      private SessionService $sessionService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Obtener perfil del usuario autenticado
    */
   public function getProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
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

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'data' => [
                  'profile' => $this->formatUserProfile($user)
               ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (\Exception $e) {
         $this->logger->error('Error obteniendo perfil', [
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
    * Actualizar perfil del usuario autenticado
    */
   public function updateProfile(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
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

         $data = $request->getParsedBody();

         $validationErrors = $this->validateProfileData($data);
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

         $this->logger->info('Perfil actualizado exitosamente', [
            'user_id' => $user->getId()
         ]);

         return $this->createJsonResponse($response, [
            'status' => 'success',
            'message' => 'Perfil actualizado exitosamente',
            'data' => [
                  'profile' => $this->formatUserProfile($user)
               ]
         ]);

      } catch (UserNotFoundException $e) {
         return $this->createJsonResponse($response, [
            'status' => 'error',
            'message' => 'Usuario no encontrado'
         ], 404);

      } catch (\Exception $e) {
         $this->logger->error('Error actualizando perfil', [
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
    * Cambiar contraseña del usuario autenticado
    */
   public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
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

         if (strlen($data['new_password']) < 8) {
            return $this->createJsonResponse($response, [
               'status' => 'error',
               'message' => 'La nueva contraseña debe tener al menos 8 caracteres'
            ], 400);
         }

         $this->userService->changePassword(
            $userId,
            $data['current_password'],
            $data['new_password']
         );

         $this->logger->info('Contraseña cambiada exitosamente', [
            'user_id' => $userId
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
    * Validar datos de perfil
    */
   private function validateProfileData(array $data): array {
      $errors = [];

      if (isset($data['first_name']) && empty($data['first_name'])) {
         $errors['first_name'] = 'El nombre no puede estar vacío';
      }

      if (isset($data['last_name']) && empty($data['last_name'])) {
         $errors['last_name'] = 'El apellido no puede estar vacío';
      }

      if (isset($data['office_phone']) && !empty($data['office_phone'])) {
         if (!preg_match('/^[0-9\-\+\(\)\s]+$/', $data['office_phone'])) {
            $errors['office_phone'] = 'Formato de teléfono inválido';
         }
      }

      if (isset($data['main_organizational_unit_id']) && !empty($data['main_organizational_unit_id'])) {
         if (!is_numeric($data['main_organizational_unit_id']) || (int) $data['main_organizational_unit_id'] <= 0) {
            $errors['main_organizational_unit_id'] = 'ID de unidad organizacional inválido';
         }
      }

      return $errors;
   }

   /**
    * Formatear datos de perfil para respuesta
    */
   private function formatUserProfile($user): array {
      return [
         'id' => $user->getId(),
         'username' => $user->getUsername(),
         'email' => $user->getEmail(),
         'first_name' => $user->getFirstName(),
         'last_name' => $user->getLastName(),
         'full_name' => $user->getFirstName() . ' ' . $user->getLastName(),
         'cedula' => $user->getCedula(),
         'office_phone' => $user->getOfficePhone(),
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
