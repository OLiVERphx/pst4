-- ============================================================================
-- Railway, paso 2 (DESPUÉS del primer despliegue de la tienda): usuario sw_ml
-- ============================================================================
-- Las tablas ya deben existir (las crea la tienda al arrancar): los permisos son
-- por columna. Ejecutar como root. Cambia la contraseña por una generada, solo
-- con letras sin tilde, números y guiones. Después: git checkout database/sql/railway/
--
-- sw_ml solo puede LEER las columnas que usa el servicio de analítica. No ve
-- nombres, cédulas, teléfonos, direcciones de entrega, pagos ni comprobantes.
-- ============================================================================

CREATE USER IF NOT EXISTS 'sw_ml'@'%' IDENTIFIED BY 'CAMBIAR-clave-ml';

GRANT SELECT (id, usuario_id, estado, total, created_at, deleted_at) ON smartphoneworld.pedidos TO 'sw_ml'@'%';
GRANT SELECT (pedido_id, producto_id, cantidad) ON smartphoneworld.items_pedido TO 'sw_ml'@'%';
GRANT SELECT (id, nombre, stock, stock_minimo, activo, deleted_at) ON smartphoneworld.products TO 'sw_ml'@'%';
-- Solo lo necesario para saber si el comprador es un cliente
GRANT SELECT (id, rol_id) ON smartphoneworld.users TO 'sw_ml'@'%';
GRANT SELECT (id, nombre) ON smartphoneworld.roles TO 'sw_ml'@'%';

FLUSH PRIVILEGES;
SHOW GRANTS FOR 'sw_ml'@'%';
