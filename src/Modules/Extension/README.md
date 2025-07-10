# Extension Module

Módulo de gestión de trabajos de extensión del sistema VIEX.

#### MÓDULO 2: `ExtensionWork` (Gestión de Trabajos de Extensión)

Este es el módulo central y más complejo. Gestiona el ciclo de vida completo de todos los tipos de trabajos de extensión.

-  **Responsabilidad Principal:** CRUD de trabajos, gestión de participantes, adjuntos, y orquestación del flujo de trabajo de aprobación.
-  **Componentes Clave:**
   -  `Controllers/`: `WorkController`, `AttachmentController`, `ParticipantController`.
   -  `Services/`:
      -  `WorkService`: Orquestador principal.
      -  `WorkflowService`: Lógica de transición de estados y permisos de flujo.
      -  `AttachmentService`: Gestión segura de archivos adjuntos.
   -  `Models/Entities/`: `ExtensionWork`, `WorkStatus`, `Attachment`, `WorkParticipant`, y las entidades de detalle (`ProjectDetails`, `ActivityDetails`, etc.).
   -  `Models/Repositories/`: `WorkRepository`, `StatusHistoryRepository`.
   -  `Strategies/`: Implementación del Patrón Estrategia para manejar la lógica específica de cada tipo de trabajo (`ProjectStrategy`, `ActivityStrategy`, etc.).

---

### Tarea 1: Creación y Edición de Trabajos de Extensión

Esta tarea se enfoca en cómo un profesor registra y modifica sus trabajos, manejando la complejidad de los diferentes tipos.

**1.1. Implementar el Patrón Estrategia (`WorkTypeStrategy`)**

-  **Decisión de Diseño Clave:** Evitar a toda costa un `if/else` o `switch` gigante basado en `work_type_id` dentro de los controladores o servicios.
-  **Conceptualización:**
   1. Crear una interfaz `WorkTypeStrategyInterface`. Esta definirá el contrato que todos los tipos de trabajo deben seguir:
      -  `getDetailModelClass(): string`: Devuelve la clase del modelo de detalle (`ProjectDetails::class`, `ActivityDetails::class`, etc.).
      -  `getValidationRules(bool $isCreation): array`: Devuelve las reglas de validación específicas para los campos de detalle de este tipo de trabajo.
      -  `processDetails(ExtensionWork $work, array $data): void`: Contiene la lógica para crear o actualizar los registros en las tablas de detalle (`project_details`, `activity_details`, etc.).
   2. Implementar clases concretas: `ProjectWorkStrategy`, `ActivityWorkStrategy`, `PublicationWorkStrategy`, y `TechnicalAssistanceStrategy`.
   3. Crear una `WorkTypeStrategyFactory` que, a partir de un `work_type_id`, instancie y devuelva la estrategia correcta. El `WorkService` usará esta fábrica.

**1.2. Orquestar la Creación/Actualización (`WorkService`)**

-  **Conceptualización:** El `WorkService` será el director de orquesta, no el que toca los instrumentos.
-  **`createWork(array $commonData, array $detailData, int $userId)`:**
   1. Recibe los datos comunes (título, fechas, etc.) y los datos de detalle por separado.
   2. Valida los datos comunes.
   3. Crea el registro principal en `extension_works` a través del `WorkRepository`. El trabajo se crea como `is_draft = true`.
   4. Usa la `WorkTypeStrategyFactory` para obtener la estrategia correcta según el `work_type_id`.
   5. Delega la validación y el procesamiento de los datos de detalle a la estrategia: `$strategy->processDetails($work, $detailData)`.
   6. Registra el evento inicial en `work_status_history` a través del `StatusHistoryRepository`.
-  **`updateWork(int $workId, array $commonData, array $detailData, int $userId)`:**
   1. Obtiene la entidad `ExtensionWork` usando el `WorkRepository`.
   2. **Verificación de Permisos:** Llama a un método de la entidad como `$work->canBeEditedBy($userId)`. La entidad `ExtensionWork` contiene esta lógica de negocio.
   3. Actualiza los campos del objeto `ExtensionWork`.
   4. Obtiene la estrategia y le delega la actualización de los detalles.
   5. Llama a `$work->save()` (que a su vez llama al repositorio).

**1.3. Gestionar la Interfaz de Usuario (`WorkController`)**

-  **Conceptualización:** El controlador es delgado y solo se preocupa del HTTP.
-  **`create()` y `edit()`:** Preparan la vista del formulario. Pueden obtener listas de catálogos (tipos de trabajo, unidades organizacionales) para poblar los `<select>`.
-  **`store()` y `update()`:**
   1. Validan la entrada básica (ej. que el título no esté vacío).
   2. Separan los datos comunes de los datos de detalle (que vendrán prefijados en el formulario, ej. `project_objective`, `activity_duration`).
   3. Llaman al `WorkService` pasándole los datos ya preparados.
   4. Manejan la respuesta del servicio para redirigir con un mensaje de éxito o error.

---

### Tarea 2: Gestión del Flujo de Trabajo de Aprobación

Este es el proceso secuencial que involucra a múltiples roles.

**2.1. Diseñar el `WorkflowService`**

-  **Decisión de Diseño Clave:** Centralizar toda la lógica de transición de estados en un solo lugar.
-  **Conceptualización:**
   -  **`canTransition(ExtensionWork $work, User $user, string $targetStatus): bool`:** Método principal que verifica si un usuario tiene el permiso para mover un trabajo a un nuevo estado.
      -  Ejemplo de lógica interna: "Si `$targetStatus` es 'Aprobado por Decano' y el rol del `$user` es 'Decano' y el estado actual del `$work` es 'Enviado a Decano', entonces devuelve `true`".
   -  **`transitionTo(ExtensionWork $work, User $user, string $targetStatus, ?string $comments = null)`:** Ejecuta la transición.
      1. Primero llama a `canTransition()` para asegurarse.
      2. Actualiza el `current_status_id` del trabajo.
      3. Crea un nuevo registro en `work_status_history`.
      4. **Punto Clave (Eventos):** Dispara un evento de dominio, por ejemplo, `WorkApprovedByCoordinator`.
-  **Configuración del Flujo:** El `WorkflowService` podría leer las reglas de transición desde un archivo de configuración para mayor flexibilidad.
   ```php
   // config/workflow.php
   return [
       'transitions' => [
           WorkStatus::DRAFT => [
               'target' => WorkStatus::PENDING_COORDINATOR,
               'allowed_role' => 'Profesor',
               'action_name' => 'submit',
           ],
           // ... más transiciones
       ],
   ];
   ```

**2.2. Implementar los Event Listeners**

-  **Conceptualización:** Crear clases `Listener` que se suscriban a los eventos disparados por el `WorkflowService`.
-  **Ejemplos:**
   -  **`SendApprovalNotificationListener`:** Se suscribe al evento `WorkApprovedByCoordinator`. Su lógica es simple: obtiene los datos del evento (trabajo, usuario) y utiliza el `MailerService` para notificar al siguiente en la cadena (el Decano).
   -  **`SendRejectionNotificationListener`:** Notifica al profesor cuando su trabajo es devuelto para subsanación o rechazado.

**2.3. Exponer Acciones en el `WorkController`**

-  **Conceptualización:** El controlador tendrá acciones específicas para el flujo.
-  **`submitForReview(int $workId)`:** Llama a `$workflowService->transitionTo($work, $user, WorkStatus::PENDING_COORDINATOR)`.
-  **`approve(int $workId)`:** Obtiene el estado objetivo según el rol del usuario y llama a `$workflowService->transitionTo(...)`.
-  **`requestRevision(int $workId, Request $request)`:** Llama al `WorkflowService` pasando los comentarios del formulario.

---

### Tarea 3: Gestión de Componentes Adicionales (Adjuntos y Participantes)

Estas funcionalidades son parte del trabajo de extensión pero merecen su propia gestión.

**3.1. Gestión Segura de Archivos (`AttachmentService` y `AttachmentController`)**

-  **Conceptualización:**
   -  **Subida (`store`):**
      1. El `AttachmentController` recibe el archivo.
      2. Valida el archivo (tamaño, tipo MIME) usando las reglas de validación de Phast.
      3. Delega al `AttachmentService`.
      4. El `AttachmentService` genera un nombre de archivo único, lo mueve a una ubicación segura (fuera del directorio `public/`), y crea el registro en la tabla `attachments` a través del `AttachmentRepository`.
   -  **Descarga (`show`):**
      1. El `AttachmentController` recibe el ID del adjunto.
      2. Usa el `AttachmentService` para obtener el registro del archivo.
      3. El servicio verifica que el usuario actual tenga permiso para ver el trabajo asociado a ese adjunto.
      4. Si tiene permiso, el servicio devuelve la ruta física del archivo, y el controlador genera una `Response` de tipo `file` para forzar la descarga, con el nombre original del archivo.

**3.2. Gestión de Participantes (`ParticipantService` y `ParticipantController`)**

-  **Conceptualización:** Similar a los adjuntos. Un controlador gestiona las peticiones de añadir/eliminar participantes de un trabajo, y un servicio contiene la lógica de negocio para interactuar con el `WorkParticipantRepository`. Esto se manejará probablemente vía AJAX desde la vista de edición del trabajo.

---

### Resumen del Flujo Conceptual del Módulo

1. **Estrategia primero:** Se define una estrategia para cada tipo de trabajo. Esto dicta sus campos, reglas y comportamiento.
2. **Servicio Orquestador (`WorkService`):** Utiliza la estrategia adecuada para crear/actualizar un trabajo, manteniendo la lógica principal limpia.
3. **Entidades con lógica:** El objeto `ExtensionWork` sabe sobre sus propios estados y reglas (`isDraft()`, `canBeEditedBy()`).
4. **Flujo de Trabajo Centralizado (`WorkflowService`):** Todas las transiciones de estado pasan por este servicio, que verifica permisos y registra el historial.
5. **Eventos y Notificaciones:** El flujo de trabajo dispara eventos, y los Listeners reaccionan a ellos (ej. enviando emails), desacoplando la notificación de la lógica de negocio principal.
6. **Controladores delgados:** Los controladores solo gestionan la comunicación HTTP, validan la entrada superficialmente y delegan toda la lógica a los servicios.

---

**Responsabilidad:** El ciclo de vida completo de un trabajo de extensión, desde su creación hasta su estado final antes de la certificación.

-  **`extension_works`**: La tabla principal y la entidad agregada raíz (Aggregate Root en DDD) del módulo.
-  **`work_status_history`**: Es la bitácora del ciclo de vida de un `extension_work`. Indisociable de este.
-  **`work_participants`**: Los participantes son un componente directo de un trabajo.
-  **`attachments`**: Las evidencias y adjuntos son parte integral de la definición de un trabajo.
-  **Entidades de Detalle (Especialización):**
   -  **`activity_details`**: Almacena datos específicos de las actividades.
   -  **`project_details`**: Almacena datos específicos de los proyectos.
   -  **`publication_details`**: Almacena datos específicos de las publicaciones.
   -  **`technical_assistance_details`**: Almacena datos específicos de las asistencias.
-  **Detalles de Proyectos (Sub-entidades):**
   -  **`project_costs_per_activity`**
   -  **`project_resources`**
   -  **`project_schedule_activities`**

---

## **Tabla:** `extension_works`

**Descripción:** Registro central de trabajos de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ `work_types`, `users`, `organizational_units`, `work_statuses`, `certifications`
-  _Tablas que dependen de ella:_ `activity_details`, `attachments`, `certifications`, `project_details`, `publication_details`, `technical_assistance_details`, `work_participants`, `work_status_history`
   **Campos:**
-  `id`: Identificador único del trabajo.
-  `title`: Título del trabajo.
-  `description`: Descripción.
-  `work_type_id`: FK al tipo de trabajo.
-  `primary_responsible_user_id`: Responsable principal.
-  `organizational_unit_id`: Unidad ejecutora.
-  `current_status_id`: Estado actual.
-  `final_certification_id`: FK opcional a la certificación final.
-  `metadata`: Datos adicionales en JSON.
-  `is_draft`: Indica si es borrador.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `work_status_history`

**Descripción:** Historial de cambios de estado.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`, `work_statuses`, `users`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `extension_work_id`: FK al trabajo.
-  `from_status_id`: Estado origen.
-  `to_status_id`: Estado destino.
-  `changed_by_user_id`: Usuario que realizó el cambio.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

## **Tabla:** `work_participants`

**Descripción:** Participantes de trabajos.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`, `users`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `extension_work_id`: FK al trabajo.
-  `user_id`: FK al usuario participante.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `attachments`

**Descripción:** Archivos adjuntos a trabajos de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`, `users`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `extension_work_id`: FK al trabajo.
-  `file_name`: Nombre original del archivo.
-  `stored_path`: Ruta de almacenamiento.
-  `uploaded_by_user_id`: Usuario que subió.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `activity_details`

**Descripción:** Detalles de actividades de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `extension_work_id`: FK al trabajo.
-  `introduction`: Introducción.
-  `justification`: Justificación.
-  `methodology`: Metodología.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `project_details`

**Descripción:** Detalle específico para proyectos de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`, `institutional_project_types`, `users`
-  _Tablas que dependen de ella:_ `project_costs_per_activity`, `project_resources`, `project_schedule_activities`
   **Campos:**
-  `extension_work_id`: FK al trabajo de extensión.
-  `project_category`: Categoría del proyecto.
-  `institutional_project_type_id`: FK al tipo institucional.
-  `general_description`: Descripción general.
-  `ss_coordinator_tutor_user_id`: Profesor tutor.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `publication_details`

**Descripción:** Detalles de publicaciones.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `extension_work_id`: FK al trabajo.
-  `publication_title_as_appears`: Título de la publicación.
-  `relevance_justification`: Justificación de relevancia.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `technical_assistance_details`

**Descripción:** Detalles de asistencias técnicas.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `extension_work_id`: FK al trabajo.
-  `assistance_denomination`: Tipo de asistencia.
-  `collaborating_institution_name`: Institución colaboradora.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `project_costs_per_activity`

**Descripción:** Costos por actividad de proyecto.
**Relaciones:**

-  _Tablas de las que depende:_ `project_details`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `project_detail_id`: FK a detalle de proyecto.
-  `activity_description`: Descripción de la actividad.
-  `cost_value`: Valor.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `project_resources`

**Descripción:** Recursos de proyectos de extensión.
**Relaciones:**

-  _Tablas de las que depende:_ `project_details`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `project_detail_id`: FK a detalle de proyecto.
-  `resource_type`: Tipo de recurso.
-  `description`: Descripción.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

## **Tabla:** `project_schedule_activities`

**Descripción:** Cronograma de actividades.
**Relaciones:**

-  _Tablas de las que depende:_ `project_details`
-  _Tablas que dependen de ella:_ Ninguna

**Campos:**

-  `id`: Identificador.
-  `project_detail_id`: FK a detalle de proyecto.
-  `activity_description`: Descripción de la actividad.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

**Lógica de Dependencia:** Este es el módulo de dominio principal. Dependerá del módulo `Auth` (para saber quién es el responsable y quién puede realizar acciones) y del módulo `Organizational` (para saber a qué unidad pertenece).
