<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Verificación en dos pasos - Smartphone World</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.auth._estilos')
</head>
<body>
    @php $recuperacion = request()->boolean('recuperacion') || session('recuperacion'); @endphp
    <main class="tarjeta">
        <div class="marca">
            <div class="logo">SW</div>
            <div class="marca-texto">
                <strong>Smartphone World</strong>
                <span>Panel de administración</span>
            </div>
        </div>

        <h1>Verificación en dos pasos</h1>
        <p class="sub" id="instruccion">
            @if ($recuperacion)
                Escribe uno de tus códigos de recuperación. Cada uno sirve una sola vez.
            @else
                Abre tu app de autenticación y escribe el código de 6 dígitos de Smartphone World.
            @endif
        </p>

        @error('codigo')
            <div class="alerta" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.2fa.verificar') }}" onsubmit="this.querySelector('.boton').disabled = true">
            @csrf
            <input type="hidden" name="recuperacion" value="{{ $recuperacion ? 1 : 0 }}">
            <div class="campo">
                <label for="codigo">{{ $recuperacion ? 'Código de recuperación' : 'Código de verificación' }}</label>
                @if ($recuperacion)
                    <input id="codigo" type="text" name="codigo" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="abcde-12345" required autofocus>
                @else
                    <input id="codigo" class="codigo" type="text" name="codigo" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" placeholder="000000" required autofocus>
                @endif
            </div>

            <button type="submit" class="boton">Verificar</button>
        </form>

        <div class="acciones">
            @if ($recuperacion)
                <a class="enlace" href="{{ route('admin.2fa.desafio') }}">Usar el código de la app</a>
            @else
                <a class="enlace" href="{{ route('admin.2fa.desafio', ['recuperacion' => 1]) }}">¿Perdiste el teléfono? Usa un código de recuperación</a>
            @endif
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="enlace">Salir</button>
            </form>
        </div>

        <p class="pie">Los intentos de acceso quedan registrados.</p>
    </main>
</body>
</html>
