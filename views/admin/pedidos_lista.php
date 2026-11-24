<?php /** Variables: $items, $pag, $estado, $q */ ?>
<div class="admin__acciones">
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="pedidos">
        <label class="solo-lector" for="q">Buscar por número, cliente o correo</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="N° de pedido, cliente o correo" maxlength="60">
        <label class="solo-lector" for="estado">Estado</label>
        <select id="estado" name="estado">
            <option value="">Todos los estados</option>
            <?php foreach (Pedido::ESTADOS as $k => $t): ?><option value="<?= e($k) ?>"<?= $estado === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--oscuro" type="submit">Filtrar</button>
    </form>
</div>
<div class="caja caja--tabla">
<?php if (!$items): ?><p class="vacio-texto">No hay pedidos con esos filtros.</p><?php else: ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Unidades</th><th>Total</th><th>Estado</th><th><span class="solo-lector">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($items as $p): ?>
            <tr>
                <td>#<?= (int) $p['id'] ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime($p['creado_en']))) ?></td>
                <td><?= e($p['nombre'] . ' ' . $p['apellido']) ?><br><small><?= e($p['email']) ?></small></td>
                <td><?= (int) $p['unidades'] ?></td>
                <td><?= e(dinero($p['total'])) ?></td>
                <td><span class="estado estado--<?= e($p['estado']) ?>"><?= e(Pedido::ESTADOS[$p['estado']]) ?></span></td>
                <td><a href="<?= e(aurl('pedidos', ['a' => 'ver', 'id' => $p['id']])) ?>">Ver detalle</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</div>
<?= paginacion_html($pag, true) ?>
