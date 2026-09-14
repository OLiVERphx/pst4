<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SmartphoneWorld - Tienda</title>
  <link rel="stylesheet" href="{{ asset('tienda/css/tienda.css') }}">
</head>
<body>
  @yield('content')

  <div id="toastContainer" class="toast-container"></div>
  <script src="{{ asset('tienda/js/tienda.js') }}"></script>
</body>
</html>
