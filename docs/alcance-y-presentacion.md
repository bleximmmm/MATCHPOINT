# MATCHPOINT — Guion para presentación oral (10 minutos)

1. **Presentación (1 min):** MATCHPOINT es una tienda especializada en botines de fútbol. La propuesta combina catálogo comercial y control de inventario.
2. **Identidad y experiencia (1 min):** negro, blanco y verde eléctrico; navegación fija; diseño responsive; accesos por superficie y colección.
3. **Catálogo (1 min):** búsqueda por texto, filtros por marca, categoría, precio y ofertas; orden por precio, novedades y popularidad.
4. **Ficha y carrito (1 min):** detalle técnico, talle obligatorio, disponibilidad y control de cantidades.
5. **Usuarios (1 min):** registro, login, sesiones, contraseñas con hash y roles almacenados.
6. **Compra (1 min):** dirección y teléfono, confirmación de pedido de prueba, detalle y descuento de stock dentro de una transacción.
7. **Administración (1 min):** dashboard, inventario, alertas de stock mínimo y movimientos.
8. **Movimientos (1 min):** historial de entradas, salidas y ajustes; usuario responsable, fecha y observaciones. Se muestra en modo de consulta y se alimenta con cambios de productos, compras y cancelaciones.
9. **Reportes (1 min):** movimientos por fechas, productos más movidos y exportación CSV.
10. **Cierre (1 min):** mostrar el SQL, relaciones y medidas de seguridad; explicar que no se procesan pagos reales.

## Modelo de datos
`usuarios`, `marcas`, `categorias`, `proveedores`, `productos`, `producto_talles`, `favoritos`, `pedidos`, `detalle_pedidos` y `movimientos`. Las claves foráneas vinculan el catálogo, el inventario por talle, los pedidos y la trazabilidad de stock.
