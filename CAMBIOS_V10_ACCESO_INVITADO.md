# V10 · Acceso invitado para portafolio

## Objetivo
Permitir que una persona que visite el portafolio pueda entrar al sistema sin conocer un usuario ni contraseña, manteniendo las operaciones sensibles protegidas.

## Cambios
- Botón **Ingresar como invitado** en el formulario de acceso.
- Ruta POST protegida por CSRF y limitación de solicitudes.
- Nuevo rol `invitado` con permisos exclusivamente de consulta.
- Nueva cuenta `Invitado · Portafolio`, creada por `DemoGuestSeeder`.
- El invitado puede consultar los principales módulos y reportes de todas las sucursales.
- No puede crear, editar o eliminar ventas, compras, salidas, cotizaciones, productos, cajas ni otras operaciones.
- No puede abrir/cerrar caja ni registrar pagos.
- No puede ver Usuarios/Roles ni Cuentas bancarias.
- No puede cambiar la contraseña de la cuenta demo.
- El rol `invitado` tiene una lista blanca de permisos. Si recibe por error un permiso fuera de esa lista, el acceso público se bloquea automáticamente.
- El rol y la cuenta invitada quedan protegidos contra edición/eliminación accidental desde la administración.
- Se muestra una banda visual **Modo invitado · Portafolio** dentro del sistema.
- Se agregaron `DEMO_GUEST_LOGIN` y `DEMO_GUEST_EMAIL` a `.env.example`.

## Activar/desactivar
Por defecto el botón está habilitado. Para ocultarlo:

```env
DEMO_GUEST_LOGIN=false
```

Para volver a habilitarlo:

```env
DEMO_GUEST_LOGIN=true
```
