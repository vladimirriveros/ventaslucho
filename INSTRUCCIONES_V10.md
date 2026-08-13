# Instrucciones V10

No se agregaron migraciones ni dependencias.

Para una base de datos que ya existe, ejecutar:

```bash
php artisan optimize:clear
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=DemoGuestSeeder
php artisan permission:cache-reset
php artisan serve
```

No es necesario ejecutar `migrate`, `migrate:fresh`, `composer install`, `npm install` ni `npm run build`.

Si voluntariamente se reconstruye la base de pruebas desde cero, `php artisan migrate:fresh --seed` también crea automáticamente el rol y usuario invitado.

Después abra `/login` y pulse **Ingresar como invitado**.
