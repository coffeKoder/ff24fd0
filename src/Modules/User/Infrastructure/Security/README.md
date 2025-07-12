# Infrastructure/Security Layer - User Module

La capa de Seguridad proporciona servicios fundamentales para la protección y autenticación de usuarios en el sistema VIEX.

## Componentes Implementados

### 1. PasswordHasher

**Archivo:** `PasswordHasher.php`
**Propósito:** Manejo seguro de contraseñas con hash, validación y evaluación de fortaleza.

#### Características principales:

-  Hash seguro usando `password_hash()` con `PASSWORD_ARGON2ID`
-  Verificación de contraseñas con `password_verify()`
-  Validación de fortaleza de contraseña (longitud, caracteres especiales, mayúsculas, minúsculas, números)
-  Puntuación de fortaleza de 0-100
-  Generador de contraseñas seguras
-  Verificación de contraseñas comprometidas (preparado para integración con APIs)

#### Uso:

```php
$hasher = new PasswordHasher();

// Hash de contraseña
$hash = $hasher->hash('mi_contraseña_segura');

// Verificación
$isValid = $hasher->verify('mi_contraseña_segura', $hash);

// Validación de fortaleza
$validation = $hasher->validatePasswordStrength('MiContraseña123!');
// Retorna: ['isValid' => true, 'score' => 85, 'messages' => [...]]

// Generar contraseña segura
$securePassword = $hasher->generateSecurePassword(16);
```

### 2. TokenGenerator

**Archivo:** `TokenGenerator.php`
**Propósito:** Generación de tokens criptográficamente seguros para diferentes propósitos.

#### Características principales:

-  Tokens para recuperación de contraseña con validación temporal
-  Generación de UUIDs v4
-  Tokens seguros personalizables (longitud, alfabeto)
-  Tokens con timestamp para verificación temporal
-  Múltiples formatos y propósitos

#### Uso:

```php
$generator = new TokenGenerator();

// Token para reset de contraseña (válido por 1 hora)
$resetData = $generator->generatePasswordResetToken('user@email.com');
// Retorna: ['token' => '...', 'expires_at' => timestamp, 'email' => '...']

// UUID v4
$uuid = $generator->generateUuid();

// Token personalizado
$customToken = $generator->generateSecureToken(32, 'ABCDEF0123456789');

// Verificar token de reset
$isValid = $generator->verifyPasswordResetToken($token, 'user@email.com');
```

### 3. RateLimiter

**Archivo:** `RateLimiter.php`
**Propósito:** Sistema de rate limiting para prevenir ataques de fuerza bruta y abuso del sistema.

#### Características principales:

-  Almacenamiento basado en archivos (sin dependencias externas)
-  Rate limiting configurable por acción
-  Escalamiento de penalizaciones
-  Limpieza automática de archivos expirados
-  Soporte para múltiples tipos de limitación

#### Tipos de Rate Limiting:

1. **Login attempts:** 5 intentos / 15 minutos
2. **Password reset:** 3 intentos / 60 minutos
3. **Email verification:** 3 intentos / 30 minutos
4. **API calls:** 100 llamadas / hora
5. **IP-based limiting:** Configurable por acción
6. **Escalating attempts:** Aumenta tiempo de bloqueo

#### Uso:

```php
$rateLimiter = new RateLimiter();

// Verificar intentos de login
$loginStatus = $rateLimiter->loginAttempts('user@email.com');
if ($loginStatus['blocked']) {
    throw new Exception("Demasiados intentos. Espere {$loginStatus['resetIn']} segundos.");
}

// Registrar intento fallido
$rateLimiter->hit('login:user@email.com');

// Rate limiting por IP
$ipStatus = $rateLimiter->ipAttempts($_SERVER['REMOTE_ADDR'], 'login');

// Verificar antes de procesar
$rateLimiter->checkLimit('api:' . $apiKey, 100, 60);

// Bloqueo temporal manual
$rateLimiter->temporaryBlock('user:123', 30); // 30 minutos

// Limpiar intentos exitosos
$rateLimiter->clear('login:user@email.com');
```

## Configuración

### Variables de Entorno Recomendadas

```env
# Configuración de RateLimiter
RATE_LIMIT_CACHE_DIR=/path/to/cache/rate_limits
RATE_LIMIT_DEFAULT_MAX_ATTEMPTS=5
RATE_LIMIT_DEFAULT_DECAY_MINUTES=15

# Configuración de Passwords
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_SPECIAL_CHARS=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true

# Configuración de Tokens
TOKEN_RESET_EXPIRE_HOURS=1
TOKEN_DEFAULT_LENGTH=32
```

### Integración con DI Container

```php
// En services.config.php
use Viex\Modules\User\Infrastructure\Security\{PasswordHasher, TokenGenerator, RateLimiter};

return [
    PasswordHasher::class => DI\create(),

    TokenGenerator::class => DI\create(),

    RateLimiter::class => DI\create()
        ->constructor(
            DI\env('RATE_LIMIT_CACHE_DIR', sys_get_temp_dir() . '/viex_rate_limit'),
            DI\env('RATE_LIMIT_DEFAULT_MAX_ATTEMPTS', 5),
            DI\env('RATE_LIMIT_DEFAULT_DECAY_MINUTES', 15)
        ),
];
```

## Consideraciones de Seguridad

### 1. Almacenamiento de Rate Limits

-  Los archivos de cache se almacenan con permisos 755
-  Datos sensibles se hashean antes del almacenamiento
-  Limpieza automática de archivos expirados

### 2. Tokens

-  Todos los tokens usan `random_bytes()` para seguridad criptográfica
-  Tokens de reset tienen expiración automática
-  Validación temporal integrada

### 3. Passwords

-  Hash con Argon2ID (más seguro que bcrypt)
-  Validación de fortaleza configurable
-  Preparado para verificación de contraseñas comprometidas

## Integración con Application Layer

Estos componentes están diseñados para ser utilizados por:

1. **Authentication Services**: Para hash y verificación de contraseñas
2. **Password Reset Services**: Para generación y validación de tokens
3. **Login Services**: Para rate limiting de intentos
4. **API Services**: Para throttling de llamadas
5. **Security Middleware**: Para verificación automática de límites

## Tests Recomendados

1. **PasswordHasher Tests**:

   -  Verificación de hash/verify
   -  Validación de fortaleza
   -  Generación de contraseñas seguras

2. **TokenGenerator Tests**:

   -  Generación de tokens únicos
   -  Validación temporal
   -  Verificación de tokens de reset

3. **RateLimiter Tests**:
   -  Conteo de intentos
   -  Expiración automática
   -  Escalamiento de penalizaciones
   -  Limpieza de archivos

## Próximos Pasos

1. **Application Services**: Implementar servicios que usen estos componentes
2. **Middleware**: Crear middleware para rate limiting automático
3. **Configuration**: Crear clase de configuración centralizada
4. **Logging**: Integrar con Monolog para auditoría de seguridad
5. **Tests**: Implementar suite completa de tests unitarios

---

**Nota**: Esta implementación prioriza la seguridad y la simplicidad, evitando dependencias externas complejas mientras proporciona características robustas de seguridad para el sistema VIEX.
