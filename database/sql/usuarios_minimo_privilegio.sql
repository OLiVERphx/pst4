-- ============================================================================
-- Usuarios MySQL de mínimo privilegio para Smartphone World
-- ============================================================================
-- Ejecutar UNA vez como root (HeidiSQL en Laragon, o la consola de MySQL).
-- Antes de ejecutar, cambia las dos contraseñas de ejemplo por unas fuertes.
--
--   sw_app       -> el que usa la aplicación en el .env (DB_USERNAME).
--                   Solo lee y escribe filas: no puede borrar ni crear tablas,
--                   ni crear usuarios, ni leer otras bases de datos.
--   sw_migrador  -> solo para "php artisan migrate --database=mysql_migraciones"
--                   (DB_MIGRATION_USERNAME). Puede cambiar el esquema de esta
--                   base, pero no tiene privilegios globales ni GRANT.
--
-- Si alguien obtiene la contraseña de la aplicación (por ejemplo, leyendo el
-- .env), no puede hacer DROP TABLE ni tocar otras bases.
--
-- En un hosting donde la aplicación se conecta desde otra máquina (Railway, VPS
-- con BD separada), cambia 'localhost' por la IP del servidor de la aplicación
-- o por '%'.
-- ============================================================================

CREATE USER IF NOT EXISTS 'sw_app'@'localhost' IDENTIFIED BY 'CAMBIAR_clave_app';
CREATE USER IF NOT EXISTS 'sw_migrador'@'localhost' IDENTIFIED BY 'CAMBIAR_clave_migrador';

-- Aplicación: solo manipulación de datos.
-- LOCK TABLES lo necesita mysqldump para los respaldos programados.
GRANT SELECT, INSERT, UPDATE, DELETE, LOCK TABLES
    ON smartphoneworld.* TO 'sw_app'@'localhost';

-- Migraciones: estructura de esta base únicamente.
GRANT ALL PRIVILEGES ON smartphoneworld.* TO 'sw_migrador'@'localhost';

FLUSH PRIVILEGES;

-- Comprobación: debe listar solo los permisos de arriba.
SHOW GRANTS FOR 'sw_app'@'localhost';
SHOW GRANTS FOR 'sw_migrador'@'localhost';

-- ----------------------------------------------------------------------------
-- Opcional, MUY recomendado en Laragon: root viene sin contraseña.
-- Descomenta, pon una clave y ejecuta. Después actualiza HeidiSQL con esa clave.
-- ----------------------------------------------------------------------------
-- ALTER USER 'root'@'localhost' IDENTIFIED BY 'CAMBIAR_clave_root';
