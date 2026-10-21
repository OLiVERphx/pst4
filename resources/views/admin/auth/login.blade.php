<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso al panel - Smartphone World</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('admin.auth._estilos')
</head>
<body>
    <main class="tarjeta">
        <div class="marca">
            <div class="logo">SW</div>
            <div class="marca-texto">
                <strong>Smartphone World</strong>
                <span>Panel de administración</span>
            </div>
        </div>

        <h1>Iniciar sesión</h1>
        <p class="sub">Acceso solo para personal autorizado</p>

        @if (session('error'))
            <div class="alerta" role="alert">{{ session('error') }}</div>
        @endif
        @error('email')
            <div class="alerta" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.login') }}" onsubmit="this.querySelector('.boton').disabled = true">
            @csrf
            <div class="campo">
                <label for="email">Correo electrónico</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}"
                       autocomplete="username" autofocus required>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="control">
                    <input id="password" type="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="ver" aria-controls="password"
                            onclick="const i = document.getElementById('password'); const v = i.type === 'password'; i.type = v ? 'text' : 'password'; this.textContent = v ? 'Ocultar' : 'Mostrar';">Mostrar</button>
                </div>
                @error('password') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="captcha"><x-turnstile /></div>

            <button type="submit" class="boton">Entrar al panel</button>
        </form>

        <p class="pie">Smartphone World C.A. · Valera, Estado Trujillo<br>Los intentos de acceso quedan registrados.</p>
    </main>
</body>
</html>
