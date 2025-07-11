# Análisis y Resolución del Error de Memory Exhaustion en VIEX

## 📋 Resumen Ejecutivo

Durante la integración del módulo Organizational en el framework VIEX, se presentó un error crítico de agotamiento de memoria (`PHP Fatal error: Allowed memory size of 2147483648 bytes exhausted`) que impedía la carga de servicios del sistema. Mediante un análisis sistemático de trazado inverso de dependencias, se identificó y resolvió una **dependencia circular en Doctrine ORM** que causaba un bucle infinito durante la carga de metadatos.

---

## 🔍 Análisis del Problema

### Error Inicial

```
PHP Fatal error: Allowed memory size of 2147483648 bytes exhausted (tried to allocate 262144 bytes)
in /vendor/doctrine/persistence/src/Persistence/Mapping/AbstractClassMetadataFactory.php on line 146
```

### Síntomas Observados

-  ✅ La aplicación se inicializaba correctamente
-  ✅ El EntityManager se creaba sin problemas
-  ❌ Error de memoria al intentar obtener el Repository
-  ❌ Fallo específico en `AbstractClassMetadataFactory.php:146`

---

## 🕵️ Metodología de Investigación

### 1. Trazado Inverso de Dependencias

Se implementó un script de análisis (`trace_dependencies.php`) que permitió identificar el punto exacto del fallo:

```php
// Ruta de ejecución identificada:
Container DI → OrganizationalServiceProvider → DoctrineOrganizationalUnitRepository
└── EntityManager.getRepository(OrganizationalUnit::class)
    └── Doctrine MetadataFactory.loadMetadata()
        └── LOOP INFINITO (Memory Exhaustion)
```

### 2. Análisis Granular del Constructor

Se creó un script específico (`analyze_repository_constructor.php`) que aisló el problema al método:

```php
$this->entityManager->getRepository(OrganizationalUnit::class)
```

Este método desencadenaba la carga de metadatos de Doctrine, donde ocurría el bucle infinito.

---

## 🎯 Causa Raíz Identificada

### Dependencia Circular en Doctrine ORM

**Archivo**: `src/Modules/Organizational/Domain/Entities/OrganizationalUnit.php`

**Configuración problemática**:

```php
#[ORM\Entity(repositoryClass: 'Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine\DoctrineOrganizationalUnitRepository')]
```

### Secuencia del Bucle Infinito

1. **DI Container** intenta crear `DoctrineOrganizationalUnitRepository`
2. **Repository Constructor** llama a `$entityManager->getRepository(OrganizationalUnit::class)`
3. **Doctrine MetadataFactory** lee los atributos de la entidad `OrganizationalUnit`
4. **Entidad OrganizationalUnit** especifica `repositoryClass: 'DoctrineOrganizationalUnitRepository'`
5. **Doctrine** intenta instanciar el Repository que ya se está creando
6. **BUCLE INFINITO** → Agotamiento de memoria

```mermaid
graph TD
    A[DI Container] --> B[DoctrineOrganizationalUnitRepository]
    B --> C[EntityManager.getRepository()]
    C --> D[MetadataFactory.loadMetadata()]
    D --> E[OrganizationalUnit attributes]
    E --> F[repositoryClass specified]
    F --> B

    style F fill:#ff9999
    style B fill:#ff9999
```

---

## ✅ Solución Implementada

### Eliminación de la Referencia Circular

**Cambio realizado** en `OrganizationalUnit.php`:

**Antes** (problemático):

```php
#[ORM\Entity(repositoryClass: 'Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine\DoctrineOrganizationalUnitRepository')]
#[ORM\Table(name: 'organizational_units')]
#[ORM\HasLifecycleCallbacks]
```

**Después** (corregido):

```php
#[ORM\Entity]
#[ORM\Table(name: 'organizational_units')]
#[ORM\HasLifecycleCallbacks]
```

### Justificación Técnica

-  **Doctrine ORM** no requiere la especificación explícita de `repositoryClass` en los atributos de la entidad
-  **DI Container** maneja la inyección del Repository a través de interfaces
-  **Eliminación de la referencia** rompe el ciclo de dependencias sin afectar la funcionalidad

---

## 🧪 Verificación de la Solución

### Pruebas Realizadas

1. **Test de Constructor Aislado**:

   ```bash
   php analyze_repository_constructor.php
   ```

   **Resultado**: ✅ Repository creado exitosamente

2. **Test de Integración Completa**:

   ```bash
   php debug_integration.php
   ```

   **Resultado**: ✅ Todos los servicios del módulo funcionando

3. **Test de Aplicación Real**:
   **Resultado**: ✅ Sistema operativo sin errores de memoria

---

## 🎓 Lecciones Aprendidas

### Mejores Prácticas Identificadas

1. **Evitar Referencias Circulares en ORM**:

   -  No especificar `repositoryClass` en entidades cuando se usa DI
   -  Usar interfaces para inyección de dependencias

2. **Estrategia de Debug para Memory Exhaustion**:

   -  Implementar trazado inverso de dependencias
   -  Aislar componentes específicos para identificar bucles
   -  Analizar stack traces de Doctrine detalladamente

3. **Configuración de Doctrine ORM**:
   -  Los atributos `#[ORM\Entity]` deben ser mínimos
   -  La inyección de dependencias se maneja a nivel de contenedor, no de entidad

### Antipatrones Evitados

❌ **No hacer**:

```php
#[ORM\Entity(repositoryClass: MyRepository::class)]
class MyEntity {
    // Esto puede crear dependencias circulares con DI
}
```

✅ **Hacer**:

```php
#[ORM\Entity]
class MyEntity {
    // Limpio y sin referencias circulares
}

// Registrar en DI Container:
$container->set(MyRepositoryInterface::class, MyRepository::class);
```

---

## 📊 Impacto de la Resolución

### Antes de la Corrección

-  ❌ Sistema inoperativo
-  ❌ Memory limit de 2GB agotado
-  ❌ Módulo Organizational no funcional
-  ❌ Imposibilidad de crear servicios

### Después de la Corrección

-  ✅ Sistema completamente operativo
-  ✅ Uso normal de memoria
-  ✅ Módulo Organizational completamente funcional
-  ✅ Todos los servicios DI registrados y funcionando
-  ✅ Integración con Oracle Database operativa

---

## 🔧 Herramientas de Diagnóstico Desarrolladas

### Scripts de Análisis Creados

1. **`trace_dependencies.php`**: Trazado inverso de dependencias
2. **`analyze_repository_constructor.php`**: Análisis granular del constructor
3. **`test_oracle_connection.php`**: Verificación de conectividad DB
4. **`debug_integration.php`**: Test de integración completa

Estos scripts proporcionan una metodología replicable para diagnosticar problemas similares en el futuro.

---

## 📝 Conclusión

La resolución exitosa de este error demuestra la importancia de:

1. **Análisis sistemático** en lugar de intentos aleatorios de corrección
2. **Trazado granular** para identificar dependencias circulares
3. **Comprensión profunda** de la interacción entre Doctrine ORM y sistemas DI
4. **Pruebas incrementales** para validar cada paso de la resolución

El sistema VIEX está ahora completamente operativo y preparado para el desarrollo continuado del módulo de gestión organizacional.

---

_Resolución completada el 11 de julio de 2025_  
_Sistema: VIEX - Plataforma de Registro y Certificación de Trabajos de Extensión_  
_Framework: Slim 4 + Doctrine ORM v3 + PHP-DI_
