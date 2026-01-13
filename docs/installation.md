# Guía de Instalación y Configuración

## Configuración Manual (Opcional)

Si prefieres hacerlo paso a paso o necesitas personalizar antes de migrar:

### A. Publicar Configuración
```bash
php artisan vendor:publish --tag=innosoft-auth-config
```

### B. Editar `config/auth-core.php`
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

### C. Publicar y Ejecutar Migraciones
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"
php artisan migrate
```

### D. Ejecutar Seeder
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
