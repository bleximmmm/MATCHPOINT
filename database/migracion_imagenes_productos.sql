USE matchpoint;

START TRANSACTION;

DELETE FROM producto_imagenes
WHERE (id_producto = 8 AND orden = 3 AND ruta LIKE 'img/productos/mp-ad-008-3.%')
   OR (id_producto = 15 AND orden = 2 AND ruta LIKE 'img/productos/mp-mz-015-2.%')
   OR (id_producto = 18 AND orden = 3 AND ruta LIKE 'img/productos/mp-nb-018-3.%');

CREATE TEMPORARY TABLE migracion_imagenes (
  id_producto INT UNSIGNED NOT NULL,
  orden TINYINT UNSIGNED NOT NULL,
  ruta VARCHAR(255) NOT NULL,
  PRIMARY KEY (id_producto, orden)
) ENGINE=InnoDB;

INSERT INTO migracion_imagenes (id_producto, orden, ruta) VALUES
(1,1,'img/productos/mp-nk-001-1.png'),
(1,2,'img/productos/mp-nk-001-2.png'),
(1,3,'img/productos/mp-nk-001-3.png'),
(2,1,'img/productos/mp-nk-002-1.png'),
(2,2,'img/productos/mp-nk-002-2.png'),
(2,3,'img/productos/mp-nk-002-3.png'),
(3,1,'img/productos/mp-nk-003-1.png'),
(3,2,'img/productos/mp-nk-003-2.png'),
(3,3,'img/productos/mp-nk-003-3.png'),
(4,1,'img/productos/mp-nk-004-1.png'),
(4,2,'img/productos/mp-nk-004-2.png'),
(4,3,'img/productos/mp-nk-004-3.png'),
(5,1,'img/productos/mp-ad-005-1.png'),
(5,2,'img/productos/mp-ad-005-2.png'),
(5,3,'img/productos/mp-ad-005-3.png'),
(5,4,'img/productos/mp-ad-005-4.png'),
(5,5,'img/productos/mp-ad-005-5.png'),
(6,1,'img/productos/mp-ad-006-1.png'),
(6,2,'img/productos/mp-ad-006-2.png'),
(6,3,'img/productos/mp-ad-006-3.png'),
(7,1,'img/productos/mp-ad-007-1.png'),
(7,2,'img/productos/mp-ad-007-2.png'),
(7,3,'img/productos/mp-ad-007-3.png'),
(8,1,'img/productos/mp-ad-008-1.png'),
(8,2,'img/productos/mp-ad-008-2.png'),
(9,1,'img/productos/mp-ad-009-1.png'),
(9,2,'img/productos/mp-ad-009-2.png'),
(9,3,'img/productos/mp-ad-009-3.png'),
(10,1,'img/productos/mp-pu-010-1.png'),
(10,2,'img/productos/mp-pu-010-2.png'),
(10,3,'img/productos/mp-pu-010-3.png'),
(11,1,'img/productos/mp-pu-011-1.png'),
(11,2,'img/productos/mp-pu-011-2.png'),
(11,3,'img/productos/mp-pu-011-3.png'),
(12,1,'img/productos/mp-pu-012-1.png'),
(12,2,'img/productos/mp-pu-012-2.png'),
(12,3,'img/productos/mp-pu-012-3.png'),
(13,1,'img/productos/mp-pu-013-2.png'),
(13,2,'img/productos/amp-pu-013-3.png'),
(13,3,'img/productos/amp-pu-013-1.jpg'),
(14,1,'img/productos/mp-mz-014-1.png'),
(14,2,'img/productos/mp-mz-014-2.png'),
(14,3,'img/productos/mp-mz-014-3.png'),
(15,1,'img/productos/mp-mz-015-1.png'),
(15,3,'img/productos/mp-mz-015-3.png'),
(16,1,'img/productos/mp-um-016-1.png'),
(16,2,'img/productos/mp-um-016-2.png'),
(16,3,'img/productos/mp-um-016-3.png'),
(17,1,'img/productos/mp-nb-017-1.png'),
(17,2,'img/productos/mp-nb-017-2.png'),
(17,3,'img/productos/mp-nb-017-3.png'),
(18,1,'img/productos/mp-nb-018-1.png'),
(18,2,'img/productos/mp-nb-018-2.png');

UPDATE producto_imagenes AS pi
JOIN migracion_imagenes AS mi
  ON mi.id_producto = pi.id_producto AND mi.orden = pi.orden
SET pi.ruta = mi.ruta
WHERE pi.ruta LIKE 'img/productos/%';

UPDATE producto_imagenes
SET orden = 2
WHERE id_producto = 15 AND orden = 3 AND ruta = 'img/productos/mp-mz-015-3.png';

DROP TEMPORARY TABLE migracion_imagenes;

COMMIT;
