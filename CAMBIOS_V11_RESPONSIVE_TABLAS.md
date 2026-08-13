# V11 - Responsive general de tablas

## Objetivo
Mejorar la lectura y operación de los listados del sistema en teléfonos, evitando tablas comprimidas y desplazamientos horizontales difíciles de usar.

## Cambios
- Las tablas tradicionales se conservan en escritorio y tablet grande.
- En pantallas de hasta 767 px, cada fila se presenta como una tarjeta.
- Cada valor muestra automáticamente el nombre de su columna como etiqueta.
- Compatible con filas regeneradas por DataTables y Livewire.
- Los botones de acciones se ajustan en varias líneas sin salirse de la pantalla.
- Los controles de DataTables se reorganizan para móvil:
  - buscador a ancho completo;
  - selector de cantidad legible;
  - exportaciones en cuadrícula;
  - paginación centrada y adaptable;
  - información del listado sin desbordamiento.
- Los encabezados de tarjetas y sus botones se reorganizan en móvil.
- Se mantiene compatibilidad con tema claro y oscuro.
- Las tablas que en el futuro deban conservar desplazamiento horizontal pueden excluirse con `data-mobile-table="scroll"` o `no-mobile-cards`.

## Base de datos
No hay cambios de base de datos, migraciones ni seeders.
