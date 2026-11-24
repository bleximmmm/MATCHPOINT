USE matchpoint;

DROP TRIGGER IF EXISTS trg_pedidos_devolver_stock;

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
