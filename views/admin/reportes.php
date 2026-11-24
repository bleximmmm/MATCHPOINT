<?php /** Variables: $reporte, $f, $itemsReporte, $pag */
$tabs = ['stock-bajo' => 'Stock bajo', 'movimientos' => 'Movimientos por fecha', 'mas-movidos' => 'Más movidos'];
$csv = aurl('reportes', ['r' => $reporte, 'a' => 'csv', 'desde' => $f['desde'], 'hasta' => $f['hasta'], 'tipo' => $f['tipo']]);
?>
<nav class="tabs" aria-label="Reportes">
    <?php foreach ($tabs as $k => $t): ?><a href="<?= e(aurl('reportes', ['r' => $k])) ?>"<?= $reporte === $k ? ' aria-current="page"' : '' ?>><?= e($t) ?></a><?php endforeach; ?>
</nav>

<div class="admin__acciones">
    <?php if ($reporte !== 'stock-bajo'): ?>
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="reportes"><input type="hidden" name="r" value="<?= e($reporte) ?>">
        <label for="desde">Desde</label><input type="date" id="desde" name="desde" value="<?= e($f['desde']) ?>">
        <label for="hasta">Hasta</label><input type="date" id="hasta" name="hasta" value="<?= e($f['hasta']) ?>">
        <label class="solo-lector" for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <option value="">Todos los tipos</option>
            <?php foreach (['entrada' => 'Entradas', 'salida' => 'Salidas', 'ajuste' => 'Ajustes'] as $k => $t): ?><option value="<?= e($k) ?>"<?= $f['tipo'] === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--oscuro" type="submit">Generar</button>
    </form>
    <?php else: ?><p>Productos activos cuyo stock total está por debajo del mínimo configurado.</p><?php endif; ?>
    <a class="btn btn--linea" href="<?= e($csv) ?>">Exportar a CSV</a>
</div>

<div class="caja caja--tabla">
<?php if (!$itemsReporte): ?>
    <p class="vacio-texto"><?= $reporte === 'stock-bajo' ? 'No hay productos por debajo del stock mínimo.' : 'No hay datos para ese período.' ?></p>
<?php elseif ($reporte === 'stock-bajo'): ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr><th>SKU</th><th>Producto</th><th>Stock</th><th>Mínimo</th><th>Faltan</th><th>Proveedor</th></tr></thead>
        <tbody>
        <?php foreach ($itemsReporte as $r): ?>
            <tr class="fila-alerta">
                <td><?= e($r['sku']) ?></td>
                <td><a href="<?= e(aurl('productos', ['a' => 'editar', 'id' => $r['id']])) ?>"><?= e($r['nombre']) ?></a></td>
                <td><span class="stock stock--bajo"><?= (int) $r['stock'] ?></span></td>
                <td><?= (int) $r['stock_minimo'] ?></td>
                <td><?= (int) $r['faltante'] ?></td>
                <td><?= e($r['proveedor']) ?><br><small><?= e($r['telefono']) ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php elseif ($reporte === 'mas-movidos'): ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr><th>SKU</th><th>Producto</th><th>Movimientos</th><th>Entraron</th><th>Salieron</th><th>Unidades movidas</th></tr></thead>
        <tbody>
        <?php foreach ($itemsReporte as $r): ?>
            <tr><td><?= e($r['sku']) ?></td><td><?= e($r['nombre']) ?></td><td><?= (int) $r['movimientos'] ?></td><td><?= (int) $r['entradas'] ?></td><td><?= (int) $r['salidas'] ?></td><td><strong><?= (int) $r['unidades'] ?></strong></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php else: ?>
    <div class="tabla-scroll"><table class="tabla tabla--compacta">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Talle</th><th>Cant.</th><th>Stock</th><th>Usuario</th><th>Observaciones</th></tr></thead>
        <tbody>
        <?php foreach ($itemsReporte as $m): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime($m['fecha']))) ?></td>
                <td><span class="estado estado--<?= e($m['tipo']) ?>"><?= e(ucfirst($m['tipo'])) ?></span></td>
                <td><?= e($m['producto']) ?><br><small><?= e($m['sku']) ?></small></td>
                <td><?= (int) $m['talle'] ?></td><td><?= (int) $m['cantidad'] ?></td>
                <td><?= (int) $m['stock_anterior'] ?> &rarr; <?= (int) $m['stock_posterior'] ?></td>
                <td><?= e($m['usuario']) ?></td><td><?= e($m['observaciones']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</div>
<?php if ($pag) echo paginacion_html($pag, true); ?>
