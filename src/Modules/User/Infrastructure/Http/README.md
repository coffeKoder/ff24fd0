# Infraestructura HTTP - Módulo User

Esta capa de infraestructura HTTP implementa los controladores, middlewares, DTOs de request/response y rutas para el módulo User, siguiendo los principios de arquitectura hexagonal y DDD.

## Estructura

```
Infrastructure/Http/
├── Controllers/           # Controladores HTTP
│   ├── AuthController.php
│   ├── ProfileController.php
│   └── UserManagementController.php
├── Middleware/           # Middlewares de seguridad
│   ├── AuthenticationMiddleware.php
│   └── AuthorizationMiddleware.php
├── Requests/            # DTOs de entrada
│   ├── CreateUserRequest.php
│   └── LoginRequest.php
├── Responses/           # DTOs de salida
│   ├── AuthResponse.php
│   └── UserResponse.php
└── Routes/              # Configuración de rutas
    └── user.routes.php
```

## Controladores

### AuthController

Maneja la autenticación de usuarios:

-  `POST /api/auth/login` - Iniciar sesión
-  `POST /api/auth/logout` - Cerrar sesión
-  `GET /api/auth/me` - Información del usuario autenticado
-  `POST /api/auth/extend-session` - Extender sesión activa

### ProfileController

Gestiona el perfil del usuario autenticado:

-  `GET /api/profile` - Obtener perfil
-  `PUT /api/profile` - Actualizar perfil
-  `PUT /api/profile/password` - Cambiar contraseña

### UserManagementController

Administración de usuarios (requiere permisos):

-  `GET /api/users` - Listar usuarios
-  `POST /api/users` - Crear usuario
-  `GET /api/users/{id}` - Obtener usuario
-  `PUT /api/users/{id}` - Actualizar usuario
-  `POST /api/users/{id}/activate` - Activar usuario
-  `POST /api/users/{id}/deactivate` - Desactivar usuario
-  `PUT /api/users/{id}/password` - Cambiar contraseña de usuario

## Middlewares

### AuthenticationMiddleware

-  Verifica que existe una sesión activa
-  Valida que el usuario está autenticado
-  Agrega información del usuario al request
-  Registra logs de acceso

### AuthorizationMiddleware

-  Verifica permisos específicos para cada endpoint
-  Permite configurar permisos requeridos por ruta
-  Registra intentos de acceso denegados
-  Integra con el sistema RBAC

## Características de Seguridad

### Autenticación Basada en Sesiones

-  Utiliza sesiones nativas de PHP con Aura.Session
-  Regeneración automática de IDs de sesión
-  Timeouts configurables
-  Validación de IP y User-Agent

### Sistema de Permisos RBAC

-  Verificación granular de permisos
-  Integración con grupos de usuarios
-  Logs detallados de autorización
-  Manejo de permisos por unidad organizacional

### Validación de Datos

-  DTOs tipados para requests y responses
-  Validación de entrada en cada endpoint
-  Sanitización automática de datos
-  Mensajes de error estructurados

## Manejo de Errores

Todos los controladores implementan manejo consistente de errores:

```json
{
	"status": "error",
	"message": "Descripción del error",
	"errors": {
		"campo": "Detalle específico del error"
	}
}
```

### Códigos de Estado HTTP

-  `200` - Operación exitosa
-  `201` - Recurso creado
-  `400` - Datos de entrada inválidos
-  `401` - No autenticado
-  `403` - Sin permisos suficientes
-  `404` - Recurso no encontrado
-  `409` - Conflicto (usuario ya existe)
-  `500` - Error interno del servidor

## Logging

### Eventos Registrados

-  Intentos de login (exitosos y fallidos)
-  Accesos autorizados y denegados
-  Operaciones CRUD en usuarios
-  Cambios de contraseña
-  Errores del sistema

### Formato de Logs

```php
$this->logger->info('Evento', [
    'user_id' => $userId,
    'action' => 'action_name',
    'context' => ['additional' => 'data']
]);
```

## Integración con Application Layer

Los controladores actúan como adaptadores entre HTTP y la capa de aplicación:

```php
// Ejemplo en AuthController
$credentials = Credentials::fromStrings($data['identifier'], $data['password']);
$user = $this->loginService->authenticate($credentials);
```

### Dependencias Inyectadas

-  `LoginService` - Autenticación de usuarios
-  `UserService` - Gestión de usuarios
-  `SessionService` - Manejo de sesiones
-  `PermissionService` - Verificación de permisos
-  `LoggerInterface` - Registro de eventos

## Ejemplos de Uso

### Login

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"identifier": "user@example.com", "password": "password123"}'
```

### Obtener Perfil

```bash
curl -X GET http://localhost/api/profile \
  -H "Cookie: VIEX_SESSION=session_id_here"
```

### Crear Usuario (Admin)

```bash
curl -X POST http://localhost/api/users \
  -H "Content-Type: application/json" \
  -H "Cookie: VIEX_SESSION=admin_session_id" \
  -d '{
    "username": "newuser",
    "email": "newuser@example.com",
    "password": "securepass123",
    "first_name": "Juan",
    "last_name": "Pérez",
    "cedula": "12345678"
  }'
```

## Testing

Los controladores pueden ser probados usando:

-  PHPUnit para tests unitarios
-  Requests HTTP simulados con PSR-7
-  Mocks de servicios de aplicación
-  Validación de responses JSON

## Notas de Implementación

### Compatibilidad PHP

-  Código compatible con PHP 8.0+
-  Uso de tipos estrictos
-  Manejo de excepciones moderno

### Framework Integration

-  Compatible con Slim Framework
-  Implementa PSR-7 para HTTP
-  Usa PSR-3 para logging
-  Sigue PSR-4 para autoloading

### Performance

-  Validación eficiente de sesiones
-  Queries optimizadas de permisos
-  Logging asíncrono disponible
-  Caché de datos de usuario en sesión
