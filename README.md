# InnoSoft Auth Core Package

Módulo central de autenticación, autorización (RBAC) y seguridad para el ecosistema InnoSoft (POS, Contabilidad, Agenda).

## Features Principales
- **Autenticación Completa:** Login, Registro, Recuperación de Contraseña, 2FA (TOTP).
- **RBAC Avanzado:** Roles y Permisos granulares, Middleware de protección.
- **Arquitectura Moderna:** Hexagonal, DDD, CQRS, Event-Driven.
- **Seguridad y Auditoría:** Logs detallados, alertas de seguridad, pruning configurable.
- **Multitenancy:** Soporte para equipos y contextos aislados.
- **API Ready:** Endpoints RESTful optimizados y documentados.

## Requisitos
- PHP 8.2+
- Laravel 10/11
- Base de datos compatible con Eloquent

## Instalación

```bash
composer require innosoft/auth-core
```

## Configuración Básica

### 1. Instalación Automática (Recomendado)
Ejecuta el comando unificado que publicará la configuración, migraciones y ejecutará los seeders necesarios.

```bash
php artisan innosoft:install
```

### 2. Configuración Manual (Opcional)
Si prefieres hacerlo paso a paso o necesitas personalizar antes de migrar, consulta la documentación detallada.

## Uso Básico

### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

### Protección de Rutas
```php
Route::middleware(['auth:sanctum', 'permission:accounting.create_invoice'])->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'store']);
});
```

## Security & Audit

- Registro automático de eventos críticos
- Logs consultables vía API
- Pruning configurable

➡ Ver detalles en [docs/audit.md](docs/audit.md)

## Multi-Tenancy Support

- Context-aware authorization
- Tokens por equipo
- Middleware `auth.context`

➡ Ver implementación completa en [docs/multitenancy.md](docs/multitenancy.md)

## Architecture

Diseñado bajo:
- Hexagonal Architecture
- Domain-Driven Design (DDD)
- CQRS
- Event-Driven Architecture (EDA)

➡ Detalles técnicos en [docs/architecture.md](docs/architecture.md)

## Documentation

- Architecture → [docs/architecture.md](docs/architecture.md)
- API Reference → [docs/api.md](docs/api.md)
- RBAC → [docs/rbac.md](docs/rbac.md)
- Multitenancy → [docs/multitenancy.md](docs/multitenancy.md)

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

## License

MIT
