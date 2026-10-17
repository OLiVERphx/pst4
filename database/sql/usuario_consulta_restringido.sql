-- ============================================================================
-- Usuario de consulta restringido: sw_consulta
-- ============================================================================
-- Ejecutar como root (HeidiSQL: Archivo > Cargar archivo SQL, luego F9).
-- Antes, cambia la contraseña de ejemplo CAMBIAR_clave_consulta.
--
-- Este usuario NO tiene SELECT sobre ninguna tabla. Solo puede ejecutar tres
-- procedimientos almacenados con parámetros:
--   * SELECT * FROM users WHERE 1=1        -> "SELECT command denied"
--   * CALL sp_consultar_pedido("' OR 1=1 --") -> 0 filas: el parámetro se trata
--     como un dato, nunca como código SQL (consulta parametrizada).
--
-- Los procedimientos se ejecutan con los permisos de quien los creó (SQL
-- SECURITY DEFINER), devuelven solo columnas no sensibles y un número limitado
-- de filas. Es el mismo principio que usa la aplicación (consultas
-- parametrizadas), aplicado dentro de la base de datos.
-- ============================================================================

USE smartphoneworld;

DROP PROCEDURE IF EXISTS sp_consultar_pedido;
DROP PROCEDURE IF EXISTS sp_buscar_producto;
DROP PROCEDURE IF EXISTS sp_ventas_del_mes;

DELIMITER $$

-- Estado de UN pedido a partir de su número exacto (ej. SW-2026-1352).
-- No expone datos del cliente (nombre, teléfono, dirección).
CREATE PROCEDURE sp_consultar_pedido(IN p_numero VARCHAR(30))
    READS SQL DATA
    SQL SECURITY DEFINER
BEGIN
    SELECT numero_pedido, estado, tipo_entrega, total, created_at
    FROM pedidos
    WHERE numero_pedido = p_numero
      AND deleted_at IS NULL
    LIMIT 1;
END$$

-- Productos activos cuyo nombre contiene el texto (mínimo 3 letras, máximo 20
-- resultados). Los comodines % y _ se escapan: no sirven para listar todo.
CREATE PROCEDURE sp_buscar_producto(IN p_texto VARCHAR(50))
    READS SQL DATA
    SQL SECURITY DEFINER
BEGIN
    IF p_texto IS NULL OR CHAR_LENGTH(TRIM(p_texto)) < 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Escribe al menos 3 letras del producto';
    END IF;

    SELECT codigo, nombre, precio_detal, (stock - stock_reservado) AS disponible
    FROM products
    WHERE activo = 1
      AND deleted_at IS NULL
      AND nombre LIKE CONCAT('%', REPLACE(REPLACE(REPLACE(TRIM(p_texto), '\\', '\\\\'), '%', '\\%'), '_', '\\_'), '%')
    ORDER BY nombre
    LIMIT 20;
END$$

-- Totales de ventas confirmadas de un mes (solo cifras agregadas, sin detalle).
CREATE PROCEDURE sp_ventas_del_mes(IN p_anio INT, IN p_mes INT)
    READS SQL DATA
    SQL SECURITY DEFINER
BEGIN
    IF p_mes IS NULL OR p_mes < 1 OR p_mes > 12 OR p_anio IS NULL OR p_anio < 2000 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Mes o año inválido';
    END IF;

    SELECT COUNT(*) AS pedidos, COALESCE(SUM(total), 0) AS total_usd
    FROM pedidos
    WHERE estado IN ('pago_confirmado', 'pago_confirmado_retirar', 'en_camino', 'entregado', 'completado')
      AND YEAR(created_at) = p_anio
      AND MONTH(created_at) = p_mes
      AND deleted_at IS NULL;
END$$

DELIMITER ;

CREATE USER IF NOT EXISTS 'sw_consulta'@'localhost' IDENTIFIED BY 'CAMBIAR_clave_consulta';

-- Solo EXECUTE sobre estos tres procedimientos. Ningún SELECT, INSERT, UPDATE...
GRANT EXECUTE ON PROCEDURE smartphoneworld.sp_consultar_pedido TO 'sw_consulta'@'localhost';
GRANT EXECUTE ON PROCEDURE smartphoneworld.sp_buscar_producto TO 'sw_consulta'@'localhost';
GRANT EXECUTE ON PROCEDURE smartphoneworld.sp_ventas_del_mes TO 'sw_consulta'@'localhost';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'sw_consulta'@'localhost';

-- ============================================================================
-- Demostración: conéctate en HeidiSQL como sw_consulta y ejecuta una a una
-- ============================================================================
-- SELECT * FROM users WHERE 1=1;                 -- ERROR 1142: SELECT command denied
-- SELECT * FROM pedidos;                         -- ERROR 1142: SELECT command denied
-- DELETE FROM pedidos;                           -- ERROR 1142: DELETE command denied
-- CALL sp_consultar_pedido('SW-2026-1352');      -- 1 fila (usa un número real de tu base)
-- CALL sp_consultar_pedido("' OR 1=1 --");       -- 0 filas: la inyección no funciona
-- CALL sp_consultar_pedido("x' OR '1'='1");      -- 0 filas
-- CALL sp_buscar_producto('aud');                -- productos con "aud" en el nombre
-- CALL sp_buscar_producto('%%%');                -- 0 filas: el comodín se trata como texto
-- CALL sp_ventas_del_mes(2026, 10);              -- solo totales del mes
