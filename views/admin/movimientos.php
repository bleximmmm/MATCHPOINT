<?php /** Variables: $items, $pag, $f */ ?>
<p class="ayuda-caja">Este historial es de solo consulta. Los cambios realizados desde Productos, las ventas y las cancelaciones se registran automáticamente.</p>
<section>
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="movimientos">
        <label class="solo-lector" for="fq">Buscar</label>
        <input type="search" id="fq" name="q" value="<?= e($f['q']) ?>" placeholder="Producto, SKU u observación" maxlength="60">
        <label class="solo-lector" for="ftipo">Tipo</label>
        <select id="ftipo" name="tipo_f">
            <option value="">Todos los tipos</option>
            <?php foreach (['entrada' => 'Entradas', 'salida' => 'Salidas', 'ajuste' => 'Ajustes'] as $k => $t): ?><option value="<?= e($k) ?>"<?= $f['tipo'] === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <label for="fdesde">Desde</label><input type="date" id="fdesde" name="desde" value="<?= e($f['desde']) ?>">
        <label for="fhasta">Hasta</label><input type="date" id="fhasta" name="hasta" value="<?= e($f['hasta']) ?>">
        <button class="btn btn--oscuro" type="submit">Filtrar</button>
    </form>
    <div class="caja caja--tabla">
    <?php if (!$items): ?><p class="vacio-texto">No hay movimientos con esos filtros.</p><?php else: ?>
        <div class="tabla-scroll"><table class="tabla tabla--compacta">
            <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Talle</th><th>Cant.</th><th>Stock</th><th>Usuario</th><th>Observaciones</th></tr></thead>
            <tbody>
            <?php foreach ($items as $m): ?>
                <tr>
                    <td><?= e(date('d/m/Y H:i', strtotime($m['fecha']))) ?></td>
                    <td><span class="estado estado--<?= e($m['tipo']) ?>"><?= e(ucfirst($m['tipo'])) ?></span></td>
                    <td><?= e($m['producto']) ?><br><small><?= e($m['sku']) ?></small></td>
                    <td><?= (int) $m['talle'] ?></td>
                    <td><?= (int) $m['cantidad'] ?></td>
                    <td><?= (int) $m['stock_anterior'] ?> &rarr; <?= (int) $m['stock_posterior'] ?></td>
                    <td><?= e($m['usuario']) ?></td>
                    <td><?= e($m['observaciones']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
    </div>
    <?= paginacion_html($pag, true) ?>
</section>
