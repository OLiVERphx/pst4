-- ============================================================================
-- Railway, paso 1 (ANTES del primer despliegue de la tienda): base y usuarios
-- ============================================================================
-- Ejecutar como root contra el servicio MySQL de Railway (ver deploy/RAILWAY.md).
-- Cambia las dos contraseñas por otras generadas, SOLO con letras sin tilde,
-- números, guiones y guion bajo (sin ñ). No guardes este archivo con las
-- contraseñas: después ejecuta  git checkout database/sql/railway/
--
--   sw_app       -> usuario de la tienda (DB_USERNAME): solo lee y escribe filas
--   sw_migrador  -> solo para las migraciones (DB_MIGRATION_USERNAME)
--
-- En Railway la tienda y la base son contenedores distintos, por eso el usuario
-- es '%' (cualquier origen) y no 'localhost'. La base no es accesible desde
-- internet si NO activas el "TCP Proxy" público del servicio MySQL (o lo apagas
-- al terminar este paso).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS smartphoneworld CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'sw_app'@'%' IDENTIFIED BY 'CAMBIAR_clave_app';
CREATE USER IF NOT EXISTS 'sw_migrador'@'%' IDENTIFIED BY 'CAMBIAR_clave_migrador';

-- LOCK TABLES lo necesita mysqldump para los respaldos programados
GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES ON smartphoneworld.* TO 'sw_app'@'%';
GRANT ALL PRIVILEGES ON smartphoneworld.* TO 'sw_migrador'@'%';

FLUSH PRIVILEGES;

SHOW GRANTS FOR 'sw_app'@'%';
SHOW GRANTS FOR 'sw_migrador'@'%';
