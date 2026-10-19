-- ============================================================================
-- Usuario de solo lectura para el microservicio de analítica (ml-service): sw_ml
-- ============================================================================
-- Ejecutar como root. Cambia la contraseña de ejemplo por una SOLO con letras sin
-- tilde, números y guiones (sin ñ: ver el problema de codificación en Windows).
--
-- Solo puede LEER las columnas exactas que usan sus consultas (permisos por columna).
-- No ve nombres de clientes, cédulas, teléfonos, direcciones, pagos ni comprobantes.
-- ============================================================================

CREATE USER IF NOT EXISTS 'sw_ml'@'localhost' IDENTIFIED BY 'CAMBIAR-clave-ml';

GRANT SELECT (id, usuario_id, estado, total, created_at, deleted_at) ON smartphoneworld.pedidos TO 'sw_ml'@'localhost';
GRANT SELECT (pedido_id, producto_id, cantidad) ON smartphoneworld.items_pedido TO 'sw_ml'@'localhost';
GRANT SELECT (id, nombre, stock, stock_minimo, activo, deleted_at) ON smartphoneworld.products TO 'sw_ml'@'localhost';
-- Solo lo necesario para saber si el comprador es un cliente
GRANT SELECT (id, rol_id) ON smartphoneworld.users TO 'sw_ml'@'localhost';
GRANT SELECT (id, nombre) ON smartphoneworld.roles TO 'sw_ml'@'localhost';

FLUSH PRIVILEGES;
SHOW GRANTS FOR 'sw_ml'@'localhost';
