"""
Segmentación de clientes RFM (Recencia, Frecuencia, Monto).

Cada cliente recibe una nota de 1 a 5 en cada dimensión según su posición (quintil)
respecto a los demás clientes. Con R y el promedio de F y M se asigna un segmento con
nombre y una acción sugerida. Se prefirió RFM a KMeans porque el resultado se puede
explicar y usar directamente en la tienda.
"""
from __future__ import annotations

import numpy as np
import pandas as pd

MIN_CLIENTES = 30

# Orden de evaluación: el primer segmento cuya regla se cumpla
SEGMENTOS = (
    ("Campeones", lambda r, fm, f: r >= 4 and fm >= 4,
     "Compran seguido, hace poco y gastan más. Ofrecer novedades y trato preferente."),
    ("Nuevos", lambda r, fm, f: r >= 4 and f == 1,
     "Primera compra reciente. Dar seguimiento para que vuelvan."),
    ("Leales", lambda r, fm, f: r >= 3 and fm >= 3,
     "Clientes constantes. Mantener con promociones al mayor o combos."),
    ("Potenciales", lambda r, fm, f: r >= 3,
     "Compraron hace poco pero poco. Recomendar productos relacionados."),
    ("En riesgo", lambda r, fm, f: fm >= 3,
     "Fueron buenos clientes y dejaron de comprar. Contactar con una oferta."),
    ("Perdidos", lambda r, fm, f: True,
     "Compraron poco y hace tiempo. Solo campañas generales."),
)


def _nota(valores: pd.Series, mayor_es_mejor: bool = True) -> pd.Series:
    """Nota 1-5 por quintil de rango (los empates reciben la misma nota)."""
    pct = valores.rank(method="average", pct=True, ascending=mayor_es_mejor)
    return np.ceil(pct * 5).clip(1, 5).astype(int)


def reporte_segmentos(clientes: pd.DataFrame, hoy: pd.Timestamp) -> dict:
    """
    clientes: cliente_id, ultima_compra (datetime), frecuencia (pedidos), monto (USD).
    """
    if len(clientes) < MIN_CLIENTES:
        return {"status": "insufficient_data", "items": [], "resumen": [],
                "motivo": f"Se necesitan al menos {MIN_CLIENTES} clientes con compras; hay {len(clientes)}."}

    df = clientes.copy()
    df["recencia_dias"] = (hoy.normalize() - df["ultima_compra"].dt.normalize()).dt.days
    df["r"] = _nota(df["recencia_dias"], mayor_es_mejor=False)
    df["f"] = _nota(df["frecuencia"])
    df["m"] = _nota(df["monto"])
    df["fm"] = np.floor((df["f"] + df["m"]) / 2 + 0.5).astype(int)

    def segmento(fila) -> str:
        for nombre, regla, _ in SEGMENTOS:
            if regla(fila["r"], fila["fm"], int(fila["frecuencia"])):
                return nombre
        return "Perdidos"

    df["segmento"] = df.apply(segmento, axis=1)

    acciones = {nombre: accion for nombre, _, accion in SEGMENTOS}
    resumen = []
    for nombre, _, _ in SEGMENTOS:
        grupo = df[df["segmento"] == nombre]
        if grupo.empty:
            continue
        resumen.append({
            "segmento": nombre,
            "clientes": int(len(grupo)),
            "monto_total": round(float(grupo["monto"].sum()), 2),
            "recencia_promedio_dias": int(round(grupo["recencia_dias"].mean())),
            "accion": acciones[nombre],
        })

    items = [{
        "cliente_id": int(f["cliente_id"]), "segmento": f["segmento"],
        "r": int(f["r"]), "f": int(f["f"]), "m": int(f["m"]),
        "recencia_dias": int(f["recencia_dias"]), "frecuencia": int(f["frecuencia"]),
        "monto": round(float(f["monto"]), 2),
    } for _, f in df.sort_values(["segmento", "monto"], ascending=[True, False]).iterrows()]

    return {"status": "ok", "resumen": resumen, "items": items}
