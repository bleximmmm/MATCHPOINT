<?php /** Variables: $pedido, $detalle */ ?>
<div class="admin__dos">
    <section class="caja">
        <h2>Productos del pedido #<?= (int) $pedido['id'] ?></h2>
        <ul class="resumen__items">
            <?php foreach ($detalle as $d): ?>
                <li>
                    <img src="<?= e(img($d['imagen'])) ?>" alt="" width="72" height="48" loading="lazy">
                    <span><?= e($d['nombre_producto']) ?><br><small>Talle <?= (int) $d['talle'] ?> &times; <?= (int) $d['cantidad'] ?> a <?= e(dinero($d['precio_unitario'])) ?></small></span>
                    <span><?= e(dinero($d['subtotal'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <p class="resumen__total-linea">Total <strong><?= e(dinero($pedido['total'])) ?></strong></p>
    </section>
    <div>
        <section class="caja">
            <h2>Estado</h2>
            <p><span class="estado estado--<?= e($pedido['estado']) ?>"><?= e(Pedido::ESTADOS[$pedido['estado']]) ?></span> &nbsp; <?= e(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></p>
            <?php if ($pedido['estado'] === 'cancelado'): ?>
                <p><small>Un pedido cancelado no puede cambiar de estado; su stock ya fue devuelto.</small></p>
            <?php else: ?>
            <form class="formulario" method="post" action="<?= e(aurl('pedidos', ['a' => 'estado'])) ?>" data-confirmar-cancelar>
                <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                <div class="campo">
                    <label for="estado">Cambiar estado a</label>
                    <select id="estado" name="estado">
                        <?php foreach (Pedido::ESTADOS as $k => $t): ?><option value="<?= e($k) ?>"<?= $pedido['estado'] === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
                    </select>
                    <p class="campo__ayuda">Al cancelar, el stock vuelve al depósito automáticamente.</p>
                </div>
                <button class="btn btn--oscuro" type="submit" data-cargando>Actualizar estado</button>
            </form>
            <?php endif; ?>
        </section>
        <section class="caja">
            <h2>Cliente</h2>
            <p><?= e($pedido['cli_nombre'] . ' ' . $pedido['cli_apellido']) ?><br><a href="mailto:<?= e($pedido['cli_email']) ?>"><?= e($pedido['cli_email']) ?></a><?php if ($pedido['cli_telefono']): ?><br>Tel. <?= e($pedido['cli_telefono']) ?><?php endif; ?></p>
            <h2>Envío</h2>
            <p><?= e($pedido['nombre_envio']) ?><br><?= e($pedido['direccion']) ?><br><?= e($pedido['ciudad']) ?>, <?= e($pedido['provincia']) ?> (<?= e($pedido['codigo_postal']) ?>)<br>Tel. <?= e($pedido['telefono']) ?></p>
            <p><small><?= e($pedido['metodo_pago']) ?></small></p>
        </section>
        <a class="btn btn--linea" href="<?= e(aurl('pedidos')) ?>">Volver a pedidos</a>
    </div>
</div>
