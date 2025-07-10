# Domain Layer - Value Objects y Excepciones

## Value Objects Implementados

### 1. UnitType

Value Object que representa los tipos de unidades organizacionales válidas en el sistema.

#### Características:

-  **Inmutable**: Una vez creado, no se puede modificar
-  **Validación**: Solo acepta tipos válidos según la estructura universitaria
-  **Métodos de conveniencia**: Factory methods para cada tipo
-  **Comparación**: Método `equals()` para comparar instancias

#### Uso:

```php
// Crear usando factory methods
$facultad = UnitType::facultad();
$departamento = UnitType::departamento();

// Crear usando create()
$sede = UnitType::create('Sede');

// Validaciones
$unitType = UnitType::create('Facultad');
if ($unitType->isFacultad()) {
    // Es una facultad
}

// Verificar categorías
if ($unitType->isAcademicUnit()) {
    // Es una unidad académica (Facultad, Centro Regional, Instituto)
}
```

#### Tipos Válidos:

-  `Sede` - Sede principal o campus
-  `Facultad` - Facultad académica
-  `Centro Regional` - Centro regional universitario
-  `Instituto` - Instituto especializado
-  `Departamento` - Departamento académico
-  `Escuela` - Escuela profesional
-  `Direccion` - Dirección administrativa
-  `Coordinacion` - Coordinación específica
-  `Division` - División organizacional
-  `Centro` - Centro especializado

### 2. HierarchyPath

Value Object que representa la ruta jerárquica completa de una unidad organizacional.

#### Características:

-  **Inmutable**: Una vez creado, no se puede modificar
-  **Validación**: Valida que la ruta sea coherente
-  **Navegación**: Métodos para navegar por la jerarquía
-  **Comparación**: Métodos para comparar rutas jerárquicas

#### Uso:

```php
// Crear desde string
$path = HierarchyPath::create('Universidad > Facultad de Ingeniería > Departamento de Sistemas');

// Crear desde array
$path = HierarchyPath::fromArray([
    'Universidad',
    'Facultad de Ingeniería',
    'Departamento de Sistemas'
]);

// Crear unidad raíz
$rootPath = HierarchyPath::root('Universidad');

// Navegación
$segments = $path->getSegments(); // ['Universidad', 'Facultad de Ingeniería', 'Departamento de Sistemas']
$root = $path->getRoot(); // 'Universidad'
$leaf = $path->getLeaf(); // 'Departamento de Sistemas'
$depth = $path->getDepth(); // 3

// Relaciones jerárquicas
$parentPath = $path->getParentPath(); // 'Universidad > Facultad de Ingeniería'
$childPath = $path->appendChild('Coordinación de Redes'); // 'Universidad > ... > Coordinación de Redes'

// Comparaciones
if ($path1->isAncestorOf($path2)) {
    // path1 es ancestro de path2
}

if ($path1->isDescendantOf($path2)) {
    // path1 es descendiente de path2
}
```

## Excepciones Implementadas

### 1. InvalidHierarchyException

Excepción lanzada cuando se detectan problemas en la estructura jerárquica.

#### Factory Methods:

```php
// Referencia circular
InvalidHierarchyException::circularReference($unitName, $parentName);

// Auto-referencia
InvalidHierarchyException::selfReference($unitName);

// Tipo de padre inválido
InvalidHierarchyException::invalidParentType($childType, $parentType);

// Profundidad máxima excedida
InvalidHierarchyException::maxDepthExceeded($maxDepth, $currentDepth);

// No se puede eliminar unidad con hijos
InvalidHierarchyException::cannotDeleteUnitWithChildren($unitName, $childrenCount);
```

### 2. UnitNotFoundException

Excepción lanzada cuando no se encuentra una unidad organizacional.

#### Factory Methods:

```php
// Por ID
UnitNotFoundException::withId($id);

// Por nombre
UnitNotFoundException::withName($name);

// Por tipo
UnitNotFoundException::withType($type);

// Por nombre y tipo
UnitNotFoundException::withNameAndType($name, $type);

// Por criterios múltiples
UnitNotFoundException::withCriteria($criteria);
```

## Integración con la Entidad OrganizationalUnit

La entidad `OrganizationalUnit` ha sido mejorada para usar estos Value Objects:

### Métodos Nuevos:

```php
// Obtener tipo como Value Object
$unitType = $unit->getUnitType();

// Obtener ruta jerárquica como Value Object
$hierarchyPath = $unit->getHierarchyPathVO();

// Verificar tipo usando Value Object
if ($unit->isOfType(UnitType::facultad())) {
    // Es una facultad
}

// Verificar categorías
if ($unit->isAcademicUnit()) {
    // Es una unidad académica
}

// Crear unidad hijo con validación
$child = $unit->createChild('Departamento de Sistemas', 'Departamento');
```

### Validaciones Automáticas:

-  **Construcción**: Valida que el tipo sea válido al crear la unidad
-  **Asignación de padre**: Valida que no se creen referencias circulares
-  **Jerarquía**: Valida que la estructura jerárquica sea coherente
-  **Profundidad**: Controla la profundidad máxima de la jerarquía

## Reglas de Jerarquía Implementadas

```
Sede
├── Facultad
│   ├── Departamento
│   │   ├── Coordinacion
│   │   └── Centro
│   ├── Escuela
│   │   ├── Coordinacion
│   │   └── Centro
│   └── Direccion
│       ├── Coordinacion
│       └── Division
├── Centro Regional
│   ├── Departamento
│   ├── Escuela
│   └── Coordinacion
└── Instituto
    ├── Departamento
    ├── Division
    └── Centro
```

Estas reglas se validan automáticamente al crear unidades hijas o mover unidades en la jerarquía.
