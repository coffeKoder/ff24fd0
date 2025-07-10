# Integración del Módulo Organizational con el Framework

## Descripción

Esta documentación describe cómo se ha integrado el módulo Organizational con el framework principal de la aplicación VIEX.

## Archivos de Integración

### 1. ServiceProvider (`app/ProviderServices/OrganizationalServiceProvider.php`)

Este archivo integra los servicios del módulo Organizational con el contenedor de dependencias principal del framework.

#### Responsabilidades:

-  Registrar las definiciones de servicios del módulo
-  Configurar servicios que requieren el EntityManager del framework
-  Proporcionar acceso a servicios manuales para casos especiales

#### Servicios Registrados:

-  `OrganizationalHierarchyService`: Navegación jerárquica
-  `UnitManagementService`: Gestión CRUD de unidades
-  `ContextService`: Resolución de contextos organizacionales
-  Todos los casos de uso (CreateOrganizationalUnit, UpdateOrganizationalUnit, etc.)
-  Controladores HTTP (OrganizationalController, HierarchyController)

### 2. Rutas (`app/Routes/organizational.route.php`)

Define las rutas HTTP para el módulo Organizational, siguiendo el patrón del framework.

#### Rutas API:

-  `GET /api/organizational/units` - Listar unidades
-  `POST /api/organizational/units` - Crear unidad
-  `GET /api/organizational/units/{id}` - Obtener unidad
-  `PUT /api/organizational/units/{id}` - Actualizar unidad
-  `DELETE /api/organizational/units/{id}` - Eliminar unidad

#### Rutas de Jerarquía:

-  `GET /api/organizational/hierarchy/tree` - Árbol jerárquico
-  `GET /api/organizational/hierarchy/stats` - Estadísticas
-  `GET /api/organizational/hierarchy/units/{id}/context` - Contexto de unidad
-  `GET /api/organizational/hierarchy/units/{id}/lineage` - Línea de ascendencia
-  `GET /api/organizational/hierarchy/units/{id}/descendants` - Descendientes
-  `PATCH /api/organizational/hierarchy/units/{id}/move` - Mover unidad

### 3. Configuración (`config/organizational.config.php`)

Configuración específica del módulo que se integra con el sistema de configuración del framework.

#### Configuraciones Incluidas:

-  **Caché**: TTL para diferentes tipos de datos
-  **Validaciones**: Reglas de negocio y restricciones
-  **Jerarquía**: Tipos permitidos y relaciones padre-hijo
-  **Paginación**: Tamaños de página por defecto
-  **Logging**: Configuración de logs
-  **Eventos**: Configuración de eventos del sistema
-  **Seguridad**: Roles y permisos

## Flujo de Integración

### 1. Inicialización del Framework

```php
// app/Bootstrap/Application.php
$app = new Application();
$container = $app->getContainer();
```

### 2. Carga de Servicios

El framework automáticamente carga todos los archivos `*.php` en `app/ProviderServices/`, incluyendo `OrganizationalServiceProvider.php`.

### 3. Registro de Rutas

El framework automáticamente carga todos los archivos `*.php` en `app/Routes/`, incluyendo `organizational.route.php`.

### 4. Resolución de Dependencias

Cuando se hace una petición HTTP a una ruta del módulo, el framework:

1. Resuelve el controlador desde el contenedor
2. Inyecta automáticamente las dependencias necesarias
3. Ejecuta la acción correspondiente

## Uso de los Servicios

### Desde el Contenedor

```php
// Obtener servicio de jerarquía
$hierarchyService = $container->get(OrganizationalHierarchyService::class);

// Obtener servicio de gestión
$unitService = $container->get(UnitManagementService::class);
```

### Servicios Manuales

```php
// Para casos que requieren EntityManager específico
$manualServices = $container->get('OrganizationalModule.Services');
$hierarchyService = $manualServices['hierarchyService'];
$useCases = $manualServices['useCases'];
```

## Configuración de Entorno

### Variables de Entorno Requeridas

```env
# Base de datos
DB_HOST=localhost
DB_DATABASE=viex_db
DB_USERNAME=viex_user
DB_PASSWORD=viex_pass

# Configuración de caché
CACHE_ENABLED=true
CACHE_TTL=3600

# Configuración de logging
LOG_LEVEL=info
LOG_ORGANIZATIONAL=true
```

## Middleware y Seguridad

### Middleware Aplicado

Las rutas del módulo heredan todo el middleware configurado en el framework:

-  **CORS**: Configurado para peticiones cross-origin
-  **Authentication**: Verificación de tokens JWT/Session
-  **Authorization**: Verificación de permisos por rol
-  **Rate Limiting**: Protección contra abuso
-  **Error Handling**: Manejo uniforme de errores

### Roles y Permisos

```php
// Roles con acceso al módulo
$allowedRoles = [
    'admin',
    'organizational_admin',
    'unit_coordinator',
    'dean',
    'director',
];
```

## Pruebas de Integración

### Ejecutar Pruebas

```bash
# Ejecutar script de prueba
php test_organizational_integration.php

# Ejecutar con Composer
composer run test:integration
```

### Verificaciones Incluidas

-  ✓ Registro correcto de servicios
-  ✓ Resolución de dependencias
-  ✓ Carga de configuración
-  ✓ Disponibilidad de casos de uso
-  ✓ Controladores HTTP funcionales

## Troubleshooting

### Errores Comunes

1. **Service not found**: Verificar que `OrganizationalServiceProvider.php` esté en `app/ProviderServices/`
2. **Route not found**: Verificar que `organizational.route.php` esté en `app/Routes/`
3. **Database connection**: Verificar configuración de Doctrine en `config/database.config.php`
4. **Cache issues**: Verificar permisos en `storage/cache/`

### Logs

```bash
# Ver logs del módulo
tail -f storage/logs/organizational.log

# Ver logs generales
tail -f storage/logs/app.log
```

## Próximos Pasos

1. **Middleware de Autenticación**: Implementar middleware específico para el módulo
2. **Validación de Permisos**: Agregar validación de permisos por endpoint
3. **Rate Limiting**: Configurar límites específicos por tipo de operación
4. **Monitoring**: Agregar métricas y monitoring
5. **Caching**: Implementar cache distribuido con Redis

## Documentación API

Una vez integrado, la documentación de la API estará disponible en:

-  Swagger UI: `http://localhost:8000/api/docs`
-  JSON Schema: `http://localhost:8000/api/schema`

## Contacto

Para dudas sobre la integración:

-  Desarrollador: Fernando Castillo <fdocst@gmail.com>
-  Documentación: [INFRASTRUCTURE.md](../src/Modules/Organizational/INFRASTRUCTURE.md)
