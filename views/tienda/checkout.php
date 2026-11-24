<?php /** Finalizar compra. Variables: $items, $total, $d, $errores, $provincias */
$campos = function ($nombre, $etiqueta, $extra = '', $tipo = 'text') use ($d, $errores) {
    $valor = $d[$nombre];
    include ROOT_PATH . '/views/partials/campo.php';
};
?>
<section class="encabezado-pagina"><div class="contenedor"><h1>Finalizar compra</h1></div></section>
<section class="seccion seccion--clara">
    <div class="contenedor">
        <ol class="pasos" aria-label="Pasos de la compra">
            <li>Carrito revisado</li>
            <li aria-current="step">Datos de envío</li>
            <li>Confirmación</li>
        </ol>
        <div class="carrito">
            <form class="formulario formulario--caja" action="<?= e(url('checkout')) ?>" method="post" novalidate>
                <?= csrf_campo() ?>
                <h2>Datos de envío</h2>
                <?php $campos('nombre', 'Nombre y apellido de quien recibe', 'required maxlength="120" autocomplete="name"'); ?>
                <?php $campos('telefono', 'Teléfono de contacto', 'required maxlength="30" autocomplete="tel" inputmode="tel"', 'tel'); ?>
                <?php $campos('direccion', 'Calle y altura (con piso o departamento si corresponde)', 'required maxlength="160" autocomplete="street-address"'); ?>
                <div class="campos-2">
                    <?php $campos('ciudad', 'Ciudad', 'required maxlength="80" autocomplete="address-level2"'); ?>
                    <?php $campos('codigo_postal', 'Código postal', 'required maxlength="8" autocomplete="postal-code"'); ?>
                </div>
                <div class="campo<?= isset($errores['provincia']) ? ' campo--error' : '' ?>">
                    <label for="f-provincia">Provincia</label>
                    <select id="f-provincia" name="provincia" required autocomplete="address-level1">
                        <option value="">Elegí una provincia</option>
                        <?php foreach ($provincias as $pv): ?>
                            <option value="<?= e($pv) ?>"<?= $d['provincia'] === $pv ? ' selected' : '' ?>><?= e($pv) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errores['provincia'])): ?><p class="campo-error"><?= e($errores['provincia']) ?></p><?php endif; ?>
                </div>

                <h2>Método de compra</h2>
                <label class="opcion-pago">
                    <input type="radio" name="metodo_pago" value="demo" checked>
                    <span><strong>Compra de demostración</strong><br>No se realiza ningún cobro. El pedido se guarda y el stock se descuenta como en una compra real.</span>
                </label>
                <?php if (isset($errores['metodo_pago'])): ?><p class="campo-error"><?= e($errores['metodo_pago']) ?></p><?php endif; ?>

                <button class="btn btn--verde btn--grande btn--bloque" type="submit" data-cargando>CONFIRMAR PEDIDO</button>
            </form>

            <aside class="resumen" aria-label="Tu pedido">
                <h2>Tu pedido</h2>
                <ul class="resumen__items">
                    <?php foreach ($items as $it): ?>
                        <li>
                            <img src="<?= e(img($it['imagen'])) ?>" alt="" width="72" height="48" loading="lazy">
                            <span><?= e($it['nombre']) ?><br><small>Talle <?= (int) $it['talle'] ?> &times; <?= (int) $it['cantidad'] ?></small></span>
                            <span><?= e(dinero($it['subtotal'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <dl><div class="resumen__total"><dt>Total</dt><dd><?= e(dinero($total)) ?></dd></div></dl>
                <a class="btn btn--linea btn--bloque" href="<?= e(url('carrito')) ?>">Volver al carrito</a>
            </aside>
        </div>
    </div>
</section>
