# Changelog

## v1.0.0 - 2025-01-12
### Added
- **Multitenancy Support:** Implementación completa de autorización contextual por equipos (Teams).
  - Middleware `auth.context` para aislamiento lógico.
  - Endpoints para cambio de contexto (`/teams/{id}/switch`).
  - Validación de pertenencia extensible (`TeamMembershipValidator`).
- **Audit System:** Sistema de auditoría robusto.
  - Logging automático de eventos de seguridad (Login, Password Change, 2FA).
  - Logging de cambios en RBAC (Roles/Permisos).
  - Comando `auth:prune` para limpieza de logs y tokens expirados.
- **Internationalization (i18n):** Soporte completo para traducción de mensajes de error y validación.
- **Security Enhancements:**
  - Rate Limiting configurable por IP/Usuario.
  - Validación de contraseñas fuertes en producción (Uncompromised, Symbols, Mixed Case).
  - Gate global de `SuperAdmin`.
- **Architecture:**
  - Bus de comandos dinámico con autodiscovery.
  - Eventos de dominio para todas las acciones críticas (EDA).

### Changed
- Refactorización masiva a Arquitectura Hexagonal estricta.
- Estandarización de respuestas API.
- Mejora en la inyección de dependencias del ServiceProvider.

## v0.3.0
- Sistema RBAC (Roles & Permissions)
- Protección de Rutas (Middleware)
- Gestión de Roles y Permisos (API)
- Uso Programático (CQRS / Hexagonal)
- Consultas Optimizadas (Read Model)

## v0.2.0
- Seguridad Avanzada
- Gestión de usuarios (API)
- Recuperación de Contraseña
- Two-Factor Authentication (2FA)
- Respuesta de Login
