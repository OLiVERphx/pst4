"""
API del microservicio. Solo la consume Laravel (cabecera X-Api-Key); no hay CORS.

    GET  /health            estado del servicio y de la conexión a la base
    GET  /api/ml/reports    pronóstico de demanda + segmentación de clientes (con caché)
    POST /api/ml/recalcular descarta la caché y recalcula
"""
from __future__ import annotations

import hmac
import logging
import threading
import time
from typing import Callable

import pandas as pd
from fastapi import Depends, FastAPI, Header, HTTPException

from . import datos as fuente
from .config import Config
from .demanda import reporte_demanda
from .segmentos import reporte_segmentos

log = logging.getLogger("ml-service")
cfg = Config()

app = FastAPI(title="Smartphone World — analítica predictiva", docs_url=None, redoc_url=None, openapi_url=None)

_cache: dict = {"momento": 0.0, "reporte": None}
_lock = threading.Lock()

# Se puede sustituir en las pruebas para no depender de MySQL
cargar_datos: Callable[[pd.Timestamp], fuente.Datos] = lambda hoy: fuente.cargar(cfg, hoy)
ahora: Callable[[], pd.Timestamp] = lambda: pd.Timestamp.now(tz="America/Caracas").tz_localize(None)


def verificar_clave(x_api_key: str | None = Header(default=None)) -> None:
    if not cfg.api_key:
        raise HTTPException(status_code=503, detail="ML_API_KEY no configurada en el servicio")
    if not x_api_key or not hmac.compare_digest(x_api_key, cfg.api_key):
        raise HTTPException(status_code=401, detail="Clave inválida")


def generar_reporte() -> dict:
    hoy = ahora()
    d = cargar_datos(hoy)
    return {
        "generado_en": hoy.isoformat(timespec="seconds"),
        "datos": {
            "semanas_historial": cfg.semanas_historial,
            "lineas_venta": int(len(d.ventas)),
            "productos": int(len(d.productos)),
            "clientes": int(len(d.clientes)),
        },
        "demanda": reporte_demanda(d.ventas, d.productos, hoy, cfg.semanas_historial),
        "segmentos": reporte_segmentos(d.clientes, hoy),
    }


def _reporte_en_cache(forzar: bool = False) -> dict:
    with _lock:
        vigente = _cache["reporte"] is not None and (time.monotonic() - _cache["momento"]) < cfg.cache_ttl
        if forzar or not vigente:
            _cache["reporte"] = generar_reporte()
            _cache["momento"] = time.monotonic()
        return _cache["reporte"]


@app.get("/health")
def health() -> dict:
    return {"status": "ok", "base_de_datos": "ok" if fuente.comprobar_conexion(cfg) else "sin_conexion"}


@app.get("/api/ml/reports", dependencies=[Depends(verificar_clave)])
def reportes() -> dict:
    try:
        return _reporte_en_cache()
    except Exception as e:  # la base caída no debe tumbar el servicio
        log.exception("Error generando el reporte")
        raise HTTPException(status_code=503, detail=f"No se pudo generar el reporte: {type(e).__name__}")


@app.post("/api/ml/recalcular", dependencies=[Depends(verificar_clave)])
def recalcular() -> dict:
    reporte = _reporte_en_cache(forzar=True)
    return {"status": "ok", "generado_en": reporte["generado_en"]}
