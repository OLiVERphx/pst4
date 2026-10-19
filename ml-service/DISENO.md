# Microservicio de analítica predictiva: diseño

Estado: **implementado** (19/10/2026). Decisiones tomadas: opción A, datos de demostración mientras no haya historial real, pronóstico + reposición y RFM, mismo servidor que Laravel. Instalación en [README.md](README.md).

## 1. Qué hay hoy y por qué no sirve tal cual

`ml-service/main.py` (prototipo del primer commit):

| Problema | Consecuencia |
|---|---|
| Lee `data/*.csv` que nada genera | Siempre responde "no_data" |
| Laravel lo consulta pero la vista de Reportes no usa la respuesta | Aunque funcionara, no se vería |
| Demanda: regresión lineal sobre días con venta, sin rellenar días en cero, extrapolando la tendencia | Sobreestima y puede proyectar valores absurdos con pocos datos |
| Segmentación: KMeans k=3 sin nombre de segmento | Resultado no accionable para la tienda ("cliente del grupo 2") |
| Top productos y riesgo de stock | Duplican lo que Reportes ya calcula en PHP |
| CORS `*`, sin autenticación, FastAPI 0.95 (2023) | Cualquiera en la red puede consultarlo |

Se conserva la idea (FastAPI + pandas, en Docker) y se rehace lo demás. No hizo falta scikit-learn ni statsmodels: los modelos elegidos son cortos y se implementan con numpy, lo que también permite explicar cada cálculo en la defensa.

## 2. Límite principal: los datos

Un modelo predictivo necesita historial. El sistema aún no está en producción, así que **no tiene ventas reales acumuladas**.

| Uso | Mínimo razonable |
|---|---|
| Pronóstico semanal por producto | 12 semanas de ventas del producto (ideal: 26 o más) |
| Segmentación de clientes (RFM) | 30 clientes con al menos una compra |
| "Comprados juntos" (reglas de asociación) | Cientos de pedidos con 2 o más productos |

Opciones para la defensa:

1. **Importar el historial real de Smartphone World** (Excel, cuaderno de ventas, sistema anterior) con un importador CSV a las tablas `pedidos` / `items_pedido`. Es la opción más defendible.
2. **Generador de datos sintéticos** con estacionalidad y ruido, marcado claramente como "datos de demostración". Sirve para mostrar el funcionamiento, no para afirmar precisión. **Implementado:** `php artisan demo:ventas` (pedidos `DEMO-…`, clientes `@demo.local`, se elimina con `--limpiar`; Reportes muestra un aviso mientras existan).

El servicio debe decir con honestidad cuándo no hay datos suficientes (`insufficient_data`) y no inventar predicciones.

## 3. Alternativas evaluadas

| Opción | A favor | En contra | Veredicto |
|---|---|---|---|
| **A. Microservicio Python (FastAPI)** que lee MySQL en solo lectura | Ecosistema de ML real (statsmodels, scikit-learn); separado del sistema principal; coincide con lo planteado en el proyecto | Un proceso más que desplegar y mantener | **Recomendada** |
| B. Todo en PHP dentro de Laravel | Un solo despliegue | Sin librerías de ML maduras; reimplementar modelos a mano | Descartada |
| C. Servicio externo (AutoML en la nube) | Nada que programar | Costo, datos del negocio fuera, dependencia de internet | Descartada |

## 4. Arquitectura

```
Laravel (panel)                       ml-service (FastAPI, Python 3.12+)
───────────────                       ──────────────────────────────────
Reportes ──GET /api/ml/reports───────► reporte guardado ML_CACHE_TTL (1 h)
          cabecera X-Api-Key            │
          timeout 1 s conexión / 5 s    └─ lee MySQL con el usuario sw_ml
          copia de 10 min en Laravel       (SELECT por columna, ver abajo)
          si falla: aviso y reintento
          a los 5 min
Recalcular ─POST /api/ml/recalcular──►
```

- **Datos:** lectura directa de MySQL con **`sw_ml`** (`database/sql/usuario_ml.sql`). Tiene `SELECT` solo sobre las columnas que usan las consultas: `pedidos(id, usuario_id, estado, total, created_at, deleted_at)`, `items_pedido(pedido_id, producto_id, cantidad)`, `products(id, nombre, stock, stock_minimo, activo, deleted_at)`, `users(id, rol_id)` y `roles(id, nombre)`. No puede leer nombres, cédulas, teléfonos, direcciones de entrega ni pagos (MySQL responde ERROR 1143).
- **Seguridad:** clave compartida `ML_API_KEY` en `X-Api-Key`, comparada en tiempo constante (`hmac.compare_digest`); sin clave configurada el servicio no entrega reportes. Escucha en `127.0.0.1`; sin CORS ni documentación interactiva. Consultas parametrizadas (PyMySQL).
- **Sin datos personales en el servicio:** trabaja con IDs; Laravel agrega nombres de productos y clientes al mostrar el reporte.
- **Tolerancia a fallos:** si el servicio está apagado, la clave no coincide o la base falla, Reportes muestra el resto de la página y un mensaje claro (`sin_configurar`, `caido`, `clave_invalida`).

## 5. Modelos implementados

### 5.1 Pronóstico de demanda y sugerencia de reposición (`app/demanda.py`)

- Serie **semanal** (lunes a domingo) por producto, solo semanas completas, rellenando con 0 las semanas sin venta. Cuentan los pedidos en `Order::ESTADOS_PAGADOS`.
- Clasificación por **ADI** (intervalo medio entre semanas con venta, Syntetos-Boylan): ADI > 1,32 = demanda **intermitente**.
- Candidatos:
  - **Regular:** ingenuo (repetir la última semana), promedio móvil de 4 semanas, **suavizado exponencial simple** (alfa elegido entre 0,1 y 0,9).
  - **Intermitente:** ingenuo, promedio móvil, **Croston con corrección SBA**.
  - **Menos de 12 semanas desde la primera venta:** promedio móvil, confianza "baja" (`poco_historial`).
  - Se descartó Holt (tendencia): con historiales cortos extrapola la tendencia y produce valores absurdos, el mismo defecto del prototipo.
- **Selección por evaluación rodante:** desde cada una de las últimas 12 semanas, cada modelo pronostica la semana siguiente usando solo el historial anterior; gana el de menor **RMSE**. Ante empate gana el más simple, y si nada supera al ingenuo se usa el ingenuo.
  - Un único bloque de 4 semanas reservadas resultó demasiado ruidoso: con demanda estable de ~10/semana el ingenuo "ganaba" por azar y pronosticaba 4.
  - Se usa RMSE y no MAE porque con demanda intermitente el MAE premia pronosticar 0, que deja a la tienda sin stock.
  - Simulación (300 series por tipo, error en el total de 4 semanas, que es lo que importa para reponer): regular 6,59 frente a 7,29 del promedio de 4 semanas y 12,09 del ingenuo; intermitente 3,20 frente a 3,15 y 4,49. La prueba `test_en_simulacion_el_sistema_supera_al_pronostico_ingenuo` exige mejorar al ingenuo en 20 % o más.
- **Confianza:** error del método / venta semanal promedio: alta < 0,5; media < 1; baja ≥ 1.
- **Reposición sugerida** = ⌈pronóstico de 4 semanas + stock mínimo − stock actual⌉ si es positivo. La tabla se ordena por urgencia.

### 5.2 Segmentación de clientes RFM (`app/segmentos.py`)

- **R**ecencia (días desde la última compra), **F**recuencia (pedidos pagados) y **M**onto (USD), con nota de 1 a 5 por quintil de rango respecto a los demás clientes. Solo usuarios con rol `cliente`.
- Reglas en orden (R y FM = promedio redondeado de F y M):

| Segmento | Regla | Acción sugerida |
|---|---|---|
| Campeones | R ≥ 4 y FM ≥ 4 | Novedades y trato preferente |
| Nuevos | R ≥ 4 y una sola compra | Seguimiento para que vuelvan |
| Leales | R ≥ 3 y FM ≥ 3 | Promociones al mayor o combos |
| Potenciales | R ≥ 3 | Productos relacionados |
| En riesgo | FM ≥ 3 | Contactar con una oferta |
| Perdidos | resto | Campañas generales |

- Mínimo 30 clientes con compras; si no, `insufficient_data` con el motivo.

### 5.3 "Comprados juntos" (futuro)

Reglas de asociación (apriori). Solo cuando haya cientos de pedidos con varios productos; hoy no es factible.

## 6. Contrato de la API

`GET /api/ml/reports` (cabecera `X-Api-Key`)

```json
{
  "generado_en": "2026-10-19T10:00:00",
  "datos": {"semanas_historial": 52, "lineas_venta": 1428, "productos": 16, "clientes": 58},
  "demanda": {
    "status": "ok",
    "semanas_analizadas": 52,
    "items": [{
      "producto_id": 4, "status": "ok", "tipo": "regular", "metodo": "ses", "confianza": "media",
      "error_modelo": 4.3, "error_ingenuo": 5.17, "semanas_historial": 40,
      "pronostico_semanal": [5.9, 5.9, 5.9, 5.9], "stock_actual": 0, "stock_minimo": 3,
      "reponer_sugerido": 27
    }]
  },
  "segmentos": {
    "status": "ok",
    "resumen": [{"segmento": "En riesgo", "clientes": 12, "monto_total": 2676.5,
                 "recencia_promedio_dias": 122, "accion": "Fueron buenos clientes y dejaron de comprar. Contactar con una oferta."}],
    "items": [{"cliente_id": 7, "segmento": "En riesgo", "r": 1, "f": 4, "m": 4,
               "recencia_dias": 130, "frecuencia": 9, "monto": 310.0}]
  }
}
```

- `demanda.items[].status`: `ok`, `poco_historial` o `sin_ventas`. `demanda.status` / `segmentos.status`: `ok`, `insufficient_data` (con `motivo`) o `no_data`.
- `GET /health`: `{"status": "ok", "base_de_datos": "ok" | "sin_conexion"}` (sin clave).
- `POST /api/ml/recalcular`: descarta la copia guardada.

## 7. Pruebas

| Dónde | Qué comprueba |
|---|---|
| `ml-service/tests` (pytest) | Series semanales con ceros, clasificación, cada modelo, simulación contra el ingenuo, reposición, segmentos RFM, API (clave, 401, 503, caché) |
| `tests/Feature/ServicioMlReportesTest.php` | Laravel envía la clave, guarda la respuesta, muestra nombres, avisa de clave inválida o servicio caído, Recalcular, `demo:ventas` (crear, limpiar, bloqueo en producción) |

## 8. Pendiente

- Reemplazar los datos de demostración por historial real (importador CSV) cuando la tienda lo entregue.
- Mover el servicio a un contenedor aparte (hay `Dockerfile`) si se separan los servidores.
