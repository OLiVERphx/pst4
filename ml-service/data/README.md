Carpeta de datos para ml-service.

Colocar CSV opcionales:
- sales.csv -> columnas: date (YYYY-MM-DD), product_id, quantity
- products.csv -> columnas: id, name, stock
- customers.csv -> columnas: id, last_purchase_date (YYYY-MM-DD), total_spent, orders_count

Si los CSV no están presentes, el servicio devolverá secciones con status 'no_data' o 'insufficient_data'.