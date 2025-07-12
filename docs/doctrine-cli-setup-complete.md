# Doctrine CLI - Configuración Completada

## ✅ Estado: COMPLETADO

La configuración de la consola de Doctrine CLI ha sido completada exitosamente para el framework VIEX con Oracle Database.

## Archivos Creados/Modificados

### 1. `/bin/doctrine` - Script Principal de la Consola

-  Archivo ejecutable que proporciona acceso a todos los comandos de Doctrine
-  Compatible con Doctrine ORM v3.5.0
-  Configurado para usar el EntityManager desde el contenedor DI de la aplicación

### 2. `/cli-config.php` - Configuración Legacy (Opcional)

-  Archivo de configuración para compatibilidad con versiones anteriores
-  Retorna directamente el EntityManager para Doctrine 3.x

### 3. `/docs/doctrine-cli-guide.md` - Guía Completa de Uso

-  Documentación detallada de todos los comandos disponibles
-  Ejemplos específicos para el módulo organizacional
-  Precauciones y buenas prácticas

## Entidad Corregida

### `OrganizationalUnit.php`

-  ✅ Campos booleanos convertidos a `smallint` para compatibilidad con Oracle
-  ✅ Nombres de columnas explícitos en snake_case (`is_active`, `created_at`, etc.)
-  ✅ Métodos actualizados para manejar conversión int ↔ bool
-  ✅ Mapeo completamente validado

## Esquema de Base de Datos

### Tabla `organizational_units`

```sql
CREATE TABLE organizational_units (
    id NUMBER PRIMARY KEY,
    name VARCHAR2(255) NOT NULL,
    type VARCHAR2(50) NOT NULL,
    is_active NUMBER(1) DEFAULT 1,
    created_at TIMESTAMP(0) NOT NULL,
    updated_at TIMESTAMP(0) NOT NULL,
    soft_deleted NUMBER(1) DEFAULT 0,
    parent_id NUMBER REFERENCES organizational_units(id)
);
```

## Comandos Verificados

### ✅ Funcionando Correctamente

```bash
# Información de entidades
./bin/doctrine orm:info

# Validación de mapeo
./bin/doctrine orm:validate-schema --skip-sync

# Gestión de esquema
./bin/doctrine orm:schema-tool:create
./bin/doctrine orm:schema-tool:drop --force

# Consultas SQL directas
./bin/doctrine dbal:run-sql "SELECT * FROM organizational_units"

# Limpieza de cache
./bin/doctrine orm:clear-cache:metadata
```

## Resolución de Problemas

### Problema Principal Resuelto

-  **Issue**: Oracle no soporta tipo `boolean` nativo
-  **Solución**: Usar `smallint` con valores 0/1 y conversión en métodos PHP
-  **Resultado**: Compatibilidad completa manteniendo la interfaz booleana

### Compatibilidad API

-  **Issue**: Doctrine ORM 3.x cambió la API de ConsoleRunner
-  **Solución**: Usar directamente EntityManager en lugar de HelperSet
-  **Resultado**: Consola funcionando con la API actual

## Próximos Pasos

1. **Migrations**: Considerar implementar Doctrine Migrations para cambios futuros
2. **Testing**: Ejecutar tests del módulo organizacional
3. **Documentación**: Actualizar README del proyecto con comandos CLI

## Comandos de Uso Común

```bash
# Verificar estado del sistema
./bin/doctrine orm:info
./bin/doctrine orm:validate-schema --skip-sync

# Gestionar esquema (desarrollo)
./bin/doctrine orm:schema-tool:update --dump-sql
./bin/doctrine orm:schema-tool:update --force

# Consultas de diagnóstico
./bin/doctrine dbal:run-sql "SELECT COUNT(*) as total FROM organizational_units"
./bin/doctrine dbal:run-sql "DESCRIBE organizational_units"

# Limpieza de cache
./bin/doctrine orm:clear-cache:metadata
./bin/doctrine orm:clear-cache:query
```

---

**Nota**: La configuración está lista para uso en desarrollo. Para producción, se recomienda usar migrations en lugar de `schema-tool:update`.
