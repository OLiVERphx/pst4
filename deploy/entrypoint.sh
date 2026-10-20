#!/bin/sh
# Arranque del contenedor: espera la base, migra, inicializa y levanta nginx + php-fpm + planificador.
set -eu

cd /app

# El volumen se monta como root: la aplicación (www-data) debe poder escribir en él
mkdir -p storage/app/private/receipts storage/app/backup-temp storage/app/backups-programados \
         storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

# Estos comandos corren como www-data para que los archivos que crean le pertenezcan
artisan() { su -s /bin/sh www-data -c "php artisan $*"; }

# Falta algo imprescindible: mejor fallar con un mensaje claro que arrancar a medias
for var in APP_KEY DB_HOST DB_DATABASE DB_USERNAME DB_PASSWORD; do
    eval "valor=\${$var:-}"
    if [ -z "$valor" ]; then
        echo "ERROR: falta la variable $var en el servicio." >&2
        exit 1
    fi
done

echo "Esperando la base de datos en ${DB_HOST}..."
intentos=0
until php -r '
    try {
        new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: "3306").";dbname=".getenv("DB_DATABASE"),
                getenv("DB_USERNAME"), getenv("DB_PASSWORD"), [PDO::ATTR_TIMEOUT => 3]);
    } catch (Throwable $e) { fwrite(STDERR, $e->getMessage().PHP_EOL); exit(1); }
'; do
    intentos=$((intentos + 1))
    if [ "$intentos" -ge 30 ]; then
        echo "ERROR: la base de datos no responde (revisa DB_HOST, DB_USERNAME y DB_PASSWORD)." >&2
        exit 1
    fi
    sleep 3
done

# Migraciones con el usuario que puede cambiar el esquema (sw_migrador), no con el de la aplicación
echo "Migrando..."
artisan migrate --force --database=mysql_migraciones

echo "Inicializando..."
artisan produccion:inicializar

artisan storage:link --force >/dev/null 2>&1 || true

# Cachés de producción (después de migrar e inicializar: dependen del entorno ya completo)
artisan config:cache
artisan route:cache
artisan view:cache
artisan event:cache

# nginx escucha en el puerto que asigne la plataforma. Por IPv6 solo si el contenedor tiene
# (sin IPv6, "listen [::]" hace que nginx no arranque)
export PORT="${PORT:-8080}"
if [ -s /proc/net/if_inet6 ]; then
    export LISTEN_IPV6="listen [::]:${PORT} default_server;"
else
    export LISTEN_IPV6=""
fi
envsubst '${PORT} ${LISTEN_IPV6}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "Listo. Escuchando en el puerto ${PORT}."
exec /usr/bin/supervisord -c /etc/supervisor/tienda.conf
