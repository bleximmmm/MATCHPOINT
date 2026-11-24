<?php /** Confirmación. Variables: $pedido, $detalle */ ?>
<section class="seccion seccion--clara">
    <div class="contenedor confirmacion">
        <svg class="confirmacion__marca" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true"><circle cx="32" cy="32" r="30" fill="#101114"/><path d="M19 33l9 9 17-19" fill="none" stroke="#C6FF00" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <h1>Recibimos tu pedido</h1>
        <p>Número de pedido: <strong>#<?= (int) $pedido['id'] ?></strong>. Podés seguirlo desde “Mis pedidos”.</p>

        <div class="caja">
            <h2>Detalle</h2>
            <ul class="resumen__items">
                <?php foreach ($detalle as $d): ?>
                    <li>
                        <img src="<?= e(img($d['imagen'])) ?>" alt="" width="72" height="48">
                        <span><?= e($d['nombre_producto']) ?><br><small>Talle <?= (int) $d['talle'] ?> &times; <?= (int) $d['cantidad'] ?></small></span>
                        <span><?= e(dinero($d['subtotal'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="resumen__total-linea">Total <strong><?= e(dinero($pedido['total'])) ?></strong></p>
        </div>
        <div class="caja">
            <h2>Envío</h2>
            <p><?= e($pedido['nombre_envio']) ?><br><?= e($pedido['direccion']) ?><br><?= e($pedido['ciudad']) ?>, <?= e($pedido['provincia']) ?> (<?= e($pedido['codigo_postal']) ?>)<br>Tel. <?= e($pedido['telefono']) ?></p>
            <p><small><?= e($pedido['metodo_pago']) ?>: no se realizó ningún cobro.</small></p>
        </div>
        <div class="confirmacion__acciones">
            <a class="btn btn--oscuro" href="<?= e(url('cuenta_pedidos')) ?>">Ver mis pedidos</a>
            <a class="btn btn--linea" href="<?= e(url('catalogo')) ?>">Seguir comprando</a>
        </div>
    </div>
</section>
