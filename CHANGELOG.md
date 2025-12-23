# Changelog

Todas las cambios notables en este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-22

### Added
- **Arquitectura DDD & CQRS:** Implementación completa para el módulo de Usuarios.
- **Event Bus:** Sistema de eventos de dominio (`UserRegistered`, `UserEmailChanged`, `UserDeleted`) desacoplado de Eloquent.
- **Audit Logging:** Sistema de auditoría (`LogSecurityEvents`) que registra cambios críticos de seguridad y accesos.
- **Async Notifications:** Envío de correos en segundo plano (Colas) para verificación de email y alertas de seguridad.
- **DTOs:** Implementación de `UserView` para respuestas de API optimizadas y seguras.
- **User Repository:** Nuevo método `search()` para búsquedas paginadas eficientes sin hidratación de entidades pesadas.
- **Api Handling:** Nuevo trait `HandlesApiExecution` para estandarizar respuestas HTTP (404, 409, 500).

### Changed
- **Refactor:** Los Handlers de Usuarios ahora son transaccionales y siguen el principio de responsabilidad única.
- **Refactor:** `UserController` simplificado delegando la orquestación a los Handlers y el manejo de errores al Trait base.
- **Database:** Optimización de consultas de lectura separando modelos de escritura (Domain Entities) de modelos de lectura.

### Security
- **Alertas:** Se envían correos automáticos al email antiguo cuando se detecta un cambio de dirección de correo.
- **Logs:** Se registra IP y User Agent en cada evento de seguridad crítico.

---

## [0.3.0] - 2025-12-20
### Added
- Sistema RBAC (Roles y Permisos).
- Middleware de protección de rutas.

## [0.2.0] - 2025-12-15
### Added
- Autenticación base (Login/Register).
- Soporte 2FA con Google Authenticator.