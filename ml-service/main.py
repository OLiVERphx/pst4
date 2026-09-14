"""
Microservicio mínimo de ML para SmartphoneWorld.
Expone: GET /api/ml/reports

Notas en español sobre decisiones y límites están incluidas en los comentarios.
"""
from fastapi import FastAPI
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
from sklearn.cluster import KMeans
import os
from datetime import datetime, timedelta

app = FastAPI(title="SmartphoneWorld ML Service")

# Permitir CORS para que el panel pueda consumirlo desde el navegador en prototipos.
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["GET"],
    allow_headers=["*"],
)

DATA_DIR = os.path.join(os.path.dirname(__file__), 'data')


def load_sales():
    path = os.path.join(DATA_DIR, 'sales.csv')
    if not os.path.exists(path):
        return None
    df = pd.read_csv(path, parse_dates=['date'])
    return df


def load_products():
    path = os.path.join(DATA_DIR, 'products.csv')
    if not os.path.exists(path):
        return None
    df = pd.read_csv(path)
    return df


def load_customers():
    path = os.path.join(DATA_DIR, 'customers.csv')
    if not os.path.exists(path):
        return None
    df = pd.read_csv(path, parse_dates=['last_purchase_date'])
    return df


@app.get('/api/ml/reports')
def ml_reports():
    """
    Devuelve un JSON con las secciones:
    - demand_prediction: lista de {product_id, product, weekly_forecast: [w1,w2,w3,w4], status}
    - stock_risk: lista de {product_id, product, stock, avg_daily_sales_30d, days_to_deplete}
    - top_products: lista de {product_id, product, total_sold}
    - customer_segmentation: {clusters: k, labels: [{customer_id, cluster}], centroids: [...], status}

    Implementación mínima con scikit-learn y pandas. Si no hay datos suficientes para una sección,
    se devuelve status: 'insufficient_data' para esa sección y un arreglo vacío.
    """
    sales = load_sales()
    products = load_products()
    customers = load_customers()

    result = {
        'demand_prediction': {'status': 'no_data', 'items': []},
        'stock_risk': {'status': 'no_data', 'items': []},
        'top_products': {'status': 'no_data', 'items': []},
        'customer_segmentation': {'status': 'no_data', 'items': []},
    }

    # --- Top products (simple sum over available sales history, prefer last 90 days) ---
    if sales is not None and not sales.empty:
        cutoff = pd.Timestamp.now() - pd.Timedelta(days=90)
        recent = sales[sales['date'] >= cutoff]
        top = (recent.groupby('product_id')['quantity'].sum().reset_index()
               .sort_values('quantity', ascending=False).head(20))
        result['top_products']['status'] = 'ok'
        result['top_products']['items'] = top.rename(columns={'quantity': 'total_sold'}).to_dict(orient='records')
    
    # --- Stock risk (avg daily over last 30 days) ---
    if sales is not None and products is not None and not sales.empty:
        now = pd.Timestamp.now()
        start_30 = now - pd.Timedelta(days=30)
        last30 = sales[sales['date'] >= start_30]
        sold_30 = last30.groupby('product_id')['quantity'].sum().reset_index().rename(columns={'quantity': 'sold_30'})
        merged = pd.merge(products, sold_30, left_on='id', right_on='product_id', how='left')
        merged['sold_30'] = merged['sold_30'].fillna(0)
        merged['avg_daily'] = merged['sold_30'] / 30.0
        def days_to_deplete(row):
            if row['avg_daily'] <= 0:
                return None
            return float(row['stock'] / row['avg_daily'])
        merged['days_to_deplete'] = merged.apply(days_to_deplete, axis=1)
        # ordenar de más urgente a menos urgente (None van al final)
        merged_sorted = merged.sort_values(by=['days_to_deplete'], na_position='last')
        items = merged_sorted[['id', 'name', 'stock', 'avg_daily', 'days_to_deplete']].rename(
            columns={'id': 'product_id', 'name': 'product', 'avg_daily': 'avg_daily_sales_30d'}).to_dict(orient='records')
        result['stock_risk']['status'] = 'ok'
        result['stock_risk']['items'] = items

    # --- Demand prediction: regresión lineal simple por producto ---
    # Se aplica un modelo simple por producto: transformar fecha a ordinal (días), ajustar LinearRegression
    # y predecir 28 días siguientes, luego agregar por semanas (4 semanas). Requiere al menos 2 puntos.
    demand_items = []
    if sales is not None and not sales.empty:
        # preparar series diarias por producto
        sales_daily = (sales.groupby(['product_id', 'date'])['quantity'].sum().reset_index())
        product_ids = sales_daily['product_id'].unique()
        for pid in product_ids:
            s = sales_daily[sales_daily['product_id'] == pid].sort_values('date')
            if len(s) < 2:
                # insuficiente para regresión
                demand_items.append({'product_id': int(pid), 'status': 'insufficient_data', 'weekly_forecast': []})
                continue
            # X: days since epoch
            X = (s['date'].map(pd.Timestamp.toordinal)).values.reshape(-1,1)
            y = s['quantity'].values
            try:
                model = LinearRegression()
                model.fit(X, y)
                # predecir próximos 28 días
                last_day = s['date'].max()
                future_days = np.array([(last_day + pd.Timedelta(days=i)).toordinal() for i in range(1,29)]).reshape(-1,1)
                preds = model.predict(future_days)
                preds = np.maximum(preds, 0)  # no negativos
                # sumar por semanas (7 días)
                weekly = [float(preds[i*7:(i+1)*7].sum()) for i in range(4)]
                demand_items.append({'product_id': int(pid), 'status': 'ok', 'weekly_forecast': weekly})
            except Exception as e:
                demand_items.append({'product_id': int(pid), 'status': 'error', 'message': str(e), 'weekly_forecast': []})
    if demand_items:
        result['demand_prediction']['status'] = 'ok'
        result['demand_prediction']['items'] = demand_items

    # --- Segmentación de clientes: k-means sobre frecuencia, monto total y recencia ---
    # Razonamiento (en comentarios): Estas variables (RFM: Recencia, Frecuencia, Monto) son
    # tradicionalmente suficientes para segmentación en pymes: son interpretables, robustas y
    # suficientes para identificar clientes VIP / inactivos / ocasionales sin sobreajustar.
    # KMeans con k=3 suele ser un buen inicio; si hay menos de 3 clientes o datos insuficientes,
    # devolvemos status 'insufficient_data'.
    if customers is not None and not customers.empty:
        # customers expected columns: id, last_purchase_date, total_spent, orders_count
        df = customers.copy()
        if len(df) < 3:
            result['customer_segmentation']['status'] = 'insufficient_data'
        else:
            # construir features: recency (dias desde ultima compra), frequency (orders_count), monetary (total_spent)
            df['recency_days'] = (pd.Timestamp.now() - df['last_purchase_date']).dt.days
            features = df[['recency_days', 'orders_count', 'total_spent']].fillna(0)
            # normalizar simple
            from sklearn.preprocessing import StandardScaler
            scaler = StandardScaler()
            X = scaler.fit_transform(features)
            k = 3
            kmeans = KMeans(n_clusters=k, random_state=42, n_init=10).fit(X)
            labels = kmeans.labels_
            centroids = scaler.inverse_transform(kmeans.cluster_centers_).tolist()
            items = []
            for idx, row in df.iterrows():
                items.append({'customer_id': int(row['id']), 'cluster': int(labels[idx])})
            result['customer_segmentation']['status'] = 'ok'
            result['customer_segmentation']['items'] = items
            result['customer_segmentation']['centroids'] = centroids
            result['customer_segmentation']['k'] = k
    
    return JSONResponse(result)