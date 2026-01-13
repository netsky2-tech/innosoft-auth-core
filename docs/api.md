# API Reference

## Gestión de Usuarios

El módulo de usuarios expone endpoints RESTful optimizados.

### Listar Usuarios
```http
GET /api/v1/users?page=1&per_page=10&search=juan
```

### Crear Usuario
```http request
POST /api/v1/users
Content-Type: application/json

{
    "name": "Juan Perez",
    "email": "juan@example.com",
    "password": "SecurePassword123!"
}
```

### Eliminar Usuario (Soft Delete)
```http
DELETE /api/v1/users/{id}
```
*Nota: La eliminación es lógica (Soft Delete). El usuario se marca como inactivo pero los datos persisten.*

### Asignar Rol a Usuario
```http
POST /api/v1/users/{id}/roles
Content-Type: application/json

{
    "role": "Manager"
}
```

### Revocar Rol de Usuario
```http
DELETE /api/v1/users/{id}/roles
Content-Type: application/json

{
    "role": "Manager"
}
```

## Autenticación

### Login
`POST /api/v1/auth/login`

### Registro
`POST /api/v1/auth/register`

### Recuperación de Contraseña
- **Request:** `POST /api/v1/auth/forgot-password` (Payload: `{ "email": "..." }`)
- **Reset:** `POST /api/v1/auth/reset-password` (Payload: `{ "email": "...", "token": "...", "password": "...", "password_confirmation": "..." }`)

### Two-Factor Authentication (2FA)

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
