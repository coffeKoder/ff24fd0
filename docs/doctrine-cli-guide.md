# Doctrine CLI - Guía de Uso

## Descripción

La consola de Doctrine CLI proporciona herramientas para gestionar el esquema de base de datos, cache, proxies y validación de entidades.

## Comandos Disponibles

### Información de Entidades

```bash
# Mostrar información básica de todas las entidades mapeadas
./bin/doctrine orm:info

# Describir información detallada sobre entidades específicas
./bin/doctrine orm:mapping:describe NombreDeEntidad

# Validar que el mapeo de entidades es correcto
./bin/doctrine orm:validate-schema
```

### Gestión del Esquema de Base de Datos

```bash
# Crear el esquema completo en la base de datos
./bin/doctrine orm:schema-tool:create

# Actualizar el esquema existente para coincidir con el mapeo actual
./bin/doctrine orm:schema-tool:update

# Ver el SQL que se ejecutaría sin aplicar cambios
./bin/doctrine orm:schema-tool:update --dump-sql

# Forzar la actualización (sin confirmación)
./bin/doctrine orm:schema-tool:update --force

# Eliminar completamente el esquema de la base de datos
./bin/doctrine orm:schema-tool:drop

# Ver el SQL de eliminación sin ejecutar
./bin/doctrine orm:schema-tool:drop --dump-sql

# Forzar la eliminación (sin confirmación)
./bin/doctrine orm:schema-tool:drop --force
```

### Generación de Proxies

```bash
# Generar clases proxy para todas las entidades
./bin/doctrine orm:generate-proxies

# Generar proxies en un directorio específico
./bin/doctrine orm:generate-proxies /ruta/a/directorio/proxies
```

### Limpieza de Cache

```bash
# Limpiar cache de metadata
./bin/doctrine orm:clear-cache:metadata

# Limpiar cache de consultas
./bin/doctrine orm:clear-cache:query

# Limpiar cache de resultados
./bin/doctrine orm:clear-cache:result

# Limpiar regiones específicas de cache de segundo nivel
./bin/doctrine orm:clear-cache:region:entity NombreEntidad
./bin/doctrine orm:clear-cache:region:collection NombreColeccion
./bin/doctrine orm:clear-cache:region:query NombreRegion
```

### Ejecución de Consultas

```bash
# Ejecutar DQL directamente
./bin/doctrine orm:run-dql "SELECT u FROM App\Entity\User u WHERE u.active = 1"

# Ejecutar SQL directamente
./bin/doctrine dbal:run-sql "SELECT * FROM users WHERE is_active = 1"
```

## Ejemplos Específicos para VIEX

### Validar Entidades del Módulo Organizacional

```bash
# Verificar que las entidades están correctamente mapeadas
./bin/doctrine orm:validate-schema

# Ver información de la entidad OrganizationalUnit
./bin/doctrine orm:mapping:describe "Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit"
```

### Crear/Actualizar Esquema

```bash
# Ver qué cambios se aplicarían al esquema
./bin/doctrine orm:schema-tool:update --dump-sql

# Aplicar cambios al esquema (usar con precaución en producción)
./bin/doctrine orm:schema-tool:update --force
```

### Consultas de Prueba

```bash
# Consultar unidades organizacionales activas
./bin/doctrine dbal:run-sql "SELECT id, name, code, is_active FROM organizational_units WHERE is_active = 1"

# Contar total de unidades organizacionales
./bin/doctrine dbal:run-sql "SELECT COUNT(*) as total FROM organizational_units"
```

## Notas Importantes

### Entorno de Desarrollo

-  Todos los comandos deben ejecutarse desde la raíz del proyecto
-  Asegúrate de que el contenedor de Docker con Oracle esté ejecutándose
-  La configuración se carga automáticamente desde el bootstrap de la aplicación

### Precauciones en Producción

-  Siempre usa `--dump-sql` primero para revisar los cambios antes de aplicarlos
-  Haz backup de la base de datos antes de ejecutar `schema-tool:update` o `schema-tool:drop`
-  En producción, considera usar migrations en lugar de `schema-tool:update`

### Troubleshooting

Si encuentras errores:

1. Verifica que el contenedor de Oracle esté ejecutándose: `docker-compose ps`
2. Verifica la conectividad: `./bin/doctrine dbal:run-sql "SELECT 1 FROM DUAL"`
3. Limpia el cache si es necesario: `./bin/doctrine orm:clear-cache:metadata`

## Configuración

El archivo de configuración se encuentra en:

-  `/bin/doctrine` - Script principal de la consola
-  `/cli-config.php` - Configuración legacy (si es necesaria)
-  La configuración del EntityManager se carga desde el bootstrap de la aplicación
