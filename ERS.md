## **Proyecto: Innosoft Auth Core**

ID del Proyecto: innosoft/auth-core

Versión del Documento: 1.0

Fecha: 12 de Diciembre, 2025

Framework: Laravel 12.x

Arquitectura: API RESTful / Stateless / Package

---

### **1\. Introducción y Propósito**

El propósito de innosoft/auth-core es proveer un paquete de backend reutilizable para el ecosistema de aplicaciones de Innosoft (POS, Contabilidad, ERPs). Este paquete abstrae la complejidad de la autenticación, autorización y gestión de usuarios, permitiendo que los equipos de desarrollo se enfoquen en la lógica de negocio específica de cada producto.

El sistema funcionará bajo una arquitectura **Headless (API First)**, comunicándose exclusivamente mediante **JSON** y autenticación basada en **Tokens (Bearer)**, eliminando la dependencia de cookies para sesiones, facilitando así el consumo desde aplicaciones móviles, SPAs (React/Vue) o sistemas de terceros.

---

### **2\. Tecnologías y Dependencias**

Para garantizar estabilidad y mantenibilidad, el núcleo se basará en librerías probadas por la comunidad, encapsuladas en nuestra lógica de negocio.

* **Lenguaje:** PHP 8.4+ (Tipado estricto activado).
* **Core Framework:** Laravel 12.x.
* **Motor de Autenticación (Tokens):** laravel/sanctum (Configurado para uso estricto de API Tokens, sin Stateful guard).
* **Motor de ACL (RBAC):** spatie/laravel-permission (Versión compatible con L12).
* **Respuesta API:** Formato estandarizado (JSend o similar).

---

### **3\. Alcance del Proyecto y Roadmap de Versiones**

El desarrollo se dividirá en fases para garantizar un Time-to-Market rápido con el MVP, seguido de características de robustez empresarial.

#### **Fase 1: MVP (Versión 1.0.0) \- *Seguridad Esencial***

* Instalación y configuración automática (php artisan innosoft:install).
* Autenticación básica (Login con credenciales).
* Generación y revocación de Tokens de Acceso Personal (PAT).
* Gestión CRUD de Usuarios (Crear, Editar, Desactivar).
* Sistema RBAC: Roles y Permisos (Crear roles, asignar permisos, asignar roles a usuarios).
* Middleware de protección de rutas basado en permisos.
* Endpoints para "Mi Perfil" y cambio de contraseña.

#### **Fase 2: Enterprise Ready (Versión 2.0.0+) \- *Auditoría y Seguridad Avanzada***

* Autenticación de Dos Factores (2FA) vía OTP/TOTP (Google Authenticator).
* Registro de Auditoría (Activity Logs) para cada acción de seguridad.
* Rate Limiting avanzado por Rol (ej. Cajeros vs Admins).
* Gestión de "Teams" o "Tenant" (Multitenencia básica).
* Despacho de Eventos de Dominio (UserLoggedIn, RoleAssigned) para que la app principal reaccione.

---

### **4\. Requisitos Funcionales (MVP \- v1.0)**

#### **4.1. Módulo de Autenticación**

**RF-01: Login (Issue Token)**

* El sistema debe recibir email y password.
* Si es válido, retornar un access\_token (Sanctum) en texto plano.
* Debe retornar el objeto user con sus roles y permisos cargados para caché en frontend.
* **Restricción:** No debe setear cookies.

**RF-02: Logout (Revoke Token)**

* Debe invalidar el token actual utilizado en la petición (currentAccessToken()-\>delete()).

**RF-03: Validación de Credenciales**

* Uso de Hash seguro (Bcrypt o Argon2id por defecto en Laravel 12).

#### **4.2. Módulo de Gestión de Usuarios**

**RF-04: CRUD de Usuarios**

* API para crear usuarios (Administradores creando cajeros/contadores).
* Validación de unicidad de email.
* Soft Deletes: Los usuarios no se borran físicamente, se marcan como inactivos (deleted\_at).

**RF-05: Perfil de Usuario**

* Endpoint /api/me para obtener datos del usuario autenticado.

#### **4.3. Módulo de Autorización (RBAC)**

**RF-06: Gestión de Roles**

* CRUD de Roles (ej. "Admin", "Cajero", "Auditor").
* Los roles se guardan en base de datos (roles table).

**RF-07: Gestión de Permisos**

* CRUD de Permisos (ej. pos.create\_sale, accounting.view\_reports).
* Endpoint para sincronizar permisos (Sync) a un Rol.

**RF-08: Asignación**

* Endpoint para asignar/revocar roles a un usuario.
* Endpoint para verificar permisos directos (can()).

**RF-09: Middleware de Protección**

* El paquete debe proveer un middleware alias (ej. auth.innosoft) que verifique token válido y scopes/permisos requeridos.

#### 4.4 Módulo de Autenticación de Dos Factores (2FA) \- Fase 2

El sistema debe soportar TOTP (Time-based One-Time Password) compatible con Google Authenticator, Authy o Microsoft Authenticator.

**RF-10: Habilitar 2FA**

* Endpoint para generar el "Secreto" y el código QR (en formato base64/SVG).
* El usuario debe confirmar un código válido para activar el 2FA definitivamente.
* Generación de **Códigos de Recuperación** (Recovery Codes) para casos de pérdida del dispositivo.

**RF-11: Flujo de Login con 2FA**

* Si el usuario tiene 2FA activo, el endpoint `/login` **NO** debe devolver el Token de Acceso final.
* Debe devolver un token temporal con scope restringido (`scope:partial_login`) o un identificador de sesión efímero.
* Se requiere un segundo endpoint `/2fa/verify` que reciba el código TOTP y el token temporal para liberar el `access_token` definitivo.

**RF-12: Deshabilitar 2FA**

* Requiere confirmación de contraseña actual para proceder.

#### **4.5. Módulo de Auditoría y Logs (Activity Logs) \- Fase 2**

Necesario para trazabilidad en sistemas contables y POS.

**RF-13: Registro Automático de Eventos de Seguridad**

El sistema debe registrar automáticamente en base de datos (`activity_log` table):

- Intentos de Login fallidos (con IP y User Agent).
- Creación/Edición de usuarios.
- Cambios de permisos o roles.
- Revocación de tokens.

El modelo debe registrar el "Causante" (`causer_id`) y el "Afectado" (`subject_id`).

**RF-14: Consulta de Logs**

* Endpoint para que administradores vean el historial de acciones de un usuario específico.
* Endpoint para ver el historial de cambios de un recurso (ej: "Quién cambió el rol de este usuario").

#### **4.6. Módulo de Eventos del Sistema (Dispatching) \- Fase 2**

Para que el software "Host" (POS, Contabilidad) reaccione sin modificar el paquete.

**RF-15: Disparo de Eventos Nativos de Laravel** El paquete disparará eventos que la aplicación principal puede escuchar:

* `Innosoft\AuthCore\Events\UserLoggedIn`: Para actualizar "última conexión" o estadísticas.
* `Innosoft\AuthCore\Events\UserCreated`: Para crear perfiles asociados (ej. crear un "Cajero" en el POS cuando se crea un "User").
* `Innosoft\AuthCore\Events\RoleAssigned`: Para notificaciones.
* `Innosoft\AuthCore\Events\SecurityAlert`: Cuando se detectan múltiples logins fallidos.

#### **4.7. Módulo de Multitenencia Básica (Teams/Sucursales) \- Fase 2**

Para sistemas que manejan múltiples sucursales o empresas.

**RF-16: Asociación a Entidades (Scope)**

* Los usuarios pueden pertenecer a uno o varios "Teams" (o Sucursales).
* El token de autenticación puede llevar (opcionalmente) un *Claim* indicando el `current_team_id`.
* Middleware para verificar si el usuario tiene acceso al Team solicitado en el Header (`X-Team-ID`).

---

### **5\. Requisitos No Funcionales**

**RNF-01: Seguridad**

* Todas las respuestas de error de autenticación deben ser genéricas ("Credenciales inválidas") para evitar enumeración de usuarios.
* Protección contra ataques de fuerza bruta en el endpoint de login (Rate Limiting: 5 intentos por minuto).

**RNF-02: Escalabilidad**

* La base de datos debe estar indexada correctamente en las tablas pivot de roles y permisos.

**RNF-03: Interoperabilidad**

* Las respuestas deben ser estrictamente JSON (Content-Type: application/json).
* Los códigos de estado HTTP deben ser semánticos (200 OK, 401 Unauthorized, 403 Forbidden, 422 Validation Error).

**RNF-04: Portabilidad**

* El paquete debe publicar sus migraciones, pero permitir que el usuario las modifique si es necesario antes de correr migrate.
* Debe tener un archivo de configuración config/innosoft-auth.php para definir prefijos de tablas o nombres de modelos.

**RNF-05: Rate Limiting Dinámico \- Fase 2**

* El sistema debe permitir configurar límites de peticiones distintos según el rol.
    * Ejemplo: `Role: Admin` \-\> 120 req/min.
    * Ejemplo: `Role: Bot/Integración` \-\> 600 req/min.
* Uso de `Illuminate\Cache\RateLimiting\Limit`.

**RNF-06: Pruning (Limpieza) de Datos \- Fase 2**

* El sistema debe incluir un comando programado (`schedule`) para limpiar:
    * Tokens revocados o expirados.
    * Logs de auditoría antiguos (ej. \> 1 año) para no saturar la BD del cliente.

**RNF-07: Internacionalización (i18n) \- Fase 2**

* Todos los mensajes de error y validación deben usar archivos de traducción de Laravel (`lang/es/auth.php`) para permitir que el software Host cambie el idioma fácilmente.

---

### **6\. Diseño de API (Preliminar)**

A continuación, se define la estructura de las rutas principales que expondrá el paquete. Todas bajo el prefijo configurado (default: /api/auth).

| Verbo | Endpoint | Descripción | Requiere Auth | Permiso Req. |
| :---- | :---- | :---- | :---- | :---- |
| POST | /login | Obtener Token | No | \- |
| POST | /logout | Revocar Token | Si | \- |
| GET | /me | Datos del usuario actual | Si | \- |
| GET | /users | Listar usuarios | Si | users.view |
| POST | /users | Crear usuario | Si | users.create |
| POST | /users/{id}/roles | Asignar roles | Si | users.assign\_roles |
| GET | /roles | Listar Roles | Si | roles.view |
| POST | /roles | Crear Rol | Si | roles.create |
| PUT | /roles/{id}/permissions | Sincronizar permisos | Si | roles.update |

Se agregan los endpoints avanzados de la fase 2\.

| Verbo | Endpoint | Descripción | Requiere Auth | Fase |
| :---- | :---- | :---- | :---- | :---- |
| **Auth 2FA** |  |  |  |  |
| POST | /auth/2fa/setup | Iniciar configuración (Retorna QR/Secret) | Si | 2 |
| POST | /auth/2fa/confirm | Confirmar y activar 2FA | Si | 2 |
| POST | /auth/2fa/verify | Validar OTP (Paso 2 del Login) | Token Parcial | 2 |
| DELETE | /auth/2fa | Desactivar 2FA | Si | 2 |
| GET | /auth/2fa/recovery-codes | Obtener códigos de respaldo | Si | 2 |
| **Auditoría** |  |  |  |  |
| GET | /audit/logs | Listar logs del sistema (Filtros por fecha/usuario) | Si (audit.view) | 2 |
| GET | /users/{id}/logs | Ver actividad de un usuario específico | Si (audit.view) | 2 |
| **Teams/Context** |  |  |  |  |
| GET | /teams | Listar Teams a los que pertenece el usuario | Si | 2 |
| POST | /teams/{id}/switch | Cambiar contexto (Refrescar Token con nuevo Team ID) | Si | 2 |

---

### **7\. Estructura de Respuesta JSON (Estándar)**

Para asegurar que el Frontend desacoplado pueda manejar errores de forma uniforme:

**Éxito (200 OK):**

JSON  
{  
"success": true,  
"data": {  
"token": "1|abcdef123456...",  
"user": {  
"id": 1,  
"name": "Admin",  
"roles": \["SuperAdmin"\]  
}  
},  
"message": "Autenticación exitosa"  
}

**Error (422 Unprocessable Entity \- Validación):**

JSON  
{  
"success": false,  
"error\_code": "VALIDATION\_ERROR",  
"message": "Datos inválidos",  
"errors": {  
"email": \["El email ya ha sido registrado."\]  
}  
}

**Caso: Login Correcto pero requiere 2FA (200 OK o 403 Forbidden controlado)**

JSON  
{  
"success": true, // O false dependiendo de como quiera manejarlo el frontend  
"status": "2fa\_required",  
"message": "Autenticación de dos factores requerida.",  
"data": {  
"temp\_token": "2|partial\_access\_token\_xyz...", // Solo sirve para endpoint /2fa/verify  
"expires\_in": 300 // Segundos  
}  
}

**Caso: Evento de Auditoría (Estructura interna en BD)**

JSON  
// Columna 'properties' en tabla activity\_log  
{  
"ip": "192.168.1.50",  
"browser": "Chrome 120.0",  
"old": { "role": "cajero" },  
"attributes": { "role": "supervisor" }  
}

---

### **8\. Consideraciones de Instalación y Configuración**

Para facilitar la integración en los softwares (POS, ERP), el paquete debe exponer un archivo de configuración robusto `config/innosoft-auth.php`:

PHP  
return \[  
'prefix' \=\> 'api/auth', // Prefijo de rutas  
'users\_table\_name' \=\> 'users', // Flexibilidad en nombre de tabla  
'features' \=\> \[  
'2fa' \=\> true,      // Activar/Desactivar features por proyecto  
'teams' \=\> false,  
'registration' \=\> false, // Si se permite auto-registro  
\],  
'rate\_limits' \=\> \[  
'login' \=\> 5, // Intentos por minuto  
'api' \=\> 60,  
\],  
'super\_admin\_role' \=\> 'SuperAdmin', // Rol que bypassea permisos  
\];

