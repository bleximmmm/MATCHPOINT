<?php /** Variables: $productos, $usuarios, $pedidos, $ventas, $pendientes, $ventasMes, $estados, $bajo, $recientes */ ?>
<div class="metricas">
    <a class="metrica" href="<?= e(aurl('productos')) ?>"><span class="metrica__valor"><?= (int) $productos ?></span><span>Productos</span></a>
    <a class="metrica" href="<?= e(aurl('usuarios')) ?>"><span class="metrica__valor"><?= (int) $usuarios ?></span><span>Usuarios</span></a>
    <a class="metrica" href="<?= e(aurl('pedidos')) ?>"><span class="metrica__valor"><?= (int) $pedidos ?></span><span>Pedidos (<?= (int) $pendientes ?> pendientes)</span></a>
    <a class="metrica metrica--destacada" href="<?= e(aurl('pedidos')) ?>"><span class="metrica__valor"><?= e(dinero($ventas)) ?></span><span>Ventas registradas</span></a>
</div>

<div class="dashboard-graficos">
    <section class="caja grafico">
        <h2>Ventas por mes</h2>
        <p class="grafico__ayuda">Total de pedidos no cancelados en los últimos seis meses.</p>
        <?php $maxVentas = max(array_column($ventasMes, 'total')); ?>
        <div class="grafico__meses" role="list" aria-label="Ventas mensuales en los últimos seis meses">
            <?php foreach ($ventasMes as $mes): ?>
                <?php $alto = $maxVentas > 0 ? ($mes['total'] / $maxVentas) * 100 : 0; ?>
                <div class="grafico__mes" role="listitem">
                    <span class="grafico__importe"><?= e(dinero($mes['total'])) ?></span>
                    <div class="grafico__columna">
                        <span class="grafico__barra" aria-hidden="true" style="height: <?= e((string) $alto) ?>%"></span>
                    </div>
                    <span class="grafico__etiqueta"><?= e($mes['mes']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="caja grafico">
        <h2>Pedidos por estado</h2>
        <p class="grafico__ayuda">Cantidad de pedidos según su estado actual.</p>
        <?php $maxPedidosEstado = max(array_column($estados, 'cantidad')); ?>
        <div class="grafico__estados">
            <?php foreach ($estados as $estado): ?>
                <?php $ancho = $maxPedidosEstado > 0 ? ($estado['cantidad'] / $maxPedidosEstado) * 100 : 0; ?>
                <div class="grafico__fila">
                    <div class="grafico__dato"><span><?= e($estado['nombre']) ?></span><strong><?= (int) $estado['cantidad'] ?></strong></div>
                    <div class="grafico__pista" role="img" aria-label="<?= e($estado['nombre'] . ': ' . $estado['cantidad'] . ' pedidos') ?>">
                        <span class="grafico__barra" style="width: <?= e((string) $ancho) ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<div class="admin__dos">
    <section class="caja">
        <h2>Alertas de stock bajo</h2>
        <?php if (!$bajo): ?>
            <p>Todos los productos están por encima de su stock mínimo.</p>
        <?php else: ?>
            <?php
            $detalleBajo = array_map(function ($producto) {
                return $producto['nombre'] . ' - stock: ' . $producto['stock'] . ', mínimo: ' . $producto['stock_minimo'];
            }, array_slice($bajo, 0, 6));
            if (count($bajo) > 6) {
                $detalleBajo[] = 'Y ' . (count($bajo) - 6) . ' producto(s) más.';
            }
            ?>
            <p><?= count($bajo) ?> producto(s) están por debajo de su stock mínimo.</p>
            <button class="btn btn--oscuro" type="button" data-modal-mensaje="<?= e(implode("\n", $detalleBajo)) ?>" data-modal-titulo="Alertas de stock bajo">Ver productos</button>
            <p><a href="<?= e(aurl('reportes', ['r' => 'stock-bajo'])) ?>">Ver reporte completo</a></p>
        <?php endif; ?>
    </section>
    <section class="caja">
        <h2>Últimos pedidos</h2>
        <?php if (!$recientes): ?><p>Todavía no hay pedidos.</p><?php else: ?>
            <div class="tabla-scroll"><table class="tabla tabla--compacta">
                <thead><tr><th>N°</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recientes as $r): ?>
                    <tr>
                        <td><a href="<?= e(aurl('pedidos', ['a' => 'ver', 'id' => $r['id']])) ?>">#<?= (int) $r['id'] ?></a></td>
                        <td><?= e($r['nombre'] . ' ' . $r['apellido']) ?></td>
                        <td><?= e(dinero($r['total'])) ?></td>
                        <td><span class="estado estado--<?= e($r['estado']) ?>"><?= e(Pedido::ESTADOS[$r['estado']]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </section>
</div>
