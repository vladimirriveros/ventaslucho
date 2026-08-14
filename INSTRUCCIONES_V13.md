# Instrucciones V13

Esta versión solo corrige la comunicación frontend/backend del Asistente y las URLs AJAX relacionadas.

## Local

Ejecutar:

```bash
php artisan optimize:clear
php artisan serve
```

No ejecutar migraciones ni seeders.

## Render

Subir los cambios a GitHub y esperar a que Render complete el deploy. No ejecutar `migrate:fresh`.

Después del despliegue conviene ejecutar `php artisan optimize:clear` como parte del comando de despliegue o desde la consola de Render si se dispone de ella.

Si el navegador conserva el JavaScript anterior, hacer una recarga forzada o limpiar la caché del sitio.
