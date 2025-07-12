# 🎓 VIEX - Plataforma de Registro y Certificación de Trabajos de Extensión

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![Doctrine ORM](https://img.shields.io/badge/Doctrine%20ORM-3.5-orange)](https://doctrine-project.org)
[![Slim Framework](https://img.shields.io/badge/Slim%20Framework-4.x-green)](https://slimframework.com)
[![Architecture](https://img.shields.io/badge/Architecture-DDD%20%2B%20Hexagonal-purple)](https://github.com)

> Plataforma web para la digitalización y automatización del proceso de registro, gestión, validación y certificación de trabajos de extensión de la Universidad de Panamá.

## 📋 Descripción del Proyecto

VIEX es una aplicación web que digitaliza el "Manual de Procedimientos Para Presentar Trabajos de Extensión" de la Vicerrectoría de Extensión de la Universidad de Panamá, permitiendo:

- ✅ **Registro digital** de proyectos de extensión, actividades, publicaciones y asistencias técnicas
- ✅ **Flujo de trabajo automatizado** desde propuesta hasta certificación
- ✅ **Sistema RBAC** para gestión de roles y permisos
- ✅ **Repositorio centralizado** de todos los trabajos de extensión
- ✅ **Seguimiento transparente** del estado de las propuestas
- ✅ **Certificación digital** por la Vicerrectoría de Extensión

## 🏗️ Arquitectura

El proyecto sigue **Domain-Driven Design (DDD)** con **Arquitectura Hexagonal**:

```
src/Modules/
├── User/                    # ✅ COMPLETADO
├── Extension/               # 🚧 Próximo
├── Certification/           # 🚧 Planificado
├── Organizational/          # 🚧 Planificado
├── Reporting/              # 🚧 Planificado
└── Admin/                  # 🚧 Planificado
```

### 📁 Estructura de Módulos
```
ModuleName/
├── Application/     # Casos de uso y DTOs
├── Domain/         # Entidades, VOs, Repositories
└── Infrastructure/ # Implementaciones técnicas
```

## 🚀 Estado del Proyecto

### ✅ Completado
- [x] **Configuración base del framework**
- [x] **Estructura modular DDD**
- [x] **Sistema de configuración**
- [x] **Integración con Doctrine ORM**
- [x] **Sistema de logging con Monolog**
- [x] **Contenedor de dependencias PHP-DI**
- [x] **Middleware y routing con Slim**
- [x] **Gestión de sesiones con Aura.Session**
- [x] **Módulo User** - Sistema completo de usuarios, autenticación y RBAC ✨

### 🚧 En Desarrollo
- [ ] **Módulo Extension** - Gestión de proyectos de extensión  
- [ ] **Módulo Certification** - Sistema de certificaciones
- [ ] **Módulo Organizational** - Estructura organizacional
- [ ] **Módulo Reporting** - Reportes y estadísticas
- [ ] **Módulo Admin** - Administración del sistema

### 📊 Métricas del Módulo User
- **90 archivos** implementados
- **13,437 líneas** de código
- **Arquitectura DDD** completa
- **API REST** documentada
- **Sistema RBAC** funcional

## 🛠️ Stack Tecnológico

### Backend Core
- **PHP 8.0+** - Lenguaje principal con tipado estricto
- **Slim Framework 4** - Micro-framework para HTTP
- **Doctrine ORM 3.5** - Object-Relational Mapping
- **PHP-DI 7.0** - Inyección de dependencias
- **FastRoute** - Routing optimizado

### Base de Datos
- **Oracle Database** - Motor de base de datos empresarial
- **Doctrine Migrations** - Control de versiones de BD
- **Schema optimizado** con convenciones UP

### Seguridad
- **Aura.Session** - Gestión de sesiones seguras
- **Argon2ID** - Hash de contraseñas robusto
- **RBAC System** - Control de acceso basado en roles
- **CSRF Protection** - Protección contra ataques CSRF
- **Input Validation** - Validación exhaustiva de datos

### Calidad y Testing
- **PHPStan** - Análisis estático de código
- **PHP_CodeSniffer** - Estándares de codificación PSR-12
- **PHPUnit** - Testing unitario y de integración
- **Monolog** - Logging estructurado

### Herramientas de Desarrollo
- **Composer** - Gestión de dependencias
- **Docker** - Contenedorización
- **Git** - Control de versiones con Conventional Commits

## 🚦 Instalación y Configuración

### Prerrequisitos
- PHP 8.0 o superior
- Oracle Database 19c+
- Composer 2.0+
- Git

### 1. Clonar el repositorio
```bash
git clone https://github.com/coffeKoder/ff24fd0.git viex-platform
cd viex-platform
```

### 2. Instalar dependencias
```bash
composer install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
```

Editar `.env` con tus configuraciones:
```env
# Base de datos Oracle
DB_HOST=localhost
DB_PORT=1521
DB_NAME=ORCL
DB_USER=viex_user
DB_PASS=secure_password

# Aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Sesiones
SESSION_NAME=viex_session
SESSION_LIFETIME=3600

# Logging
LOG_LEVEL=debug
LOG_PATH=/storage/logs
```

### 4. Configurar base de datos
```bash
# Ejecutar migraciones
vendor/bin/doctrine-migrations migrate

# Cargar datos iniciales (opcional)
composer run seed
```

### 5. Configurar servidor web
```bash
# Desarrollo con servidor integrado PHP
composer start

# O usar Docker
docker-compose up -d
```

Acceder a: `http://localhost:8080`

## 🧪 Testing y Calidad

### Ejecutar análisis de código
```bash
# Análisis estático con PHPStan
composer run phpstan

# Verificar estándares de código
composer run phpcs

# Corregir código automáticamente
composer run phpcbf
```

### Ejecutar tests
```bash
# Tests unitarios
composer run test

# Tests con cobertura
composer run test:coverage

# Tests específicos del módulo User
composer run test -- tests/Modules/User/
```

## 📚 Documentación

### Estructura de la Documentación
- [`DEVELOPMENT_STATUS.md`](./DEVELOPMENT_STATUS.md) - Estado actual del desarrollo
- [`docs/api/`](./docs/api/) - Documentación de API REST
- [`docs/architecture/`](./docs/architecture/) - Diagramas y documentación técnica
- [`docs/user/`](./docs/user/) - Manuales de usuario por rol

### API REST - Módulo User
```http
# Autenticación
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/register
GET    /api/auth/me

# Gestión de perfiles
GET    /api/profile
PUT    /api/profile
PUT    /api/profile/password

# Administración de usuarios (Admin)
GET    /api/users
GET    /api/users/{id}
POST   /api/users
PUT    /api/users/{id}
DELETE /api/users/{id}
```

## 👥 Roles y Permisos

### Roles del Sistema
- **👨‍🏫 Profesor/Proponente** - Registra trabajos de extensión
- **👨‍💼 Coordinador Extensión** - Revisa propuestas de su unidad
- **🏛️ Decano/Director** - Aprueba y tramita propuestas
- **⚡ Admin VIEX** - Gestión global y certificación
- **🔍 Consulta** - Visualización de información pública

### Sistema RBAC Implementado
- Autenticación robusta con sesiones seguras
- Autorización granular por recurso y acción
- Middleware de control de acceso
- Logging de eventos de seguridad

## 🤝 Contribución

### Estándares de Desarrollo
- **Conventional Commits** para mensajes de commit
- **PSR-12** para estilo de código PHP
- **Principios SOLID** en diseño de clases
- **DDD** para estructura de módulos
- **Clean Code** principles

### Flujo de Trabajo
1. Fork del repositorio
2. Crear rama feature: `git checkout -b feat/nueva-funcionalidad`
3. Commits con formato: `feat(modulo): descripción del cambio`
4. Push y crear Pull Request
5. Review y merge tras aprobación

### Conventional Commits
```bash
feat(user): implementar autenticación JWT
fix(auth): corregir validación de tokens expirados
docs(api): actualizar documentación de endpoints
test(user): añadir tests para casos de uso
refactor(domain): mejorar estructura de value objects
```

## 📊 Roadmap

### v1.0.0 - Módulo User ✅
- [x] Sistema de autenticación y autorización
- [x] Gestión de usuarios y perfiles
- [x] API REST completa
- [x] Sistema RBAC funcional

### v1.1.0 - Módulo Extension 🚧
- [ ] Registro de proyectos de extensión
- [ ] Formularios dinámicos por tipo de trabajo
- [ ] Flujo de aprobación automatizado
- [ ] Gestión de evidencias y documentos

### v1.2.0 - Módulo Certification 📋
- [ ] Sistema de certificación digital
- [ ] Generación de certificados PDF
- [ ] Envío automático por email
- [ ] Repositorio de certificaciones

### v2.0.0 - Sistema Completo 🎯
- [ ] Todos los módulos integrados
- [ ] Dashboard avanzado con métricas
- [ ] Reportes executivos automatizados
- [ ] Integración con sistemas UP existentes

## 📞 Soporte y Contacto

### Desarrollador Principal
**Fernando Castillo** - Desarrollador Senior PHP  
- 🏗️ **Especialización:** DDD, Clean Architecture, PHP Enterprise
- 🎯 **Enfoque:** Código mantenible, escalable y siguiendo mejores prácticas
- 📧 **Email:** fernando.castillo@up.ac.pa

### Vicerrectoría de Extensión - Universidad de Panamá
- 🏛️ **Institución:** Universidad de Panamá
- 📧 **Email:** extension@up.ac.pa
- 🌐 **Web:** https://www.up.ac.pa

## 📜 Licencia

Este proyecto está licenciado bajo los términos de la Universidad de Panamá.
Uso restringido para fines académicos e institucionales.

---

**Versión:** v1.0.0-user-module  
**Última actualización:** $(date +%Y-%m-%d)  
**Estado:** ✅ Módulo User completamente funcional
