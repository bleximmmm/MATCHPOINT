<?php /** Variables: $pedido, $detalle */ include ROOT_PATH . '/views/cuenta/_menu.php'; ?>
<section class="seccion seccion--clara">
    <div class="contenedor dos-cajas">
        <div class="caja">
            <h2>Productos <span class="estado estado--<?= e($pedido['estado']) ?>"><?= e(Pedido::ESTADOS[$pedido['estado']]) ?></span></h2>
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
        </div>
        <div class="caja">
            <h2>Pedido #<?= (int) $pedido['id'] ?></h2>
            <p>Realizado el <?= e(date('d/m/Y \a \l\a\s H:i', strtotime($pedido['creado_en']))) ?></p>
            <p><?= e($pedido['nombre_envio']) ?><br><?= e($pedido['direccion']) ?><br><?= e($pedido['ciudad']) ?>, <?= e($pedido['provincia']) ?> (<?= e($pedido['codigo_postal']) ?>)<br>Tel. <?= e($pedido['telefono']) ?></p>
            <p><small><?= e($pedido['metodo_pago']) ?></small></p>
            <a class="btn btn--linea" href="<?= e(url('cuenta_pedidos')) ?>">Volver a mis pedidos</a>
        </div>
    </div>
</section>
