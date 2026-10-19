"""
Pronóstico de demanda semanal por producto y sugerencia de reposición.

Decisiones:
- Se trabaja por SEMANA (lunes a domingo) y las semanas sin venta cuentan como 0.
  Ignorar los ceros (como hacía el prototipo) sobreestima la demanda.
- Cada producto se clasifica por el intervalo medio entre ventas (ADI, Syntetos-Boylan):
  ADI > 1,32 = demanda intermitente (muchas semanas en 0, típico en accesorios).
- Candidatos según el tipo de demanda:
    regular      -> ingenuo, promedio móvil, suavizado exponencial simple (SES)
    intermitente -> ingenuo, promedio móvil, Croston corregido (SBA)
- Se elige el candidato con menor error cuadrático (RMSE) en una evaluación rodante: desde cada una
  de las últimas 12 semanas (o menos si el historial es corto), cada modelo pronostica la semana siguiente usando solo el
  historial anterior, y se promedia el error contra lo que realmente se vendió. Un solo
  bloque de prueba resultó demasiado ruidoso (el ingenuo "ganaba" por azar).
  Se usa RMSE y no MAE porque con demanda intermitente el MAE premia pronosticar 0
  (deja a la tienda sin stock). Simulación con 300 series por tipo, error en el total de
  4 semanas (lo que importa para reponer): regular 6,59 (vs 7,29 promedio de 4 semanas y
  12,09 ingenuo); intermitente 3,20 (vs 3,15 y 4,49). Ver tests/test_demanda.py. Si ningún
  modelo supera al ingenuo, se usa el ingenuo: nunca se presenta un modelo peor que
  "repetir la última semana".
"""
from __future__ import annotations

import math

import numpy as np
import pandas as pd

HORIZONTE = 4        # semanas a pronosticar
EVALUACION = 12      # máximo de semanas de la evaluación rodante (un paso adelante)
MIN_SEMANAS = 12     # historial mínimo (desde la primera venta) para usar modelos
UMBRAL_ADI = 1.32    # Syntetos-Boylan: por encima, demanda intermitente
ALFAS = (0.1, 0.2, 0.3, 0.5, 0.7, 0.9)


# ---------------------------------------------------------------------------
# Preparación de series
# ---------------------------------------------------------------------------

def series_semanales(ventas: pd.DataFrame, hasta: pd.Timestamp, semanas: int) -> pd.DataFrame:
    """
    ventas: columnas producto_id, fecha (datetime), cantidad.
    Devuelve una tabla semanas x productos con las unidades vendidas, rellenando con 0.
    Solo semanas completas: la última es la que terminó antes de `hasta`.
    """
    fin = (hasta.normalize() - pd.Timedelta(days=hasta.weekday()))  # lunes de la semana en curso (excluida)
    inicio = fin - pd.Timedelta(weeks=semanas)
    indice = pd.date_range(inicio, fin - pd.Timedelta(weeks=1), freq="W-MON")
    if ventas.empty:
        return pd.DataFrame(index=indice)

    v = ventas[(ventas["fecha"] >= inicio) & (ventas["fecha"] < fin)].copy()
    v["semana"] = (v["fecha"].dt.normalize() - pd.to_timedelta(v["fecha"].dt.weekday, unit="D"))
    tabla = v.pivot_table(index="semana", columns="producto_id", values="cantidad", aggfunc="sum")
    return tabla.reindex(indice).fillna(0.0)


def clasificar(serie: np.ndarray) -> str:
    """'regular' o 'intermitente' según el intervalo medio entre semanas con venta."""
    con_venta = np.count_nonzero(serie)
    if con_venta == 0:
        return "sin_ventas"
    adi = len(serie) / con_venta
    return "intermitente" if adi > UMBRAL_ADI else "regular"


# ---------------------------------------------------------------------------
# Modelos: cada uno recibe el historial y devuelve HORIZONTE valores
# ---------------------------------------------------------------------------

def ingenuo(h: np.ndarray, n: int = HORIZONTE) -> np.ndarray:
    return np.full(n, float(h[-1]) if len(h) else 0.0)


def promedio_movil(h: np.ndarray, n: int = HORIZONTE, k: int = 4) -> np.ndarray:
    return np.full(n, float(np.mean(h[-k:])) if len(h) else 0.0)


def _ses_nivel(h: np.ndarray, alfa: float) -> tuple[float, float]:
    """Suavizado exponencial simple: nivel final y error cuadrático de un paso."""
    nivel = float(h[0])
    sse = 0.0
    for x in h[1:]:
        sse += (x - nivel) ** 2
        nivel = alfa * x + (1 - alfa) * nivel
    return nivel, sse


def ses(h: np.ndarray, n: int = HORIZONTE) -> np.ndarray:
    """SES con el alfa que minimiza el error de un paso dentro del historial."""
    if len(h) < 2:
        return ingenuo(h, n)
    mejor = min(ALFAS, key=lambda a: _ses_nivel(h, a)[1])
    return np.full(n, _ses_nivel(h, mejor)[0])


def croston_sba(h: np.ndarray, n: int = HORIZONTE, alfa: float = 0.1) -> np.ndarray:
    """
    Croston con corrección de Syntetos-Boylan (SBA) para demanda intermitente:
    suaviza por separado el tamaño de las ventas y el intervalo entre ellas.
    Pronóstico por semana = (1 - alfa/2) * tamaño / intervalo.
    """
    indices = np.flatnonzero(h)
    if len(indices) == 0:
        return np.zeros(n)
    tamano = float(h[indices[0]])
    intervalo = float(indices[0] + 1)
    for anterior, actual in zip(indices[:-1], indices[1:]):
        tamano = alfa * h[actual] + (1 - alfa) * tamano
        intervalo = alfa * (actual - anterior) + (1 - alfa) * intervalo
    return np.full(n, (1 - alfa / 2) * tamano / intervalo)


MODELOS = {
    "ingenuo": ingenuo,
    "promedio_movil": promedio_movil,
    "ses": ses,
    "croston_sba": croston_sba,
}
CANDIDATOS = {
    "regular": ("ingenuo", "promedio_movil", "ses"),
    "intermitente": ("ingenuo", "promedio_movil", "croston_sba"),
}


# ---------------------------------------------------------------------------
# Selección por backtest y resultado por producto
# ---------------------------------------------------------------------------

def error_rodante(serie: np.ndarray, modelo) -> float:
    """RMSE de pronosticar una semana adelante desde cada una de las últimas EVALUACION semanas."""
    n = len(serie)
    # Siempre se dejan al menos 4 semanas de historial para el primer pronóstico.
    # Medido con 200 series simuladas: 12 semanas eligen mejor que 4 u 8 (menos azar).
    ventana = max(1, min(EVALUACION, n - 4))
    errores = np.array([serie[t] - modelo(serie[:t], 1)[0] for t in range(n - ventana, n)])
    return float(np.sqrt(np.mean(errores ** 2)))


def pronosticar(serie: np.ndarray) -> dict:
    """
    serie: unidades por semana, de la más antigua a la más reciente (incluye ceros).
    Devuelve método elegido, errores del backtest, confianza y pronóstico.
    """
    serie = np.asarray(serie, dtype=float)
    tipo = clasificar(serie)
    if tipo == "sin_ventas":
        return {"status": "sin_ventas", "tipo": tipo, "metodo": None, "confianza": None,
                "pronostico_semanal": [0.0] * HORIZONTE, "error_modelo": None, "error_ingenuo": None}

    # Historial útil: desde la primera semana con venta
    util = serie[np.flatnonzero(serie)[0]:]
    if len(util) < MIN_SEMANAS:
        p = promedio_movil(util)
        return {"status": "poco_historial", "tipo": tipo, "metodo": "promedio_movil", "confianza": "baja",
                "pronostico_semanal": _redondear(p), "error_modelo": None, "error_ingenuo": None,
                "semanas_historial": int(len(util))}

    errores = {nombre: error_rodante(util, MODELOS[nombre]) for nombre in CANDIDATOS[tipo]}
    # Ante empate gana el más simple (el orden de CANDIDATOS va de simple a complejo)
    elegido = min(CANDIDATOS[tipo], key=lambda nombre: (round(errores[nombre], 6), CANDIDATOS[tipo].index(nombre)))
    pronostico = MODELOS[elegido](util, HORIZONTE)

    escala = max(float(np.mean(util)), 1e-9)
    relativo = errores[elegido] / escala
    confianza = "alta" if relativo < 0.5 else ("media" if relativo < 1.0 else "baja")

    return {"status": "ok", "tipo": tipo, "metodo": elegido, "confianza": confianza,
            "pronostico_semanal": _redondear(pronostico),
            "error_modelo": round(errores[elegido], 2), "error_ingenuo": round(errores["ingenuo"], 2),
            "semanas_historial": int(len(util))}


def _redondear(valores: np.ndarray) -> list[float]:
    return [round(max(float(v), 0.0), 1) for v in valores]


def reposicion_sugerida(pronostico_semanal: list[float], stock: int, stock_minimo: int) -> int:
    """Unidades a pedir para cubrir las próximas 4 semanas sin bajar del stock mínimo."""
    necesidad = sum(pronostico_semanal) + stock_minimo - stock
    return max(0, math.ceil(necesidad))


def reporte_demanda(ventas: pd.DataFrame, productos: pd.DataFrame, hoy: pd.Timestamp, semanas: int) -> dict:
    """
    ventas: producto_id, fecha, cantidad.  productos: id, stock, stock_minimo, activo.
    Devuelve el bloque 'demanda' del reporte, ordenado por urgencia de reposición.
    """
    activos = productos[productos["activo"].astype(bool)]
    if activos.empty:
        return {"status": "no_data", "items": []}

    tabla = series_semanales(ventas, hoy, semanas)
    if tabla.empty or tabla.to_numpy().sum() == 0:
        return {"status": "insufficient_data", "items": [],
                "motivo": "No hay ventas confirmadas en el período analizado."}

    items = []
    for _, prod in activos.iterrows():
        pid = int(prod["id"])
        serie = tabla[pid].to_numpy() if pid in tabla.columns else np.zeros(len(tabla))
        r = pronosticar(serie)
        r["producto_id"] = pid
        r["stock_actual"] = int(prod["stock"])
        r["stock_minimo"] = int(prod["stock_minimo"])
        r["reponer_sugerido"] = reposicion_sugerida(r["pronostico_semanal"], r["stock_actual"], r["stock_minimo"])
        items.append(r)

    items.sort(key=lambda r: (-r["reponer_sugerido"], -sum(r["pronostico_semanal"])))
    return {"status": "ok", "semanas_analizadas": int(len(tabla)), "items": items}
