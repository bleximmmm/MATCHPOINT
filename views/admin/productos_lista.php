<?php /** Variables: $items, $pag, $f, $orden, $categorias */ ?>
<div class="admin__acciones">
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="productos">
        <label class="solo-lector" for="q">Buscar por nombre, SKU o categoría</label>
        <input type="search" id="q" name="q" value="<?= e($f['q']) ?>" placeholder="Nombre, SKU, marca o categoría" maxlength="60">
        <label class="solo-lector" for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $f['id_categoria'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['nombre']) ?></option><?php endforeach; ?>
        </select>
        <label class="solo-lector" for="estado">Estado</label>
        <select id="estado" name="estado">
            <?php foreach (['' => 'Todos los estados', 'activo' => 'Activos', 'inactivo' => 'Inactivos', 'bajo' => 'Stock bajo', 'agotado' => 'Sin stock'] as $k => $t): ?>
                <option value="<?= e($k) ?>"<?= $f['estado'] === $k ? ' selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <label class="solo-lector" for="orden">Orden</label>
        <select id="orden" name="orden">
            <?php foreach (['recientes' => 'Más recientes', 'nombre' => 'Nombre', 'stock' => 'Menor stock', 'precio_asc' => 'Precio menor', 'precio_desc' => 'Precio mayor'] as $k => $t): ?>
                <option value="<?= e($k) ?>"<?= $orden === $k ? ' selected' : '' ?>><?= e($t) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn--oscuro" type="submit">Filtrar</button>
    </form>
    <a class="btn btn--verde" href="<?= e(aurl('productos', ['a' => 'nuevo'])) ?>">Nuevo producto</a>
</div>

<div class="caja caja--tabla">
<?php if (!$items): ?>
    <p class="vacio-texto">No hay productos con esos filtros.</p>
<?php else: ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr><th>Producto</th><th>SKU</th><th>Categoría</th><th>Precio</th><th>Stock</th><th>Estado</th><th><span class="solo-lector">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($items as $p): $bajo = (int) $p['stock'] < (int) $p['stock_minimo']; ?>
            <tr class="<?= $bajo ? 'fila-alerta' : '' ?>">
                <td class="celda-prod"><img src="<?= e(img($p['img1'])) ?>" alt="" width="64" height="42" loading="lazy"><span><strong><?= e($p['nombre']) ?></strong><br><small><?= e($p['marca']) ?></small></span></td>
                <td><?= e($p['sku']) ?></td>
                <td><?= e($p['categoria']) ?></td>
                <td><?= e(dinero($p['precio_final'])) ?><?php if ((int) $p['descuento'] > 0): ?><br><small>-<?= (int) $p['descuento'] ?>% sobre <?= e(dinero($p['precio_venta'])) ?></small><?php endif; ?></td>
                <td><span class="stock<?= $bajo ? ' stock--bajo' : '' ?>"><?= (int) $p['stock'] ?></span> <small>/ mín. <?= (int) $p['stock_minimo'] ?></small></td>
                <td><span class="estado estado--<?= (int) $p['activo'] ? 'activo' : 'inactivo' ?>"><?= (int) $p['activo'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="celda-acciones">
                    <a href="<?= e(aurl('productos', ['a' => 'editar', 'id' => $p['id']])) ?>">Editar</a>
                    <?php if (Auth::esAdmin()): ?>
                    <form method="post" action="<?= e(aurl('productos', ['a' => 'eliminar'])) ?>" data-confirmar="¿Eliminar &quot;<?= e($p['nombre']) ?>&quot;? Si tiene pedidos o movimientos se dará de baja en lugar de borrarse.">
                        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><button type="submit" class="enlace-peligro">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>
</div>
<?= paginacion_html($pag, true) ?>
