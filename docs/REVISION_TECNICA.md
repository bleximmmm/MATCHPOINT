# Revisión técnica de MATCHPOINT

## Estado funcional verificable
- CRUD administrativo de usuarios, productos, marcas, categorías y proveedores; los pedidos se listan, se consultan y se actualiza su estado.
- Gestión de stock por producto y talle desde el panel.
- Historial de movimientos de entrada, salida y ajuste de solo consulta; las actualizaciones se generan desde productos, compras y cancelaciones.
- Reportes de stock bajo, movimientos por rango de fechas y productos más movidos.
- Dashboard con gráficos de ventas por mes y pedidos por estado.
- Exportación de reportes a CSV.
- La eliminación directa de pedidos desde la base devuelve el stock mediante un trigger, sin duplicar la devolución de pedidos cancelados.
- Validación de formularios, PDO con sentencias preparadas y protección CSRF.
- Registro e inicio de sesión con `password_hash()` y `password_verify()`.

## Verificaciones realizadas
- PHP 8.2 disponible para validación de sintaxis.
- Las 60 archivos PHP pasaron `php -l` sin errores de sintaxis.
- Las tres credenciales de demostración verificadas contra los hashes del SQL.
- `matchpoint.sql` importado correctamente en MariaDB 11.4.9.
- Contadores confirmados: 18 productos, 5 categorías, 3 proveedores, 136 movimientos y 6 pedidos.
- Inicio y catálogo respondieron HTTP 200 desde WAMP/Apache.
- El usuario administrador inició sesión y accedió al dashboard protegido.
- El código incluye bloqueo de filas con `SELECT ... FOR UPDATE` para movimientos y compras.

## Pendientes antes de entregar
- Inicializar un repositorio Git y crear commits descriptivos.
- Importar `database/matchpoint.sql` en WAMP y ejecutar recorridos completos de compra.
- Verificar visualmente las pantallas en escritorio y móvil.
- Mantener copias de seguridad si se vuelve a importar el SQL, porque la instalación limpia usa `DROP TABLE`.
