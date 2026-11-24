# MATCHPOINT — Estado del proyecto

**Fecha de revisión:** 22 de noviembre de 2026  
**Tipo de proyecto:** tienda web de botines de fútbol con administración de inventario.

## 1. Descripción general

MATCHPOINT es una aplicación web desarrollada con PHP, MySQL/MariaDB, HTML, CSS y JavaScript. Incluye una tienda para clientes y un panel privado para administrar productos, pedidos, usuarios y existencias.

Las compras son de demostración: se registra el pedido y se descuenta el stock, pero no se procesa un pago real.

## 2. Funcionalidades implementadas

### Tienda para clientes

- Página de inicio y catálogo con búsqueda, filtros por marca, categoría, precio, talle, disponibilidad y ofertas.
- Ordenamiento y paginación del catálogo.
- Ficha de producto con imágenes, características, talles y disponibilidad por talle.
- Carrito de compras con control de cantidades y validación de stock.
- Registro, inicio y cierre de sesión.
- Edición del perfil, consulta de pedidos y lista de favoritos.
- Checkout con datos de envío y confirmación de compra de demostración.

### Panel de administración

- Acceso por roles de administrador y operador.
- Dashboard con métricas, pedidos recientes, alertas de stock bajo y gráficos de ventas mensuales y pedidos por estado.
- Alta, edición y baja de productos; gestión de precios, imágenes, talles y existencias.
- Administración de marcas, categorías y proveedores.
- Administración de usuarios, roles y estado de las cuentas, restringida al administrador.
- Consulta de pedidos, detalle y actualización de su estado. No hay alta ni borrado administrativo de pedidos.
- Consulta del historial de movimientos de inventario: entrada, salida y ajuste generados automáticamente por productos, compras y cancelaciones.
- Reportes de stock bajo, movimientos por fecha y productos más movidos.
- Exportación de reportes a CSV.

### Inventario y persistencia

- El stock se administra por producto y talle.
- Las compras y movimientos usan transacciones para mantener consistentes el inventario, el pedido y su detalle.
- El stock de disponibilidad mostrado en la tienda se calcula desde las existencias por talle.
- Cancelar un pedido desde el panel devuelve el stock.
- La eliminación directa de un pedido no cancelado desde la base también devuelve el stock mediante un trigger y registra el movimiento correspondiente. Un pedido cancelado no devuelve stock por segunda vez.

### Seguridad

- Consultas a la base mediante PDO y sentencias preparadas.
- Contraseñas almacenadas con hash.
- Validación de datos en el servidor, escape de salida y protección CSRF.
- Sesiones con cookies protegidas, vencimiento por inactividad y revalidación de permisos.
- Validación de imágenes subidas y protección de la carpeta de subidas contra ejecución de scripts.

## 3. Datos de demostración

El archivo de instalación [matchpoint.sql](../database/matchpoint.sql) incluye:

- 18 productos, 6 marcas, 5 categorías y 3 proveedores.
- Talles del 38 al 46.
- Usuarios de prueba para los roles administrador, operador y cliente.
- Pedidos y movimientos de inventario de demostración.

Las cantidades de pedidos y movimientos pueden variar en una base que ya se haya usado, porque las compras y operaciones del panel modifican los datos.

## 4. Aspectos parcialmente cubiertos o pendientes

### Gestión administrativa de pedidos

El checkout crea pedidos y el panel permite listarlos, ver su detalle y cambiar su estado. No hay pantallas administrativas para crear pedidos manualmente ni para eliminarlos. La devolución automática al borrar se aplica en la base de datos; no constituye una opción de borrado dentro del panel.

Si la consigna exige un CRUD completo de pedidos en la interfaz, este punto debe completarse. Para preservar el historial, conviene preferir la cancelación del pedido antes que el borrado definitivo.

### Gestión administrativa de pedidos

El panel permite listar pedidos, consultar su detalle y cambiar su estado. No permite crear pedidos manualmente ni borrarlos desde la interfaz; si el PDF solicita un CRUD completo de pedidos, este requisito sigue pendiente.

### Administración de sesiones activas

La aplicación controla vencimiento e invalida sesiones cuando una cuenta deja de estar activa o pierde permisos. No incluye una pantalla para listar y revocar sesiones activas individualmente.

### Pruebas y validación visual

Se verificaron sintaxis PHP de los archivos modificados, la carga de las tres vistas de reportes y los flujos principales de modales en WAMP. No se realizó una prueba automatizada completa de todos los flujos de compra y administración ni una revisión visual exhaustiva en distintos tamaños de pantalla.

### Versionado

El directorio del proyecto no contiene un repositorio Git. Si la entrega solicita historial de cambios o commits, hay que inicializarlo y preparar ese historial.

### Instalación de la base

El SQL principal es para una instalación limpia: elimina y vuelve a crear las tablas. No debe importarse sobre una base con información que se quiera conservar. Para instalar únicamente la devolución automática de stock en una base existente, se incluye [migracion_devolucion_stock_pedidos.sql](../database/migracion_devolucion_stock_pedidos.sql).

## 5. Funciones adicionales

La tienda también incluye favoritos, tema claro y oscuro, galería de imágenes, filtros avanzados y productos relacionados. Son mejoras de experiencia de uso; no reemplazan los requisitos principales de gestión, inventario, seguridad y reportes.

No se integra una pasarela de pago real.

## 6. Verificaciones del entorno

La aplicación se probó en WAMP con PHP y MySQL. Durante la revisión se detectó que el entorno tenía más de una instancia de base de datos; por eso es importante que phpMyAdmin y la aplicación estén usando la misma instancia y la base `matchpoint`.

El stock debe verificarse recargando la página del producto después de cambiar un pedido o las existencias. La migración del trigger debe ejecutarse en la misma instancia de MySQL que usa la aplicación.

## 7. Prioridades recomendadas antes de entregar

1. Confirmar con el PDF si requiere CRUD administrativo completo de pedidos o administración de sesiones activas.
2. Inicializar Git si solicitan versionado.
3. Probar en WAMP una compra completa, una cancelación, la devolución al eliminar un pedido y los reportes.
4. Verificar que la base de phpMyAdmin sea la misma que utiliza la aplicación.
5. No reimportar el SQL principal sobre una base con datos sin contar con un respaldo.
