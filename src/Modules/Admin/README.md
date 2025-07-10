# Admin Module

# MÓDULO 6: `Admin` (Configuración y Catálogos)

Módulo transversal para la administración de configuraciones del sistema y catálogos simples que no justifican un módulo propio.

-  **Responsabilidad Principal:** Gestión de catálogos maestros y configuración del sistema.
-  **Componentes Clave:**
   -  `Controllers/`: `CatalogController`, `SettingsController`.
   -  `Models/Entities/`: `WorkType`, `InstitutionalProjectType`. (Si estos catálogos tienen lógica compleja, podrían moverse a `ExtensionWork`, pero si son solo CRUD, `Admin` es adecuado).

---

### Tarea 1: Gestión de Catálogos Maestros (`CatalogController`)

Esta tarea se centra en proporcionar una interfaz CRUD (Crear, Leer, Actualizar, Eliminar) para las tablas de datos que actúan como catálogos.

**1.1. Diseñar el `CatalogController` de forma Genérica**

-  **Decisión de Diseño Clave:** Evitar crear un controlador separado para cada catálogo (`WorkTypeController`, `StatusController`, etc.). Esto generaría mucho código repetitivo. En su lugar, crear un `CatalogController` genérico que pueda gestionar múltiples tipos de catálogos.
-  **Conceptualización:**
   -  **Rutas:** Las rutas se definirán de forma que indiquen qué catálogo se está gestionando:
      -  `GET /admin/catalogs/{catalog_name}` (ej. `/admin/catalogs/work-types`)
      -  `POST /admin/catalogs/{catalog_name}`
      -  `PUT /admin/catalogs/{catalog_name}/{id}`
      -  `DELETE /admin/catalogs/{catalog_name}/{id}`
   -  **Controlador (`CatalogController`):**
      -  Cada método (`index`, `store`, `update`, `destroy`) recibirá el `$catalog_name` como parámetro desde la ruta.
      -  El controlador tendrá un "mapa" que asocie el nombre del catálogo de la URL con la clase del Modelo/Entidad correspondiente.
         ```php
         // En CatalogController.php
         private function getModelForCatalog(string $catalogName): string {
             $map = [
                 'work-types' => WorkType::class,
                 'work-statuses' => WorkStatus::class,
                 'institutional-project-types' => InstitutionalProjectType::class,
             ];
             if (!isset($map[$catalogName])) {
                 throw new \Exception("Catálogo no válido: $catalogName");
             }
             return $map[$catalogName];
         }
         ```
      -  El método `index($catalog_name)` usará el mapa para obtener el modelo, llamará al método estático `all()` del modelo (`$modelClass::all()`) y pasará los resultados a una vista genérica `admin/catalogs/index`.
      -  La lógica de `store`, `update` y `destroy` será igualmente genérica, operando sobre el modelo obtenido del mapa.

**1.2. Implementar la Lógica de Negocio en un `CatalogService`**

-  **Conceptualización:** Aunque el CRUD es simple, un servicio puede encapsular la lógica de negocio, como las validaciones y la invalidación de caché.
-  **Responsabilidades (`CatalogService`):**
   -  **Validación:** Antes de crear o actualizar, asegurarse de que los nombres de los catálogos sean únicos (ej. no pueden existir dos tipos de trabajo con el mismo nombre).
   -  **Reglas de Eliminación:** Implementar lógica para prevenir la eliminación de un registro de catálogo si está en uso. Por ejemplo, no se puede eliminar el tipo de trabajo "Proyecto de Extensión" si ya existen trabajos de ese tipo en la base de datos. El método `deleteCatalogEntry` verificaría estas dependencias antes de proceder.
   -  **Invalidación de Caché:** Después de cualquier operación de escritura (crear, actualizar, eliminar), el servicio debe ser responsable de **invalidar cualquier caché** que dependa de estos catálogos. Por ejemplo, si se actualiza un tipo de trabajo, se debe limpiar la caché que almacena la lista de tipos de trabajo para los formularios.

**1.3. Vistas Genéricas**

-  **Conceptualización:** Crear un conjunto de vistas reutilizables para la gestión de catálogos.
-  **Componentes de la Vista:**
   -  `admin/catalogs/index.phtml`: Una vista que recibe un array de `items` y un `catalog_name`. Renderiza una tabla con los datos y los botones de acción (Editar/Eliminar). El título de la página y las URL de los botones se generan dinámicamente usando el `catalog_name`.
   -  `admin/catalogs/form.phtml`: Un formulario genérico para crear o editar un registro. Los campos pueden ser tan simples como "Nombre" y "Descripción".

---

### Tarea 2: Gestión de Configuraciones del Sistema (`SettingsController`)

Esta tarea se enfoca en permitir a los administradores modificar ciertos comportamientos de la aplicación sin necesidad de cambiar el código o las variables de entorno.

**2.1. Identificar Configuraciones Administrables**

-  **Conceptualización:** No todas las configuraciones deben estar en la UI. Solo aquellas que un administrador de VIEX (no un desarrollador) necesitaría cambiar.
-  **Candidatos Potenciales:**
   -  **Textos de Notificaciones por Email:** Permitir al admin editar las plantillas de los correos que se envían (ej. "Su trabajo ha sido aprobado").
   -  **Plazos y Recordatorios:** Definir el número de días para la certificación (actualmente 20 días hábiles) y configurar recordatorios automáticos.
   -  **Parámetros del Flujo de Trabajo:** Si el flujo se hace configurable (como se sugirió en el Módulo `ExtensionWork`), aquí se podría gestionar.
   -  **Contenido de Páginas Estáticas:** Texto de la página "Acerca de" o "Contacto" si existieran.

**2.2. Diseñar el Almacenamiento de Configuraciones**

-  **Decisión de Diseño Clave:** ¿Dónde guardar estas configuraciones?
   -  **Opción A (Archivo):** Un archivo `config/settings.php` que es modificado por el `SettingsController`. **Ventaja:** Simple. **Desventaja:** Requiere que el proceso del servidor web tenga permisos de escritura sobre los archivos de configuración, lo cual es un riesgo de seguridad.
   -  **Opción B (Base de Datos - Recomendada):** Crear una tabla `settings` con una estructura de `(key, value)`.
      -  `key`: `notification_email_subject_approved`
      -  `value`: `¡Tu trabajo de extensión ha sido certificado!`
-  **Ventaja de la Opción B:** Es más segura, transaccional, y no requiere permisos de escritura especiales.

**2.3. Implementar el `SettingsController` y `SettingsService`**

-  **Conceptualización:**
   -  El `SettingsController` mostrará un formulario con todas las configuraciones agrupadas por sección (Notificaciones, Flujo de Trabajo, etc.).
   -  Al guardar, el controlador pasará todos los datos al `SettingsService`.
   -  El `SettingsService` iterará sobre los datos y actualizará cada registro en la tabla `settings` (o creará nuevos si no existen).
-  **Acceso a las Configuraciones:**
   -  Crear una función helper global o un método en un servicio base, por ejemplo, `setting('notification_email_subject_approved')`.
   -  Este helper debe cachear las configuraciones de la base de datos para evitar consultarla en cada petición. La caché se invalida cada vez que el `SettingsService` guarda nuevas configuraciones.

---

### Tarea 3: Seguridad y Permisos del Módulo

Dado que este módulo es altamente sensible, el control de acceso debe ser estricto.

**3.1. Definir Permisos Granulares**

-  **Conceptualización:** Crear permisos específicos en la tabla `permissions`.
   -  `admin.view_catalogs`
   -  `admin.manage_work_types` (permite CRUD en el catálogo de tipos de trabajo)
   -  `admin.manage_work_statuses`
   -  `admin.view_settings`
   -  `admin.manage_settings`
-  **Asignación:** Estos permisos solo se asignarán al rol de "Administrador VIEX" o "Super Administrador".

**3.2. Aplicar Middleware de Autorización**

-  **Conceptualización:** Todas las rutas definidas en `app/Modules/Admin/routes.php` deben estar agrupadas y protegidas por el middleware `Authorize`.

   ```php
   // En routes.php del módulo Admin
   Router::group(['prefix' => 'admin', 'middleware' => ['Authorize']], function () {
       // Rutas para catálogos, protegidas por permisos específicos
       Router::get('/catalogs/{catalog_name}', 'Admin\Controllers\CatalogController@index')
             ->middleware('Authorize:admin.view_catalogs');

       // Rutas para configuración
       Router::get('/settings', 'Admin\Controllers\SettingsController@index')
             ->middleware('Authorize:admin.view_settings');
   });
   ```

### Resumen del Flujo Conceptual del Módulo

1. **Acceso Seguro:** Un usuario con el rol de "Administrador VIEX" inicia sesión y navega a la sección de "Administración". El middleware `Authorize` verifica sus permisos antes de darle acceso.
2. **Gestión de Catálogos:** El administrador accede a `/admin/catalogs/work-statuses`. El `CatalogController` identifica que se está pidiendo el catálogo de estados, obtiene los datos usando el modelo `WorkStatus` y los muestra en una vista genérica.
3. **Impacto Controlado:** El administrador edita un estado. El `CatalogService` valida el cambio, actualiza la base de datos y, crucialmente, limpia la caché de estados en toda la aplicación para que los cambios se reflejen inmediatamente en los formularios y filtros.
4. **Configuración Flexible:** El administrador va a la sección de configuración y cambia el asunto de un correo de notificación. El `SettingsService` actualiza la fila correspondiente en la tabla `settings` y limpia la caché de configuraciones. La próxima vez que se envíe ese correo, usará el nuevo texto sin necesidad de un despliegue de código.

---

**Responsabilidad:** Gestión de los datos maestros que definen el comportamiento del sistema.

-  **`work_types`**: Define los tipos de trabajo disponibles. Es un catálogo maestro.
-  **`work_statuses`**: Define los estados posibles en el flujo de trabajo.
-  **`institutional_project_types`**: Define los tipos de proyectos institucionales, otro catálogo.

## **Tabla:** `work_types`

**Descripción:** Catálogo de tipos de trabajos de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ `extension_works`
   **Campos:**
-  `id`: Identificador único del tipo de trabajo.
-  `name`: Nombre del tipo de trabajo (Ej: Proyecto, Actividad).
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `work_statuses`

**Descripción:** Catálogo de estados del flujo de trabajo.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ `extension_works`, `work_status_history`
   **Campos:**
-  `id`: Identificador único del estado.
-  `name`: Nombre del estado (Ej: Borrador, Aprobado).
-  `description`: Descripción detallada del estado.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `institutional_project_types`

**Descripción:** Tipos de proyectos institucionales.
**Relaciones:**

-  _Tablas de las que depende:_ Ninguna
-  _Tablas que dependen de ella:_ `project_details`
   **Campos:**
-  `id`: Identificador único.
-  `name`: Nombre del tipo de proyecto institucional.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

**Lógica de Dependencia:** Es un módulo de soporte. Módulos como `ExtensionWork` y `Reporting` dependerán de él para interpretar IDs y mostrar nombres descriptivos.
