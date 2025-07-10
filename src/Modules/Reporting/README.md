# Reporting Module

# MÓDULO 5: `Reporting` (Reportes y Estadísticas)

Un módulo dedicado a la inteligencia de negocio, crucial para la toma de decisiones de VIEX.

-  **Responsabilidad Principal:** Generar reportes consolidados, estadísticas y dashboards sobre la actividad de extensión en la universidad.
-  **Componentes Clave:**
   -  `Controllers/`: `ReportController`, `DashboardController`.
   -  `Services/`: `StatisticsService`, `ReportGenerationService`.
   -  `Models/Repositories/`: `ReportRepository` (Puede contener consultas SQL optimizadas que crucen datos de otros módulos).
   -  _Nota: Este módulo leerá datos de `ExtensionWork` y `Organizational`, pero no modificará su lógica de negocio._

---

### Tarea 1: Agregación de Datos y Estadísticas (`StatisticsService` y `ReportRepository`)

El corazón de este módulo es la capacidad de procesar grandes volúmenes de datos para obtener métricas significativas.

**1.1. Diseñar el `ReportRepository`**

-  **Decisión de Diseño Clave:** Evitar el uso del ORM para consultas de agregación complejas. Las consultas de reportes a menudo involucran múltiples `JOINs`, `GROUP BY`, y funciones de agregación (`COUNT`, `SUM`, `AVG`). Intentar construir esto con el ORM puede ser ineficiente y resultar en consultas subóptimas.
-  **Conceptualización:**
   -  Este repositorio contendrá métodos con **consultas SQL crudas y optimizadas**. Cada método corresponderá a una métrica específica.
   -  **Métodos Clave:**
      -  `getWorkCountsByStatus(): array`: Devuelve un array como `['draft' => 150, 'approved' => 800, ...]`.
      -  `getWorkCountsByType(): array`: Devuelve `['project' => 400, 'activity' => 500, ...]`.
      -  `getWorkCountsByOrganizationalUnit(int $parentId = null)`: Devuelve el conteo de trabajos por unidad académica. Puede recibir un `parentId` para mostrar, por ejemplo, solo las Facultades o los Departamentos de una Facultad específica.
      -  `getAverageTimeToCertification(): float`: Calcula el tiempo promedio (en días) desde `submitted_at` hasta `issue_date` en la tabla de certificaciones.
      -  `getMostActiveProfessors(int $limit = 10): array`: Devuelve un ranking de los profesores con más trabajos certificados.
   -  **Seguridad:** Aunque sean consultas crudas, deben ser seguras. Todos los parámetros de entrada (como fechas o IDs de unidad) deben ser vinculados (`bound`) usando `?` para prevenir inyección SQL.

**1.2. Diseñar el `StatisticsService`**

-  **Conceptualización:** Este servicio actúa como una fachada sobre el `ReportRepository`. Su responsabilidad es obtener los datos crudos del repositorio y prepararlos para ser consumidos por los controladores (ej. formatear datos para gráficos, combinar resultados de varias consultas).
-  **Responsabilidades Principales:**
   -  **Caching de Datos Agregados:** Las estadísticas no necesitan ser en tiempo real. Este servicio debe implementar una capa de caché robusta.
      -  **Decisión de Diseño:** Usar un sistema de caché como Redis o File Cache con un TTL razonable (ej. 1 a 24 horas, dependiendo de la métrica). Una clave de caché podría ser `statistics:work_counts_by_status`.
   -  **Preparación para Visualización:**
      -  `getDashboardData()`: Un método que llama a varias funciones del `ReportRepository` (`getWorkCountsByStatus`, `getWorkCountsByType`, etc.) y compila todos los datos necesarios para el dashboard principal en una única estructura de datos.
      -  `getChartDataForWorksByUnit()`: Formatea los datos para que una librería de gráficos (como Chart.js o ApexCharts) pueda consumirlos directamente. Por ejemplo:
         ```json
         {
           "labels": ["Facultad de Ciencias", "Facultad de Humanidades", ...],
           "series": [120, 95, ...]
         }
         ```
-  **Invalidación de Caché:** Implementar un mecanismo para refrescar la caché. Podría ser un comando de consola (`php phast cache:clear-reports`) que se ejecute periódicamente (vía `cron job`) o un botón en la interfaz de administrador.

---

### Tarea 2: Presentación de Datos (Controladores y Vistas)

Esta tarea se enfoca en cómo los usuarios finales interactúan con los reportes y estadísticas.

**2.1. Implementar el `DashboardController`**

-  **Conceptualización:** Este controlador es responsable de la vista principal de estadísticas, un panel de control visual para los administradores de VIEX.
-  **Método `index()`:**
   1. Verificar que el usuario tenga el permiso adecuado (ej. `reporting.view.dashboard`).
   2. Llamar a `$statisticsService->getDashboardData()`.
   3. Pasar los datos compilados a una vista (`dashboard/index`).
   4. La vista contendrá los componentes de visualización (tarjetas de métricas, gráficos de torta, gráficos de barras) que consumirán los datos preparados por el servicio.

**2.2. Implementar el `ReportController`**

-  **Conceptualización:** Este controlador gestionará la generación de reportes tabulares, más detallados y con filtros personalizables.
-  **Método `index()`:**
   1. Muestra una vista con un formulario de filtros avanzados:
      -  Rango de fechas
      -  Tipo de trabajo (multiselect)
      -  Estado del trabajo (multiselect)
      -  Unidad Organizacional (un selector de árbol o dropdown jerárquico)
-  **Método `generate(Request $request)`:**
   1. Recibe los filtros del formulario.
   2. Valida los filtros.
   3. Llama a un servicio `ReportGenerationService` (ver Tarea 3) pasándole los filtros.
   4. Recibe los datos del reporte y el formato deseado (HTML, PDF, CSV).
   5. Devuelve la respuesta apropiada:
      -  Si es HTML, renderiza una vista con la tabla de resultados.
      -  Si es PDF o CSV, devuelve una respuesta de tipo `file` para iniciar la descarga.

---

### Tarea 3: Generación de Reportes Exportables (`ReportGenerationService`)

Este servicio se especializa en crear los archivos de reporte descargables.

**3.1. Diseñar el `ReportGenerationService`**

-  **Conceptualización:** Similar al `PdfGenerationService` del módulo de certificación, este servicio abstrae las librerías para generar archivos.
-  **Método Principal: `generateReport(array $filters, string $format = 'html')`**
   1. Llama a un método específico en el `ReportRepository` para obtener los datos crudos basados en los filtros. Por ejemplo, `findWorksByCriteria(array $filters)`. Esta consulta puede ser compleja e incluir muchos `JOINs`.
   2. **Lógica de Formato:**
      -  **`html`:** Simplemente devuelve los datos para que el controlador los renderice en una vista.
      -  **`pdf`:** Utiliza el `PdfGenerationService` o una librería similar. Puede ser un desafío formatear tablas grandes en PDF, por lo que a menudo se prefiere un formato de "resumen".
      -  **`csv`/`xlsx`:** Esta es la opción más común y útil. Utiliza una librería (como `PhpSpreadsheet` o simplemente `fputcsv`) para crear un archivo con los datos.
         -  Primero, escribe la fila de encabezados.
         -  Luego, itera sobre los resultados de la consulta y escribe cada fila en el archivo.
   3. El método devuelve la ruta al archivo generado o los datos en crudo, dependiendo del formato.

---

### Resumen del Flujo Conceptual del Módulo

1. **Consultas Optimizadas:** El `ReportRepository` contiene consultas SQL especializadas y performantes para agregar datos.
2. **Capa de Servicio y Caché:** El `StatisticsService` orquesta la obtención de datos, los formatea y los cachea agresivamente para evitar recalcularlos en cada petición.
3. **Dashboard Visual:** El `DashboardController` consume los datos pre-agregados del `StatisticsService` para mostrar una vista general rápida y visual.
4. **Reportes Detallados y Flexibles:** El `ReportController` permite a los usuarios aplicar filtros específicos, y delega la tarea pesada de generar y formatear los datos al `ReportGenerationService`.
5. **Exportación:** El `ReportGenerationService` utiliza librerías especializadas para generar archivos descargables en formatos estándar como CSV o PDF.

---

**Responsabilidad:** Inteligencia de negocio y visualización de datos agregados.

-  **(Sin Entidades Propias)**: Este es un módulo especial. No "posee" tablas de datos primarias. Su función es **leer** datos de los otros módulos (`ExtensionWork`, `Organizational`, `Certification`, `Auth`) y presentarlos de forma agregada. Su `ReportRepository` contendrá consultas complejas que cruzan los límites de los otros módulos de forma segura y optimizada.

**Lógica de Dependencia:** Es un módulo de solo lectura que depende de casi todos los demás módulos para obtener sus datos.
