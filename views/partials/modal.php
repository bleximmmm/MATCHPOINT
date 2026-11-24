<div id="modal-mensajes-pendientes" hidden>
    <?php foreach (flash_obtener() as $m): ?>
        <div data-modal-pendiente data-modal-tipo="<?= e($m['tipo']) ?>"><?= e($m['mensaje']) ?></div>
    <?php endforeach; ?>
</div>
<dialog class="modal-global" id="modal-global" aria-labelledby="modal-titulo" aria-describedby="modal-mensaje">
    <div class="modal-global__caja">
        <button class="modal-global__cerrar" type="button" data-modal-cerrar aria-label="Cerrar diálogo">×</button>
        <span class="modal-global__icono" id="modal-icono" aria-hidden="true">i</span>
        <h2 id="modal-titulo">Aviso</h2>
        <p id="modal-mensaje"></p>
        <div class="modal-global__acciones">
            <button class="btn btn--linea" id="modal-cancelar" type="button" hidden>Cancelar</button>
            <button class="btn btn--verde" id="modal-aceptar" type="button">Aceptar</button>
        </div>
    </div>
</dialog>
