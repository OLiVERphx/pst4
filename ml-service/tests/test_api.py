import pandas as pd
from fastapi.testclient import TestClient

from app import main
from app.datos import normalizar

client = TestClient(main.app)


def _datos_falsos(hoy):
    fechas = pd.date_range(hoy - pd.Timedelta(weeks=30), periods=30, freq="W-MON")
    ventas = pd.DataFrame({"producto_id": 1, "fecha": fechas, "cantidad": 4})
    productos = pd.DataFrame({"id": [1], "nombre": ["A"], "stock": [2], "stock_minimo": [3], "activo": [1]})
    clientes = pd.DataFrame({"cliente_id": [1], "ultima_compra": [hoy], "frecuencia": [1], "monto": [10.0]})
    return normalizar(ventas, productos, clientes)


def setup_function():
    main._cache.update(momento=0.0, reporte=None)
    main.cargar_datos = _datos_falsos


def test_sin_clave_no_responde():
    assert client.get("/api/ml/reports").status_code == 401
    assert client.get("/api/ml/reports", headers={"X-Api-Key": "otra"}).status_code == 401


def test_reporte_con_clave():
    r = client.get("/api/ml/reports", headers={"X-Api-Key": "clave-de-prueba"})
    assert r.status_code == 200
    cuerpo = r.json()
    assert cuerpo["demanda"]["status"] == "ok"
    assert cuerpo["demanda"]["items"][0]["reponer_sugerido"] > 0
    assert cuerpo["segmentos"]["status"] == "insufficient_data"  # solo 1 cliente


def test_cache_y_recalcular():
    llamadas = []
    main.cargar_datos = lambda hoy: llamadas.append(1) or _datos_falsos(hoy)
    h = {"X-Api-Key": "clave-de-prueba"}
    client.get("/api/ml/reports", headers=h)
    client.get("/api/ml/reports", headers=h)
    assert len(llamadas) == 1
    assert client.post("/api/ml/recalcular", headers=h).status_code == 200
    assert len(llamadas) == 2


def test_base_caida_devuelve_503_sin_tumbar_el_servicio():
    def falla(hoy):
        raise ConnectionError("sin base")
    main.cargar_datos = falla
    r = client.get("/api/ml/reports", headers={"X-Api-Key": "clave-de-prueba"})
    assert r.status_code == 503


def test_sin_documentacion_publica():
    assert client.get("/docs").status_code == 404


def test_archivo_env_no_pisa_variables_del_sistema(tmp_path, monkeypatch):
    from app.config import cargar_archivo_env

    env = tmp_path / ".env"
    env.write_text("# comentario\nML_PRUEBA_A=desde-archivo\nML_PRUEBA_B=\"con comillas\"\n", encoding="utf-8")
    monkeypatch.setenv("ML_PRUEBA_A", "del-sistema")
    monkeypatch.delenv("ML_PRUEBA_B", raising=False)

    cargar_archivo_env(env)

    import os
    assert os.environ["ML_PRUEBA_A"] == "del-sistema"
    assert os.environ["ML_PRUEBA_B"] == "con comillas"
    monkeypatch.delenv("ML_PRUEBA_B")
