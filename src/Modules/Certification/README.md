# Certification Module

# MÓDULO 4: `Certification` (Certificación y Validación)

Se enfoca exclusivamente en la etapa final del proceso: la generación, almacenamiento y validación de los certificados oficiales.

-  **Responsabilidad Principal:** Generar documentos de certificación (PDF), asignar números únicos y proveer mecanismos de validación (ej. códigos QR o enlaces de verificación).
-  **Componentes Clave:**
   -  `Controllers/`: `CertificationController` (para descarga y validación pública).
   -  `Services/`:
      -  `CertificationService`: Lógica de emisión y numeración.
      -  `PdfGenerationService`: Responsable de crear el PDF basado en plantillas.
   -  `Models/Entities/`: `Certification`.
   -  `Models/Repositories/`: `CertificationRepository`.

---

### Tarea 1: Lógica de Emisión y Numeración (`CertificationService`)

Este servicio es el cerebro del módulo. Orquesta la creación de un certificado como un acto oficial dentro del sistema.

**1.1. Diseñar el `CertificationService`**

-  **Conceptualización:** Este servicio será invocado exclusivamente por el `WorkflowService` del módulo `ExtensionWork` cuando un trabajo alcanza el estado final de aprobación. No debe ser llamado directamente desde un controlador.
-  **Método Principal: `issueForWork(ExtensionWork $work, User $issuer): Certification`**
   1. **Precondición:** Verificar que el trabajo realmente esté en un estado "Aprobado" y que aún no tenga un certificado asociado. Esto previene la doble emisión.
   2. **Generar Número Único:** Invocar a un método privado `generateUniqueCertificateNumber()`.
      -  **Decisión de Diseño:** La estructura del número de certificado debe ser definida y robusta. Por ejemplo: `VIEX-{AÑO}-{ID_UNIDAD_ACADEMICA}-{CONSECUTIVO}`. El servicio se encargará de obtener el próximo número consecutivo de forma atómica para evitar _race conditions_ (se puede lograr con un bloqueo de tabla o una transacción a nivel de `SERIALIZABLE` si la base de datos lo permite).
   3. **Llamar al `PdfGenerationService`:** Pasar los datos del trabajo (`$work`), el nuevo número de certificado y el emisor (`$issuer`) para generar el documento PDF.
   4. **Almacenar el PDF:** Recibir la ruta del archivo PDF generado y almacenarla de forma segura.
   5. **Crear el Registro en la BD:** Usar el `CertificationRepository` para crear una nueva entrada en la tabla `certifications`, guardando el número, la fecha de emisión, el ID del emisor y la ruta al archivo PDF.
   6. **Actualizar el Trabajo de Extensión:** Actualizar el `extension_works.final_certification_id` con el ID del nuevo certificado.
   7. **Devolver la Entidad:** Retornar el objeto `Certification` recién creado.

**1.2. Implementar la Generación de Número Único**

-  **Conceptualización:** Este es un punto crítico.
   -  **Opción A (Simple):** `SELECT MAX(id) FROM certifications` y sumar 1 para el consecutivo. **Riesgo:** Propenso a _race conditions_ en un entorno de alta concurrencia.
   -  **Opción B (Mejor):** Crear una tabla separada `certificate_sequences` que mantenga el último número por `(año, unidad_academica)`. La obtención y actualización de este número debe hacerse dentro de una transacción con un nivel de aislamiento elevado o usando `SELECT ... FOR UPDATE` para bloquear la fila.
   -  **Opción C (Framework):** Si el framework soporta secuencias de base de datos, usarlas.

---

### Tarea 2: Generación del Documento PDF (`PdfGenerationService`)

Este componente se especializa en una única tarea: crear el PDF. Esto lo desacopla de la lógica de negocio de la certificación.

**2.1. Diseñar el `PdfGenerationService`**

-  **Conceptualización:** Un servicio que abstrae la librería de generación de PDF subyacente (ej. `TCPDF`, `FPDF`, `Dompdf`). Si en el futuro se decide cambiar de librería, solo se modifica este servicio.
-  **Método Principal: `generate(array $data): string`**
   1. Recibe un array de datos estandarizado que incluye:
      -  `certificate_number`
      -  `issue_date`
      -  `work_title`
      -  `responsible_name`
      -  `participants_names`
      -  `organizational_unit_name`
      -  etc.
   2. **Cargar Plantilla:** Cargar una plantilla HTML/PHP (`.phtml`) predefinida para el certificado. Esta plantilla contendrá placeholders para los datos.
   3. **Inyectar Datos:** Poblar la plantilla con los datos recibidos.
   4. **Generar Código QR:** Generar un código QR que contenga la URL de validación pública (ver Tarea 3). La URL debe ser algo como `https://viex.up.ac.pa/certificados/validar/{UUID_DEL_CERTIFICADO}`.
   5. **Renderizar a PDF:** Usar la librería elegida para convertir el HTML poblado en un archivo PDF.
   6. **Almacenamiento:** Guardar el PDF en una ruta segura y no pública (ej. `storage/app/certificates/`). El nombre del archivo debe ser único y no adivinable, por ejemplo, usando un UUID.
   7. **Devolver la Ruta:** Retornar la ruta física donde se guardó el archivo para que el `CertificationService` la almacene en la base de datos.

---

### Tarea 3: Descarga y Validación de Certificados (`CertificationController`)

Esta es la cara visible del módulo para los usuarios finales.

**3.1. Descarga Segura de Certificados**

-  **Conceptualización:** Un usuario no debe poder acceder a un PDF simplemente conociendo su URL. El acceso debe ser controlado.
-  **Ruta:** `GET /certificados/{certification_id}/descargar`
-  **Controlador (`download`):**
   1. Obtener el ID del certificado de la ruta.
   2. Usar el `CertificationRepository` para encontrar el certificado.
   3. **Verificación de Permisos:**
      -  Obtener el trabajo de extensión asociado (`$certification->extensionWork`).
      -  Verificar si el usuario autenticado tiene permiso para ver ese trabajo (debe ser el responsable, un participante, o un administrador con los permisos adecuados). Esta lógica puede estar en un `Policy` o en el `WorkService`.
   4. Si el permiso es concedido, obtener la ruta física del archivo (`stored_path`) y devolver una respuesta de tipo `file` para iniciar la descarga.
   5. Si no tiene permiso, devolver un error 403 (Prohibido).

**3.2. Validación Pública de Certificados**

-  **Conceptualización:** Un tercero (fuera de la universidad) que recibe un certificado debe poder verificar su autenticidad.
-  **Ruta Pública:** `GET /certificados/validar/{uuid}`. La ruta no debe usar el ID numérico secuencial para evitar que alguien pueda iterar y descubrir todos los certificados. La tabla `certifications` debería tener una columna `uuid` o `validation_token`.
-  **Controlador (`validatePublic`):**
   1. Obtener el `uuid` de la ruta.
   2. Buscar en `CertificationRepository` por `uuid`.
   3. **Si se encuentra:** Mostrar una página pública simple y clara que confirme la validez del certificado. Debe mostrar información clave no sensible para la verificación visual:
      -  "Certificado Válido"
      -  Número de Certificado: `VIEX-2024-FAC-123`
      -  Título del Trabajo: `Seminario de PHP Avanzado`
      -  Emitido a: `Nombre del Responsable`
      -  Fecha de Emisión: `2024-10-28`
   4. **Si no se encuentra:** Mostrar una página que indique "Certificado no encontrado o inválido".

---

### Resumen del Flujo Conceptual del Módulo

1. **Activación:** El módulo `ExtensionWork` aprueba un trabajo y le pide al `CertificationService` que emita un certificado.
2. **Emisión Interna:** El `CertificationService` genera un número único, le pide al `PdfGenerationService` que cree y guarde el PDF (incluyendo un código QR con un UUID de validación), y finalmente registra toda esta información en la tabla `certifications`.
3. **Descarga Privada:** Un usuario autenticado y con permisos descarga su certificado a través de una ruta segura (`/certificados/{id}/descargar`) gestionada por el `CertificationController`.
4. **Validación Pública:** Un tercero escanea el código QR del PDF, lo que lo lleva a una página pública (`/certificados/validar/{uuid}`) que confirma la autenticidad del documento sin exponer datos sensibles ni requerir inicio de sesión.

---

**Responsabilidad:** La etapa final, oficial y pública del proceso.

-  **`certifications`**: La entidad principal de este módulo. Representa el artefacto final y oficial.

## **Tabla:** `certifications`

**Descripción:** Registro de certificaciones emitidas.
**Relaciones:**

-  _Tablas de las que depende:_ `extension_works`, `users`
-  _Tablas que dependen de ella:_ `extension_works` (campo `final_certification_id`)
   **Campos:**
-  `id`: Identificador de certificación.
-  `extension_work_id`: FK al trabajo.
-  `certification_number`: Número de certificación.
-  `issued_by_user_id`: Usuario que emite.
-  `is_active`: estado del registro
-  `created_at`: datetime de la creacion del registro
-  `updated_at`: datetime de la ultima actualizacion del registro
-  `soft_delete`: datetime de la eliminacion logica del registro

---

**Lógica de Dependencia:** Este módulo se activa al final del flujo del módulo `ExtensionWork`. Depende de `ExtensionWork` para saber qué certificar y de `Auth` para saber quién emite el certificado.
