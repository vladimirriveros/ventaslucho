# Instrucciones V11

No se modificó la base de datos ni las dependencias.

Ejecutar:

```bash
php artisan optimize:clear
php artisan serve
```

No es necesario ejecutar migrate, migrate:fresh, seeders, composer install, npm install ni npm run build.

Después de actualizar el proyecto, en el teléfono conviene recargar forzadamente la página o borrar la caché del navegador si todavía se observa el CSS anterior.
