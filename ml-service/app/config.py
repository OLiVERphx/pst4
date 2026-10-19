"""Configuración por variables de entorno o por el archivo ml-service/.env (ver .env.example)."""
import os
from dataclasses import dataclass, field
from pathlib import Path

ARCHIVO_ENV = Path(__file__).resolve().parent.parent / ".env"


def cargar_archivo_env(ruta: Path = ARCHIVO_ENV) -> None:
    """Lee líneas NOMBRE=valor. Las variables ya definidas en el sistema tienen prioridad."""
    if not ruta.is_file():
        return
    for linea in ruta.read_text(encoding="utf-8").splitlines():
        linea = linea.strip()
        if not linea or linea.startswith("#") or "=" not in linea:
            continue
        nombre, valor = linea.split("=", 1)
        os.environ.setdefault(nombre.strip(), valor.strip().strip('"').strip("'"))


cargar_archivo_env()


def _env(nombre: str, defecto: str = "") -> str:
    return os.environ.get(nombre, defecto).strip()


@dataclass(frozen=True)
class Config:
    db_host: str = field(default_factory=lambda: _env("ML_DB_HOST", "127.0.0.1"))
    db_port: int = field(default_factory=lambda: int(_env("ML_DB_PORT", "3306")))
    db_database: str = field(default_factory=lambda: _env("ML_DB_DATABASE", "smartphoneworld"))
    db_username: str = field(default_factory=lambda: _env("ML_DB_USERNAME", "sw_ml"))
    db_password: str = field(default_factory=lambda: _env("ML_DB_PASSWORD"))
    # Clave compartida con Laravel (cabecera X-Api-Key). Sin clave el servicio no responde reportes.
    api_key: str = field(default_factory=lambda: _env("ML_API_KEY"))
    # Segundos que se reutiliza un reporte ya calculado
    cache_ttl: int = field(default_factory=lambda: int(_env("ML_CACHE_TTL", "3600")))
    # Semanas de historial que se leen de la base
    semanas_historial: int = field(default_factory=lambda: int(_env("ML_SEMANAS_HISTORIAL", "52")))


# Estados de pedido que cuentan como venta pagada.
# Debe coincidir con App\Models\Order::ESTADOS_PAGADOS en Laravel.
ESTADOS_PAGADOS = (
    "pago_confirmado", "pago_confirmado_retirar", "en_camino", "entregado", "completado",
    "pago_verificado", "procesando", "enviado",
)
