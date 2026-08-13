# Instrucciones V12

No hay migraciones, seeders ni dependencias nuevas.

Después de reemplazar el proyecto o actualizar desde Git, ejecutar:

```bash
php artisan optimize:clear
php artisan serve
```

No es necesario ejecutar `migrate`, `migrate:fresh --seed`, `composer install`, `npm install` ni `npm run build` únicamente por esta versión.

## Pruebas sugeridas
1. Iniciar sesión con un usuario de sucursal y abrir el botón **Asistente**.
2. Preguntar `¿Cuánto vendimos hoy?`.
3. Preguntar por un producto real: `¿Tenemos taladro y cuánto cuesta?`.
4. Preguntar `¿Cuál fue el producto más vendido este mes?`.
5. Probar `Calcula 2 taladros, 3 discos de corte`.
6. Probar el micrófono desde Chrome/Edge y conceder permiso de voz.
7. Como Superadministrador, cambiar el selector de sucursal del asistente y repetir las consultas.
8. Como Invitado, comprobar que consulta datos pero no aparecen acciones de escritura.
