-- ============================================================================
-- MATCHPOINT - Esquema de base de datos (MySQL 5.7+ / MariaDB 10.3+)
-- Importar con:  mysql -u root -p < database/matchpoint.sql
-- ============================================================================
CREATE DATABASE IF NOT EXISTS matchpoint CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE matchpoint;

DROP TRIGGER IF EXISTS trg_pedidos_devolver_stock;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS movimientos, detalle_pedidos, pedidos, favoritos, producto_talles,
  producto_imagenes, productos, talles, proveedores, categorias, marcas, usuarios;
SET FOREIGN_KEY_CHECKS = 1;

-- Usuarios del sistema: clientes de la tienda y personal (operador / admin)
CREATE TABLE usuarios (
  id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre    VARCHAR(60)  NOT NULL,
  apellido  VARCHAR(60)  NOT NULL,
  email     VARCHAR(120) NOT NULL,
  password  VARCHAR(255) NOT NULL,                 -- hash generado con password_hash()
  telefono  VARCHAR(30)  NULL,
  rol       ENUM('cliente','operador','admin') NOT NULL DEFAULT 'cliente',
  activo    TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  KEY idx_usuarios_rol (rol)
) ENGINE=InnoDB;

CREATE TABLE marcas (
  id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60)  NOT NULL,
  slug   VARCHAR(80)  NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_marcas_nombre (nombre),
  UNIQUE KEY uq_marcas_slug (slug)
) ENGINE=InnoDB;

-- Las categorías representan el tipo de cancha / superficie
CREATE TABLE categorias (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre      VARCHAR(80)  NOT NULL,
  slug        VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_nombre (nombre),
  UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE proveedores (
  id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre   VARCHAR(120) NOT NULL,
  contacto VARCHAR(100) NULL,
  telefono VARCHAR(30)  NULL,
  email    VARCHAR(120) NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_proveedores_nombre (nombre)
) ENGINE=InnoDB;

-- Talles argentinos (38 a 46)
CREATE TABLE talles (
  id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  valor TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_talles_valor (valor)
) ENGINE=InnoDB;

CREATE TABLE productos (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sku            VARCHAR(30)  NOT NULL,             -- código interno único
  nombre         VARCHAR(120) NOT NULL,
  descripcion    TEXT         NULL,
  id_marca       INT UNSIGNED NOT NULL,
  id_categoria   INT UNSIGNED NOT NULL,
  id_proveedor   INT UNSIGNED NOT NULL,
  precio_compra  DECIMAL(10,2) NOT NULL DEFAULT 0,
  precio_venta   DECIMAL(10,2) NOT NULL,            -- precio de lista (sin descuento)
  descuento      TINYINT UNSIGNED NOT NULL DEFAULT 0, -- porcentaje 0-90
  stock          INT UNSIGNED NOT NULL DEFAULT 0,   -- total = suma de producto_talles.stock
  stock_minimo   INT UNSIGNED NOT NULL DEFAULT 5,
  material       VARCHAR(120) NULL,
  suela          VARCHAR(120) NULL,
  color          VARCHAR(80)  NULL,
  caracteristicas TEXT        NULL,                 -- una característica por línea
  nuevo          TINYINT(1)   NOT NULL DEFAULT 0,
  ventas         INT UNSIGNED NOT NULL DEFAULT 0,   -- contador para "más populares"
  activo         TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_productos_sku (sku),
  KEY idx_productos_marca (id_marca),
  KEY idx_productos_categoria (id_categoria),
  KEY idx_productos_proveedor (id_proveedor),
  KEY idx_productos_activo_creado (activo, creado_en),
  CONSTRAINT fk_productos_marca     FOREIGN KEY (id_marca)     REFERENCES marcas(id),
  CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id),
  CONSTRAINT fk_productos_proveedor FOREIGN KEY (id_proveedor) REFERENCES proveedores(id)
) ENGINE=InnoDB;

CREATE TABLE producto_imagenes (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_producto INT UNSIGNED NOT NULL,
  ruta        VARCHAR(255) NOT NULL,                -- relativa a /public
  orden       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  KEY idx_imagenes_producto (id_producto, orden),
  CONSTRAINT fk_imagenes_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Stock por producto y talle
CREATE TABLE producto_talles (
  id_producto INT UNSIGNED NOT NULL,
  id_talle    INT UNSIGNED NOT NULL,
  stock       INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id_producto, id_talle),
  KEY idx_pt_talle (id_talle),
  CONSTRAINT fk_pt_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_pt_talle    FOREIGN KEY (id_talle)    REFERENCES talles(id)
) ENGINE=InnoDB;

CREATE TABLE favoritos (
  id_usuario  INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_producto),
  KEY idx_fav_producto (id_producto),
  CONSTRAINT fk_fav_usuario  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id)  ON DELETE CASCADE,
  CONSTRAINT fk_fav_producto FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_usuario    INT UNSIGNED NOT NULL,
  estado        ENUM('pendiente','confirmado','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
  total         DECIMAL(12,2) NOT NULL,
  nombre_envio  VARCHAR(120) NOT NULL,
  telefono      VARCHAR(30)  NOT NULL,
  direccion     VARCHAR(160) NOT NULL,
  ciudad        VARCHAR(80)  NOT NULL,
  provincia     VARCHAR(80)  NOT NULL,
  codigo_postal VARCHAR(12)  NOT NULL,
  metodo_pago   VARCHAR(40)  NOT NULL DEFAULT 'Compra de demostración',
  creado_en     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_pedidos_usuario (id_usuario),
  KEY idx_pedidos_estado_fecha (estado, creado_en),
  CONSTRAINT fk_pedidos_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE detalle_pedidos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pedido       INT UNSIGNED NOT NULL,
  id_producto     INT UNSIGNED NOT NULL,
  id_talle        INT UNSIGNED NOT NULL,
  nombre_producto VARCHAR(160) NOT NULL,            -- copia del nombre al momento de la compra
  cantidad        INT UNSIGNED NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,           -- precio final pagado
  PRIMARY KEY (id),
  KEY idx_detalle_pedido (id_pedido),
  KEY idx_detalle_producto (id_producto),
  CONSTRAINT fk_detalle_pedido   FOREIGN KEY (id_pedido)   REFERENCES pedidos(id) ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos(id),
  CONSTRAINT fk_detalle_talle    FOREIGN KEY (id_talle)    REFERENCES talles(id)
) ENGINE=InnoDB;

-- Historial de entradas, salidas y ajustes de stock
CREATE TABLE movimientos (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_producto     INT UNSIGNED NOT NULL,
  id_talle        INT UNSIGNED NOT NULL,
  id_usuario      INT UNSIGNED NULL,
  id_pedido       INT UNSIGNED NULL,
  tipo            ENUM('entrada','salida','ajuste') NOT NULL,
  cantidad        INT UNSIGNED NOT NULL,            -- unidades movidas (en ajustes: diferencia absoluta)
  stock_anterior  INT UNSIGNED NOT NULL,
  stock_posterior INT UNSIGNED NOT NULL,
  fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  observaciones   VARCHAR(255) NULL,
  PRIMARY KEY (id),
  KEY idx_mov_producto (id_producto),
  KEY idx_mov_fecha (fecha),
  KEY idx_mov_usuario (id_usuario),
  KEY idx_mov_pedido (id_pedido),
  KEY idx_mov_talle (id_talle),
  CONSTRAINT fk_mov_producto FOREIGN KEY (id_producto) REFERENCES productos(id),
  CONSTRAINT fk_mov_talle    FOREIGN KEY (id_talle)    REFERENCES talles(id),
  CONSTRAINT fk_mov_usuario  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id) ON DELETE SET NULL,
  CONSTRAINT fk_mov_pedido   FOREIGN KEY (id_pedido)   REFERENCES pedidos(id)  ON DELETE SET NULL
) ENGINE=InnoDB;

DELIMITER //
CREATE TRIGGER trg_pedidos_devolver_stock
BEFORE DELETE ON pedidos
FOR EACH ROW
BEGIN
  DECLARE terminado TINYINT DEFAULT 0;
  DECLARE v_producto INT UNSIGNED;
  DECLARE v_talle INT UNSIGNED;
  DECLARE v_cantidad INT UNSIGNED;
  DECLARE v_antes INT UNSIGNED;
  DECLARE cur_detalles CURSOR FOR
    SELECT id_producto, id_talle, cantidad
    FROM detalle_pedidos
    WHERE id_pedido = OLD.id;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET terminado = 1;

  IF OLD.estado <> 'cancelado' THEN
    OPEN cur_detalles;
    leer_detalles: LOOP
      FETCH cur_detalles INTO v_producto, v_talle, v_cantidad;
      IF terminado = 1 THEN
        LEAVE leer_detalles;
      END IF;

      INSERT INTO producto_talles (id_producto, id_talle, stock)
      VALUES (v_producto, v_talle, 0)
      ON DUPLICATE KEY UPDATE stock = stock;

      SELECT stock INTO v_antes
      FROM producto_talles
      WHERE id_producto = v_producto AND id_talle = v_talle;

      UPDATE producto_talles
      SET stock = stock + v_cantidad
      WHERE id_producto = v_producto AND id_talle = v_talle;

      UPDATE productos
      SET stock = (
        SELECT COALESCE(SUM(stock), 0)
        FROM producto_talles
        WHERE id_producto = v_producto
      )
      WHERE id = v_producto;

      INSERT INTO movimientos (
        id_producto, id_talle, id_usuario, id_pedido, tipo, cantidad,
        stock_anterior, stock_posterior, observaciones
      ) VALUES (
        v_producto, v_talle, NULL, NULL, 'entrada', v_cantidad,
        v_antes, v_antes + v_cantidad,
        CONCAT('Devolucion automatica por eliminacion del pedido #', OLD.id)
      );
    END LOOP;
    CLOSE cur_detalles;
  END IF;
END//
DELIMITER ;


-- =============================== DATOS DE DEMOSTRACIÓN ===============================

SET NAMES utf8mb4;

INSERT INTO usuarios (id,nombre,apellido,email,password,telefono,rol,activo,creado_en) VALUES
(1,'Admin','Matchpoint','admin@matchpoint.com','$2y$12$NmLaz82c.IMiK3/8mYweqeKCmRtviaw7V3L5uXPnB/n8kfnx4s.9e',NULL,'admin',1,'2026-08-01 10:00:00'),
(2,'Operador','Depósito','operador@matchpoint.com','$2y$12$uHpJji4QlBts50Jx9M08wePIy377nFDijt1J87x7cVMIJJUcbJEty',NULL,'operador',1,'2026-08-01 10:05:00'),
(3,'Cliente','Demo','cliente@matchpoint.com','$2y$12$z7BaOEL06Q7bN46exl7x1O5JML5TYAvAd4ZYegVusW.Cp4X4mQhZq','+54 9 341 5551234','cliente',1,'2026-09-10 18:20:00');

INSERT INTO marcas (id,nombre,slug) VALUES
(1,'Nike','nike'),
(2,'Adidas','adidas'),
(3,'Puma','puma'),
(4,'Mizuno','mizuno'),
(5,'Umbro','umbro'),
(6,'New Balance','new-balance');

INSERT INTO categorias (id,nombre,slug,descripcion) VALUES
(1,'Césped natural','cesped-natural','Botines con tapones para canchas de césped natural (FG).'),
(2,'Césped sintético','cesped-sintetico','Tapones cortos y numerosos para césped artificial (AG).'),
(3,'Turf','turf','Suela con tacos bajos para canchas duras y sintético corto (TF).'),
(4,'Fútbol sala','futbol-sala','Calzado de suela plana para parquet y canchas techadas (IC).'),
(5,'Terreno blando','terreno-blando','Tapones largos de aluminio para barro y césped pesado (SG).');

INSERT INTO proveedores (id,nombre,contacto,telefono,email) VALUES
(1,'Distribuidora Deportiva del Litoral','Marcela Benítez','+54 341 4455012','ventas@ddlitoral.com.ar'),
(2,'Sportline Mayorista','Diego Salinas','+54 11 4789-3321','pedidos@sportlinemayorista.com.ar'),
(3,'Importadora Cancha Uno','Lucía Ferreyra','+54 351 4210987','contacto@canchauno.com.ar');

INSERT INTO talles (id,valor) VALUES
(1,38),
(2,39),
(3,40),
(4,41),
(5,42),
(6,43),
(7,44),
(8,45),
(9,46);

INSERT INTO productos (id,sku,nombre,descripcion,id_marca,id_categoria,id_proveedor,precio_compra,precio_venta,descuento,stock,stock_minimo,material,suela,color,caracteristicas,nuevo,ventas,activo,creado_en) VALUES
(1,'MP-NK-001','Mercurial Vapor 16 Elite','Hecho para quien vive de los arranques cortos. Capellada finísima, ajuste cerrado y una placa que responde rápido en los primeros cinco metros.',1,1,1,255000,389999,0,29,10,'Malla ligera con capa de tracción','Placa Air Zoom con tapones cónicos y de cuchilla','Lima / Negro','Peso aproximado: 210 g\nCuello bajo con ajuste ceñido\nPlantilla de espuma de baja densidad\nUso recomendado: césped natural seco',1,65,1,'2026-10-26 12:00:00'),
(2,'MP-NK-002','Phantom GX 2 Academy','Zona de golpeo texturizada para dar efecto a la pelota y una suela pensada para no clavarse en el sintético.',1,2,1,138000,214999,15,27,10,'Sintético texturizado','Tapones cortos para césped sintético','Blanco / Naranja','Peso aproximado: 285 g\nPlantilla acolchada\nCierre de cordones asimétrico\nUso recomendado: césped sintético',0,42,1,'2026-08-21 12:00:00'),
(3,'MP-NK-003','Tiempo Legend 11 Pro','El clásico de cuero que se amolda al pie después de dos partidos. Toque suave y mucha sensibilidad en la pelota parada.',1,1,1,180000,279999,0,33,10,'Cuero natural de canguro','Placa de nailon con tapones mixtos','Negro / Dorado','Peso aproximado: 245 g\nCuero que se adapta al pie\nLengüeta acolchada\nUso recomendado: césped natural',0,53,1,'2026-08-25 12:00:00'),
(4,'MP-NK-004','Streetgato','Zapatilla de sala con puntera reforzada y suela de goma que agarra en parquet y cemento alisado.',1,4,1,88000,134999,10,52,8,'Sintético con refuerzo en la puntera','Goma plana no marcante','Azul / Blanco','Peso aproximado: 300 g\nSuela no marcante\nPuntera reforzada contra el desgaste\nUso recomendado: parquet y cemento',0,40,1,'2026-08-29 12:00:00'),
(5,'MP-AD-005','Predator Elite','Pensado para el que arma juego y define. La zona de control en el empeine aporta agarre al conducir y al pegarle.',2,1,2,240000,369999,0,24,10,'Capellada con zona de control texturizada','Placa Carbitex con tapones de cuchilla','Blanco / Rojo','Peso aproximado: 230 g\nCollar de calce firme\nPlantilla de cuero sintético\nUso recomendado: césped natural firme',1,72,1,'2026-10-30 12:00:00'),
(6,'MP-AD-006','F50 Pro','Una de las opciones más livianas de la tienda. Buena para extremos y delanteros que viven de la velocidad punta.',2,1,2,212000,329999,12,7,12,'Sintético Fiberskin','Placa Sprintframe de baja densidad','Azul eléctrico / Negro','Peso aproximado: 198 g\nCapellada de una sola pieza\nPlantilla delgada para sentir el piso\nUso recomendado: césped natural seco',0,55,1,'2026-09-06 12:00:00'),
(7,'MP-AD-007','Copa Pure 2 League','Cuero sintético de tacto blando, buena opción para entrenar toda la semana en sintético sin gastar de más.',2,2,2,120000,189999,20,19,10,'Cuero sintético suave','Tapones para césped sintético','Negro / Blanco','Peso aproximado: 270 g\nCosturas reforzadas\nPlantilla acolchada\nUso recomendado: césped sintético',0,48,1,'2026-09-10 12:00:00'),
(8,'MP-AD-008','Top Sala Competition','Calzado de sala con malla interna que ventila y una goma plana que acompaña los giros rápidos.',2,4,2,94000,149999,0,26,8,'Sintético con malla interna','Goma plana de alto agarre','Blanco / Verde','Peso aproximado: 290 g\nMalla interna transpirable\nSuela de goma plana\nUso recomendado: parquet',0,33,1,'2026-09-14 12:00:00'),
(9,'MP-AD-009','Predator Pro SG','Para canchas pesadas y días de lluvia. Los tapones largos de aluminio se clavan en el barro y no te dejan patinar.',2,5,2,228000,359999,8,39,6,'Capellada sintética con textura','Placa mixta con tapones de aluminio','Negro / Lima','Peso aproximado: 320 g\nTapones de aluminio intercambiables\nCordones cubiertos\nUso recomendado: terreno blando',0,29,1,'2026-09-18 12:00:00'),
(10,'MP-PU-010','Future 8 Ultimate','Sin cordones fijos: el ajuste se regula con una banda elástica. Cómodo para quien cambia mucho de dirección.',3,1,3,224000,349999,0,35,10,'Malla adaptable de una pieza','Placa Pebax con tapones cónicos','Turquesa / Negro','Peso aproximado: 215 g\nAjuste regulable en el empeine\nCapellada elástica\nUso recomendado: césped natural',1,59,1,'2026-11-04 12:00:00'),
(11,'MP-PU-011','Ultra 5 Match','Liviano y de colores fuertes. Una suela estable para sintético que no castiga las rodillas.',3,2,3,126000,199999,15,41,10,'Sintético ligero','Tapones cortos para césped sintético','Amarillo / Negro','Peso aproximado: 240 g\nPlantilla cómoda\nCierre de cordones tradicional\nUso recomendado: césped sintético',0,45,1,'2026-09-26 12:00:00'),
(12,'MP-PU-012','King Match TT','Para el fútbol de los martes en cancha dura. Tacos bajos que no se rompen contra el cemento.',3,3,3,98000,159999,0,35,10,'Cuero sintético con costuras reforzadas','Goma con tacos bajos tipo turf','Negro / Blanco','Peso aproximado: 275 g\nCosturas dobles\nTacos de goma de baja altura\nUso recomendado: turf y canchas duras',0,36,1,'2026-09-30 12:00:00'),
(13,'MP-PU-013','Truco III','El modelo de entrada para sala. Simple, resistente y a un precio razonable.',3,4,3,76000,119999,10,24,8,'Sintético','Goma plana','Rojo / Blanco','Peso aproximado: 295 g\nSuela de goma plana\nLengüeta acolchada\nUso recomendado: parquet y cemento',0,31,1,'2026-10-04 12:00:00'),
(14,'MP-MZ-014','Morelia Neo IV Beta Japan','Cuero fino y una horma angosta. Es el botín para quien prioriza la sensación de pelota sobre cualquier otra cosa.',4,1,2,268000,399999,0,5,8,'Cuero de canguro K-Leather','Placa de nailon ligera','Negro / Plata','Peso aproximado: 190 g\nFabricación artesanal en Japón\nHorma angosta\nUso recomendado: césped natural',0,49,1,'2026-10-08 12:00:00'),
(15,'MP-MZ-015','Alpha Select','Versión liviana de la línea Alpha, adaptada para canchas de césped artificial.',4,2,2,148000,229999,10,27,8,'Sintético','Tapones para césped sintético','Blanco / Azul','Peso aproximado: 225 g\nCapellada de una pieza\nPlantilla ligera\nUso recomendado: césped sintético',0,35,1,'2026-10-12 12:00:00'),
(16,'MP-UM-016','Velocita VI Pro','Opción de gama alta de Umbro para jugadores rápidos. Poco peso y un calce que no se mueve dentro del botín.',5,1,3,158000,249999,0,35,8,'Malla sintética','Placa ligera con tapones cónicos','Verde / Negro','Peso aproximado: 220 g\nCollar tipo calcetín\nPlantilla de espuma\nUso recomendado: césped natural',0,27,1,'2026-10-16 12:00:00'),
(17,'MP-NB-017','Tekela V4 Pro','Capellada elástica que se siente como una media y una suela turf de buen agarre en canchas duras.',6,3,3,138000,219999,8,6,8,'Capellada textil elástica','Suela turf de goma','Gris / Lima','Peso aproximado: 235 g\nCapellada tipo media\nPlantilla anatómica\nUso recomendado: turf',0,30,1,'2026-10-20 12:00:00'),
(18,'MP-NB-018','Furon V7 Pro SG','Tapones largos para cancha blanda. Por el momento sin stock; se repone con el próximo pedido al proveedor.',6,5,3,196000,309999,0,0,6,'Capellada de punto Hypoknit','Tapones mixtos de aluminio','Naranja / Negro','Peso aproximado: 300 g\nTapones metálicos\nCapellada de punto\nUso recomendado: terreno blando',0,22,1,'2026-10-24 12:00:00');

INSERT INTO producto_imagenes (id_producto,ruta,orden) VALUES
(1,'img/productos/mp-nk-001-1.png',1),
(1,'img/productos/mp-nk-001-2.png',2),
(1,'img/productos/mp-nk-001-3.png',3),
(2,'img/productos/mp-nk-002-1.png',1),
(2,'img/productos/mp-nk-002-2.png',2),
(2,'img/productos/mp-nk-002-3.png',3),
(3,'img/productos/mp-nk-003-1.png',1),
(3,'img/productos/mp-nk-003-2.png',2),
(3,'img/productos/mp-nk-003-3.png',3),
(4,'img/productos/mp-nk-004-1.png',1),
(4,'img/productos/mp-nk-004-2.png',2),
(4,'img/productos/mp-nk-004-3.png',3),
(5,'img/productos/mp-ad-005-1.png',1),
(5,'img/productos/mp-ad-005-2.png',2),
(5,'img/productos/mp-ad-005-3.png',3),
(5,'img/productos/mp-ad-005-4.png',4),
(5,'img/productos/mp-ad-005-5.png',5),
(6,'img/productos/mp-ad-006-1.png',1),
(6,'img/productos/mp-ad-006-2.png',2),
(6,'img/productos/mp-ad-006-3.png',3),
(7,'img/productos/mp-ad-007-1.png',1),
(7,'img/productos/mp-ad-007-2.png',2),
(7,'img/productos/mp-ad-007-3.png',3),
(8,'img/productos/mp-ad-008-1.png',1),
(8,'img/productos/mp-ad-008-2.png',2),
(9,'img/productos/mp-ad-009-1.png',1),
(9,'img/productos/mp-ad-009-2.png',2),
(9,'img/productos/mp-ad-009-3.png',3),
(10,'img/productos/mp-pu-010-1.png',1),
(10,'img/productos/mp-pu-010-2.png',2),
(10,'img/productos/mp-pu-010-3.png',3),
(11,'img/productos/mp-pu-011-1.png',1),
(11,'img/productos/mp-pu-011-2.png',2),
(11,'img/productos/mp-pu-011-3.png',3),
(12,'img/productos/mp-pu-012-1.png',1),
(12,'img/productos/mp-pu-012-2.png',2),
(12,'img/productos/mp-pu-012-3.png',3),
(13,'img/productos/mp-pu-013-2.png',1),
(13,'img/productos/amp-pu-013-3.png',2),
(13,'img/productos/amp-pu-013-1.jpg',3),
(14,'img/productos/mp-mz-014-1.png',1),
(14,'img/productos/mp-mz-014-2.png',2),
(14,'img/productos/mp-mz-014-3.png',3),
(15,'img/productos/mp-mz-015-1.png',1),
(15,'img/productos/mp-mz-015-3.png',2),
(16,'img/productos/mp-um-016-1.png',1),
(16,'img/productos/mp-um-016-2.png',2),
(16,'img/productos/mp-um-016-3.png',3),
(17,'img/productos/mp-nb-017-1.png',1),
(17,'img/productos/mp-nb-017-2.png',2),
(17,'img/productos/mp-nb-017-3.png',3),
(18,'img/productos/mp-nb-018-1.png',1),
(18,'img/productos/mp-nb-018-2.png',2);

INSERT INTO producto_talles (id_producto,id_talle,stock) VALUES
(1,1,2),
(1,2,8),
(1,3,3),
(1,4,0),
(1,5,0),
(1,6,2),
(1,7,6),
(1,8,5),
(1,9,3),
(2,1,3),
(2,2,3),
(2,3,2),
(2,4,3),
(2,5,0),
(2,6,4),
(2,7,4),
(2,8,5),
(2,9,3),
(3,1,3),
(3,2,5),
(3,3,6),
(3,4,0),
(3,5,8),
(3,6,3),
(3,7,4),
(3,8,4),
(3,9,0),
(4,1,3),
(4,2,6),
(4,3,6),
(4,4,14),
(4,5,5),
(4,6,9),
(4,7,4),
(4,8,2),
(4,9,3),
(5,1,3),
(5,2,0),
(5,3,6),
(5,4,3),
(5,5,4),
(5,6,3),
(5,7,0),
(5,8,2),
(5,9,3),
(6,1,0),
(6,2,1),
(6,3,2),
(6,4,2),
(6,5,1),
(6,6,0),
(6,7,0),
(6,8,1),
(6,9,0),
(7,1,1),
(7,2,4),
(7,3,0),
(7,4,0),
(7,5,0),
(7,6,4),
(7,7,5),
(7,8,5),
(7,9,0),
(8,1,1),
(8,2,2),
(8,3,0),
(8,4,5),
(8,5,0),
(8,6,8),
(8,7,5),
(8,8,3),
(8,9,2),
(9,1,1),
(9,2,0),
(9,3,3),
(9,4,6),
(9,5,5),
(9,6,5),
(9,7,8),
(9,8,8),
(9,9,3),
(10,1,1),
(10,2,5),
(10,3,2),
(10,4,4),
(10,5,8),
(10,6,2),
(10,7,5),
(10,8,6),
(10,9,2),
(11,1,0),
(11,2,4),
(11,3,10),
(11,4,2),
(11,5,2),
(11,6,8),
(11,7,8),
(11,8,5),
(11,9,2),
(12,1,3),
(12,2,4),
(12,3,8),
(12,4,1),
(12,5,5),
(12,6,8),
(12,7,2),
(12,8,4),
(12,9,0),
(13,1,0),
(13,2,4),
(13,3,0),
(13,4,0),
(13,5,8),
(13,6,3),
(13,7,6),
(13,8,3),
(13,9,0),
(14,1,0),
(14,2,0),
(14,3,1),
(14,4,1),
(14,5,2),
(14,6,1),
(14,7,0),
(14,8,0),
(14,9,0),
(15,1,0),
(15,2,5),
(15,3,4),
(15,4,3),
(15,5,6),
(15,6,2),
(15,7,4),
(15,8,3),
(15,9,0),
(16,1,3),
(16,2,4),
(16,3,5),
(16,4,5),
(16,5,8),
(16,6,2),
(16,7,2),
(16,8,5),
(16,9,1),
(17,1,1),
(17,2,0),
(17,3,2),
(17,4,1),
(17,5,1),
(17,6,0),
(17,7,1),
(17,8,0),
(17,9,0),
(18,1,0),
(18,2,0),
(18,3,0),
(18,4,0),
(18,5,0),
(18,6,0),
(18,7,0),
(18,8,0),
(18,9,0);

INSERT INTO favoritos (id_usuario,id_producto) VALUES (3,1),(3,5),(3,10);

INSERT INTO pedidos (id,id_usuario,estado,total,nombre_envio,telefono,direccion,ciudad,provincia,codigo_postal,metodo_pago,creado_en) VALUES
(1,3,'entregado',541998,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-09-14 20:41:00'),
(2,3,'entregado',369999,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-10-02 11:05:00'),
(3,3,'cancelado',279999,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-10-15 16:30:00'),
(4,3,'enviado',425747,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-10-27 09:12:00'),
(5,3,'confirmado',349999,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-11-09 19:48:00'),
(6,3,'pendiente',169999,'Cliente Demo','+54 9 341 5551234','Av. Siempre Viva 742','Rosario','Santa Fe','S2000','Compra de demostración','2026-11-16 22:03:00');

INSERT INTO detalle_pedidos (id_pedido,id_producto,id_talle,nombre_producto,cantidad,precio_unitario) VALUES
(1,1,5,'Mercurial Vapor 16 Elite',1,389999),
(1,7,4,'Copa Pure 2 League',1,151999),
(2,5,6,'Predator Elite',1,369999),
(3,3,7,'Tiempo Legend 11 Pro',1,279999),
(4,2,5,'Phantom GX 2 Academy',1,182749),
(4,4,5,'Streetgato',2,121499),
(5,10,4,'Future 8 Ultimate',1,349999),
(6,11,5,'Ultra 5 Match',1,169999);

INSERT INTO movimientos (id_producto,id_talle,id_usuario,id_pedido,tipo,cantidad,stock_anterior,stock_posterior,fecha,observaciones) VALUES
(1,1,2,NULL,'entrada',2,0,2,'2026-08-18 09:00:00','Ingreso inicial de mercadería'),
(1,2,2,NULL,'entrada',8,0,8,'2026-08-18 09:03:00','Ingreso inicial de mercadería'),
(1,3,2,NULL,'entrada',3,0,3,'2026-08-18 09:06:00','Ingreso inicial de mercadería'),
(1,5,2,NULL,'entrada',1,0,1,'2026-08-18 09:12:00','Ingreso inicial de mercadería'),
(1,6,2,NULL,'entrada',2,0,2,'2026-08-18 09:15:00','Ingreso inicial de mercadería'),
(1,7,2,NULL,'entrada',6,0,6,'2026-08-18 09:18:00','Ingreso inicial de mercadería'),
(1,8,2,NULL,'entrada',5,0,5,'2026-08-18 09:21:00','Ingreso inicial de mercadería'),
(1,9,2,NULL,'entrada',3,0,3,'2026-08-18 09:24:00','Ingreso inicial de mercadería'),
(2,1,2,NULL,'entrada',3,0,3,'2026-08-18 09:27:00','Ingreso inicial de mercadería'),
(2,2,2,NULL,'entrada',3,0,3,'2026-08-18 09:30:00','Ingreso inicial de mercadería'),
(2,3,2,NULL,'entrada',2,0,2,'2026-08-18 09:33:00','Ingreso inicial de mercadería'),
(2,4,2,NULL,'entrada',3,0,3,'2026-08-18 09:36:00','Ingreso inicial de mercadería'),
(2,5,2,NULL,'entrada',1,0,1,'2026-08-18 09:39:00','Ingreso inicial de mercadería'),
(2,6,2,NULL,'entrada',4,0,4,'2026-08-18 09:42:00','Ingreso inicial de mercadería'),
(2,7,2,NULL,'entrada',4,0,4,'2026-08-18 09:45:00','Ingreso inicial de mercadería'),
(2,8,2,NULL,'entrada',5,0,5,'2026-08-18 09:48:00','Ingreso inicial de mercadería'),
(2,9,2,NULL,'entrada',3,0,3,'2026-08-18 09:51:00','Ingreso inicial de mercadería'),
(3,1,2,NULL,'entrada',3,0,3,'2026-08-18 09:54:00','Ingreso inicial de mercadería'),
(3,2,2,NULL,'entrada',5,0,5,'2026-08-18 09:57:00','Ingreso inicial de mercadería'),
(3,3,2,NULL,'entrada',6,0,6,'2026-08-18 10:00:00','Ingreso inicial de mercadería'),
(3,5,2,NULL,'entrada',8,0,8,'2026-08-18 10:06:00','Ingreso inicial de mercadería'),
(3,6,2,NULL,'entrada',3,0,3,'2026-08-18 10:09:00','Ingreso inicial de mercadería'),
(3,7,2,NULL,'entrada',4,0,4,'2026-08-18 10:12:00','Ingreso inicial de mercadería'),
(3,8,2,NULL,'entrada',4,0,4,'2026-08-18 10:15:00','Ingreso inicial de mercadería'),
(4,1,2,NULL,'entrada',3,0,3,'2026-08-18 10:21:00','Ingreso inicial de mercadería'),
(4,2,2,NULL,'entrada',6,0,6,'2026-08-18 10:24:00','Ingreso inicial de mercadería'),
(4,3,2,NULL,'entrada',6,0,6,'2026-08-18 10:27:00','Ingreso inicial de mercadería'),
(4,4,2,NULL,'entrada',8,0,8,'2026-08-18 10:30:00','Ingreso inicial de mercadería'),
(4,5,2,NULL,'entrada',7,0,7,'2026-08-18 10:33:00','Ingreso inicial de mercadería'),
(4,6,2,NULL,'entrada',3,0,3,'2026-08-18 10:36:00','Ingreso inicial de mercadería'),
(4,7,2,NULL,'entrada',4,0,4,'2026-08-18 10:39:00','Ingreso inicial de mercadería'),
(4,8,2,NULL,'entrada',2,0,2,'2026-08-18 10:42:00','Ingreso inicial de mercadería'),
(4,9,2,NULL,'entrada',3,0,3,'2026-08-18 10:45:00','Ingreso inicial de mercadería'),
(5,1,2,NULL,'entrada',3,0,3,'2026-08-18 10:48:00','Ingreso inicial de mercadería'),
(5,3,2,NULL,'entrada',6,0,6,'2026-08-18 10:54:00','Ingreso inicial de mercadería'),
(5,4,2,NULL,'entrada',3,0,3,'2026-08-18 10:57:00','Ingreso inicial de mercadería'),
(5,5,2,NULL,'entrada',4,0,4,'2026-08-18 11:00:00','Ingreso inicial de mercadería'),
(5,6,2,NULL,'entrada',4,0,4,'2026-08-18 11:03:00','Ingreso inicial de mercadería'),
(5,8,2,NULL,'entrada',2,0,2,'2026-08-18 11:09:00','Ingreso inicial de mercadería'),
(5,9,2,NULL,'entrada',3,0,3,'2026-08-18 11:12:00','Ingreso inicial de mercadería'),
(6,2,2,NULL,'entrada',1,0,1,'2026-08-18 11:18:00','Ingreso inicial de mercadería'),
(6,3,2,NULL,'entrada',2,0,2,'2026-08-18 11:21:00','Ingreso inicial de mercadería'),
(6,4,2,NULL,'entrada',2,0,2,'2026-08-18 11:24:00','Ingreso inicial de mercadería'),
(6,5,2,NULL,'entrada',1,0,1,'2026-08-18 11:27:00','Ingreso inicial de mercadería'),
(6,8,2,NULL,'entrada',1,0,1,'2026-08-18 11:36:00','Ingreso inicial de mercadería'),
(7,1,2,NULL,'entrada',1,0,1,'2026-08-18 11:42:00','Ingreso inicial de mercadería'),
(7,2,2,NULL,'entrada',4,0,4,'2026-08-18 11:45:00','Ingreso inicial de mercadería'),
(7,4,2,NULL,'entrada',1,0,1,'2026-08-18 11:51:00','Ingreso inicial de mercadería'),
(7,6,2,NULL,'entrada',4,0,4,'2026-08-18 11:57:00','Ingreso inicial de mercadería'),
(7,7,2,NULL,'entrada',5,0,5,'2026-08-18 12:00:00','Ingreso inicial de mercadería'),
(7,8,2,NULL,'entrada',5,0,5,'2026-08-18 12:03:00','Ingreso inicial de mercadería'),
(8,1,2,NULL,'entrada',1,0,1,'2026-08-18 12:09:00','Ingreso inicial de mercadería'),
(8,2,2,NULL,'entrada',2,0,2,'2026-08-18 12:12:00','Ingreso inicial de mercadería'),
(8,4,2,NULL,'entrada',5,0,5,'2026-08-18 12:18:00','Ingreso inicial de mercadería'),
(8,6,2,NULL,'entrada',8,0,8,'2026-08-18 12:24:00','Ingreso inicial de mercadería'),
(8,7,2,NULL,'entrada',5,0,5,'2026-08-18 12:27:00','Ingreso inicial de mercadería'),
(8,8,2,NULL,'entrada',3,0,3,'2026-08-18 12:30:00','Ingreso inicial de mercadería'),
(8,9,2,NULL,'entrada',2,0,2,'2026-08-18 12:33:00','Ingreso inicial de mercadería'),
(9,1,2,NULL,'entrada',1,0,1,'2026-08-18 12:36:00','Ingreso inicial de mercadería'),
(9,3,2,NULL,'entrada',3,0,3,'2026-08-18 12:42:00','Ingreso inicial de mercadería'),
(9,4,2,NULL,'entrada',6,0,6,'2026-08-18 12:45:00','Ingreso inicial de mercadería'),
(9,5,2,NULL,'entrada',5,0,5,'2026-08-18 12:48:00','Ingreso inicial de mercadería'),
(9,6,2,NULL,'entrada',5,0,5,'2026-08-18 12:51:00','Ingreso inicial de mercadería'),
(9,7,2,NULL,'entrada',8,0,8,'2026-08-18 12:54:00','Ingreso inicial de mercadería'),
(9,8,2,NULL,'entrada',8,0,8,'2026-08-18 12:57:00','Ingreso inicial de mercadería'),
(9,9,2,NULL,'entrada',3,0,3,'2026-08-18 13:00:00','Ingreso inicial de mercadería'),
(10,1,2,NULL,'entrada',1,0,1,'2026-08-18 13:03:00','Ingreso inicial de mercadería'),
(10,2,2,NULL,'entrada',5,0,5,'2026-08-18 13:06:00','Ingreso inicial de mercadería'),
(10,3,2,NULL,'entrada',2,0,2,'2026-08-18 13:09:00','Ingreso inicial de mercadería'),
(10,4,2,NULL,'entrada',5,0,5,'2026-08-18 13:12:00','Ingreso inicial de mercadería'),
(10,5,2,NULL,'entrada',5,0,5,'2026-08-18 13:15:00','Ingreso inicial de mercadería'),
(10,6,2,NULL,'entrada',2,0,2,'2026-08-18 13:18:00','Ingreso inicial de mercadería'),
(10,7,2,NULL,'entrada',5,0,5,'2026-08-18 13:21:00','Ingreso inicial de mercadería'),
(10,8,2,NULL,'entrada',6,0,6,'2026-08-18 13:24:00','Ingreso inicial de mercadería'),
(10,9,2,NULL,'entrada',2,0,2,'2026-08-18 13:27:00','Ingreso inicial de mercadería'),
(11,2,2,NULL,'entrada',4,0,4,'2026-08-18 13:33:00','Ingreso inicial de mercadería'),
(11,3,2,NULL,'entrada',6,0,6,'2026-08-18 13:36:00','Ingreso inicial de mercadería'),
(11,4,2,NULL,'entrada',2,0,2,'2026-08-18 13:39:00','Ingreso inicial de mercadería'),
(11,5,2,NULL,'entrada',3,0,3,'2026-08-18 13:42:00','Ingreso inicial de mercadería'),
(11,6,2,NULL,'entrada',8,0,8,'2026-08-18 13:45:00','Ingreso inicial de mercadería'),
(11,7,2,NULL,'entrada',8,0,8,'2026-08-18 13:48:00','Ingreso inicial de mercadería'),
(11,8,2,NULL,'entrada',5,0,5,'2026-08-18 13:51:00','Ingreso inicial de mercadería'),
(11,9,2,NULL,'entrada',2,0,2,'2026-08-18 13:54:00','Ingreso inicial de mercadería'),
(12,1,2,NULL,'entrada',3,0,3,'2026-08-18 13:57:00','Ingreso inicial de mercadería'),
(12,2,2,NULL,'entrada',4,0,4,'2026-08-18 14:00:00','Ingreso inicial de mercadería'),
(12,3,2,NULL,'entrada',8,0,8,'2026-08-18 14:03:00','Ingreso inicial de mercadería'),
(12,4,2,NULL,'entrada',2,0,2,'2026-08-18 14:06:00','Ingreso inicial de mercadería'),
(12,5,2,NULL,'entrada',5,0,5,'2026-08-18 14:09:00','Ingreso inicial de mercadería'),
(12,6,2,NULL,'entrada',8,0,8,'2026-08-18 14:12:00','Ingreso inicial de mercadería'),
(12,7,2,NULL,'entrada',2,0,2,'2026-08-18 14:15:00','Ingreso inicial de mercadería'),
(12,8,2,NULL,'entrada',4,0,4,'2026-08-18 14:18:00','Ingreso inicial de mercadería'),
(13,2,2,NULL,'entrada',4,0,4,'2026-08-18 14:27:00','Ingreso inicial de mercadería'),
(13,5,2,NULL,'entrada',8,0,8,'2026-08-18 14:36:00','Ingreso inicial de mercadería'),
(13,6,2,NULL,'entrada',3,0,3,'2026-08-18 14:39:00','Ingreso inicial de mercadería'),
(13,7,2,NULL,'entrada',6,0,6,'2026-08-18 14:42:00','Ingreso inicial de mercadería'),
(13,8,2,NULL,'entrada',3,0,3,'2026-08-18 14:45:00','Ingreso inicial de mercadería'),
(14,3,2,NULL,'entrada',1,0,1,'2026-08-18 14:57:00','Ingreso inicial de mercadería'),
(14,4,2,NULL,'entrada',1,0,1,'2026-08-18 15:00:00','Ingreso inicial de mercadería'),
(14,5,2,NULL,'entrada',2,0,2,'2026-08-18 15:03:00','Ingreso inicial de mercadería'),
(14,6,2,NULL,'entrada',1,0,1,'2026-08-18 15:06:00','Ingreso inicial de mercadería'),
(15,2,2,NULL,'entrada',5,0,5,'2026-08-18 15:21:00','Ingreso inicial de mercadería'),
(15,3,2,NULL,'entrada',4,0,4,'2026-08-18 15:24:00','Ingreso inicial de mercadería'),
(15,4,2,NULL,'entrada',3,0,3,'2026-08-18 15:27:00','Ingreso inicial de mercadería'),
(15,5,2,NULL,'entrada',6,0,6,'2026-08-18 15:30:00','Ingreso inicial de mercadería'),
(15,6,2,NULL,'entrada',3,0,3,'2026-08-18 15:33:00','Ingreso inicial de mercadería'),
(15,7,2,NULL,'entrada',4,0,4,'2026-08-18 15:36:00','Ingreso inicial de mercadería'),
(15,8,2,NULL,'entrada',3,0,3,'2026-08-18 15:39:00','Ingreso inicial de mercadería'),
(16,1,2,NULL,'entrada',3,0,3,'2026-08-18 15:45:00','Ingreso inicial de mercadería'),
(16,2,2,NULL,'entrada',4,0,4,'2026-08-18 15:48:00','Ingreso inicial de mercadería'),
(16,3,2,NULL,'entrada',5,0,5,'2026-08-18 15:51:00','Ingreso inicial de mercadería'),
(16,4,2,NULL,'entrada',5,0,5,'2026-08-18 15:54:00','Ingreso inicial de mercadería'),
(16,5,2,NULL,'entrada',8,0,8,'2026-08-18 15:57:00','Ingreso inicial de mercadería'),
(16,6,2,NULL,'entrada',2,0,2,'2026-08-18 16:00:00','Ingreso inicial de mercadería'),
(16,7,2,NULL,'entrada',2,0,2,'2026-08-18 16:03:00','Ingreso inicial de mercadería'),
(16,8,2,NULL,'entrada',5,0,5,'2026-08-18 16:06:00','Ingreso inicial de mercadería'),
(16,9,2,NULL,'entrada',1,0,1,'2026-08-18 16:09:00','Ingreso inicial de mercadería'),
(17,1,2,NULL,'entrada',1,0,1,'2026-08-18 16:12:00','Ingreso inicial de mercadería'),
(17,3,2,NULL,'entrada',2,0,2,'2026-08-18 16:18:00','Ingreso inicial de mercadería'),
(17,4,2,NULL,'entrada',1,0,1,'2026-08-18 16:21:00','Ingreso inicial de mercadería'),
(17,5,2,NULL,'entrada',1,0,1,'2026-08-18 16:24:00','Ingreso inicial de mercadería'),
(17,7,2,NULL,'entrada',1,0,1,'2026-08-18 16:30:00','Ingreso inicial de mercadería'),
(1,5,3,1,'salida',1,1,0,'2026-09-14 20:41:00','Venta - Pedido #1'),
(7,4,3,1,'salida',1,1,0,'2026-09-14 20:41:00','Venta - Pedido #1'),
(4,4,2,NULL,'entrada',6,8,14,'2026-09-22 10:00:00','Reposición de stock - proveedor Distribuidora Deportiva del Litoral'),
(4,6,2,NULL,'entrada',6,3,9,'2026-09-22 10:05:00','Reposición de stock - proveedor Distribuidora Deportiva del Litoral'),
(5,6,3,2,'salida',1,4,3,'2026-10-02 11:05:00','Venta - Pedido #2'),
(11,3,2,NULL,'entrada',4,6,10,'2026-10-12 12:40:00','Reposición de stock - proveedor Importadora Cancha Uno'),
(3,7,3,3,'salida',1,4,3,'2026-10-15 16:30:00','Venta - Pedido #3'),
(3,7,1,3,'entrada',1,3,4,'2026-10-16 10:20:00','Cancelación de pedido #3'),
(10,5,1,NULL,'entrada',3,5,8,'2026-10-20 11:15:00','Reposición de stock - proveedor Importadora Cancha Uno'),
(2,5,3,4,'salida',1,1,0,'2026-10-27 09:12:00','Venta - Pedido #4'),
(4,5,3,4,'salida',2,7,5,'2026-10-27 09:12:00','Venta - Pedido #4'),
(12,4,1,NULL,'ajuste',1,2,1,'2026-11-03 17:30:00','Ajuste por recuento físico de depósito'),
(10,4,3,5,'salida',1,5,4,'2026-11-09 19:48:00','Venta - Pedido #5'),
(15,6,2,NULL,'salida',1,3,2,'2026-11-12 09:50:00','Par de exhibición retirado del depósito'),
(11,5,3,6,'salida',1,3,2,'2026-11-16 22:03:00','Venta - Pedido #6');
