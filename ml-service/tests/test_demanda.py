import numpy as np
import pandas as pd

from app.demanda import (clasificar, croston_sba, pronosticar, reporte_demanda,
                         reposicion_sugerida, series_semanales)

rng = np.random.default_rng(42)


def test_demanda_estable_pronostica_cerca_del_promedio():
    serie = rng.poisson(10, size=30).astype(float)
    r = pronosticar(serie)
    assert r["status"] == "ok"
    assert r["tipo"] == "regular"
    assert all(7 <= v <= 13 for v in r["pronostico_semanal"])
    # Nunca se elige un modelo peor que el ingenuo en el backtest
    assert r["error_modelo"] <= r["error_ingenuo"]


def test_demanda_intermitente_usa_candidatos_de_intermitente():
    serie = np.zeros(30)
    serie[[2, 7, 13, 19, 24, 29]] = [3, 2, 4, 3, 2, 3]
    r = pronosticar(serie)
    assert r["tipo"] == "intermitente"
    assert r["metodo"] in ("ingenuo", "promedio_movil", "croston_sba")
    # ~3 unidades cada ~5 semanas -> menos de 1,5 por semana
    assert all(0 <= v <= 1.5 for v in r["pronostico_semanal"]) or r["metodo"] == "ingenuo"


def test_croston_sba_estima_tamano_entre_intervalo():
    serie = np.array([0, 0, 0, 4, 0, 0, 0, 4, 0, 0, 0, 4], dtype=float)
    p = croston_sba(serie)
    assert abs(p[0] - (1 - 0.05) * 4 / 4) < 0.05


def test_sin_ventas_no_inventa_demanda():
    r = pronosticar(np.zeros(20))
    assert r["status"] == "sin_ventas"
    assert r["pronostico_semanal"] == [0.0, 0.0, 0.0, 0.0]


def test_poco_historial_se_marca_con_confianza_baja():
    serie = np.concatenate([np.zeros(20), [5, 6, 4, 5, 6]])
    r = pronosticar(serie)
    assert r["status"] == "poco_historial"
    assert r["confianza"] == "baja"


def test_ninguna_prediccion_es_negativa_aunque_la_tendencia_baje():
    serie = np.array([20, 18, 16, 14, 12, 10, 8, 6, 4, 2, 1, 0, 0, 0, 0, 0], dtype=float)
    r = pronosticar(serie)
    assert all(v >= 0 for v in r["pronostico_semanal"])


def test_reposicion_cubre_cuatro_semanas_y_el_minimo():
    assert reposicion_sugerida([3, 3, 3, 3], stock=5, stock_minimo=4) == 11
    assert reposicion_sugerida([1, 1, 1, 1], stock=50, stock_minimo=4) == 0


def test_series_semanales_rellena_ceros_y_excluye_la_semana_en_curso():
    hoy = pd.Timestamp("2026-10-15")  # jueves
    ventas = pd.DataFrame({
        "producto_id": [1, 1, 1],
        "fecha": pd.to_datetime(["2026-09-28 10:00", "2026-09-30 12:00", "2026-10-13 09:00"]),
        "cantidad": [2.0, 3.0, 7.0],
    })
    tabla = series_semanales(ventas, hoy, semanas=4)
    assert list(tabla[1]) == [0.0, 0.0, 5.0, 0.0]  # la venta del 13/10 es de la semana en curso


def test_reporte_ordena_por_reposicion_mas_urgente():
    hoy = pd.Timestamp("2026-10-19")
    fechas = pd.date_range(end="2026-10-12", periods=30, freq="W-MON")  # hasta la última semana completa
    ventas = pd.concat([
        pd.DataFrame({"producto_id": 1, "fecha": fechas, "cantidad": 10.0}),
        pd.DataFrame({"producto_id": 2, "fecha": fechas, "cantidad": 1.0}),
    ])
    productos = pd.DataFrame({"id": [1, 2, 3], "nombre": ["A", "B", "C"], "stock": [5, 50, 9],
                              "stock_minimo": [3, 3, 3], "activo": [1, 1, 0]})
    r = reporte_demanda(ventas, productos, hoy, semanas=40)
    assert r["status"] == "ok"
    assert [i["producto_id"] for i in r["items"]] == [1, 2]  # el 3 está inactivo
    assert r["items"][0]["reponer_sugerido"] == 38          # 4 x 10 + 3 - 5
    assert r["items"][1]["reponer_sugerido"] == 0


def _error_total(generador, semilla_base, n=300):
    """Error medio en el total de las 4 semanas siguientes (lo que importa para reponer)."""
    sistema, ingenuo_ = [], []
    for semilla in range(n):
        s = generador(np.random.default_rng(semilla_base + semilla))
        historial, futuro = s[:30], s[30:]
        p = np.array(pronosticar(historial)["pronostico_semanal"])
        sistema.append(abs(p.sum() - futuro.sum()))
        ingenuo_.append(abs(4 * historial[-1] - futuro.sum()))
    return np.mean(sistema), np.mean(ingenuo_)


def test_en_simulacion_el_sistema_supera_al_pronostico_ingenuo():
    regular = lambda rng: rng.poisson(10, 34).astype(float)
    intermitente = lambda rng: np.where(rng.random(34) < 0.25, rng.integers(1, 6, 34), 0).astype(float)
    for generador, base in ((regular, 0), (intermitente, 5000)):
        sistema, ingenuo_ = _error_total(generador, base)
        assert sistema < ingenuo_ * 0.8


def test_clasificar():
    assert clasificar(np.array([1, 2, 3, 1])) == "regular"
    assert clasificar(np.array([0, 0, 3, 0, 0, 2])) == "intermitente"
