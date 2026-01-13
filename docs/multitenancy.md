# Multitenancy / Teams (Context Awareness)

El paquete soporta autorización contextual (Teams) sin imponer una estructura de base de datos específica. Esto permite reutilizar roles y permisos en diferentes sucursales o equipos.

## 1. Habilitar la Feature
En `config/auth-core.php`:
 ```php
 'features' => [
     'teams' => true, // Activa el modo Tenant-Aware
 ],
 ```

## 2. Integración con el Modelo de Usuario (Requerido)
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

## 3. Endpoints de Gestión
El paquete expone endpoints para listar y cambiar de contexto.

- **Listar Equipos:** `GET /api/v1/teams`
  - *Nota:* Requiere que tu modelo `User` tenga una relación o método `teams` que retorne los equipos.
  
- **Cambiar Equipo:** `POST /api/v1/teams/{id}/switch`
  - Retorna un nuevo token válido solo para ese equipo.
  - El token incluirá el claim `current_team_id`.

## 4. Uso en Rutas
Aplica el middleware `auth.context` en las rutas que requieren aislamiento.

 ```php
 Route::middleware(['auth:sanctum', 'auth.context'])->group(function () {
     // El paquete filtrará automáticamente los permisos según el Team ID recibido en el header
     Route::get('/sales', ...);
 });
 ```

## 5. Consumo (Frontend)
Envía el ID del equipo en los headers de cada petición:
`X-Team-ID: <team_id>`
