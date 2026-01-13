# RBAC (Roles & Permissions)

El paquete implementa un sistema robusto de control de acceso.

## Protección de Rutas (Middleware)
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

## Gestión de Roles y Permisos (API)

El paquete expone endpoints completos para la gestión dinámica de roles y permisos.

**Roles:**
- Listar: `GET /api/v1/roles`
- Crear: `POST /api/v1/roles`
- Actualizar: `PUT /api/v1/roles/{id}`
- Eliminar: `DELETE /api/v1/roles/{id}`
- Sincronizar Permisos: `POST /api/v1/roles/{name}/permissions/sync`
- Asignar Permiso: `POST /api/v1/roles/{name}/permissions`
- Revocar Permiso: `DELETE /api/v1/roles/{name}/permissions`

**Permisos:**
- Listar: `GET /api/v1/permissions`
- Crear: `POST /api/v1/permissions`
- Actualizar: `PUT /api/v1/permissions/{id}`
- Eliminar: `DELETE /api/v1/permissions/{id}`

## Uso Programático (CQRS / Hexagonal)
Si necesitas gestionar roles desde tu código (ej. un panel de admin), utiliza los Handlers expuestos para mantener la integridad arquitectónica.

```php
use InnoSoft\AuthCore\Application\Roles\Commands\CreateRoleCommand;
use Illuminate\Support\Facades\Bus;

public function store(Request $request)
{
    $command = new CreateRoleCommand(
        name: $request->name,
        permissions: $request->permissions // ['users.view', ...]
    );
    
    Bus::dispatch($command);
    
    return response()->json(['message' => 'Rol creado correctamente']);
}
```

## Consultas Optimizadas (Read Model)
Para listar roles en el frontend sin sobrecarga:

```php
use InnoSoft\AuthCore\Application\Roles\Queries\GetRolesQuery;
use InnoSoft\AuthCore\Application\Roles\Queries\Handlers\GetRolesHandler;

public function index(Request $request, GetRolesHandler $handler)
{
    // Retorna DTO optimizados (RoleReadModel) con paginación
    return $handler->handle(new GetRolesQuery(...));
}
```
