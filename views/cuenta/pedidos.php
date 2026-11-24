<?php /** Variables: $pedidos */ include ROOT_PATH . '/views/cuenta/_menu.php'; ?>
<section class="seccion seccion--clara">
    <div class="contenedor">
    <?php if (!$pedidos): ?>
        <div class="vacio vacio--caja">
            <h2>Todavía no hiciste ningún pedido</h2>
            <p>Cuando compres, vas a poder seguir el estado de cada pedido desde acá.</p>
            <a class="btn btn--oscuro" href="<?= e(url('catalogo')) ?>">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <div class="tabla-scroll">
            <table class="tabla">
                <thead><tr><th>Pedido</th><th>Fecha</th><th>Unidades</th><th>Total</th><th>Estado</th><th><span class="solo-lector">Acciones</span></th></tr></thead>
                <tbody>
                <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td>#<?= (int) $p['id'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($p['creado_en']))) ?></td>
                        <td><?= (int) $p['unidades'] ?></td>
                        <td><?= e(dinero($p['total'])) ?></td>
                        <td><span class="estado estado--<?= e($p['estado']) ?>"><?= e(Pedido::ESTADOS[$p['estado']]) ?></span></td>
                        <td><a href="<?= e(url('cuenta_pedido', ['id' => $p['id']])) ?>">Ver detalle</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    </div>
</section>
