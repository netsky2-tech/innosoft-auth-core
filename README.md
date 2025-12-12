# InnoSoft Auth Core Package

Módulo central de autenticación y autorización para el ecosistema InnoSoft (POS, Contabilidad, Agenda).

## Instalación

```bash
composer require innosoft/auth-core
```

## Setup Inicial
Publicar configuración y migraciones:
```bash
php artisan vendor:publish --tag=innosoft-auth-config
php artisan vendor:publish --tag=innosoft-auth-migrations
```

Ejecutar migraciones

```bash
php artisan migrate
```

## Uso
### Proteger rutas con permisos
El paquete registra automáticamente el middleware permission.

```php
Route::middleware(['auth:sanctum', 'permission:accounting.create_invoice'])->group(function () {
    Route::post('/invoices', ...);
});
```

### Gestión de usuarios (API)
El paquete expone endpoints listos para usar

- POST /api/auth/login

- POST /api/auth/register

