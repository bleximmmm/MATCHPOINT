<?php
/** Páginas públicas: inicio, catálogo y detalle de producto. */
class TiendaController
{
    public static function inicio(): void
    {
        $accesos = [
            ['titulo' => 'Césped natural',   'texto' => 'Tapones firmes para canchas de pasto.', 'url' => url('catalogo', ['cancha' => ['cesped-natural']]),   'prod' => Producto::ultimoDeCategoria('cesped-natural')],
            ['titulo' => 'Césped sintético', 'texto' => 'Suelas cortas para la cancha de siempre.', 'url' => url('catalogo', ['cancha' => ['cesped-sintetico']]), 'prod' => Producto::ultimoDeCategoria('cesped-sintetico')],
            ['titulo' => 'Fútbol sala',      'texto' => 'Goma plana para parquet y cemento.',    'url' => url('catalogo', ['cancha' => ['futbol-sala']]),      'prod' => Producto::ultimoDeCategoria('futbol-sala')],
            ['titulo' => 'Últimos lanzamientos', 'texto' => 'Los modelos que acaban de llegar.', 'url' => url('catalogo', ['nuevo' => 1, 'orden' => 'recientes']), 'prod' => Producto::ultimoLanzamiento()],
        ];
        vista('tienda/inicio', [
            'titulo'       => 'Botines de fútbol',
            'accesos'      => $accesos,
            'destacados'   => Producto::destacados(8),
            'oferta'       => Producto::mayorOferta(),
            'mayorDesc'    => Catalogo::mayorDescuento(),
            'favs'         => Favorito::ids(Auth::id()),
        ]);
    }

    public static function catalogo(): void
    {
        $f = [
            'q'          => mb_substr(get('q'), 0, 60),
            'marcas'     => lista_slugs(isset($_GET['marca']) ? $_GET['marca'] : []),
            'categorias' => lista_slugs(isset($_GET['cancha']) ? $_GET['cancha'] : []),
            'precio_min' => ctype_digit(get('precio_min')) ? get('precio_min') : '',
            'precio_max' => ctype_digit(get('precio_max')) ? get('precio_max') : '',
            'talle'      => ctype_digit(get('talle')) ? (int) get('talle') : 0,
            'disponible' => get('disponible') === '1',
            'ofertas'    => get('ofertas') === '1',
            'nuevo'      => get('nuevo') === '1',
        ];
        if ($f['precio_min'] !== '' && $f['precio_max'] !== '' && (int) $f['precio_min'] > (int) $f['precio_max']) {
            list($f['precio_min'], $f['precio_max']) = [$f['precio_max'], $f['precio_min']];
        }
        $ordenes = ['recientes' => 'Más recientes', 'populares' => 'Más populares', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a menor'];
        $orden = isset($ordenes[get('orden')]) ? get('orden') : 'recientes';

        $pagActual = ctype_digit(get('pagina')) ? (int) get('pagina') : 1;
        $r = Producto::buscar($f, $orden, $pagActual, ITEMS_POR_PAGINA);
        $pag = paginar($r['total'], ITEMS_POR_PAGINA, $pagActual);
        if ($pag['actual'] !== $pagActual) {    // pidieron una página fuera de rango
            $r = Producto::buscar($f, $orden, $pag['actual'], ITEMS_POR_PAGINA);
        }

        $titulo = 'Catálogo';
        if ($f['ofertas']) {
            $titulo = 'Ofertas';
        } elseif ($f['nuevo']) {
            $titulo = 'Últimos lanzamientos';
        } elseif ($f['q'] !== '') {
            $titulo = 'Resultados para "' . $f['q'] . '"';
        } elseif (count($f['marcas']) === 1 && !$f['categorias']) {
            foreach (Catalogo::marcas() as $m) {
                if ($m['slug'] === $f['marcas'][0]) {
                    $titulo = $m['nombre'];
                }
            }
        }

        $activos = 0;
        foreach (['q', 'precio_min', 'precio_max'] as $k) {
            if ($f[$k] !== '') $activos++;
        }
        $activos += count($f['marcas']) + count($f['categorias']) + ($f['talle'] ? 1 : 0) + ($f['disponible'] ? 1 : 0) + ($f['ofertas'] ? 1 : 0) + ($f['nuevo'] ? 1 : 0);

        vista('tienda/catalogo', [
            'titulo'      => $titulo,
            'items'       => $r['items'],
            'pag'         => $pag,
            'f'           => $f,
            'orden'       => $orden,
            'ordenes'     => $ordenes,
            'marcas'      => Catalogo::marcas(),
            'categorias'  => Catalogo::categorias(),
            'talles'      => Catalogo::talles(),
            'activos'     => $activos,
            'favs'        => Favorito::ids(Auth::id()),
        ]);
    }

    public static function producto(): void
    {
        $id = ctype_digit(get('id')) ? (int) get('id') : 0;
        $p = $id ? Producto::detalle($id) : null;
        if (!$p) {
            error_pagina(404, 'No encontramos ese producto', 'Puede que haya sido dado de baja o que el enlace no sea correcto.');
        }
        $favs = Favorito::ids(Auth::id());
        vista('tienda/producto', [
            'titulo'        => $p['marca'] . ' ' . $p['nombre'],
            'p'             => $p,
            'imagenes'      => Producto::imagenes($id),
            'talles'        => Producto::talles($id),
            'relacionados'  => Producto::relacionados($p, 4),
            'esFav'         => in_array($id, $favs, true),
            'favs'          => $favs,
        ]);
    }
}
