<?php /** Variables: $items, $pag, $q, $rol */ ?>
<div class="admin__acciones">
    <form class="filtros-barra" method="get" action="<?= e(BASE_URL . '/admin/index.php') ?>">
        <input type="hidden" name="p" value="usuarios">
        <label class="solo-lector" for="q">Buscar usuario</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Nombre o correo" maxlength="60">
        <label class="solo-lector" for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="">Todos los roles</option>
            <?php foreach (Usuario::ROLES as $k => $t): ?><option value="<?= e($k) ?>"<?= $rol === $k ? ' selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
        </select>
        <button class="btn btn--oscuro" type="submit">Filtrar</button>
    </form>
    <a class="btn btn--verde" href="<?= e(aurl('usuarios', ['a' => 'nuevo'])) ?>">Nuevo usuario</a>
</div>
<div class="caja caja--tabla">
<?php if (!$items): ?><p class="vacio-texto">No hay usuarios con esos filtros.</p><?php else: ?>
    <div class="tabla-scroll"><table class="tabla">
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Pedidos</th><th>Alta</th><th>Estado</th><th><span class="solo-lector">Acciones</span></th></tr></thead>
        <tbody>
        <?php foreach ($items as $u): ?>
            <tr>
                <td><?= e($u['nombre'] . ' ' . $u['apellido']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e(Usuario::ROLES[$u['rol']]) ?></td>
                <td><?= (int) $u['pedidos'] ?></td>
                <td><?= e(date('d/m/Y', strtotime($u['creado_en']))) ?></td>
                <td><span class="estado estado--<?= (int) $u['activo'] ? 'activo' : 'inactivo' ?>"><?= (int) $u['activo'] ? 'Activo' : 'De baja' ?></span></td>
                <td class="celda-acciones">
                    <a href="<?= e(aurl('usuarios', ['a' => 'editar', 'id' => $u['id']])) ?>">Editar</a>
                    <?php if ((int) $u['id'] !== Auth::id()): ?>
                    <form method="post" action="<?= e(aurl('usuarios', ['a' => 'estado'])) ?>" data-confirmar="<?= (int) $u['activo'] ? '¿Dar de baja esta cuenta? No podrá iniciar sesión.' : '¿Reactivar esta cuenta?' ?>">
                        <?= csrf_campo() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <button type="submit" class="<?= (int) $u['activo'] ? 'enlace-peligro' : 'enlace-simple' ?>"><?= (int) $u['activo'] ? 'Dar de baja' : 'Reactivar' ?></button>
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
