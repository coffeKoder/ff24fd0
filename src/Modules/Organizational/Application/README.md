# Capa Application - Módulo Organizational

## Descripción

La capa Application contiene la lógica de aplicación del módulo Organizational, incluyendo casos de uso, servicios, DTOs y eventos. Esta capa orquesta las operaciones del dominio y coordina entre las diferentes capas del sistema.

## Estructura

```
Application/
├── DTOs/                    # Objetos de transferencia de datos
│   ├── OrganizationalUnitDTO.php
│   └── HierarchyTreeDTO.php
├── Events/                  # Eventos de dominio
│   ├── EventDispatcherInterface.php
│   ├── SimpleEventDispatcher.php
│   ├── UnitCreated.php
│   ├── UnitMoved.php
│   └── HierarchyChanged.php
├── Services/                # Servicios de aplicación
│   ├── OrganizationalHierarchyService.php
│   ├── UnitManagementService.php
│   └── ContextService.php
└── UseCases/               # Casos de uso
    ├── CreateOrganizationalUnit.php
    ├── UpdateOrganizationalUnit.php
    ├── DeleteOrganizationalUnit.php
    ├── GetOrganizationalUnit.php
    ├── GetHierarchyTree.php
    ├── MoveUnit.php
    ├── SearchOrganizationalUnits.php
    └── GetHierarchyStatistics.php
```

## DTOs (Data Transfer Objects)

### OrganizationalUnitDTO

DTO para transferir datos de unidades organizacionales entre capas.

```php
$dto = new OrganizationalUnitDTO(
    id: 1,
    name: 'Facultad de Ingeniería',
    type: 'FACULTY',
    parentId: null,
    parentName: null,
    hierarchyPath: '/1',
    depthLevel: 0,
    childrenCount: 5,
    isActive: true,
    isAcademicUnit: true,
    isAdministrativeUnit: false,
    isTeachingUnit: false,
    createdAt: new DateTimeImmutable(),
    updatedAt: new DateTimeImmutable(),
    children: []
);
```

### HierarchyTreeDTO

DTO para representar árboles jerárquicos de unidades organizacionales.

```php
$tree = new HierarchyTreeDTO($unitDTO, $childrenArray);
```

## Eventos

### UnitCreated

Evento disparado cuando se crea una nueva unidad organizacional.

```php
$event = new UnitCreated($unitDTO, $parentDTO);
```

### UnitMoved

Evento disparado cuando una unidad se mueve en la jerarquía.

```php
$event = new UnitMoved($unitDTO, $oldParentDTO, $newParentDTO);
```

### HierarchyChanged

Evento disparado cuando la jerarquía cambia.

```php
$event = new HierarchyChanged($affectedUnitIds, $changeType, $metadata);
```

## Servicios

### OrganizationalHierarchyService

Servicio para navegación y validación jerárquica.

**Métodos principales:**
- `getHierarchyTree(?int $rootId = null): HierarchyTreeDTO`
- `getSubTreeForUnit(int $unitId): HierarchyTreeDTO`
- `getLineageForUnit(int $unitId): array`
- `getDescendantsForUnit(int $unitId): array`
- `isAncestorOf(int $ancestorId, int $descendantId): bool`
- `validateUnitMove(int $unitId, ?int $newParentId): bool`
- `searchUnits(string $searchTerm): array`
- `getHierarchyStatistics(): array`

### UnitManagementService

Servicio para gestión CRUD de unidades organizacionales.

**Métodos principales:**
- `createUnit(string $name, string $type, ?int $parentId = null): OrganizationalUnitDTO`
- `updateUnit(int $unitId, array $data): OrganizationalUnitDTO`
- `deleteUnit(int $unitId, bool $forceDelete = false): bool`
- `moveUnit(int $unitId, ?int $newParentId): OrganizationalUnitDTO`

### ContextService

Servicio para resolución de contextos organizacionales.

**Métodos principales:**
- `getUnitContext(int $unitId): array`
- `getUserContext(int $userId): array`
- `resolveContextForResource(string $resourceType, int $resourceId): array`

## Casos de Uso

### CreateOrganizationalUnit

Caso de uso para crear una nueva unidad organizacional.

```php
$createUseCase = new CreateOrganizationalUnit($unitManagementService, $eventDispatcher);
$unitDTO = $createUseCase->execute('Facultad de Ingeniería', 'FACULTY', null);
```

### GetHierarchyTree

Caso de uso para obtener el árbol jerárquico de unidades organizacionales.

```php
$getTreeUseCase = new GetHierarchyTree($hierarchyService);
$treeDTO = $getTreeUseCase->execute(); // Árbol completo
$subTreeDTO = $getTreeUseCase->execute(1); // Subárbol desde unidad 1
```

### SearchOrganizationalUnits

Caso de uso para buscar unidades organizacionales.

```php
$searchUseCase = new SearchOrganizationalUnits($hierarchyService);
$units = $searchUseCase->execute('Ingeniería');
$facultyUnits = $searchUseCase->getByType('FACULTY');
$rootUnits = $searchUseCase->getRootUnits();
```

## Event Dispatcher

### EventDispatcherInterface

Interfaz para el despachador de eventos.

```php
interface EventDispatcherInterface {
    public function dispatch(object $event): void;
    public function addListener(string $eventClass, callable $listener): void;
    public function getListeners(string $eventClass): array;
}
```

### SimpleEventDispatcher

Implementación simple del despachador de eventos.

```php
$dispatcher = new SimpleEventDispatcher();
$dispatcher->addListener(UnitCreated::class, function($event) {
    // Manejar evento de creación de unidad
});
```

## Uso

### Ejemplo de Creación de Unidad

```php
// Configurar dependencias
$repository = new DoctrineOrganizationalUnitRepository($entityManager);
$hierarchyService = new OrganizationalHierarchyService($repository);
$eventDispatcher = new SimpleEventDispatcher();
$unitManagementService = new UnitManagementService($repository, $hierarchyService, $eventDispatcher);

// Crear caso de uso
$createUseCase = new CreateOrganizationalUnit($unitManagementService, $eventDispatcher);

// Ejecutar
$unitDTO = $createUseCase->execute('Facultad de Ingeniería', 'FACULTY', null);
```

### Ejemplo de Navegación Jerárquica

```php
// Obtener árbol jerárquico
$getTreeUseCase = new GetHierarchyTree($hierarchyService);
$tree = $getTreeUseCase->execute();

// Obtener estadísticas
$statsUseCase = new GetHierarchyStatistics($hierarchyService);
$stats = $statsUseCase->execute();
$lineage = $statsUseCase->getLineage(5);
```

## Patrones Implementados

- **Command Pattern**: Casos de uso encapsulan operaciones
- **Event Sourcing**: Eventos para cambios en el dominio
- **DTO Pattern**: Transferencia de datos entre capas
- **Service Layer**: Servicios para lógica de aplicación
- **Repository Pattern**: Acceso a datos abstracto
- **Observer Pattern**: Event dispatcher para notificaciones

## Validaciones

- Validación de jerarquías circulares
- Validación de tipos de unidades
- Validación de permisos de eliminación
- Validación de movimientos jerárquicos
- Validación de integridad referencial

## Eventos Soportados

- Creación de unidades
- Modificación de unidades
- Eliminación de unidades
- Movimiento de unidades
- Cambios en jerarquía
- Activación/desactivación de unidades

## Configuración

Los servicios requieren configuración de dependencias a través de un contenedor de inyección de dependencias o factory patterns.

## Testing

Se recomienda usar mocks para las dependencias externas y test doubles para el event dispatcher en los tests unitarios.
