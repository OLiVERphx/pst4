# ml-service: analítica predictiva

Microservicio en Python (FastAPI) que lee las ventas de MySQL con un usuario de solo lectura y devuelve:

- **Pronóstico de demanda** de las próximas 4 semanas por producto y **cantidad sugerida a reponer**.
- **Segmentación de clientes RFM** (Campeones, Nuevos, Leales, Potenciales, En riesgo, Perdidos) con una acción sugerida.

Laravel lo consulta desde **Reportes** (`ReportController`). Si el servicio no está encendido, Reportes sigue funcionando y lo indica.
El diseño y las decisiones están en [DISENO.md](DISENO.md).

## Puesta en marcha en Windows (Laragon)

Requisitos: Python 3.12 o superior ([python.org](https://www.python.org/downloads/), marcar *Add python.exe to PATH* al instalar).

### 1. Usuario de base de datos `sw_ml`

1. Abre `database/sql/usuario_ml.sql`, cambia `CAMBIAR-clave-ml` por una contraseña **solo con letras sin tilde, números y guiones** (sin ñ).
2. Ejecútalo como `root` (HeidiSQL o consola de MySQL).
3. Después, deshaz el cambio para no subir la contraseña a GitHub: `git checkout database/sql/usuario_ml.sql`

### 2. Entorno de Python (una sola vez)

En la terminal de Laragon, dentro de la carpeta del proyecto:

```bat
cd ml-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
copy .env.example .env
```

Edita `ml-service\.env`:

```ini
ML_DB_PASSWORD=la-clave-que-pusiste-a-sw_ml
ML_API_KEY=una-clave-larga-solo-ascii
```

Una clave aleatoria se puede generar con `python -c "import secrets; print(secrets.token_urlsafe(32))"`.

### 3. Laravel

En el `.env` de Laravel (la raíz del proyecto):

```ini
ML_SERVICE_URL=http://127.0.0.1:8001
ML_API_KEY=la-misma-clave-del-paso-2
```

y luego `php artisan config:clear`.

### 4. Encender el servicio (cada vez que lo uses)

```bat
cd ml-service
.venv\Scripts\activate
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

Comprobación: abre <http://127.0.0.1:8001/health>. Debe mostrar `{"status":"ok","base_de_datos":"ok"}`.
Si dice `sin_conexion`, revisa `ML_DB_PASSWORD` y que el script del paso 1 se haya ejecutado.

### 5. Datos de demostración (mientras no haya ventas reales)

```bat
php artisan demo:ventas
```

Genera 40 semanas de pedidos simulados (número `DEMO-…`, clientes `@demo.local`). No descuentan stock.
Se eliminan con `php artisan demo:ventas --limpiar`. El comando se niega a correr en producción.

Después, en **Reportes**, pulsa **Recalcular**.

## Pruebas

```bat
pip install -r requirements-dev.txt
python -m pytest -q
```

Incluyen una simulación con series de comportamiento conocido: el método elegido debe equivocarse al menos 20 % menos que repetir la última semana.

## API

Todas las rutas `/api/ml/*` exigen la cabecera `X-Api-Key`. No hay CORS ni documentación interactiva: solo la consume Laravel.

| Ruta | Uso |
|---|---|
| `GET /health` | Estado del servicio y de la conexión a la base |
| `GET /api/ml/reports` | Reporte completo (se guarda `ML_CACHE_TTL` segundos) |
| `POST /api/ml/recalcular` | Descarta la copia guardada y recalcula |

## Producción

Debe escuchar solo en `127.0.0.1` (mismo servidor) o en la red privada del proveedor, nunca expuesto a internet.
Hay un `Dockerfile` (usuario sin privilegios, puerto 8001) para desplegarlo como servicio aparte más adelante.
