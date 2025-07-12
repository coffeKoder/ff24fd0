<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\UseCases;

use Viex\Modules\User\Application\Services\UserService;
use Viex\Modules\User\Application\Services\PermissionService;
use Viex\Modules\User\Domain\Entities\User;
use Viex\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use Viex\Modules\User\Domain\Exceptions\InvalidCredentialsException;
use Psr\Log\LoggerInterface;

/**
 * Use Case para crear un nuevo usuario en el sistema
 * 
 * Coordina el proceso completo de creación de usuario:
 * - Valida datos únicos (email, cedula)
 * - Crea el usuario con datos seguros
 * - Asigna permisos por defecto
 * - Registra la actividad
 */
final class CreateUserUseCase {
   public function __construct(
      private UserService $userService,
      private PermissionService $permissionService,
      private LoggerInterface $logger
   ) {
   }

   /**
    * Crea un nuevo usuario en el sistema
    */
   public function execute(CreateUserCommand $command): CreateUserResult {
      try {
         $this->logger->info('Iniciando creación de usuario', [
            'email' => $command->email,
            'cedula' => $command->cedula
         ]);

         // Validar comando
         $validationErrors = $command->validate();
         if (!empty($validationErrors)) {
            return new CreateUserResult(
               false,
               null,
               'Datos de entrada inválidos',
               $validationErrors
            );
         }

         // Crear el usuario (UserService maneja las validaciones de duplicados)
         $user = $this->userService->createUser(
            $command->email,
            $command->password,
            $command->firstName,
            $command->lastName,
            $command->cedula,
            $command->professorCode,
            $command->mainOrganizationalUnitId
         );

         // Cargar permisos del usuario recién creado
         $permissions = $this->permissionService->getUserPermissions($user);

         $this->logger->info('Usuario creado exitosamente', [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'cedula' => $user->getCedula()
         ]);

         return new CreateUserResult(
            true,
            $user,
            'Usuario creado exitosamente'
         );

      } catch (UserAlreadyExistsException $e) {
         $this->logger->warning('Intento de crear usuario duplicado', [
            'email' => $command->email,
            'cedula' => $command->cedula,
            'error' => $e->getMessage()
         ]);

         return new CreateUserResult(
            false,
            null,
            $e->getMessage(),
            ['duplicate' => $e->getMessage()]
         );

      } catch (\Exception $e) {
         $this->logger->error('Error durante creación de usuario', [
            'email' => $command->email,
            'cedula' => $command->cedula,
            'error' => $e->getMessage()
         ]);

         return new CreateUserResult(
            false,
            null,
            'Error durante la creación del usuario',
            ['general' => $e->getMessage()]
         );
      }
   }

   /**
    * Valida si un email está disponible
    */
   public function isEmailAvailable(string $email): bool {
      return !$this->userService->userExistsByEmail($email);
   }

   /**
    * Valida si una cédula está disponible
    */
   public function isCedulaAvailable(string $cedula): bool {
      return !$this->userService->userExistsByCedula($cedula);
   }

   /**
    * Obtiene sugerencias de username basadas en nombre y apellido
    */
   public function generateEmailSuggestions(string $firstName, string $lastName): array {
      $suggestions = [];
      $baseName = strtolower($firstName . '.' . $lastName);
      $baseNameNoSpaces = str_replace([' ', '.'], '', $baseName);

      // Sugerencias básicas
      $suggestions[] = $baseName . '@universidad.edu';
      $suggestions[] = $baseNameNoSpaces . '@universidad.edu';
      $suggestions[] = substr($firstName, 0, 1) . '.' . strtolower($lastName) . '@universidad.edu';
      $suggestions[] = strtolower($firstName) . substr($lastName, 0, 1) . '@universidad.edu';

      // Filtrar solo los disponibles
      $availableSuggestions = [];
      foreach ($suggestions as $suggestion) {
         if ($this->isEmailAvailable($suggestion)) {
            $availableSuggestions[] = $suggestion;
         } else {
            // Intentar con números
            for ($i = 1; $i <= 99; $i++) {
               $numberedSuggestion = str_replace('@', $i . '@', $suggestion);
               if ($this->isEmailAvailable($numberedSuggestion)) {
                  $availableSuggestions[] = $numberedSuggestion;
                  break;
               }
            }
         }
      }

      return array_slice($availableSuggestions, 0, 5); // Máximo 5 sugerencias
   }
}

/**
 * Comando para crear un usuario
 */
final class CreateUserCommand {
   public string $email;
   public string $password;
   public string $firstName;
   public string $lastName;
   public string $cedula;
   public ?bool $isActive;
   public ?string $professorCode;
   public ?int $mainOrganizationalUnitId;

   public function __construct(
      string $email,
      string $password,
      string $firstName,
      string $lastName,
      string $cedula,
      ?bool $isActive = true,
      ?string $professorCode = null,
      ?int $mainOrganizationalUnitId = null
   ) {
      $this->email = $email;
      $this->password = $password;
      $this->firstName = $firstName;
      $this->lastName = $lastName;
      $this->cedula = $cedula;
      $this->isActive = $isActive;
      $this->professorCode = $professorCode;
      $this->mainOrganizationalUnitId = $mainOrganizationalUnitId;
   }

   public static function fromArray(array $data): self {
      return new self(
         $data['email'] ?? '',
         $data['password'] ?? '',
         $data['firstName'] ?? '',
         $data['lastName'] ?? '',
         $data['cedula'] ?? '',
         $data['isActive'] ?? true,
         $data['professorCode'] ?? null,
         $data['mainOrganizationalUnitId'] ?? null
      );
   }

   public function toArray(): array {
      return [
         'email' => $this->email,
         'firstName' => $this->firstName,
         'lastName' => $this->lastName,
         'cedula' => $this->cedula,
         'isActive' => $this->isActive,
         'professorCode' => $this->professorCode,
         'mainOrganizationalUnitId' => $this->mainOrganizationalUnitId
      ];
   }

   public function validate(): array {
      $errors = [];

      if (empty($this->email)) {
         $errors[] = 'Email es requerido';
      } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
         $errors[] = 'Email no tiene formato válido';
      }

      if (empty($this->password)) {
         $errors[] = 'Password es requerido';
      } elseif (strlen($this->password) < 8) {
         $errors[] = 'Password debe tener al menos 8 caracteres';
      }

      if (empty($this->firstName)) {
         $errors[] = 'Nombre es requerido';
      }

      if (empty($this->lastName)) {
         $errors[] = 'Apellido es requerido';
      }

      if (empty($this->cedula)) {
         $errors[] = 'Cédula es requerida';
      }

      return $errors;
   }
}

/**
 * Resultado de la creación de usuario
 */
final class CreateUserResult {
   public function __construct(
      private bool $success,
      private ?User $user = null,
      private string $message = '',
      private array $errors = []
   ) {
   }

   public function isSuccessful(): bool {
      return $this->success;
   }

   public function getUser(): ?User {
      return $this->user;
   }

   public function getMessage(): string {
      return $this->message;
   }

   public function getErrors(): array {
      return $this->errors;
   }

   public function hasErrors(): bool {
      return !empty($this->errors);
   }

   public function toArray(): array {
      return [
         'success' => $this->success,
         'user_id' => $this->user?->getId(),
         'email' => $this->user?->getEmail(),
         'username' => $this->user?->getUsername(),
         'message' => $this->message,
         'errors' => $this->errors
      ];
   }
}
