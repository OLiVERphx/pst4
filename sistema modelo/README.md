# Smartphone World — Arquitectura Integral

Este repositorio está siendo organizado como un sistema integral que separa claramente:
- la tienda web para clientes
- el sistema local de gestión
- la capa compartida de reglas y sincronización

## Objetivo del diseño

- mantener el inventario como fuente principal de verdad
- permitir que el sitio web consulte productos disponibles
- permitir que el sistema local administre ventas, servicios y stock
- facilitar futuras migraciones o escalado sin romper la lógica del negocio

## Estructura propuesta

```text
apps/
+-- web/         # tienda pública
+-- local/       # panel administrativo
api/             # endpoints y sincronización
shared/          # modelos, reglas y utilidades comunes
docs/            # documentación técnica y procesos
```

## Módulos

### apps/web
- catálogo
- búsqueda
- carrito
- checkout
- detalle del producto
- seguimiento del pedido

### apps/local
- inventario
- productos
- proveedores
- categorías y marcas
- servicios
- órdenes
- reportes

### api
- integración entre web y local
- validación de stock
- recepción de pedidos
- actualizaciones de estado

### shared
- definiciones de entidades
- validaciones comunes
- utilidades reutilizables
- contratos de datos

## Flujo de integración

1. El sistema local gestiona el stock real.
2. El web obtiene los productos desde la capa de datos compartida.
3. El cliente genera una orden.
4. La API valida el stock y la orden.
5. El sistema local actualiza el estado del pedido.

## Principios

- separación clara por responsabilidad
- bajo acoplamiento entre módulos
- facilidad para extender el sistema
- consistencia de datos entre tienda y gestión

## Base de referencia

- Los archivos HTML de la carpeta `html` siguen sirviendo como referencia visual para la tienda.
- El modelo Laravel existente sigue siendo referencia para la lógica del negocio.
- La nueva organización busca reemplazar el acoplamiento directo con una arquitectura más limpia y escalable.
