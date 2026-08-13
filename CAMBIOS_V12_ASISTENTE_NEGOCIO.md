# V12 · Asistente de Ventas e Inventario

## Objetivo
Se agregó un asistente conversacional de solo consulta para obtener información del negocio directamente desde Laravel, sin permitir que el chat modifique ventas, inventario, caja o datos maestros.

## Consultas implementadas
- Ventas de hoy, ayer, semana, mes o año.
- Cantidad de ventas, total vendido, cobrado y pendiente.
- Ranking de productos más vendidos.
- Cantidad de productos y unidades disponibles.
- Consulta de un producto por nombre o código: existencia, stock y precio.
- Productos con stock bajo.
- Cálculo de pedidos escritos o dictados, por ejemplo: `Calcula 2 taladros, 3 discos de corte`.

## Multi-sucursal y seguridad
- Usuarios operativos consultan únicamente su `sucursal_id` aunque intenten enviar otra sucursal en la petición.
- Superadministradores pueden elegir una sucursal o consultar todas.
- El modo invitado puede usar el asistente, pero queda limitado a su sucursal demostrativa.
- El endpoint usa autenticación, CSRF, validación y rate limiting.
- No se ejecuta SQL generado por el usuario.
- No existe ninguna acción de escritura desde el asistente.

## Voz
El botón de micrófono utiliza la API de reconocimiento de voz disponible en el navegador. Funciona especialmente bien en Chrome/Edge y requiere permiso de micrófono. En navegadores sin soporte, el botón se oculta y el chat continúa funcionando por texto.

## Interfaz
- Botón flotante disponible en todas las pantallas autenticadas.
- Panel responsive para escritorio y celular.
- Compatible con tema claro/oscuro.
- Tarjetas de métricas, tablas compactas y sugerencias rápidas.
- El historial visible se conserva durante la sesión del navegador mediante `sessionStorage`.

## Archivos principales
- `app/Http/Controllers/AsistenteNegocioController.php`
- `app/Services/AsistenteNegocioService.php`
- `resources/views/partials/asistente-negocio.blade.php`
- `public/css/asistente-negocio.css`
- `public/js/asistente-negocio.js`
- `tests/Feature/AsistenteNegocioTest.php`
