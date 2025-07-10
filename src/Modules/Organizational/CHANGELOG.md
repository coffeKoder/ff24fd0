# CHANGELOG - Módulo Organizational

## [1.2.0] - 2025-07-10

### Agregado

-  Integración del `HierarchyCacheService` en `OrganizationalHierarchyService` para optimizar consultas jerárquicas
-  Método `validateUnitMove()` en `OrganizationalHierarchyService` para validar movimientos de unidades en la jerarquía
-  Método `clearHierarchyCache()` en `OrganizationalHierarchyService` para limpiar la caché manualmente
-  Configuración completa de `HierarchyCacheService` en `OrganizationalServiceProvider`
-  Caso de uso `MoveUnit` agregado al ServiceProvider para completar la funcionalidad

### Cambiado

-  Constructor de `OrganizationalHierarchyService` ahora recibe `HierarchyCacheService` como dependencia
-  Método `createManualServices()` en `OrganizationalServiceProvider` actualizado para incluir cache service
-  Mejorada la configuración de servicios para incluir todos los casos de uso

### Corregido

-  Corrección en la integración de cache para evitar errores de tipos incompatibles
-  Corregidos imports faltantes en `OrganizationalServiceProvider`
-  Validación correcta del movimiento de unidades para prevenir estructuras jerárquicas inválidas

### Técnico

-  Implementación completa de la capa Infrastructure con controladores HTTP optimizados
-  Integración correcta entre servicios de Application y Infrastructure
-  Manejo robusto de errores y excepciones en toda la capa Infrastructure

## [1.0.1] - 2025-07-10

### Corregido

-  **OrganizationalServiceProvider**: Corregido uso incorrecto de `\DI\get()` en contexto estático
-  **Inyección de dependencias**: Ajustada firma del método `createManualServices()` para recibir EntityManager como parámetro
-  **Constructores**: Corregido constructor de `UpdateOrganizationalUnit` que solo requiere un parámetro
-  **Imports**: Agregado import de `EntityManagerInterface` para tipado correcto
-  **Namespace**: Corregido namespace del repositorio Doctrine en las definiciones DI

### Agregado

-  **UsageExample**: Archivo de ejemplo mostrando cómo usar el proveedor de servicios con contenedor DI y manualmente
-  **Documentación**: Ejemplos de integración con Slim Framework y configuración típica

## [1.0.0] - 2025-07-10

### Agregado

-  **Dominio robusto** con arquitectura hexagonal

   -  Entidad `OrganizationalUnit` con validaciones de reglas de negocio
   -  Value Objects inmutables: `UnitType` y `HierarchyPath`
   -  Excepciones específicas: `InvalidHierarchyException` y `UnitNotFoundException`
   -  Interfaz del repositorio `OrganizationalUnitRepositoryInterface`
   -  Documentación interna `DOMAIN_GUIDE.md`

-  **Capa Application completa**

   -  DTOs para transferencia de datos: `OrganizationalUnitDTO` y `HierarchyTreeDTO`
   -  Eventos de dominio: `UnitCreated`, `UnitMoved`, `HierarchyChanged`
   -  Event Dispatcher: `EventDispatcherInterface` y `SimpleEventDispatcher`
   -  Servicios especializados:
      -  `OrganizationalHierarchyService` para navegación jerárquica
      -  `UnitManagementService` para gestión CRUD
      -  `ContextService` para resolución de contextos
   -  Casos de uso:
      -  `CreateOrganizationalUnit` - Crear unidades organizacionales
      -  `UpdateOrganizationalUnit` - Actualizar unidades existentes
      -  `DeleteOrganizationalUnit` - Eliminar unidades con validaciones
      -  `GetOrganizationalUnit` - Obtener unidad por ID
      -  `GetHierarchyTree` - Obtener árbol jerárquico
      -  `SearchOrganizationalUnits` - Buscar unidades por criterios
      -  `GetHierarchyStatistics` - Obtener estadísticas jerárquicas
   -  Documentación completa `Application/README.md`

-  **Configuración y proveedores**
   -  `OrganizationalServiceProvider` para inyección de dependencias
   -  Configuración de listeners de eventos
   -  Ejemplos de uso manual y con contenedor DI

### Características implementadas

-  Validación de jerarquías circulares
-  Validación de tipos de unidades universitarias
-  Navegación jerárquica (ancestros, descendientes, línea de ascendencia)
-  Búsqueda de unidades por término, tipo y nivel
-  Estadísticas jerárquicas
-  Gestión de contextos organizacionales
-  Sistema de eventos para notificaciones
-  Soporte para eliminación en cascada
-  Validaciones de integridad referencial

### Patrones implementados

-  Arquitectura Hexagonal
-  Domain Driven Design (DDD)
-  Command Pattern (Casos de uso)
-  Event Sourcing (Eventos de dominio)
-  Repository Pattern (Acceso a datos)
-  DTO Pattern (Transferencia de datos)
-  Service Layer (Servicios de aplicación)
-  Observer Pattern (Event dispatcher)
-  Value Object Pattern (Objetos de valor inmutables)

### Tecnologías utilizadas

-  PHP 8.1+
-  Doctrine ORM (preparado para integración)
-  PHP-DI (Inyección de dependencias)
-  PSR-11 (Container Interface)
-  Arquitectura SOLID

### Estructura del módulo

```
src/Modules/Organizational/
├── Domain/
│   ├── Entities/
│   │   └── OrganizationalUnit.php
│   ├── ValueObjects/
│   │   ├── UnitType.php
│   │   └── HierarchyPath.php
│   ├── Exceptions/
│   │   ├── InvalidHierarchyException.php
│   │   └── UnitNotFoundException.php
│   ├── Repository/
│   │   └── OrganizationalUnitRepositoryInterface.php
│   └── DOMAIN_GUIDE.md
├── Application/
│   ├── DTOs/
│   │   ├── OrganizationalUnitDTO.php
│   │   └── HierarchyTreeDTO.php
│   ├── Events/
│   │   ├── EventDispatcherInterface.php
│   │   ├── SimpleEventDispatcher.php
│   │   ├── UnitCreated.php
│   │   ├── UnitMoved.php
│   │   └── HierarchyChanged.php
│   ├── Services/
│   │   ├── OrganizationalHierarchyService.php
│   │   ├── UnitManagementService.php
│   │   └── ContextService.php
│   ├── UseCases/
│   │   ├── CreateOrganizationalUnit.php
│   │   ├── UpdateOrganizationalUnit.php
│   │   ├── DeleteOrganizationalUnit.php
│   │   ├── GetOrganizationalUnit.php
│   │   ├── GetHierarchyTree.php
│   │   ├── SearchOrganizationalUnits.php
│   │   └── GetHierarchyStatistics.php
│   └── README.md
├── Config/
│   └── OrganizationalServiceProvider.php
└── Infrastructure/
    └── DoctrineOrganizationalUnitRepository.php (existente)
```

### Próximos pasos

-  Integrar con la capa Infrastructure (DoctrineOrganizationalUnitRepository)
-  Implementar controladores HTTP
-  Crear tests unitarios y de integración
-  Documentar API REST
-  Integrar con módulo User para contextos de usuario
-  Implementar caché para mejorar performance
-  Crear migraciones de base de datos

### Notas técnicas

-  El módulo está diseñado para ser independiente y reutilizable
-  Sigue principios SOLID y arquitectura limpia
-  Preparado para integración con frameworks como Slim
-  Compatible con estándares PSR
-  Documentación completa incluida
-  Event-driven architecture implementada
-  Validaciones robustas implementadas
-  Preparado para testing automatizado
