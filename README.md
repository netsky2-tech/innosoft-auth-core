# InnoSoft Auth Core Package

Módulo central de autenticación, autorización (RBAC) y seguridad para el ecosistema InnoSoft (POS, Contabilidad, Agenda).

Diseñado bajo arquitectura **Hexagonal (Ports & Adapters)** y **CQRS**, listo para escalar en múltiples microservicios o proyectos modulares.

## Requisitos
- PHP 8.2+
- Laravel 10/11
- Base de datos compatible con Eloquent

## Instalación

```bash
composer require innosoft/auth-core
```

## Setup Inicial

### 1. Publicar recursos
Publica la configuración (crítica para definir roles) y las migraciones.

```bash
php artisan vendor:publish --tag=innosoft-auth-config
php artisan vendor:publish --tag=innosoft-auth-migrations
```

### 2. Configurar Roles y Permisos
Edita el archivo `config/innosoft-auth.php`. Aquí defines la matriz de seguridad de tu aplicación.

```php
// config/innosoft-auth.php
return [
    'super_admin_role' => 'SuperAdmin', // Bypass total de seguridad
    
    'roles_structure' => [
        'Manager' => ['users.create', 'reports.view'],
        'Seller'  => ['pos.sales', 'pos.refunds'],
    ],
];
```

### 3. Ejecutar Migraciones y Seeder
En tu `DatabaseSeeder.php` principal, llama al seeder del paquete para sincronizar la configuración con la DB.

```php
// DatabaseSeeder.php
public function run(): void
{
    $this->call(\InnoSoft\AuthCore\Infrastructure\Seeders\AuthCoreSeeder::class);
}
```

```bash
php artisan migrate --seed
```

---

## v0.3.0: Sistema RBAC (Roles & Permissions)

El paquete implementa un sistema robusto de control de acceso.

### Protección de Rutas (Middleware)
El paquete registra automáticamente los alias `role`, `permission` y `role_or_permission`.

**Uso recomendado (Permisos granulares):**
```php
Route::middleware(['auth:sanctum', 'permission:accounting.create_invoice'])->group(function () {
    Route::post('/invoices', [InvoiceController::class, 'store']);
});
```

**Uso por Rol (Menos flexible):**
```php
Route::middleware(['role:Manager|SuperAdmin'])->get('/stats', ...);
```

### Uso Programático (CQRS / Hexagonal)
Si necesitas gestionar roles desde tu código (ej. un panel de admin), utiliza los Handlers expuestos para mantener la integridad arquitectónica.

```php
use InnoSoft\AuthCore\Application\Roles\CreateRole\CreateRoleCommand;
use InnoSoft\AuthCore\Application\Roles\CreateRole\CreateRoleHandler;

public function store(Request $request, CreateRoleHandler $handler)
{
    $command = new CreateRoleCommand(
        name: $request->name,
        permissions: $request->permissions // ['users.view', ...]
    );
    
    $handler->handle($command);
    
    return response()->json(['message' => 'Rol creado correctamente']);
}
```

### Consultas Optimizadas (Read Model)
Para listar roles en el frontend sin sobrecarga:

```php
use InnoSoft\AuthCore\Application\Roles\Queries\GetRoles\GetRolesQuery;
use InnoSoft\AuthCore\Application\Roles\Queries\GetRoles\GetRolesHandler;

public function index(Request $request, GetRolesHandler $handler)
{
    // Retorna DTOs optimizados (RoleReadModel) con paginación
    return $handler->handle(new GetRolesQuery(...));
}
```

---

## Features v0.2.0: Seguridad Avanzada

### Gestión de usuarios (API)
Endpoints base listos para usar:
- `POST /api/auth/login`
- `POST /api/auth/register`

### Recuperación de Contraseña
Flujo completo de reset de contraseña seguro.
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
Debes usar ese token para verificar:
`POST /api/auth/two-factor/verify` (Payload: `{ "challenge_token": "...", "code": "..." }`) -> Retorna el `access_token` final.

Deshabilitar: `DELETE /api/auth/two-factor` (Payload: `{ "current_password": "..." }`)