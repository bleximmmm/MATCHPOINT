<?php /** Variables: $id, $d, $errores, $stock, $talles, $imagenes, $marcas, $categorias, $proveedores */
$t = function ($nombre, $etiqueta, $extra = '', $tipo = 'text', $ayuda = '') use ($d, $errores) {
    $valor = $d[$nombre]; include ROOT_PATH . '/views/partials/campo.php';
};
$sel = function ($nombre, $etiqueta, $lista) use ($d, $errores) { ?>
    <div class="campo<?= isset($errores[$nombre]) ? ' campo--error' : '' ?>">
        <label for="f-<?= e($nombre) ?>"><?= e($etiqueta) ?></label>
        <select id="f-<?= e($nombre) ?>" name="<?= e($nombre) ?>" required>
            <option value="">Elegí una opción</option>
            <?php foreach ($lista as $o): ?><option value="<?= (int) $o['id'] ?>"<?= (int) $d[$nombre] === (int) $o['id'] ? ' selected' : '' ?>><?= e($o['nombre']) ?></option><?php endforeach; ?>
        </select>
        <?php if (isset($errores[$nombre])): ?><p class="campo-error"><?= e($errores[$nombre]) ?></p><?php endif; ?>
    </div>
<?php };
?>
<?php if ($errores): ?>
    <div class="modal-pendiente" data-modal-pendiente data-modal-tipo="error" data-modal-titulo="Revisá los datos del producto">
        <p>Revisá estos puntos:</p>
        <ul><?php foreach ($errores as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>
<form class="formulario form-producto" action="<?= e($id ? aurl('productos', ['a' => 'editar', 'id' => $id]) : aurl('productos', ['a' => 'nuevo'])) ?>" method="post" enctype="multipart/form-data" novalidate>
    <?= csrf_campo() ?>
    <div class="caja">
        <h2>Datos del producto</h2>
        <div class="campos-2">
            <?php $t('sku', 'Código interno (SKU)', 'required maxlength="30" style="text-transform:uppercase"'); ?>
            <?php $t('nombre', 'Nombre del modelo', 'required maxlength="120"'); ?>
        </div>
        <div class="campos-3">
            <?php $sel('id_marca', 'Marca', $marcas); ?>
            <?php $sel('id_categoria', 'Categoría (tipo de cancha)', $categorias); ?>
            <?php $sel('id_proveedor', 'Proveedor', $proveedores); ?>
        </div>
        <div class="campo<?= isset($errores['descripcion']) ? ' campo--error' : '' ?>">
            <label for="f-descripcion">Descripción</label>
            <textarea id="f-descripcion" name="descripcion" rows="4" maxlength="3000"><?= e($d['descripcion']) ?></textarea>
            <?php if (isset($errores['descripcion'])): ?><p class="campo-error"><?= e($errores['descripcion']) ?></p><?php endif; ?>
        </div>
        <div class="campos-3">
            <?php $t('material', 'Material', 'maxlength="120"'); ?>
            <?php $t('suela', 'Tipo de suela', 'maxlength="120"'); ?>
            <?php $t('color', 'Color', 'maxlength="80"'); ?>
        </div>
        <div class="campo">
            <label for="f-caracteristicas">Características técnicas (una por línea)</label>
            <textarea id="f-caracteristicas" name="caracteristicas" rows="4" maxlength="2000"><?= e($d['caracteristicas']) ?></textarea>
        </div>
    </div>

    <div class="caja">
        <h2>Precios y descuento</h2>
        <div class="campos-4">
            <?php $t('precio_compra', 'Precio de compra', 'required inputmode="decimal"', 'text', 'Lo que pagás al proveedor.'); ?>
            <?php $t('precio_venta', 'Precio de venta (lista)', 'required inputmode="decimal"', 'text', 'Antes del descuento.'); ?>
            <?php $t('descuento', 'Descuento (%)', 'required inputmode="numeric" maxlength="2"', 'text', 'De 0 a 90. 0 = sin oferta.'); ?>
            <?php $t('stock_minimo', 'Stock mínimo', 'required inputmode="numeric"', 'text', 'Genera alerta por debajo de este total.'); ?>
        </div>
        <div class="opciones">
            <label class="check"><input type="checkbox" name="nuevo" value="1"<?= $d['nuevo'] ? ' checked' : '' ?>><span>Marcar como nuevo ingreso</span></label>
            <label class="check"><input type="checkbox" name="activo" value="1"<?= $d['activo'] ? ' checked' : '' ?>><span>Visible en la tienda (activo)</span></label>
        </div>
    </div>

    <div class="caja">
        <h2>Stock por talle</h2>
        <p class="ayuda-caja"><?= $id ? 'Si cambiás una cantidad, se registra un ajuste en el historial de movimientos.' : 'Las cantidades cargadas se registran como entrada de stock inicial.' ?> Consultá el historial completo en <a href="<?= e(aurl('movimientos')) ?>">Movimientos de stock</a>.</p>
        <?php if (isset($errores['stock'])): ?><p class="campo-error"><?= e($errores['stock']) ?></p><?php endif; ?>
        <div class="stock-talles">
            <?php foreach ($talles as $tl): $v = $stock[$tl['id']]; ?>
                <div class="campo">
                    <label for="stock-<?= (int) $tl['id'] ?>">Talle <?= (int) $tl['valor'] ?></label>
                    <input type="number" id="stock-<?= (int) $tl['id'] ?>" name="stock[<?= (int) $tl['id'] ?>]" min="0" max="9999" value="<?= $v < 0 ? '' : (int) $v ?>">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="caja">
        <h2>Fotografías</h2>
        <?php if ($imagenes): ?>
            <ul class="imagenes-admin">
                <?php foreach ($imagenes as $im): ?>
                    <li><img src="<?= e(img($im['ruta'])) ?>" alt="" width="120" height="80">
                        <label class="check"><input type="checkbox" name="borrar_img[]" value="<?= (int) $im['id'] ?>"><span>Quitar</span></label></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="campo<?= isset($errores['imagenes']) ? ' campo--error' : '' ?>">
            <label for="f-imagenes">Subir imágenes nuevas (JPG, PNG o WEBP, hasta 3 MB cada una)</label>
            <input type="file" id="f-imagenes" name="imagenes[]" accept="image/jpeg,image/png,image/webp" multiple>
            <p class="campo__ayuda">La primera imagen es la principal; la segunda aparece al pasar el cursor por la tarjeta.</p>
            <?php if (isset($errores['imagenes'])): ?><p class="campo-error"><?= e($errores['imagenes']) ?></p><?php endif; ?>
        </div>
    </div>

    <div class="formulario__acciones">
        <button class="btn btn--verde btn--grande" type="submit" data-cargando>Guardar producto</button>
        <a class="btn btn--linea" href="<?= e(aurl('productos')) ?>">Cancelar</a>
    </div>
</form>
