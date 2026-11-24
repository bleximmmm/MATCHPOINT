/* MATCHPOINT - Interacciones de la tienda (JavaScript vanilla).
 * Nada de lo importante depende solo de este archivo: el servidor valida todo de nuevo. */
(function () {
  'use strict';

  var base = document.body.getAttribute('data-base') || '';
  var metaToken = document.querySelector('meta[name="csrf-token"]');
  var token = metaToken ? metaToken.getAttribute('content') : '';
  var reducirMovimiento = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  /* --------------------------------------------------------------- modales */
  var modal = $('#modal-global');
  var modalTitulo = $('#modal-titulo');
  var modalMensaje = $('#modal-mensaje');
  var modalIcono = $('#modal-icono');
  var modalAceptar = $('#modal-aceptar');
  var modalCancelar = $('#modal-cancelar');
  var modalCola = [];
  var modalActual = null;

  function mostrarSiguienteModal() {
    if (!modal || modalActual || !modalCola.length) { return; }
    modalActual = modalCola.shift();
    modalTitulo.textContent = modalActual.titulo;
    modalMensaje.textContent = modalActual.mensaje;
    modalIcono.textContent = modalActual.tipo === 'error' ? '!' : modalActual.tipo === 'ok' ? '+' : 'i';
    modalCancelar.hidden = !modalActual.confirmacion;
    modalAceptar.textContent = modalActual.confirmacion ? 'Confirmar' : 'Aceptar';
    modal.showModal();
    modalAceptar.focus();
  }

  function encolarModal(datos) {
    if (!modal) { return Promise.resolve(false); }
    return new Promise(function (resolve) {
      datos.resolver = resolve;
      modalCola.push(datos);
      mostrarSiguienteModal();
    });
  }

  function notificar(mensaje, tipo, titulo) {
    tipo = tipo || 'ok';
    return encolarModal({
      mensaje: String(mensaje),
      tipo: tipo,
      titulo: titulo || (tipo === 'error' ? 'Ocurrió un problema' : tipo === 'ok' ? 'Listo' : 'Aviso'),
      confirmacion: false
    });
  }

  function confirmar(mensaje, titulo) {
    return encolarModal({
      mensaje: String(mensaje),
      tipo: 'info',
      titulo: titulo || '¿Confirmar acción?',
      confirmacion: true
    });
  }

  if (modal) {
    modalAceptar.addEventListener('click', function () { modal.close('aceptar'); });
    modalCancelar.addEventListener('click', function () { modal.close('cancelar'); });
    $('[data-modal-cerrar]', modal).addEventListener('click', function () { modal.close('cancelar'); });
    modal.addEventListener('cancel', function (e) {
      e.preventDefault();
      modal.close('cancelar');
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal) { modal.close('cancelar'); }
    });
    modal.addEventListener('close', function () {
      var actual = modalActual;
      modalActual = null;
      if (actual) { actual.resolver(!actual.confirmacion || modal.returnValue === 'aceptar'); }
      setTimeout(mostrarSiguienteModal, 0);
    });
  }

  $$('[data-modal-pendiente]').forEach(function (elemento) {
    var mensaje = elemento.textContent.replace(/\s+/g, ' ').trim();
    if (mensaje) {
      notificar(mensaje, elemento.getAttribute('data-modal-tipo'), elemento.getAttribute('data-modal-titulo'));
    }
  });
  document.addEventListener('click', function (e) {
    var botonModal = e.target.closest('[data-modal-mensaje]');
    if (!botonModal) { return; }
    notificar(
      botonModal.getAttribute('data-modal-mensaje'),
      botonModal.getAttribute('data-modal-tipo') || 'info',
      botonModal.getAttribute('data-modal-titulo')
    );
  });

  /* ------------------------------------------------ menús (hamburguesa y panel) */
  $$('.menu-btn').forEach(function (btn) {
    var destino = document.getElementById(btn.getAttribute('aria-controls'));
    if (!destino) { return; }
    function cerrar() {
      destino.classList.remove('abierto');
      btn.setAttribute('aria-expanded', 'false');
      btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace('Cerrar', 'Abrir'));
      document.body.style.overflow = '';
    }
    btn.addEventListener('click', function () {
      var abierto = destino.classList.toggle('abierto');
      btn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      btn.setAttribute('aria-label', btn.getAttribute('aria-label').replace(abierto ? 'Abrir' : 'Cerrar', abierto ? 'Cerrar' : 'Abrir'));
      document.body.style.overflow = abierto && window.innerWidth <= 900 ? 'hidden' : '';
    });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cerrar(); } });
    window.addEventListener('resize', function () { if (window.innerWidth > 900) { cerrar(); } });
  });

  $$('.nav__desplegable details').forEach(function (menu) {
    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target)) { menu.removeAttribute('open'); }
    });
    menu.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.open) {
        menu.removeAttribute('open');
        $('summary', menu).focus();
      }
    });
  });

  /* ---------------------------------------------------------------- buscador */
  var btnBuscar = $('[data-buscar]');
  var panelBuscar = $('#buscador');
  if (btnBuscar && panelBuscar) {
    btnBuscar.addEventListener('click', function () {
      var mostrar = panelBuscar.hidden;
      panelBuscar.hidden = !mostrar;
      btnBuscar.setAttribute('aria-expanded', mostrar ? 'true' : 'false');
      if (mostrar) { $('input[type="search"]', panelBuscar).focus(); }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !panelBuscar.hidden) { panelBuscar.hidden = true; btnBuscar.setAttribute('aria-expanded', 'false'); btnBuscar.focus(); }
    });
  }

  /* ------------------------------------------------- menú desplegable de cuenta */
  var cuentaMenu = $('.cuenta-menu');
  if (cuentaMenu) {
    document.addEventListener('click', function (e) { if (!cuentaMenu.contains(e.target)) { cuentaMenu.removeAttribute('open'); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { cuentaMenu.removeAttribute('open'); } });
  }

  /* ------------------------------------------------------ filtros en celulares */
  var panelFiltros = $('#filtros');
  var fondoFiltros = $('.filtros-fondo');
  function filtros(abrir) {
    if (!panelFiltros) { return; }
    panelFiltros.classList.toggle('abierto', abrir);
    if (fondoFiltros) { fondoFiltros.hidden = !abrir; }
    document.body.style.overflow = abrir ? 'hidden' : '';
  }
  $$('[data-abrir-filtros]').forEach(function (b) { b.addEventListener('click', function () { filtros(true); }); });
  $$('[data-cerrar-filtros]').forEach(function (b) { b.addEventListener('click', function () { filtros(false); }); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { filtros(false); } });

  $$('[data-autoenviar]').forEach(function (el) {
    el.addEventListener('change', function () { if (el.form) { el.form.submit(); } });
  });

  /* ------------------------------------------------------------- favoritos */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-fav]');
    if (!btn) { return; }
    e.preventDefault();
    var id = btn.getAttribute('data-fav');
    var datos = new FormData();
    datos.append('id_producto', id);
    datos.append('_csrf', token);
    fetch(base + '/index.php?p=favorito', { method: 'POST', body: datos, headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': token }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (d) { return { estado: r.status, datos: d }; }); })
      .then(function (r) {
        if (r.estado === 401 && r.datos.login) {
          notificar(r.datos.mensaje, 'info').then(function () { window.location.href = r.datos.login; });
          return;
        }
        if (!r.datos.ok) { notificar(r.datos.mensaje || 'No pudimos guardar el favorito.', 'error'); return; }
        $$('[data-fav="' + id + '"]').forEach(function (b) {
          b.classList.toggle('es-fav', r.datos.favorito);
          b.setAttribute('aria-pressed', r.datos.favorito ? 'true' : 'false');
          var lbl = (b.getAttribute('aria-label') || '').replace(/^(Agregar a|Quitar de) favoritos/, r.datos.favorito ? 'Quitar de favoritos' : 'Agregar a favoritos');
          b.setAttribute('aria-label', lbl);
        });
        notificar(r.datos.mensaje, 'ok');
      })
      .catch(function () { notificar('No hay conexión con el servidor. Probá de nuevo.', 'error'); });
  });

  /* ----------------------------------------------- selector de cantidad (+ / -) */
  $$('[data-cantidad]').forEach(function (caja) {
    var input = $('input', caja);
    function ajustar(delta) {
      var min = parseInt(input.min || '1', 10);
      var max = parseInt(input.max || '99', 10);
      var v = parseInt(input.value || String(min), 10);
      if (isNaN(v)) { v = min; }
      var nuevo = Math.min(max, Math.max(min, v + delta));
      if (nuevo !== v || String(nuevo) !== input.value) {
        input.value = nuevo;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
    $('[data-menos]', caja).addEventListener('click', function () { ajustar(-1); });
    $('[data-mas]', caja).addEventListener('click', function () { ajustar(1); });
  });

  /* Carrito: al cambiar una cantidad se actualiza solo (con una pequeña espera). */
  var formCarrito = $('#form-carrito');
  if (formCarrito) {
    var espera = null;
    var versionCambio = 0;
    $$('[data-autoenviar-cantidad]', formCarrito).forEach(function (inp) {
      inp.addEventListener('change', function () {
        clearTimeout(espera);
        versionCambio += 1;
        var versionActual = versionCambio;
        var max = parseInt(inp.max, 10);
        var aviso = Promise.resolve();
        if (parseInt(inp.value, 10) > max) {
          inp.value = max;
          aviso = notificar('Solo hay ' + max + ' unidades de ese talle.', 'info');
        }
        aviso.then(function () {
          if (versionActual !== versionCambio) { return; }
          espera = setTimeout(function () { formCarrito.classList.add('actualizando'); formCarrito.submit(); }, 600);
        });
      });
    });
    formCarrito.addEventListener('submit', function (e) {
      // El botón de quitar usa su propio formaction; solo bloqueamos cantidades inválidas.
      if (!e.submitter || !e.submitter.hasAttribute('formaction')) {
        var invalido = $$('input[type="number"]', formCarrito).some(function (i) { return !i.value || parseInt(i.value, 10) < 1; });
        if (invalido) { e.preventDefault(); notificar('La cantidad debe ser al menos 1. Usá el tacho para quitar un producto.', 'error'); }
      }
    });
  }

  /* ------------------------------------------------------- detalle de producto */
  var galeria = $('[data-galeria]');
  if (galeria) {
    var foto = $('#foto-principal', galeria);
    $$('[data-foto]', galeria).forEach(function (b) {
      b.addEventListener('click', function () {
        if (foto.getAttribute('src') === b.getAttribute('data-foto')) { return; }
        $$('[data-foto]', galeria).forEach(function (o) { o.classList.remove('activa'); o.setAttribute('aria-pressed', 'false'); });
        b.classList.add('activa');
        b.setAttribute('aria-pressed', 'true');
        if (reducirMovimiento) { foto.src = b.getAttribute('data-foto'); return; }
        foto.classList.add('cambiando');
        setTimeout(function () { foto.src = b.getAttribute('data-foto'); foto.classList.remove('cambiando'); }, 160);
      });
    });
  }

  var formCompra = $('#form-compra');
  if (formCompra) {
    var avisoStock = $('#aviso-stock');
    var cantidad = $('#cantidad', formCompra);
    var botonCompra = $('button[type="submit"]', formCompra);
    var textoOriginal = botonCompra.textContent;

    $$('input[name="id_talle"]', formCompra).forEach(function (r) {
      r.addEventListener('change', function () {
        var stock = parseInt(r.getAttribute('data-stock'), 10);
        var tope = Math.min(stock, parseInt(cantidad.getAttribute('data-max-original') || cantidad.max, 10));
        if (!cantidad.getAttribute('data-max-original')) { cantidad.setAttribute('data-max-original', cantidad.max); }
        tope = Math.min(stock, parseInt(cantidad.getAttribute('data-max-original'), 10));
        cantidad.max = tope;
        if (parseInt(cantidad.value, 10) > tope) { cantidad.value = tope; }
        avisoStock.textContent = stock <= 5 ? 'Últimas ' + stock + (stock === 1 ? ' unidad' : ' unidades') + ' en este talle.' : 'Talle disponible.';
      });
    });

    formCompra.addEventListener('submit', function (e) {
      e.preventDefault();
      var elegido = $('input[name="id_talle"]:checked', formCompra);
      if (!elegido) {
        var primerTalle = $('input[name="id_talle"]', formCompra);
        notificar('Elegí un talle para continuar.', 'error', 'Falta elegir un talle').then(function () {
          if (primerTalle) { primerTalle.focus(); }
        });
        return;
      }
      botonCompra.classList.add('is-loading');
      fetch(formCompra.action, { method: 'POST', body: new FormData(formCompra), headers: { 'X-Requested-With': 'fetch', 'X-CSRF-Token': token }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          botonCompra.classList.remove('is-loading');
          if (!d.ok) { notificar(d.mensaje, 'error'); return; }
          actualizarContador(d.cantidad);
          notificar(d.mensaje, 'ok');
          botonCompra.classList.add('agregado');
          botonCompra.textContent = 'AGREGADO';
          setTimeout(function () { botonCompra.classList.remove('agregado'); botonCompra.textContent = textoOriginal; }, 1800);
        })
        .catch(function () { botonCompra.classList.remove('is-loading'); notificar('No hay conexión con el servidor. Probá de nuevo.', 'error'); });
    });
  }

  function actualizarContador(n) {
    $$('[data-contador]').forEach(function (c) {
      c.textContent = n;
      c.hidden = n < 1;
      c.classList.remove('bump');
      void c.offsetWidth;
      if (!reducirMovimiento) { c.classList.add('bump'); }
    });
  }

  function mensajeValidacion(campo) {
    var estado = campo.validity;
    if (estado.valueMissing) { return 'Completá este campo.'; }
    if (estado.typeMismatch) { return 'Ingresá un valor con formato válido.'; }
    if (estado.patternMismatch) { return 'El formato ingresado no es válido.'; }
    if (estado.tooShort) { return 'El valor es demasiado corto.'; }
    if (estado.tooLong) { return 'El valor es demasiado largo.'; }
    if (estado.rangeUnderflow) { return 'El valor es menor al permitido.'; }
    if (estado.rangeOverflow) { return 'El valor supera el máximo permitido.'; }
    if (estado.stepMismatch) { return 'Ingresá un valor válido.'; }
    return 'Revisá los datos ingresados.';
  }

  /* ----------------------------------------- formularios: validación y estados */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (e.defaultPrevented || !form.matches('form')) { return; }
    // Mostrar los errores de validación en el mismo modal de notificaciones.
    if (form.hasAttribute('novalidate') && !form.checkValidity() && !(e.submitter && e.submitter.hasAttribute('formaction'))) {
      e.preventDefault();
      var campoInvalido = form.querySelector(':invalid');
      notificar(campoInvalido ? mensajeValidacion(campoInvalido) : 'Revisá los datos ingresados.', 'error', 'Revisá el formulario')
        .then(function () { if (campoInvalido) { campoInvalido.focus(); } });
      return;
    }
    if (form.hasAttribute('data-validar-registro')) {
      var p1 = $('#f-password', form), p2 = $('#f-password2', form);
      if (p1 && p2 && p1.value !== p2.value) {
        e.preventDefault();
        p2.setCustomValidity('Las contraseñas no coinciden.');
        notificar(p2.validationMessage, 'error', 'Revisá la contraseña').then(function () { p2.focus(); });
        p2.addEventListener('input', function () { p2.setCustomValidity(''); }, { once: true });
        return;
      }
    }

    if (form.__modalConfirmado) {
      form.__modalConfirmado = false;
    } else {
      var mensajeConfirmacion = form.getAttribute('data-confirmar');
      var selectorEstado = $('select[name="estado"]', form);
      if (!mensajeConfirmacion && form.hasAttribute('data-confirmar-cancelar') && selectorEstado && selectorEstado.value === 'cancelado') {
        mensajeConfirmacion = 'Al cancelar el pedido se devuelve el stock y no podrá reabrirse. ¿Continuar?';
      }
      if (mensajeConfirmacion) {
        e.preventDefault();
        confirmar(mensajeConfirmacion).then(function (aceptado) {
          if (!aceptado) { return; }
          form.__modalConfirmado = true;
          if (typeof form.requestSubmit === 'function') {
            if (e.submitter) { form.requestSubmit(e.submitter); }
            else { form.requestSubmit(); }
          } else {
            form.submit();
          }
        });
        return;
      }
    }
    var enviar = $('[data-cargando]', form);
    if (enviar) { enviar.classList.add('is-loading'); }
  });
  window.addEventListener('pageshow', function () { $$('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); }); });
})();

/* Selector de tema claro / oscuro (se guarda en localStorage; el atributo inicial lo pone un script del <head>). */
(function () {
  'use strict';
  var raiz = document.documentElement;
  var boton = document.querySelector('[data-theme-toggle]');
  if (!boton) { return; }
  function aplicar(tema) {
    raiz.setAttribute('data-theme', tema);
    boton.setAttribute('aria-pressed', tema === 'dark' ? 'true' : 'false');
    boton.setAttribute('aria-label', tema === 'dark' ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
  }
  aplicar(raiz.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');
  boton.addEventListener('click', function () {
    var siguiente = raiz.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    aplicar(siguiente);
    try { localStorage.setItem('matchpoint-theme', siguiente); } catch (e) {}
  });
})();
