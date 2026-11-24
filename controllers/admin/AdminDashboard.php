<?php
/** Resumen general del panel. */
class AdminDashboard
{
    public static function ejecutar(): void
    {
        vista('admin/dashboard', [
            'titulo'    => 'Panel',
            'seccion'   => 'dashboard',
            'productos' => Producto::contar(),
            'usuarios'  => Usuario::contar(),
            'pedidos'   => Pedido::contar(),
            'ventas'    => Pedido::ventasTotales(),
            'pendientes'=> Pedido::pendientes(),
            'ventasMes' => Pedido::ventasPorMes(),
            'estados'   => Pedido::cantidadPorEstado(),
            'bajo'      => Movimiento::stockBajo(),
            'recientes' => Pedido::recientes(6),
        ], 'layout/admin');
    }
}
