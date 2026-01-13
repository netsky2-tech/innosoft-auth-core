# Seguridad y Auditoria

El paquete incluye un sistema de Logging de Auditoría automático.

- Seguridad: Registra cambios de password, email, logins fallidos y exitosos.
- Gestión de Roles y Permisos: Registra creación, actualización y eliminación de roles y permisos, así como asignaciones a usuarios.
- Alertas: Envía correos de seguridad automáticamente cuando se cambia información sensible (ej. cambio de email).

## Visualización de Logs
El paquete expone endpoints para consultar los logs de auditoría, permitiendo a los administradores rastrear acciones críticas.

**Listar Logs (Filtros disponibles):**
```http
GET /api/v1/audit/logs?page=1&per_page=15&event=login&user_id=uuid
```
Permiso requerido: `audit.view`

**Logs por Usuario:**
```http
GET /api/v1/audit/users/{id}/logs
```

## Limpieza de Datos (Pruning)
Para mantener la base de datos optimizada y cumplir con políticas de retención, el paquete incluye un comando para eliminar tokens expirados y logs antiguos.

**Uso Manual:**
```bash
# Limpieza general (30 días por defecto)
php artisan auth:prune

# Personalizar retención (ej. 60 días)
php artisan auth:prune --days=60

# Solo limpiar tokens o logs
php artisan auth:prune --tokens
php artisan auth:prune --logs
```

**Programación Automática:**
Agrega el comando al `Console/Kernel.php` de tu aplicación:
```php
$schedule->command('auth:prune')->daily();
```
