import pandas as pd

from app.segmentos import MIN_CLIENTES, reporte_segmentos

HOY = pd.Timestamp("2026-10-19")


def _clientes(n=40):
    filas = []
    for i in range(n):
        if i < 10:    # compran seguido, hace poco y mucho
            filas.append((i, HOY - pd.Timedelta(days=3), 12, 400.0))
        elif i < 20:  # fueron buenos y dejaron de comprar
            filas.append((i, HOY - pd.Timedelta(days=200), 10, 350.0))
        elif i < 30:  # una compra pequeña hace mucho
            filas.append((i, HOY - pd.Timedelta(days=300), 1, 5.0))
        else:         # una sola compra muy reciente
            filas.append((i, HOY - pd.Timedelta(days=1), 1, 20.0))
    return pd.DataFrame(filas, columns=["cliente_id", "ultima_compra", "frecuencia", "monto"])


def test_segmenta_perfiles_conocidos():
    r = reporte_segmentos(_clientes(), HOY)
    assert r["status"] == "ok"
    seg = {i["cliente_id"]: i["segmento"] for i in r["items"]}
    assert seg[0] == "Campeones"
    assert seg[15] == "En riesgo"
    assert seg[25] == "Perdidos"
    assert seg[35] == "Nuevos"
    assert sum(s["clientes"] for s in r["resumen"]) == 40
    assert all(s["accion"] for s in r["resumen"])


def test_pocos_clientes_no_segmenta():
    r = reporte_segmentos(_clientes(MIN_CLIENTES - 1), HOY)
    assert r["status"] == "insufficient_data"
    assert r["items"] == []
