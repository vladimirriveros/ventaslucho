# Cambios V13 - Corrección de conexión del Asistente

## Problema corregido

El panel del Asistente cargaba correctamente, pero al enviar cualquier consulta podía mostrar `Failed to fetch`.

La causa era que el frontend recibía una URL absoluta para el endpoint del asistente. En entornos detrás de un proxy HTTPS, como Render, Laravel puede generar temporalmente una URL `http://...`; el navegador bloquea esa petición desde una página HTTPS como contenido mixto. También podía provocar problemas si se accedía al sistema con un host o puerto distinto al configurado en `APP_URL`.

## Solución aplicada

- El endpoint `asistente.consultar` ahora se entrega como una ruta relativa del mismo sitio.
- El JavaScript normaliza el endpoint al `window.location.origin` actual antes de enviar la consulta.
- Se conserva `credentials: same-origin` y la protección CSRF.
- El frontend ahora lee primero la respuesta como texto y luego intenta convertirla a JSON, evitando perder información cuando Laravel devuelve una página de error.
- Se agregaron mensajes específicos para HTTP 401, 403, 419, 422, 429 y 500.
- Si realmente ocurre un fallo de red, el chat muestra una explicación útil en lugar del mensaje técnico `Failed to fetch`.
- Las URLs AJAX de alertas también quedaron relativas para evitar el mismo problema en Render/HTTPS.

## Base de datos

No se agregaron ni modificaron migraciones, tablas, columnas, seeders o permisos.
