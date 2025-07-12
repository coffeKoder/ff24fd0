# Organizational Module - Estructura Organizacional Jerárquica

Módulo especializado en la gestión de la estructura organizacional jerárquica de la Universidad de Panamá, facilitando RBAC contextual y navegación eficiente de la jerarquía académica.

## Arquitectura y Responsabilidades

### Responsabilidades Principales

#### ✅ **Gestión de Estructura Organizacional:**

-  **Jerarquía Académica**: Manejo de Sedes, Facultades, Departamentos, Escuelas
-  **Navegación Jerárquica**: Búsqueda eficiente de ancestros y descendientes
-  **Validación de Estructura**: Prevención de ciclos y estructuras inválidas
-  **Contexto Organizacional**: Soporte para RBAC contextual

#### 🔄 **Servicios Expuestos:**

-  `OrganizationalHierarchyService`: Para navegación y validación jerárquica
-  `UnitManagementService`: Para gestión CRUD de unidades
-  `ContextService`: Para resolución de contextos organizacionales

#### 📥 **Relaciones con Otros Módulos:**

-  **User**: Asignación de usuarios a unidades principales
-  **Extension**: Contextualización de trabajos de extensión por unidad
-  **RBAC**: Roles contextuales por unidad organizacional

## Estructura del Módulo

### Domain Layer

```
src/Organizational/Domain/
├── Entities/
│   └── OrganizationalUnit.php          # ✅ Entidad principal jerárquica
├── Repository/
│   └── OrganizationalUnitRepositoryInterface.php  # ✅ Contrato de persistencia
├── ValueObjects/
│   ├── UnitType.php                    # Tipos de unidades (Sede, Facultad, etc.)
│   └── HierarchyPath.php               # Ruta jerárquica completa
└── Exceptions/
    ├── InvalidHierarchyException.php    # Estructura jerárquica inválida
    └── UnitNotFoundException.php        # Unidad no encontrada
```

### Application Layer

```
src/Organizational/Application/
├── Services/
│   ├── OrganizationalHierarchyService.php  # Navegación jerárquica
│   ├── UnitManagementService.php           # Gestión CRUD
│   └── ContextService.php                  # Resolución de contextos
├── UseCases/
│   ├── CreateOrganizationalUnit.php
│   ├── UpdateOrganizationalUnit.php
│   ├── MoveUnit.php                        # Mover en jerarquía
│   └── GetHierarchyTree.php                # Obtener árbol completo
├── DTOs/
│   ├── OrganizationalUnitDTO.php
│   └── HierarchyTreeDTO.php
└── Events/
    ├── UnitCreated.php
    ├── UnitMoved.php
    └── HierarchyChanged.php
```

### Infrastructure Layer

```
src/Organizational/Infrastructure/
├── Persistence/
│   └── Doctrine/
│       └── DoctrineOrganizationalUnitRepository.php  # ✅ Implementado
├── Http/
│   ├── OrganizationalController.php
│   └── HierarchyController.php
└── Cache/
    └── HierarchyCacheService.php          # Cache para árboles jerárquicos
```

-  `getUnitsByType(string $type)`: Devuelve todas las unidades de un tipo específico (ej. todas las 'Faculty').

---

### Tarea 2: Servicio de Jerarquía y Lógica de Negocio

El `UnitHierarchyService` contendrá la lógica de negocio que opera sobre la estructura organizacional.

**2.1. Diseñar el `UnitHierarchyService`**

-  **Conceptualización:** Este servicio será el punto de entrada para otros módulos (como `Auth` y `ExtensionWork`) que necesiten interactuar con la jerarquía organizacional.
-  **Responsabilidades Principales:**
   -  **Consulta de Jerarquías:**
      -  `getFullHierarchy()`: Obtiene el árbol completo desde el `UnitRepository` y lo devuelve, utilizando caché para evitar consultas repetidas.
      -  `getSubTreeForUnit(int $unitId)`: Devuelve la rama del árbol que comienza en una unidad específica.
      -  `getLineageForUnit(int $unitId)`: Devuelve la ruta desde una unidad hasta la raíz.
   -  **Lógica Contextual:**
      -  `getUnitsForUserContext(User $user)`: Una función crucial. Dado un usuario, devuelve todas las unidades organizacionales sobre las cuales tiene autoridad. Por ejemplo, un Decano de la Facultad de Ciencias vería su Facultad y todos los Departamentos/Escuelas debajo de ella.
      -  `isUserInUnitHierarchy(User $user, int $targetUnitId)`: Verifica si un usuario tiene autoridad sobre una unidad específica (directa o indirectamente a través de la jerarquía).

**2.2. Implementar la Lógica de Caché**

-  **Decisión de Diseño Clave:** La estructura organizacional no cambia con frecuencia. Las consultas para construir el árbol pueden ser lentas si la jerarquía es profunda. Por lo tanto, el caché es fundamental.
-  **Conceptualización:**
   -  El método `UnitHierarchyService::getFullHierarchy()` primero intentará obtener el árbol desde el `ModelCache`.
   -  Si no está en caché, llamará a `UnitRepository::getTree()`, construirá la estructura anidada y la almacenará en caché con un tiempo de vida largo (ej. 24 horas).
   -  **Invalidación de Caché:** Crear un mecanismo para invalidar la caché (ej. `UnitHierarchyService::flushCache()`) que será llamado por el `UnitController` cada vez que se cree, actualice o elimine una unidad.

---

### Tarea 3: Interfaz de Administración (`UnitController`)

El controlador que permitirá a los administradores del sistema gestionar la estructura organizacional.

**3.1. CRUD para Unidades Organizacionales**

-  **Conceptualización:** El `UnitController` implementará los métodos estándar para la gestión de las unidades.
   -  `index()`: Mostrará una vista del árbol jerárquico. Esta vista será probablemente una combinación de HTML y JavaScript (usando una librería como `jsTree` o similar) para presentar la jerarquía de forma interactiva. La data para este árbol provendrá de `$unitHierarchyService->getFullHierarchy()`.
   -  `create(int $parentId = null)`: Muestra el formulario para crear una nueva unidad. Si se proporciona un `parentId`, el formulario pre-seleccionará la unidad padre.
   -  `store(Request $request)`: Valida los datos y llama a un método en el `UnitHierarchyService` (que a su vez usará el repositorio) para crear la nueva unidad. **Importante:** Después de crearla, debe llamar a `flushCache()`.
   -  `edit(int $unitId)`: Muestra el formulario de edición para una unidad.
   -  `update(int $unitId, Request $request)`: Valida y actualiza la unidad. **Importante:** Invalida la caché.
   -  `destroy(int $unitId)`: Elimina una unidad. **Decisión de Diseño:** Debe haber una regla de negocio que impida eliminar una unidad si tiene unidades hijas o si hay usuarios/trabajos de extensión asociados a ella. Esta lógica vivirá en el `UnitHierarchyService`. **Importante:** Invalida la caché.

---

### Tarea 4: Integración con Otros Módulos

El valor real de este módulo se manifiesta en cómo otros módulos lo utilizan.

**4.1. Integración con el Módulo `Auth`**

-  **Conceptualización:** Cuando un administrador asigna un rol a un usuario (ej. "Coordinador de Extensión"), la interfaz debe permitir seleccionar **en qué unidad organizacional** se aplica ese rol. La tabla `user_user_groups` ya tiene el campo `organizational_unit_id` para esto.
-  **Ejemplo de Flujo:**
   1. Admin va a la página de un usuario.
   2. Hace clic en "Asignar Rol".
   3. Aparece un modal: `Select Rol:` [Dropdown de roles], `Select Unidad Organizacional:` [Un árbol o dropdown de unidades organizacionales obtenido desde `UnitHierarchyService`].
   4. El `AuthService` o `RoleService` crea el registro en `user_user_groups` con el `user_id`, `user_group_id`, y el `organizational_unit_id` seleccionado.

**4.2. Integración con el Módulo `ExtensionWork`**

-  **Conceptualización:** El filtrado y la visualización de trabajos de extensión dependerán del contexto organizacional del usuario.
-  **Ejemplo de Flujo:**
   1. Un Decano inicia sesión.
   2. Va a la bandeja de trabajos pendientes.
   3. El `WorkController` llama a `$unitHierarchyService->getSubTreeForUser($user)` para obtener todas las unidades bajo el mando del Decano.
   4. El controlador pasa este array de `unit_ids` al `WorkRepository` para filtrar la consulta: `->whereIn('organizational_unit_id', $allowedUnitIds)`.

### Resumen del Flujo Conceptual del Módulo

1. **Definición:** Un administrador configura la estructura jerárquica de la universidad a través del `UnitController`.
2. **Caché:** El `UnitHierarchyService` cachea esta estructura para consultas rápidas y eficientes.
3. **Contextualización:** El módulo `Auth` utiliza el `UnitHierarchyService` para asignar roles en contextos específicos (ej. "Decano de la Facultad X").
4. **Filtrado:** El módulo `ExtensionWork` utiliza el `UnitHierarchyService` para determinar qué trabajos de extensión puede ver y gestionar un usuario según su posición en la jerarquía.

---

**Responsabilidad:** Representar la jerarquía de la universidad.

-  **`organizational_units`**: La única entidad de este módulo. Contiene toda la lógica para manejar la estructura de árbol (padres, hijos, linaje).

---

## **Tabla:** `organizational_units`

**Descripción:** Estructura jerárquica de la universidad.
**Relaciones:**

-  _Tablas de las que depende:_ Puede depender de sí misma (`parent_id`)
-  _Tablas que dependen de ella:_ `users`, `extension_works`, `user_user_groups`
   **Campos:**
-  `id`: Identificador único.
-  `name`: Nombre de la unidad.
-  `type`: Tipo de unidad (Facultad, Escuela, etc.).
-  `parent_id`: Relación jerárquica.
-  `is_active`: Estado del registro (smallint, 0/1 para false/true)
-  `created_at`: Datetime de la creación del registro
-  `updated_at`: Datetime de la última actualización del registro
-  `soft_deleted`: Indicador de eliminación lógica (smallint, 0/1 para false/true)

---

**Lógica de Dependencia:** Es un módulo de soporte. Módulos como `Auth` (para el contexto de roles) y `ExtensionWork` (para la asignación de trabajos) dependerán de él.
