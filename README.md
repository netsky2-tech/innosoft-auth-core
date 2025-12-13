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

## Features v0.2.0: Seguridad Avanzada

### Recuperación de Contraseña
El paquete maneja el flujo completo de reset de contraseña seguro.
- **Request:** `POST /api/auth/forgot-password` (Payload: `{ "email": "..." }`)
- **Reset:** `POST /api/auth/reset-password` (Payload: `{ "email": "...", "token": "...", "password": "...", "password_confirmation": "..." }`)

### Two-Factor Authentication (2FA)
Implementación basada en TOTP (Google Authenticator).

**Flujo de Setup:**
1. **Iniciar:** `POST /api/auth/two-factor/enable` -> Retorna `secret` y `qr_code_url`.
2. **Confirmar:** `POST /api/auth/two-factor/confirm` (Payload: `{ "code": "123456" }`) -> Retorna `recovery_codes`.

**Flujo de Login con 2FA:**
Si el usuario tiene 2FA activo, el login normal retornará:
```json
{
    "message": "Two-factor authentication required",
    "requires_two_factor": true,
    "challenge_token": "temp_token_xyz"
}
```
Debes usar ese token para verificar: `POST /api/auth/two-factor/verify (Payload: { "challenge_token": "...", "code": "..." })` -> Retorna el access_token final.

Deshabilitar: `DELETE /api/auth/two-factor (Payload: { "current_password": "..." })`

