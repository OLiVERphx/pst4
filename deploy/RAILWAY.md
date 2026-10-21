# Despliegue en Railway

Tres servicios en un mismo proyecto de Railway:

| Servicio | Qué es | Público |
|---|---|---|
| **MySQL** | Base de datos (plantilla de Railway) | No |
| **web** | Tienda y panel (Laravel). Un solo contenedor: nginx + php-fpm + planificador de tareas | Sí, con HTTPS |
| **ml** | Analítica predictiva (`ml-service`, Python) | No: solo red privada |

Los nombres importan: las variables de abajo usan `MySQL` y `ml`. Si les pones otro nombre, cámbialo en las referencias.

> **Costo.** Railway cobra por RAM y CPU usadas. Antes de empezar, en *Settings → Usage* del proyecto pon un **límite máximo de gasto** (por ejemplo 10 USD): sin él, Railway sigue cobrando lo que pase del crédito.

## 0. Qué necesitas a mano

Genera estos valores (solo letras sin tilde, números, `-` y `_`; **sin ñ**) y guárdalos en un gestor de contraseñas o un bloc de notas privado:

| Valor | Cómo generarlo |
|---|---|
| `APP_KEY` | En tu PC: `php artisan key:generate --show` (copia el resultado, empieza con `base64:`). **Usa una clave nueva, no la de tu `.env` local.** Si la cambias después de cargar datos reales, cédulas y teléfonos dejan de poder leerse |
| Clave de `sw_app`, `sw_migrador`, `sw_ml` | `python -c "import secrets; print(secrets.token_urlsafe(24))"` (una distinta para cada usuario) |
| `ML_API_KEY` | `python -c "import secrets; print(secrets.token_urlsafe(32))"` |
| `BACKUP_ARCHIVE_PASSWORD` | Igual que arriba. **Guárdala también fuera de Railway**: sin ella no se abren los respaldos |
| `ADMIN_PASSWORD` | Mínimo 12 caracteres con letras y números |

## 1. Proyecto y base de datos

1. En Railway: **New Project → Deploy from GitHub repo** → elige el repositorio (o el fork). Si lo despliega solo, no importa: va a fallar hasta el paso 3.
2. En el mismo proyecto: **+ New → Database → Add MySQL**. Dale el nombre `MySQL`.
3. Crea la base y los usuarios (se hace una sola vez):
   1. En el servicio MySQL → **Settings → Networking → TCP Proxy**: actívalo (apunta el host y el puerto que muestra). Es solo para este paso.
   2. En HeidiSQL: nueva sesión, tipo *MySQL (TCP/IP)*, host y puerto del proxy, usuario `root` y la contraseña de la variable `MYSQL_ROOT_PASSWORD` del servicio MySQL.
   3. Abre `database/sql/railway/01_base_y_usuarios.sql`, cambia las dos contraseñas, ejecuta con **F9** y cierra **sin guardar**.
   4. Desactiva el TCP Proxy cuando termines.

## 2. Servicio `web` (la tienda)

En el servicio creado desde GitHub, pestaña **Variables → Raw Editor**, pega y completa:

```ini
APP_NAME="Smartphone World"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...                      # paso 0
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=es
APP_TIMEZONE=America/Caracas

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=3306
DB_DATABASE=smartphoneworld
DB_USERNAME=sw_app
DB_PASSWORD=...                         # la que pusiste a sw_app
DB_MIGRATION_USERNAME=sw_migrador
DB_MIGRATION_PASSWORD=...               # la que pusiste a sw_migrador

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=database

BACKUP_ARCHIVE_PASSWORD=...             # paso 0

ADMIN_EMAIL=correo-del-dueño@ejemplo.com
ADMIN_PASSWORD=...                      # paso 0
ADMIN_NOMBRE=Administrador
ADMIN_PATH=admin                        # ruta del panel; ver "Ruta del panel" abajo

ML_SERVICE_URL=http://ml.railway.internal:8001
ML_API_KEY=...                          # paso 0
```

Luego:

1. **Settings → Networking → Generate Domain** (queda `https://algo.up.railway.app`). Si `APP_URL` se resolvió antes de crear el dominio, vuelve a desplegar.
2. **Volumen (obligatorio):** clic derecho en el servicio → **Attach Volume**, ruta de montaje `/app/storage/app`. Ahí viven los comprobantes de pago y los respaldos. Sin volumen, **se borran en cada despliegue**.
3. Despliega. En los logs debe verse: `Migrando...`, `Inicializando...` y `Listo. Escuchando en el puerto ...`.
4. Entra a `https://tu-dominio/admin/login` con `ADMIN_EMAIL` y `ADMIN_PASSWORD`.

El primer arranque tarda unos minutos (migraciones). En los siguientes, el comando de inicialización no cambia nada: ni contraseñas ni permisos.

### Ruta del panel

`ADMIN_PATH` cambia `/admin` por otra ruta (solo minúsculas, números y guiones; 3 a 64 caracteres; un valor inválido vuelve a `admin`). Con `ADMIN_PATH=gestion-7f3k`, el panel queda en `https://tu-dominio/gestion-7f3k/login` y `/admin/login` da 404.

Solo aleja a los robots que prueban `/admin`: **no reemplaza** a una contraseña fuerte, al límite de intentos ni al captcha. Guárdala junto a la contraseña del administrador.

### Verificación en dos pasos (2FA)

Superadmin y admin **deben** activarla: al entrar al panel por primera vez se les muestra un QR para escanear con Google Authenticator, Microsoft Authenticator o Authy, y luego 8 códigos de recuperación (se muestran **una sola vez**: guárdalos impresos o en un gestor de contraseñas). Los vendedores pueden activarla desde *Mi cuenta*.

- **Cambiar de teléfono:** *Mi cuenta → Verificación en dos pasos → Configurar otro teléfono*.
- **Un usuario perdió el teléfono y los códigos:** el superadmin entra a *Usuarios* y pulsa *Restablecer 2FA*.
- **El único superadmin perdió ambos:** en una terminal del servicio `web` (`railway ssh`): `php artisan usuarios:restablecer-2fa correo@ejemplo.com`.
- `TWO_FACTOR_OBLIGATORIO=false` la vuelve opcional para todos (no recomendado en el servidor).

### Captcha (opcional, recomendado)

Registro, inicio de sesión (tienda y panel) y checkout usan Cloudflare Turnstile. Sin las dos variables queda apagado.

1. En <https://dash.cloudflare.com> (cuenta gratis) → **Turnstile → Add widget**. Dominio: el de Railway (`xxxx.up.railway.app`). Modo *Managed*.
2. Agrega en `web`: `TURNSTILE_SITE_KEY=...` y `TURNSTILE_SECRET_KEY=...`.
3. Si Cloudflare no responde, por defecto se deja pasar (para no frenar ventas). `TURNSTILE_FAIL_CLOSED=true` bloquea en su lugar.

## 3. Servicio `ml` (analítica)

1. Paso previo: ejecuta `database/sql/railway/02_usuario_ml.sql` como `root` (mismo método del paso 1.3; activa el TCP Proxy solo mientras lo haces). Las tablas ya existen porque la tienda arrancó.
2. **+ New → GitHub Repo** → el mismo repositorio. En el servicio nuevo: **Settings → Root Directory = `ml-service`** y nómbralo `ml`.
3. Variables:

```ini
ML_DB_HOST=${{MySQL.MYSQLHOST}}
ML_DB_PORT=3306
ML_DB_DATABASE=smartphoneworld
ML_DB_USERNAME=sw_ml
ML_DB_PASSWORD=...                      # la de sw_ml
ML_API_KEY=...                          # EXACTAMENTE la misma de la tienda
PORT=8001
```

4. **No generes dominio público** para este servicio: solo debe verse por la red privada.
5. Despliega. En **Reportes** del panel debe aparecer *Analítica predictiva*. Con una base nueva dirá que no hay datos suficientes: el pronóstico necesita ventas de al menos 12 semanas, y los segmentos al menos 30 clientes con compras. Es lo correcto, no un error.

## 4. Primer uso

1. **Configuración → Datos de la empresa y de pago:** cargar RIF y datos de pago reales. Sin ellos el checkout solo ofrece efectivo.
2. **Productos:** cargar el catálogo desde el panel. El arranque crea marcas y categorías de ejemplo, pero ningún producto. (Opcional, solo para pruebas: abre una terminal del servicio `web` y ejecuta `php artisan db:seed --class=SeederProductos --force`.)
3. **Usuarios:** crear las cuentas de vendedores con contraseñas propias.
4. **Respaldos:** generar uno desde el panel y comprobar que se descarga y se abre con `BACKUP_ARCHIVE_PASSWORD`.

## Cosas que debes saber

- **Los respaldos programados se guardan en el mismo volumen que los datos.** Protegen de un error, no de perder el servicio. Descarga uno desde el panel con regularidad, o configura un disco S3 externo (`BACKUP_DISK_DRIVER=s3`; requiere `composer require league/flysystem-aws-s3-v3`).
- **Correo:** sin SMTP (`MAIL_MAILER` por defecto es `log`), el reporte semanal se escribe en el registro y no llega a ningún buzón. Para que llegue, configura `MAIL_MAILER=smtp` y las variables `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`.
- **Datos de demostración:** no los cargues en un servidor con datos reales. `php artisan demo:ventas` se niega a correr en producción sin `--force` justamente por eso.
- **Si el crédito se acaba, Railway detiene los servicios.** Los datos del volumen se conservan un tiempo limitado después (30 días en el plan de prueba).
- **Actualizar:** cada `git push` a la rama conectada vuelve a desplegar. Las migraciones nuevas se aplican solas al arrancar.

## Si algo falla

| Síntoma en los logs | Causa y arreglo |
|---|---|
| `ERROR: falta la variable ...` | Falta esa variable en el servicio `web` |
| `la base de datos no responde` | `DB_HOST`, `DB_USERNAME` o `DB_PASSWORD` incorrectos, o no ejecutaste el paso 1.3 |
| `Access denied for user 'sw_migrador'` | Contraseña de `DB_MIGRATION_PASSWORD` distinta a la del script SQL (o con ñ) |
| `ADMIN_PASSWORD ... al menos 12 caracteres` | La contraseña del administrador es muy corta o sin números |
| Reportes dice *no responde* | El servicio `ml` está caído, o `ML_SERVICE_URL` no coincide con su nombre |
| Reportes dice *rechazó la clave* | `ML_API_KEY` distinta en `web` y en `ml` |
| `/health` del `ml` dice `sin_conexion` | No ejecutaste `02_usuario_ml.sql` o la clave `ML_DB_PASSWORD` no coincide |
| La página carga pero los comprobantes desaparecen | No se adjuntó el volumen en `/app/storage/app` |
| El *build* falla en `apt-get` o `composer` | Copia el registro del build completo (no solo la última línea) y envíalo para corregirlo |
