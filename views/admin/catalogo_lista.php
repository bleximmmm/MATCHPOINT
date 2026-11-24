<?php /** Variables: $tipo, $c, $items, $pag, $q */ ?>
<div class="admin__acciones">
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="<?= e($tipo) ?>">
        <label class="solo-lector" for="q">Buscar por nombre</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre" maxlength="60">
        <button class="btn btn--oscuro" type="submit">Buscar</button>
    </form>
    <a class="btn btn--verde" href="<?= e(aurl($tipo, ['a' => 'nuevo'])) ?>">Agregar <?= e($c['singular']) ?></a>
</div>
<div class="caja caja--tabla">
<?php if (!$items): ?><p class="vacio-texto">No hay registros.</p><?php else: ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr>
            <?php foreach ($c['campos'] as $cfg): ?><th><?= e($cfg[0]) ?></th><?php endforeach; ?>
            <th>Productos</th><th><span class="solo-lector">Acciones</span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <?php foreach ($c['campos'] as $campo => $cfg): ?><td><?= e($it[$campo]) ?></td><?php endforeach; ?>
                <td><?= (int) $it['productos'] ?></td>
                <td class="celda-acciones">
                    <a href="<?= e(aurl($tipo, ['a' => 'editar', 'id' => $it['id']])) ?>">Editar</a>
                    <?php if (Auth::esAdmin()): ?>
                    <form method="post" action="<?= e(aurl($tipo, ['a' => 'eliminar'])) ?>" data-confirmar="¿Eliminar este registro?">
                        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>"><button type="submit" class="enlace-peligro">Eliminar</button>
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
