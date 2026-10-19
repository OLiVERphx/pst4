# Microservicio de analítica predictiva: diseño propuesto

Estado: propuesta para decidir antes de implementar (19/10/2026).

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

Se conserva la idea (FastAPI + pandas + scikit-learn, en Docker) y se rehace lo demás.

## 2. Límite principal: los datos

Un modelo predictivo necesita historial. El sistema aún no está en producción, así que **no tiene ventas reales acumuladas**.

| Uso | Mínimo razonable |
|---|---|
| Pronóstico semanal por producto | 12 semanas de ventas del producto (ideal: 26 o más) |
| Segmentación de clientes (RFM) | 30 clientes con al menos una compra |
| "Comprados juntos" (reglas de asociación) | Cientos de pedidos con 2 o más productos |

Opciones para la defensa:

1. **Importar el historial real de Smartphone World** (Excel, cuaderno de ventas, sistema anterior) con un importador CSV a las tablas `pedidos` / `items_pedido`. Es la opción más defendible.
2. **Generador de datos sintéticos** con estacionalidad y ruido, marcado claramente como "datos de demostración". Sirve para mostrar el funcionamiento, no para afirmar precisión.

El servicio debe decir con honestidad cuándo no hay datos suficientes (`insufficient_data`) y no inventar predicciones.

## 3. Alternativas evaluadas

| Opción | A favor | En contra | Veredicto |
|---|---|---|---|
| **A. Microservicio Python (FastAPI)** que lee MySQL en solo lectura | Ecosistema de ML real (statsmodels, scikit-learn); separado del sistema principal; coincide con lo planteado en el proyecto | Un proceso más que desplegar y mantener | **Recomendada** |
| B. Todo en PHP dentro de Laravel | Un solo despliegue | Sin librerías de ML maduras; reimplementar modelos a mano | Descartada |
| C. Servicio externo (AutoML en la nube) | Nada que programar | Costo, datos del negocio fuera, dependencia de internet | Descartada |

## 4. Arquitectura (opción A)

```
Laravel (panel)                      ml-service (FastAPI, Python 3.12)
───────────────                      ─────────────────────────────────
Reportes ──GET /api/ml/reports──────► calcula con caché de 1 h
          cabecera X-Api-Key           │
          timeout 3 s; si falla,       └─ lee MySQL con usuario sw_ml
          muestra solo los reportes       (SELECT en pedidos, items_pedido,
          PHP (ya implementado)            products, categorias)
```

- **Datos:** lectura directa de MySQL con un usuario nuevo **`sw_ml`**, que solo tiene `SELECT` sobre 4 tablas y no puede ver `users`, `pagos` ni nada personal. Así no hay CSV desactualizados.
- **Seguridad:** clave compartida `ML_API_KEY` en la cabecera `X-Api-Key`; el servicio escucha solo en la red interna (`127.0.0.1` o red privada de Railway); sin CORS.
- **Rendimiento:** con este volumen de datos el cálculo tarda segundos. Caché de 1 h en el servicio y en Laravel. Botón "Recalcular" opcional.
- **Integración con Laravel:** ya existe `config('services.ml.url')` con timeouts cortos y tolerancia a fallos. Falta: enviar la clave, cachear la respuesta y mostrarla en la vista de Reportes.

## 5. Modelos propuestos (ajustados al tamaño del negocio)

### 5.1 Pronóstico de demanda y sugerencia de reposición (prioridad 1)

Es lo que más valor da a una tienda de accesorios: qué pedir al proveedor y cuánto.

- Serie **semanal** por producto, rellenando con 0 las semanas sin venta.
- Modelo según el patrón de cada producto:
  - **Demanda regular:** suavizado exponencial (Holt, statsmodels).
  - **Demanda intermitente** (muchas semanas en 0, típico en accesorios): método de **Croston / SBA**.
  - **Poco historial:** promedio móvil de 4 semanas, marcado como "estimación simple".
- **Evaluación honesta:** backtest con las últimas 4 semanas reservadas, comparando el error (MAE) contra el modelo ingenuo (repetir la semana anterior). Se informa cuál fue mejor; si el modelo no supera al ingenuo, se usa el ingenuo.
- **Salida para el usuario:** pronóstico de las 4 próximas semanas y **cantidad sugerida a reponer** = pronóstico de 4 semanas + stock mínimo − stock actual (si es mayor que 0), con un nivel de confianza (alta / media / baja) según el error del backtest.

### 5.2 Segmentación de clientes RFM (prioridad 2)

- **R**ecencia, **F**recuencia y **M**onto de cada cliente, calificados de 1 a 5 por quintiles.
- Segmentos con nombre y una acción sugerida: *Campeones*, *Leales*, *Nuevos*, *En riesgo*, *Perdidos*.
- Más interpretable que KMeans. KMeans queda como análisis complementario cuando haya 50 clientes o más.
- Sin datos personales: el servicio trabaja con el id del cliente y Laravel pone el nombre al mostrarlo.

### 5.3 "Comprados juntos" (futuro)

Reglas de asociación (apriori). Solo cuando haya cientos de pedidos con varios productos; hoy no es factible.

## 6. Contrato de la API

`GET /api/ml/reports` (cabecera `X-Api-Key`)

```json
{
  "generado_en": "2026-10-19T10:00:00-04:00",
  "datos": {"semanas_historial": 26, "pedidos": 412, "clientes": 58},
  "demanda": {
    "status": "ok",
    "items": [{
      "producto_id": 12, "metodo": "croston", "confianza": "media",
      "mae_modelo": 1.4, "mae_ingenuo": 2.1,
      "pronostico_semanal": [3, 3, 2, 3], "stock_actual": 4, "stock_minimo": 3,
      "reponer_sugerido": 10
    }]
  },
  "segmentos": {
    "status": "ok",
    "resumen": [{"segmento": "En riesgo", "clientes": 9, "accion": "Contactar con una oferta"}],
    "items": [{"cliente_id": 7, "segmento": "En riesgo", "r": 2, "f": 4, "m": 4}]
  }
}
```

`GET /health`: estado del servicio y de la conexión a la base de datos.

## 7. Trabajo estimado

| Bloque | Contenido |
|---|---|
| 1. Servicio | Rehacer `main.py` en módulos (datos, demanda, segmentos, API), dependencias actuales, clave API, `/health` |
| 2. Datos | Usuario `sw_ml` (script SQL); importador CSV de historial **o** generador sintético de demostración |
| 3. Modelos | Pronóstico con backtest y reposición; RFM |
| 4. Pruebas | pytest con series sintéticas de comportamiento conocido |
| 5. Laravel | Clave en la petición, caché, nueva sección en Reportes (tabla de reposición y segmentos) |
| 6. Despliegue | Docker para Railway/VPS; instrucciones para correrlo en Laragon (Python + venv) |

## 8. Decisiones pendientes

1. ¿Se aprueba la opción A?
2. ¿Hay historial real de ventas para importar, o se usan datos sintéticos de demostración?
3. ¿Alcance: pronóstico + reposición y RFM, o solo el pronóstico?
4. ¿Dónde correrá en producción: mismo servidor que Laravel o servicio aparte en Railway?
