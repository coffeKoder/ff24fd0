# User Module - Core Authentication & User Management

Este módulo es el **núcleo de la gestión de usuarios y autenticación** del sistema VIEX. Contiene toda la lógica de negocio relacionada con usuarios, roles, permisos y sesiones. El módulo `Auth` actúa como una fachada que delega todas las operaciones a este módulo.

## Arquitectura y Responsabilidades

### Principio de Centralización

El módulo User implementa el **patrón Domain-Driven Design (DDD)** para:

-  Centralizar toda la lógica de usuarios y autenticación
-  Mantener la integridad del dominio de identidad
-  Proporcionar servicios especializados para Auth y otros módulos
-  Gestionar el ciclo de vida completo de usuarios

### Responsabilidades Principales

#### ✅ Lógica de Negocio Core:

-  **Autenticación**: Validación de credenciales y generación de sesiones
-  **Gestión de Usuarios**: CRUD completo de usuarios
-  **Sistema RBAC**: Roles, permisos y autorización
-  **Seguridad**: Hashing de passwords, tokens, rate limiting
-  **Sesiones**: Gestión completa del ciclo de vida de sesiones
-  **Recuperación**: Password reset y recuperación de cuentas

#### 🔄 Servicios Expuestos:

-  `LoginService`: Para autenticación (usado por Auth)
-  `UserService`: Para gestión de usuarios
-  `SessionService`: Para manejo de sesiones
-  `TokenService`: Para generación y validación de tokens
-  `PasswordService`: Para gestión de contraseñas
-  `PermissionService`: Para verificación de permisos

## Estructura del Módulo

### Domain Layer

```
src/User/Domain/
├── Entities/
│   ├── User.php                 # Entidad principal de usuario
│   ├── UserGroup.php           # Roles/grupos de usuario
│   ├── Permission.php          # Permisos del sistema
│   └── PasswordReset.php       # Tokens de recuperación
├── ValueObjects/
│   ├── UserId.php
│   ├── Email.php
│   ├── Credentials.php
│   └── Password.php
├── Repositories/
│   ├── UserRepositoryInterface.php
│   ├── UserGroupRepositoryInterface.php
│   └── PermissionRepositoryInterface.php
└── Exceptions/
    ├── UserNotFoundException.php
    ├── InvalidCredentialsException.php
    └── UserAlreadyExistsException.php
```

### Application Layer

```
src/User/Application/
├── Services/
│   ├── LoginService.php         # Servicio de autenticación
│   ├── UserService.php          # Gestión de usuarios
│   ├── SessionService.php       # Gestión de sesiones
│   ├── TokenService.php         # Gestión de tokens
│   ├── PasswordService.php      # Gestión de contraseñas
│   ├── PermissionService.php    # Verificación de permisos
│   └── RoleService.php          # Gestión de roles
├── UseCases/
│   ├── AuthenticateUser.php
│   ├── CreateUser.php
│   ├── UpdateUserProfile.php
│   ├── ChangePassword.php
│   ├── ResetPassword.php
│   └── AssignUserRole.php
├── DTOs/
│   ├── UserDTO.php
│   ├── LoginDTO.php
│   └── UserProfileDTO.php
└── Events/
    ├── UserLoggedIn.php
    ├── UserLoggedOut.php
    └── PasswordChanged.php
```

### Infrastructure Layer

```
src/User/Infrastructure/
├── Persistence/
│   └── Doctrine/
│       ├── DoctrineUserRepository.php
│       ├── DoctrineUserGroupRepository.php
│       ├── DoctrinePermissionRepository.php
│       ├── DoctrinePasswordResetRepository.php
│       └── DoctrineUserUserGroupRepository.php
├── Security/
│   ├── PasswordHasher.php
│   ├── TokenGenerator.php
│   └── RateLimiter.php
└── Http/
    ├── ProfileController.php
    └── UserManagementController.php
```

## Servicios Principales

### LoginService ✅ IMPLEMENTADO

Servicio central de autenticación usado por el módulo Auth.

```php
<?php
class LoginService
{
    public function authenticate(Credentials $credentials): User
    {
        // 1. Validar credenciales
        $user = $this->userRepository->findByEmail($credentials->getEmail()->getValue());

        if (!$user || !$this->passwordService->verify($credentials->getPassword()->getValue(), $user->getPasswordHash())) {
            throw new InvalidCredentialsException();
        }

        // 2. Verificar estado del usuario
        if (!$user->isActive()) {
            throw new InactiveUserException();
        }

        // 3. Generar sesión
        $this->sessionService->create($user);

        // 4. Cargar permisos en sesión
        $this->loadUserPermissions($user);

        return $user;
    }

    private function loadUserPermissions(User $user): void
    {
        $permissions = $this->permissionService->getUserPermissions($user);
        $this->sessionService->setPermissions($permissions);
    }
}
```

### UserService ✅ IMPLEMENTADO

Gestión completa del ciclo de vida de usuarios.

```php
<?php
class UserService
{
    public function createUser(
        string $email,
        string $plainPassword,
        string $fullName,
        string $cedula,
        ?string $professorCode = null,
        ?int $mainOrganizationalUnitId = null
    ): User {
        // Validación de reglas de negocio
        $this->validateUserCreation($email, $cedula, $plainPassword);

        // Crear entidad
        $user = new User(
            $email, // username
            $email, // email
            $this->passwordService->hash($plainPassword),
            $firstName,
            $lastName,
            $cedula
        );

        // Persistir
        $this->userRepository->save($user);

        return $user;
    }

    public function updateUserProfile(int $userId, ...): User
    public function changePassword(int $userId, string $currentPassword, string $newPassword): void
    public function resetPassword(int $userId, string $newPassword): User
    public function activateUser(int $userId): User
    public function deactivateUser(int $userId): User
}
```

### PasswordService ✅ IMPLEMENTADO

Servicio especializado en gestión de contraseñas con validaciones de seguridad.

```php
<?php
class PasswordService
{
    public function hash(string $password): string
    public function verify(string $password, string $hash): bool
    public function validatePassword(string $password): array
    public function generateRandomPassword(int $length = 12): string
    public function isSecurePassword(string $password): bool
}
```

### PermissionService ✅ IMPLEMENTADO

Sistema RBAC para verificación de permisos.

```php
<?php
class PermissionService
{
    public function getUserPermissions(User $user): array
    public function getUserGroups(User $user): array
    public function getUserPermissionsInContext(User $user, int $organizationalUnitId): array
    public function userHasPermission(User $user, string $permission): bool
    public function userHasPermissionInContext(User $user, string $permission, int $organizationalUnitId): bool
    public function userHasRole(User $user, string $roleName): bool
    public function userHasRoleInUnit(User $user, string $roleName, int $organizationalUnitId): bool
}
```

### SessionService ✅ IMPLEMENTADO

Servicio de gestión de sesiones nativas de PHP con seguridad y timeouts.

```php
<?php
class SessionService
{
    public function create(User $user): void
    public function destroy(): void
    public function isActive(): bool
    public function getCurrentUserId(): ?int
    public function setPermissions(array $permissions): void
    public function getPermissions(): array
    public function hasPermission(string $permission): bool
    public function setUserGroups(array $userGroups): void
    public function hasRole(string $role): bool
}
```

### TokenService ✅ IMPLEMENTADO

Servicio de generación y validación de tokens para diferentes propósitos.

```php
<?php
class TokenService
{
    public function generatePasswordResetToken(string $email, ?int $expirationMinutes = null): PasswordReset
    public function validatePasswordResetToken(string $email, string $token): ?PasswordReset
    public function generateJwtToken(User $user, int $expirationMinutes = 60): string
    public function validateJwtToken(string $token): ?array
    public function cleanupExpiredTokens(): int
}
```

### RoleService ✅ IMPLEMENTADO

Servicio de gestión de roles y asignaciones contextuales RBAC.

```php
<?php
class RoleService
{
    public function createUserGroup(string $name, string $description): UserGroup
    public function assignUserToGroup(int $userId, int $groupId, int $organizationalUnitId): UserUserGroup
    public function removeUserFromGroup(int $userId, int $groupId, int $organizationalUnitId): void
    public function assignPermissionToGroup(int $groupId, int $permissionId): void
    public function removePermissionFromGroup(int $groupId, int $permissionId): void
    public function getUserRoles(int $userId): array
    public function userHasRoleInUnit(int $userId, string $roleName, int $organizationalUnitId): bool
}
```

## Entidades Principales

### User Entity

```php
<?php
#[Entity]
#[Table(name: 'users')]
class User
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[Column(type: 'string', unique: true)]
    private string $email;

    #[Column(type: 'string')]
    private string $passwordHash;

    #[Column(type: 'string')]
    private string $fullName;

    #[Column(type: 'string', unique: true)]
    private string $cedula;

    #[Column(type: 'boolean')]
    private bool $isActive = true;

    #[ManyToMany(targetEntity: UserGroup::class)]
    #[JoinTable(name: 'user_user_groups')]
    private Collection $userGroups;

    // ... métodos
}
```

### UserGroup Entity (Roles)

```php
<?php
#[Entity]
#[Table(name: 'user_groups')]
class UserGroup
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    private int $id;

    #[Column(type: 'string', unique: true)]
    private string $name;

    #[Column(type: 'string')]
    private string $description;

    #[ManyToMany(targetEntity: Permission::class)]
    #[JoinTable(name: 'user_group_permissions')]
    private Collection $permissions;

    // ... métodos
}
```

## Flujos de Autenticación

### 1. Login Flow

```
Auth\LoginController → Auth\AuthGateway → User\LoginService
```

1. **Gateway recibe request**: Auth\AuthGateway
2. **Validación básica**: Formato de datos
3. **Delegación**: User\LoginService->authenticate()
4. **Validación de credenciales**: Password verification
5. **Creación de sesión**: SessionService
6. **Carga de permisos**: PermissionService
7. **Respuesta**: AuthResponse con token/session

### 2. Permission Check Flow

```
Middleware → Auth\TokenGateway → User\PermissionService
```

1. **Request interceptado**: AuthMiddleware
2. **Token validation**: User\TokenService
3. **Permission check**: User\PermissionService
4. **Decision**: Allow/Deny access

### 3. Profile Management Flow

```
User\ProfileController → User\UserService → User\UserRepository
```

## Configuración y Uso

### Inyección de Dependencias

```php
// En app/dependencies.php

// Core services
$container->set(LoginService::class, function (ContainerInterface $c) {
    return new LoginService(
        $c->get(UserRepositoryInterface::class),
        $c->get(PasswordService::class),
        $c->get(SessionService::class),
        $c->get(PermissionService::class)
    );
});

$container->set(UserService::class, function (ContainerInterface $c) {
    return new UserService(
        $c->get(UserRepositoryInterface::class),
        $c->get(PasswordService::class)
    );
});

// Repositories
$container->set(UserRepositoryInterface::class, function (ContainerInterface $c) {
    return new UserRepository($c->get(EntityManager::class));
});
```

### Rutas de Usuario

```php
// En app/routes.php
$app->group('/user', function (Group $group) {
    $group->get('/profile', [ProfileController::class, 'show'])
        ->add(new AuthMiddleware());

    $group->put('/profile', [ProfileController::class, 'update'])
        ->add(new AuthMiddleware());

    $group->post('/change-password', [ProfileController::class, 'changePassword'])
        ->add(new AuthMiddleware());
});

// Rutas administrativas
$app->group('/admin/users', function (Group $group) {
    $group->get('/', [UserManagementController::class, 'index']);
    $group->post('/', [UserManagementController::class, 'create']);
    $group->put('/{id}', [UserManagementController::class, 'update']);
    $group->delete('/{id}', [UserManagementController::class, 'delete']);
})->add(new PermissionMiddleware('admin.users.manage'));
```

## Sistema RBAC

### Permisos Base del Sistema

```php
// Permisos de trabajos de extensión
'extension.work.view.own'
'extension.work.view.all'
'extension.work.create'
'extension.work.edit.own'
'extension.work.edit.all'
'extension.work.delete.own'
'extension.work.certify.viex'
'extension.work.certify.coordinator'

// Permisos administrativos
'admin.users.manage'
'admin.roles.manage'
'admin.system.config'

// Permisos organizacionales
'organizational.reports.view'
'organizational.analytics.access'
```

### Roles Predefinidos

```php
// Estudiante
['extension.work.view.own', 'extension.work.create', 'extension.work.edit.own']

// Coordinador de Extensión
['extension.work.view.all', 'extension.work.certify.coordinator']

// Staff VIEX
['extension.work.view.all', 'extension.work.certify.viex', 'admin.reports.view']

// Administrador
['admin.*'] // Todos los permisos administrativos
```

## Testing

### Unit Tests

```php
tests/User/Domain/UserTest.php
tests/User/Application/Services/LoginServiceTest.php
tests/User/Application/Services/UserServiceTest.php
tests/User/Application/Services/PermissionServiceTest.php
```

### Integration Tests

```php
tests/User/Infrastructure/UserRepositoryTest.php
tests/User/Integration/AuthenticationFlowTest.php
```

## Seguridad

### Password Security

-  Uso de `password_hash()` con `PASSWORD_DEFAULT`
-  Salt automático por PHP
-  Verificación con `password_verify()`

### Session Security

-  Regeneración de session ID en login
-  Timeout de sesiones inactivas
-  Invalidación en logout

### Rate Limiting

-  Límite de intentos de login por IP/email
-  Bloqueo temporal tras intentos fallidos
-  Logging de intentos sospechosos

## Relación con Otros Módulos

### 📤 Servicios Expuestos a:

-  **Auth**: LoginService, SessionService, TokenService
-  **Admin**: UserService, RoleService para administración
-  **Extension**: PermissionService para autorización
-  **Todos**: Verificación de permisos y autenticación

### 📥 Depende de:

-  **Shared**: Utilidades comunes, eventos, excepciones base
-  **Infrastructure**: EntityManager de Doctrine

## Documentación Relacionada

-  [Auth Module](../Auth/README.md) - Gateway de autenticación
-  [Shared Module](../Shared/README.md) - Utilidades comunes

2. **Asignación:** Un administrador asigna a un `UserEntity` el rol "Coordinador Extensión" en el contexto de una `OrganizationalUnit` específica.
3. **Login:** El usuario (coordinador) inicia sesión. El `AuthService` valida sus credenciales y, crucialmente, carga todos sus permisos (`['work.review.coordinator', ...]`) en la sesión.
4. **Acceso:** El coordinador intenta acceder a una ruta protegida por el middleware `Authorize('work.review.coordinator')`.
5. **Verificación:** El middleware `Authorize` consulta al `AuthService`. El `AuthService` simplemente revisa el array de permisos en la sesión. El acceso es concedido.

---

## RECURSOS

**Responsabilidad:** Todo lo relacionado con la identidad, el acceso, los roles y los permisos. Es el guardián del sistema.

-  **`users`**: Entidad central del módulo. Representa a la persona que interactúa con el sistema.
-  **`user_groups`**: Define los roles (Profesor, Decano, etc.). Es un concepto puro de autorización.
-  **`permissions`**: Catálogo de todas las acciones posibles en el sistema. Fundamental para la granularidad de la seguridad.
-  **`user_user_groups`**: Tabla de unión que vincula a un usuario con un rol en un contexto organizacional. Es el corazón del RBAC contextual.
-  **`user_group_permissions`**: Tabla de unión que define qué puede hacer cada rol.
-  **`password_reset_tokens`**: Íntimamente ligado a la gestión de cuentas de usuario, por lo que pertenece a este módulo.

---

## **Tabla:** `users`

**Descripción:** Usuarios del sistema.
**Relaciones:**

-  _Tablas de las que depende:_ `organizational_units`
-  _Tablas que dependen de ella:_ `attachments`, `certifications`, `extension_works`, `project_details`, `user_user_groups`, `work_participants`, `work_status_history`
   **Campos:**
-  `id`: Identificador de usuario.
-  `username`: Nombre de usuario.
-  `password_hash`: Contraseña cifrada.
-  `first_name`: Nombre.
-  `last_name`: Apellido.
-  `cedula`: Identificación nacional.
-  `email`: Correo electrónico.
-  `main_organizational_unit_id`: FK a la unidad organizacional principal.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

---

## **Tabla:** `user_groups`

**Descripción:** Perfiles o grupos de usuarios.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ `user_group_permissions`, `user_user_groups`
   **Campos:**
-  `id`: Identificador.
-  `name`: Nombre del grupo.
-  `description`: Descripción.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

---

## **Tabla:** `permissions`

**Descripción:** Lista de accesos dentro de la aplicación.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ `user_group_permissions`
   **Campos:**
-  `id`: Identificador.
-  `name`: Nombre del permiso.
-  `description`: Descripción del permiso.
-  `module`: Módulo relacionado.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

---

## **Tabla:** `user_user_groups`

**Descripción:** Asignación de grupos a usuarios.
**Relaciones:**

-  _Tablas de las que depende:_ `users`, `user_groups`, `organizational_units`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `user_id`: FK al usuario.
-  `user_group_id`: FK al grupo.
-  `organizational_unit_id`: FK a unidad.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `user_group_permissions`

**Descripción:** Relación entre grupos y permisos.
**Relaciones:**

-  _Tablas de las que depende:_ `user_groups`, `permissions`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `user_group_id`: FK al grupo de usuarios.
-  `permission_id`: FK al permiso.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `password_reset_tokens`

**Descripción:** Almacena tokens para la recuperación de contraseñas de usuarios.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `email`: Correo electrónico del usuario.
-  `token`: Token único para validar la recuperación.
-  `created_at`: Fecha/hora de creación del token.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

**Lógica de Dependencia:** Este módulo es fundamental y otros módulos dependerán de él para la autorización, pero él no dependerá de la lógica de negocio de los otros.

## Interfaces de Repositorio

### Patrón Repository con Interfaces

Todas las entidades del módulo User cuentan con interfaces de repositorio que definen contratos claros para las operaciones de persistencia, siguiendo el patrón Repository y facilitando la inyección de dependencias.

### UserRepositoryInterface

Interface principal para operaciones con usuarios:

```php
interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    public function findByCedula(string $cedula): ?User;
    public function findByRole(string $role): array;
    public function findByFaculty(string $faculty): array;
    public function findActiveUsers(): array;
    public function existsByEmail(string $email, ?int $excludeId = null): bool;
    public function existsByCedula(string $cedula, ?int $excludeId = null): bool;
}
```

### UserGroupRepositoryInterface

Interface para gestión de grupos/roles:

```php
interface UserGroupRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name): ?UserGroup;
    public function findActiveGroups(): array;
    public function findByPermissionModule(string $module): array;
    public function findByPermission(string $permission): array;
    public function findByUserId(int $userId): array;
    public function findByOrganizationalUnit(int $organizationalUnitId): array;
    public function existsByName(string $name, ?int $excludeId = null): bool;
    public function countActiveUsers(int $groupId): int;
    public function getStatistics(): array;
}
```

### PermissionRepositoryInterface

Interface para el sistema de permisos RBAC:

```php
interface PermissionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name): ?Permission;
    public function findActivePermissions(): array;
    public function findByModule(string $module): array;
    public function findByUserGroupId(int $userGroupId): array;
    public function findByUserId(int $userId): array;
    public function findByUserInContext(int $userId, int $organizationalUnitId): array;
    public function userHasPermission(int $userId, string $permission): bool;
    public function userHasPermissionInContext(int $userId, string $permission, int $organizationalUnitId): bool;
    public function getUniqueModules(): array;
    public function getStatisticsByModule(): array;
}
```

### UserUserGroupRepositoryInterface

Interface para RBAC contextual (asignaciones de roles en unidades organizacionales):

```php
interface UserUserGroupRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUserGroupAndUnit(int $userId, int $userGroupId, int $organizationalUnitId): ?UserUserGroup;
    public function findByUserId(int $userId): array;
    public function findActiveByUserId(int $userId): array;
    public function findUsersByGroupAndUnit(int $userGroupId, int $organizationalUnitId): array;
    public function findUserGroupsByUserAndUnit(int $userId, int $organizationalUnitId): array;
    public function existsActiveAssignment(int $userId, int $userGroupId, int $organizationalUnitId): bool;
    public function userHasRoleInUnit(int $userId, string $groupName, int $organizationalUnitId): bool;
    public function userCanActInUnit(int $userId, string $groupName, int $targetUnitId): bool;
}
```

### PasswordResetRepositoryInterface

Interface para tokens de recuperación de contraseñas:

```php
interface PasswordResetRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?PasswordReset;
    public function findByToken(string $token): ?PasswordReset;
    public function findValidTokenByEmail(string $email): ?PasswordReset;
    public function findValidTokenByToken(string $token): ?PasswordReset;
    public function findExpiredTokens(): array;
    public function existsActiveTokenForEmail(string $email): bool;
    public function deleteExpiredTokens(): int;
    public function deleteByEmail(string $email): int;
    public function cleanupOldTokens(int $retentionHours = 24): int;
}
```

### OrganizationalUnitRepositoryInterface

Interface para la estructura organizacional (módulo Organizational):

```php
interface OrganizationalUnitRepositoryInterface extends BaseRepositoryInterface
{
    public function findByName(string $name): ?OrganizationalUnit;
    public function findByType(string $type): array;
    public function findActiveUnits(): array;
    public function findRootUnits(): array;
    public function findByParentId(int $parentId): array;
    public function findHierarchyTree(): array;
    public function findAncestors(int $unitId): array;
    public function findDescendants(int $unitId): array;
    public function isAncestorOf(int $ancestorId, int $descendantId): bool;
    public function getStatisticsByType(): array;
    public function getUniqueTypes(): array;
}
```

### Características de las Interfaces

#### ✅ **Herencia de BaseRepositoryInterface**

-  Todas extienden `BaseRepositoryInterface` para operaciones CRUD básicas
-  Mantienen consistencia en el patrón de diseño Repository
-  Facilitan el testing y mocking

#### ✅ **Métodos Especializados por Dominio**

-  Cada interface incluye métodos específicos para su entidad
-  Búsquedas por campos únicos y combinaciones complejas
-  Operaciones de validación y verificación de existencia

#### ✅ **Soporte para RBAC Contextual**

-  Métodos especializados para verificación de permisos
-  Soporte para jerarquías organizacionales
-  Verificación de roles en contextos específicos

#### ✅ **Operaciones de Estadísticas**

-  Métodos para obtener métricas y contadores
-  Estadísticas agrupadas por diferentes criterios
-  Soporte para dashboards y reportes

#### ✅ **Gestión de Relaciones**

-  Métodos para navegar relaciones entre entidades
-  Búsquedas a través de relaciones Many-to-Many
-  Soporte para jerarquías y estructuras de árbol

## Casos de Uso Principales

### AuthenticateUser ✅ IMPLEMENTADO

Caso de uso para autenticación completa de usuarios.

```php
<?php
class AuthenticateUser
{
    public function execute(string $email, string $password): AuthenticateUserResult
    {
        $credentials = Credentials::fromStrings($email, $password);
        $user = $this->loginService->authenticate($credentials);

        return new AuthenticateUserResult(
            true,
            $user,
            'Autenticación exitosa'
        );
    }
}
```

### CreateUser ✅ IMPLEMENTADO

Caso de uso para creación de nuevos usuarios con validaciones completas.

```php
<?php
class CreateUser
{
    public function execute(CreateUserRequest $request): CreateUserResult
    {
        $user = $this->userService->createUser(
            $request->email,
            $request->password,
            $request->fullName,
            $request->cedula,
            $request->professorCode,
            $request->mainOrganizationalUnitId
        );

        return new CreateUserResult(true, UserDTO::fromEntity($user));
    }
}
```

### ChangePassword ✅ IMPLEMENTADO

Caso de uso para cambio seguro de contraseñas.

```php
<?php
class ChangePassword
{
    public function execute(ChangePasswordRequest $request): ChangePasswordResult
    {
        if ($request->newPassword !== $request->confirmPassword) {
            return new ChangePasswordResult(false, 'Las contraseñas no coinciden');
        }

        $this->userService->changePassword(
            $request->userId,
            $request->currentPassword,
            $request->newPassword
        );

        return new ChangePasswordResult(true, 'Contraseña cambiada exitosamente');
    }
}
```

### AssignUserRole ✅ IMPLEMENTADO

Caso de uso para asignación contextual de roles RBAC.

```php
<?php
class AssignUserRole
{
    public function execute(AssignUserRoleRequest $request): AssignUserRoleResult
    {
        $assignment = $this->roleService->assignUserToGroup(
            $request->userId,
            $request->roleId,
            $request->organizationalUnitId
        );

        return new AssignUserRoleResult(true, 'Rol asignado exitosamente');
    }
}
```

## DTOs de la Capa de Aplicación

### UserDTO ✅ IMPLEMENTADO

DTO completo para transferencia de datos de usuario.

```php
<?php
class UserDTO
{
    public int $id;
    public string $username;
    public string $email;
    public string $firstName;
    public string $lastName;
    public string $cedula;
    public ?string $professorCode;
    public bool $isActive;
    // ... otros campos

    public static function fromEntity($user): self
    public function getFullName(): string
    public function toArray(): array
}
```

### LoginDTO ✅ IMPLEMENTADO

DTO para credenciales de autenticación.

```php
<?php
class LoginDTO
{
    public string $email;
    public string $password;
    public bool $rememberMe;

    public function isValid(): bool
    public function toArray(): array
}
```

### UserProfileDTO ✅ IMPLEMENTADO

DTO para actualización de perfil de usuario.

```php
<?php
class UserProfileDTO
{
    public ?string $firstName;
    public ?string $lastName;
    public ?string $officePhone;
    // ... otros campos editables

    public function hasDataToUpdate(): bool
    public function toArray(): array
}
```

## Eventos de Dominio

### UserLoggedIn ✅ IMPLEMENTADO

Evento disparado cuando un usuario se autentica exitosamente.

```php
<?php
class UserLoggedIn
{
    private User $user;
    private DateTimeImmutable $timestamp;
    private string $ipAddress;
    private string $userAgent;

    public function toArray(): array
}
```

### UserLoggedOut ✅ IMPLEMENTADO

Evento disparado cuando un usuario cierra sesión.

### PasswordChanged ✅ IMPLEMENTADO

Evento disparado cuando se cambia una contraseña exitosamente.
