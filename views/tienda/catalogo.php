<?php /** Catálogo con filtros. Variables: $items, $pag, $f, $orden, $ordenes, $marcas, $categorias, $talles, $activos, $favs */ ?>
<section class="encabezado-pagina">
    <div class="contenedor">
        <h1><?= e($titulo) ?></h1>
        <p><?= (int) $pag['total'] ?> <?= $pag['total'] === 1 ? 'producto' : 'productos' ?></p>
    </div>
</section>

<section class="seccion seccion--clara seccion--catalogo">
    <form class="contenedor catalogo" id="form-filtros" action="<?= e(BASE_URL . '/index.php') ?>" method="get">
        <input type="hidden" name="p" value="catalogo">
        <?php if ($f['nuevo']): ?><input type="hidden" name="nuevo" value="1"><?php endif; ?>

        <aside class="filtros" id="filtros" aria-label="Filtros">
            <div class="filtros__cabeza">
                <h2>Filtros</h2>
                <button class="btn-icono filtros__cerrar" type="button" data-cerrar-filtros aria-label="Cerrar filtros"><?= icono('cerrar') ?></button>
            </div>

            <fieldset class="filtro">
                <legend>Marca</legend>
                <?php foreach ($marcas as $m): ?>
                    <label class="check"><input type="checkbox" name="marca[]" value="<?= e($m['slug']) ?>"<?= in_array($m['slug'], $f['marcas'], true) ? ' checked' : '' ?>><span><?= e($m['nombre']) ?></span></label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="filtro">
                <legend>Tipo de cancha</legend>
                <?php foreach ($categorias as $c): ?>
                    <label class="check"><input type="checkbox" name="cancha[]" value="<?= e($c['slug']) ?>"<?= in_array($c['slug'], $f['categorias'], true) ? ' checked' : '' ?>><span><?= e($c['nombre']) ?></span></label>
                <?php endforeach; ?>
            </fieldset>

            <fieldset class="filtro">
                <legend>Precio</legend>
                <div class="rango">
                    <label class="solo-lector" for="precio_min">Precio mínimo</label>
                    <input type="number" id="precio_min" name="precio_min" min="0" step="1000" inputmode="numeric" placeholder="Mínimo" value="<?= e($f['precio_min']) ?>">
                    <span aria-hidden="true">a</span>
                    <label class="solo-lector" for="precio_max">Precio máximo</label>
                    <input type="number" id="precio_max" name="precio_max" min="0" step="1000" inputmode="numeric" placeholder="Máximo" value="<?= e($f['precio_max']) ?>">
                </div>
            </fieldset>

            <fieldset class="filtro">
                <legend>Talle</legend>
                <div class="talles-filtro">
                    <label class="chip"><input type="radio" name="talle" value=""<?= !$f['talle'] ? ' checked' : '' ?>><span>Todos</span></label>
                    <?php foreach ($talles as $t): ?>
                        <label class="chip"><input type="radio" name="talle" value="<?= (int) $t['valor'] ?>"<?= $f['talle'] === (int) $t['valor'] ? ' checked' : '' ?>><span><?= (int) $t['valor'] ?></span></label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset class="filtro">
                <legend>Disponibilidad</legend>
                <label class="check"><input type="checkbox" name="disponible" value="1"<?= $f['disponible'] ? ' checked' : '' ?>><span>Solo con stock</span></label>
                <label class="check"><input type="checkbox" name="ofertas" value="1"<?= $f['ofertas'] ? ' checked' : '' ?>><span>Solo ofertas</span></label>
            </fieldset>

            <div class="filtros__acciones">
                <button class="btn btn--oscuro btn--bloque" type="submit">Aplicar filtros</button>
                <?php if ($activos > 0): ?><a class="btn btn--linea btn--bloque" href="<?= e(url('catalogo')) ?>">Limpiar filtros</a><?php endif; ?>
            </div>
        </aside>

        <div class="resultados">
            <div class="barra-herramientas">
                <button class="btn btn--linea filtros-abrir" type="button" data-abrir-filtros aria-controls="filtros">
                    <?= icono('filtro') ?> Filtros<?= $activos > 0 ? ' (' . (int) $activos . ')' : '' ?>
                </button>
                <div class="buscador-catalogo">
                    <label class="solo-lector" for="q-catalogo">Buscar por nombre o marca</label>
                    <input type="search" id="q-catalogo" name="q" maxlength="60" placeholder="Nombre o marca" value="<?= e($f['q']) ?>">
                    <button class="btn btn--oscuro" type="submit">Buscar</button>
                </div>
                <div class="orden">
                    <label for="orden">Ordenar por</label>
                    <select id="orden" name="orden" data-autoenviar>
                        <?php foreach ($ordenes as $k => $txt): ?>
                            <option value="<?= e($k) ?>"<?= $k === $orden ? ' selected' : '' ?>><?= e($txt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($items): ?>
                <div class="grilla grilla--catalogo">
                    <?php foreach ($items as $p) { include ROOT_PATH . '/views/partials/tarjeta.php'; } ?>
                </div>
                <?= paginacion_html($pag) ?>
            <?php else: ?>
                <div class="vacio vacio--caja">
                    <h2>No encontramos botines con esos filtros</h2>
                    <p>Probá sacando alguno, ampliando el rango de precio o buscando por otra marca.</p>
                    <a class="btn btn--oscuro" href="<?= e(url('catalogo')) ?>">Ver todo el catálogo</a>
                </div>
            <?php endif; ?>
        </div>
    </form>
    <div class="filtros-fondo" data-cerrar-filtros hidden></div>
</section>
