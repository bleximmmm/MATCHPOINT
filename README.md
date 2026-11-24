# MATCHPOINT — Tienda de botines de fútbol + control de stock

Tienda online de botines de fútbol con panel de administración y sistema de control de inventario.
Desarrollada con **PHP (sin frameworks), MySQL/MariaDB, HTML5, CSS3 y JavaScript vanilla**.

> Eslogan: *Tu próximo partido empieza acá.*

## Qué incluye

**Tienda (cliente)**
- Inicio con hero, navegación por marcas y superficies, productos destacados, ofertas e información visible sobre la compra de demostración.
- Avisos, errores generales y confirmaciones se muestran en modales accesibles; la validación del servidor sigue activa.
- Catálogo con filtros funcionales (marca, tipo de cancha, rango de precio, talle, disponibilidad, ofertas), buscador por nombre/marca, 4 órdenes y paginación.
- Detalle de producto: galería, disponibilidad por talle, guía rápida de talles, características, cantidad y productos relacionados; aclara que no se realizan cobros ni envíos reales.
- Carrito en sesión: agregar, quitar, cambiar cantidades, límite por stock, subtotales y total.
- Registro, inicio y cierre de sesión, edición de datos y contraseña, pedidos y favoritos.
- Compra: datos de envío → confirmación → pedido guardado en MySQL con descuento de stock en transacción.

**Panel de administración** (`/admin`) — roles **Administrador** y **Operador**
- Dashboard: productos, usuarios, pedidos, ventas, alertas de stock bajo con detalle en modal, gráfico de ventas mensuales y pedidos por estado.
- CRUD de productos (SKU único, precios de compra/venta, descuento, stock mínimo, proveedor, stock por talle, subida de fotos).
- CRUD de categorías, proveedores y marcas (relación 1:N con productos).
- Historial de movimientos de stock con filtros por fecha, tipo y producto; es de solo consulta. Los ajustes se hacen desde Productos y las ventas/cancelaciones generan sus movimientos automáticamente.
- Pedidos: detalle, datos del cliente y cambio de estado (cancelar devuelve el stock).
- Usuarios: alta, edición, roles y baja (solo administrador).
- Reportes: stock bajo, movimientos por rango de fechas y productos más movidos, con **exportación a CSV**.

| Acción | Operador | Administrador |
|---|:---:|:---:|
| Productos, stock, pedidos, reportes, alta/edición de catálogos | Sí | Sí |
| Eliminar productos, categorías, proveedores y marcas | No | Sí |
| Gestionar usuarios y permisos | No | Sí |

## Tema claro y oscuro
El encabezado incluye un selector de tema (la preferencia se guarda en el navegador). El tema claro es el predeterminado: encabezado, portada, banner de ofertas y pie en negro, y el contenido en blanco. El tema oscuro pasa también el contenido a grises oscuros. El verde eléctrico `#C6FF00` se usa solo en botones, indicadores y detalles. Todos los colores salen de variables CSS (`:root` en `public/css/style.css`).

## Requisitos
- XAMPP, WAMP o Laragon (Apache + PHP + MySQL/MariaDB).
- **PHP 7.4 o superior** (recomendado PHP 8.x) con extensiones `pdo_mysql`, `mbstring`, `fileinfo` e `iconv` (vienen activas en XAMPP).
- **MySQL 5.7+ / MariaDB 10.3+**.
- Conexión a internet para cargar las tipografías Montserrat e Inter desde Google Fonts (sin internet se usan tipografías del sistema).

## Instalación paso a paso
1. Copiá la carpeta `matchpoint` dentro de `htdocs` (XAMPP) o `www` (WAMP).
2. Iniciá **Apache** y **MySQL** desde el panel de XAMPP.
3. Abrí **phpMyAdmin** (`http://localhost/phpmyadmin`) → pestaña **Importar** → elegí `database/matchpoint.sql` → **Continuar**.
   (Crea la base `matchpoint`, las tablas y los datos de demostración. También sirve: `mysql -u root < database/matchpoint.sql`.)
   Si ya tenés la base creada, podés importar `database/migracion_devolucion_stock_pedidos.sql` para instalar la devolución automática al borrar pedidos y `database/migracion_imagenes_productos.sql` para actualizar las rutas de imágenes. No vuelvas a importar el SQL completo sobre una base con datos.
4. Si tu MySQL tiene otra contraseña o usuario, editá `config/config.php` (`DB_USER`, `DB_PASS`).
5. Asegurate de que la carpeta `public/uploads/productos` tenga permiso de escritura (en Linux/Mac: `chmod -R 775 public/uploads`).
6. Entrá a **http://localhost/matchpoint/**

No hace falta configurar la URL: se detecta sola.

## Credenciales de prueba
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@matchpoint.com | `Admin2026!` |
| Operador | operador@matchpoint.com | `Operador2026!` |
| Cliente | cliente@matchpoint.com | `Cliente2026!` |

Al iniciar sesión con un usuario de personal se redirige al panel (`/admin`). Los clientes no pueden entrar al panel.

## Datos de demostración
18 productos, 6 marcas, 5 categorías (tipos de cancha), 3 proveedores, talles 38 a 46, 6 pedidos y **136 movimientos de stock** coherentes con el stock actual. Hay productos con stock bajo (para ver las alertas) y uno agotado.

## Verificación antes de presentar
1. Importá el SQL completo desde phpMyAdmin o MySQL.
2. Probá primero el login administrador y operador.
3. Creá un producto de prueba y revisá que el movimiento se registre.
4. Realizá una compra con el usuario cliente y confirmá el descuento de stock.
5. Cambiá el estado de un pedido y comprobá que cancelar devuelve las unidades.
6. Generá los reportes y descargá el CSV.

## Estructura
```
matchpoint/
├── index.php            Controlador frontal de la tienda (rutas ?p=...)
├── admin/index.php      Controlador frontal del panel (protegido por sesión y rol)
├── config/config.php    Conexión y parámetros
├── core/                Base de datos (PDO), autenticación, carrito, helpers, bootstrap
├── models/              Consultas SQL y reglas de negocio (Producto, Pedido, Movimiento, ...)
├── controllers/         Controladores de la tienda y del panel (controllers/admin)
├── views/               Plantillas HTML (layout, tienda, cuenta, admin)
├── public/              css/, js/, img/ y uploads/ (fotos subidas)
└── database/matchpoint.sql   Estructura + datos de prueba
```
Arquitectura MVC simple: **modelos** (datos y SQL) / **controladores** (flujo y validación) / **vistas** (HTML).

## Modelo de datos
`usuarios`, `marcas`, `categorias`, `proveedores`, `talles`, `productos`, `producto_imagenes`, `producto_talles`, `favoritos`, `pedidos`, `detalle_pedidos`, `movimientos`.
Todas con claves primarias, foráneas e índices. **El stock se administra por producto y talle** (`producto_talles.stock`); las pantallas y filtros calculan la disponibilidad desde esos talles. `productos.stock` conserva el total y se recalcula dentro de la misma transacción en cada movimiento.

### Movimientos de stock
Todo cambio de stock (venta, cancelación o edición de producto) pasa por `Movimiento::registrar()`:
bloquea la fila con `SELECT ... FOR UPDATE`, valida, actualiza y guarda `stock_anterior` / `stock_posterior`.
- **Entrada**: suma. **Salida**: resta (rechaza si no alcanza). **Ajuste**: se ingresa el stock real contado y se registra la diferencia.
- Una venta es una transacción única: pedido + detalle + salidas de stock. Si un talle no alcanza, se revierte todo.
- Cancelar un pedido desde el panel devuelve el stock. Si se elimina directamente desde la base, un trigger también restituye el stock de pedidos no cancelados y registra los movimientos de entrada; un pedido ya cancelado no se devuelve dos veces.

## Seguridad aplicada
- PDO con sentencias preparadas (sin concatenar datos del usuario).
- `password_hash()` / `password_verify()` (+ rehash automático).
- Escape de toda salida con `htmlspecialchars` (`e()`).
- Token CSRF en todos los formularios POST y en las peticiones AJAX.
- Validación en el servidor de todos los datos; el JavaScript es solo ayuda visual.
- Sesiones con cookie `HttpOnly` + `SameSite`, regeneración de ID al iniciar sesión, vencimiento por inactividad y revalidación de rol/estado en cada petición.
- Control de permisos por rol; el panel exige sesión y rol de personal.
- Subida de imágenes: tipo MIME real (`finfo`), `getimagesize`, límite de 3 MB, nombre aleatorio y carpeta sin ejecución de PHP.
- Freno básico de fuerza bruta en el login; mensaje único para credenciales incorrectas.
- Protección de carpetas internas con `.htaccess`. El CSV evita inyección de fórmulas.

## Notas
- Las compras son **de demostración**: no hay pasarela de pago y no se cobra nada.
- Las imágenes incluidas están en `public/img/productos` (PNG/JPG). Desde el panel (Productos → Editar) también podés subir imágenes JPG/PNG/WEBP; la primera es la principal y la segunda aparece al pasar el cursor.
- Los nombres de marcas y modelos pertenecen a sus respectivos dueños y se usan solo con fines de demostración escolar.
- Si ves una pantalla en blanco, poné `APP_DEBUG` en `true` en `config/config.php` para ver el error.
