# InnoSoft Auth Core Package

Módulo central de autenticación, autorización (RBAC) y seguridad para el ecosistema InnoSoft (POS, Contabilidad, Agenda).

Diseñado bajo arquitectura **Hexagonal (Ports & Adapters)**, **DDD** y **CQRS**, listo para escalar en múltiples microservicios o proyectos modulares.

## Requisitos
- PHP 8.2+
- Laravel 10/11
- Base de datos compatible con Eloquent

## Instalación

```bash
composer require innosoft/auth-core
```

## Setup Inicial

### 1. Instalación Automática (Recomendado)
Ejecuta el comando unificado que publicará la configuración, migraciones y ejecutará los seeders necesarios.

```bash
php artisan innosoft:install
```

### 2. Configuración Manual (Opcional)
Si prefieres hacerlo paso a paso o necesitas personalizar antes de migrar:

**A. Publicar Configuración:**
```bash
php artisan vendor:publish --tag=innosoft-auth-config
```

**B. Editar `config/auth-core.php`:**
Aquí defines la matriz de seguridad, features y límites.

```php
// config/auth-core.php
return [
    'prefix' => 'api', // Prefijo base para las rutas (ej. api/v1/auth/login)
    
    'features' => [
        '2fa' => true,
        'teams' => false,
        'registration' => false, // Habilitar registro público
    ],

    'rate_limits' => [
        'login' => 5, // Intentos por minuto
        'api' => 60,
    ],
    
    'super_admin_role' => 'SuperAdmin',
    
    'roles_structure' => [
        'Manager' => ['users.create', 'reports.view'],
        'Seller'  => ['pos.sales', 'pos.refunds'],
    ],

    // Configuración de idioma (opcional)
    'locale' => 'es', // Forzar idioma español para el paquete
];
```

**C. Publicar y Ejecutar Migraciones:**
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

**D. Ejecutar Seeder:**
```bash
php artisan db:seed --class="InnoSoft\AuthCore\Database\Seeders\AuthCoreSeeder"
```

## Internacionalización (i18n)

El paquete soporta múltiples idiomas para los mensajes de error y validación.

### Configuración
Por defecto, el paquete utiliza el idioma configurado en tu aplicación (`config('app.locale')`).
Si deseas forzar un idioma específico para el paquete, puedes configurarlo en `config/auth-core.php`:

```php
'locale' => 'es',
```

### Personalización de Mensajes
Puedes publicar los archivos de traducción para modificarlos según tus necesidades:

```bash
php artisan vendor:publish --tag=auth-core-translations
```
Esto copiará los archivos a `resources/lang/vendor/auth-core`.

## Arquitectura orientada a Eventos (EDA)
El paquete emite Eventos de Dominio que tu aplicación principal puede escuchar para reaccionar a cambios sin acoplarse al código de autenticación.

Eventos Disponibles:

- InnoSoft\AuthCore\Domain\Users\Events\UserRegistered

- InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged

- InnoSoft\AuthCore\Domain\Users\Events\UserPasswordChanged

- InnoSoft\AuthCore\Domain\Users\Events\UserDeleted

Ejemplo de Integración (En tu App):
``` php
// app/Providers/EventServiceProvider.php
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

protected $listen = [
    UserRegistered::class => [
        \App\Listeners\SetupUserTenant::class, // Tu lógica personalizada
        \App\Listeners\SendWelcomeCoupon::class,
    ],
];
```

## Seguridad y Auditoria

El paquete incluye un sistema de Logging de Auditoría automático.

- Seguridad: Registra cambios de password, email, logins fallidos y exitosos.
- Alertas: Envía correos de seguridad automáticamente cuando se cambia información sensible (ej. cambio de email).

### Visualización de Logs
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

### Limpieza de Datos (Pruning)
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

## API & Consumo (CQRS)

### Gestión de Usuarios (Ejemplos)

El módulo de usuarios expone endpoints RESTful optimizados.

Listar Usuarios (Paginado y Filtrado):
```http
GET /api/v1/users?page=1&per_page=10&search=juan
```

Crear Usuario:
```http request
POST /api/v1/users
Content-Type: application/json

{
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "SecurePassword123!"
}
```

Eliminar Usuario (Soft Delete):
```http
DELETE /api/v1/users/{id}
```
*Nota: La eliminación es lógica (Soft Delete). El usuario se marca como inactivo pero los datos persisten.*

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
    // Retorna DTO optimizados (RoleReadModel) con paginación
    return $handler->handle(new GetRolesQuery(...));
}
```

---

## Multitenancy / Teams (Context Awareness)

El paquete soporta autorización contextual (Teams) sin imponer una estructura de base de datos específica. Esto permite reutilizar roles y permisos en diferentes sucursales o equipos.

### 1. Habilitar la Feature
En `config/auth-core.php`:
 ```php
 'features' => [
     'teams' => true, // Activa el modo Tenant-Aware
 ],
 ```

### 2. Integración con el Modelo de Usuario (Requerido)
Dado que la persistencia de los equipos es externa al paquete, **debes implementar la lógica de pertenencia** en tu modelo `User`.

El paquete intentará validar automáticamente usando uno de los siguientes métodos en tu modelo `User`:

**Opción A: Método `belongsToTeam($teamId)` (Recomendado)**
```php
// App/Models/User.php
public function belongsToTeam(string $teamId): bool
{
    return $this->teams()->where('id', $teamId)->exists();
}
```

**Opción B: Relación `teams`**
Si tienes una relación Eloquent llamada `teams`, el paquete verificará si el ID existe en la colección.

> **Nota:** Si no implementas ninguno, el cambio de equipo fallará por seguridad.

### 3. Endpoints de Gestión
El paquete expone endpoints para listar y cambiar de contexto.

- **Listar Equipos:** `GET /api/v1/teams`
  - *Nota:* Requiere que tu modelo `User` tenga una relación o método `teams` que retorne los equipos.
  
- **Cambiar Equipo:** `POST /api/v1/teams/{id}/switch`
  - Retorna un nuevo token válido solo para ese equipo.
  - El token incluirá el claim `current_team_id`.

### 4. Uso en Rutas
Aplica el middleware `auth.context` en las rutas que requieren aislamiento.

 ```php
 Route::middleware(['auth:sanctum', 'auth.context'])->group(function () {
     // El paquete filtrará automáticamente los permisos según el Team ID recibido en el header
     Route::get('/sales', ...);
 });
 ```

### 5. Consumo (Frontend)
Envía el ID del equipo en los headers de cada petición:
`X-Team-ID: <team_id>`
 

---

## Features v0.2.0: Seguridad Avanzada

### Gestión de usuarios (API)
Endpoints base listo para usar:
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/register`

### Recuperación de Contraseña
Flujo completo de reset de contraseña seguro.
- **Request:** `POST /api/v1/auth/forgot-password` (Payload: `{ "email": "..." }`)
- **Reset:** `POST /api/v1/auth/reset-password` (Payload: `{ "email": "...", "token": "...", "password": "...", "password_confirmation": "..." }`)

### Two-Factor Authentication (2FA)
Implementación basada en TOTP (Google Authenticator).

**Flujo de Setup:**
1. **Iniciar:** `POST /api/v1/auth/two-factor/enable` -> Retorna `secret` y `qr_code_url`.
2. **Confirmar:** `POST /api/v1/auth/two-factor/confirm` (Payload: `{ "code": "123456" }`) -> Retorna `recovery_codes`.

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
`POST /api/v1/auth/two-factor/verify` (Payload: `{ "challenge_token": "...", "code": "..." }`) -> Retorna el `access_token` final.

Deshabilitar: `DELETE /api/v1/auth/two-factor` (Payload: `{ "current_password": "..." }`) -> Retorna el `access_token` final.

### Respuesta de Login
La respuesta de login exitoso (tanto normal como 2FA) incluye el token y la información del usuario aplanada para facilitar su uso en el Frontend.

```json
{
    "success": true,
    "message": "Logged in successfully",
    "access_token": "eyJ0eXAi...",
    "token_type": "Bearer",
    "user": {
        "id": "uuid-...",
        "name": "John Doe",
        "email": "john@example.com",
        "roles": ["admin", "editor"],
        "permissions": ["create_posts", "publish_posts"]
    }
}
```
