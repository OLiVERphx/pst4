"""
Lectura de datos desde MySQL con el usuario de solo lectura sw_ml
(database/sql/usuario_ml.sql): no puede ver datos personales de clientes ni pagos.
"""
from __future__ import annotations

import logging
from dataclasses import dataclass

import pandas as pd
import pymysql

from .config import ESTADOS_PAGADOS, Config

log = logging.getLogger("ml-service")


@dataclass
class Datos:
    ventas: pd.DataFrame      # producto_id, fecha, cantidad
    productos: pd.DataFrame   # id, nombre, stock, stock_minimo, activo
    clientes: pd.DataFrame    # cliente_id, ultima_compra, frecuencia, monto


def conectar(cfg: Config):
    return pymysql.connect(host=cfg.db_host, port=cfg.db_port, user=cfg.db_username,
                           password=cfg.db_password, database=cfg.db_database,
                           charset="utf8mb4", connect_timeout=5, read_timeout=30)


def _consulta(conexion, sql: str, parametros: tuple) -> pd.DataFrame:
    with conexion.cursor(pymysql.cursors.DictCursor) as cur:
        cur.execute(sql, parametros)
        return pd.DataFrame(cur.fetchall())


def cargar(cfg: Config, hoy: pd.Timestamp) -> Datos:
    marcadores = ", ".join(["%s"] * len(ESTADOS_PAGADOS))
    desde = (hoy - pd.Timedelta(weeks=cfg.semanas_historial + 1)).to_pydatetime()

    conexion = conectar(cfg)
    try:
        ventas = _consulta(conexion, f"""
            SELECT ip.producto_id, p.created_at AS fecha, ip.cantidad
            FROM items_pedido ip
            JOIN pedidos p ON p.id = ip.pedido_id
            WHERE p.estado IN ({marcadores}) AND p.deleted_at IS NULL AND p.created_at >= %s
        """, (*ESTADOS_PAGADOS, desde))

        productos = _consulta(conexion, """
            SELECT id, nombre, stock, stock_minimo, activo
            FROM products WHERE deleted_at IS NULL
        """, ())

        # Solo usuarios con rol cliente (las ventas en tienda sin cliente quedan fuera).
        # sw_ml solo puede leer users.id y users.rol_id: nada de nombres ni cédulas.
        clientes = _consulta(conexion, f"""
            SELECT p.usuario_id AS cliente_id, MAX(p.created_at) AS ultima_compra,
                   COUNT(*) AS frecuencia, SUM(p.total) AS monto
            FROM pedidos p
            JOIN users u ON u.id = p.usuario_id
            JOIN roles r ON r.id = u.rol_id
            WHERE r.nombre = 'cliente' AND p.estado IN ({marcadores}) AND p.deleted_at IS NULL
            GROUP BY p.usuario_id
        """, ESTADOS_PAGADOS)
    finally:
        conexion.close()

    return normalizar(ventas, productos, clientes)


def normalizar(ventas: pd.DataFrame, productos: pd.DataFrame, clientes: pd.DataFrame) -> Datos:
    """Tipos uniformes aunque las tablas vengan vacías."""
    if ventas.empty:
        ventas = pd.DataFrame(columns=["producto_id", "fecha", "cantidad"])
    ventas["fecha"] = pd.to_datetime(ventas["fecha"])
    ventas["cantidad"] = pd.to_numeric(ventas["cantidad"]).astype(float)
    ventas["producto_id"] = ventas["producto_id"].astype(int)

    if productos.empty:
        productos = pd.DataFrame(columns=["id", "nombre", "stock", "stock_minimo", "activo"])

    if clientes.empty:
        clientes = pd.DataFrame(columns=["cliente_id", "ultima_compra", "frecuencia", "monto"])
    clientes["ultima_compra"] = pd.to_datetime(clientes["ultima_compra"])
    clientes["frecuencia"] = pd.to_numeric(clientes["frecuencia"]).astype(int)
    clientes["monto"] = pd.to_numeric(clientes["monto"]).astype(float)

    return Datos(ventas=ventas, productos=productos, clientes=clientes)


def comprobar_conexion(cfg: Config) -> bool:
    try:
        conexion = conectar(cfg)
        conexion.close()
        return True
    except Exception as e:
        # El motivo va a la consola de uvicorn, no a la respuesta (/health no pide clave)
        log.warning("Sin conexión a MySQL como %s@%s/%s: %s: %s", cfg.db_username, cfg.db_host,
                    cfg.db_database, type(e).__name__, e)
        return False
