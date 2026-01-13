# Architecture

El paquete está diseñado bajo los principios de **Hexagonal Architecture (Ports & Adapters)**, **Domain-Driven Design (DDD)** y **CQRS**.

## Arquitectura orientada a Eventos (EDA)
El paquete emite Eventos de Dominio que tu aplicación principal puede escuchar para reaccionar a cambios sin acoplarse al código de autenticación.

### Eventos Disponibles:

- `InnoSoft\AuthCore\Domain\Users\Events\UserRegistered`
- `InnoSoft\AuthCore\Domain\Users\Events\UserEmailChanged`
- `InnoSoft\AuthCore\Domain\Users\Events\UserPasswordChanged`
- `InnoSoft\AuthCore\Domain\Users\Events\UserDeleted`
- `InnoSoft\AuthCore\Domain\Users\Events\RoleAssigned`
- `InnoSoft\AuthCore\Domain\Users\Events\RoleRevoked`
- `InnoSoft\AuthCore\Domain\Roles\Events\RoleRegistered`
- `InnoSoft\AuthCore\Domain\Roles\Events\RoleUpdated`
- `InnoSoft\AuthCore\Domain\Roles\Events\RoleDeleted`
- `InnoSoft\AuthCore\Domain\Permissions\Events\PermissionRegistered`
- `InnoSoft\AuthCore\Domain\Permissions\Events\PermissionUpdated`
- `InnoSoft\AuthCore\Domain\Permissions\Events\PermissionDeleted`

### Ejemplo de Integración (En tu App):
```php
// app/Providers/EventServiceProvider.php
use InnoSoft\AuthCore\Domain\Users\Events\UserRegistered;

protected $listen = [
    UserRegistered::class => [
        \App\Listeners\SetupUserTenant::class, // Tu lógica personalizada
        \App\Listeners\SendWelcomeCoupon::class,
    ],
];
```
