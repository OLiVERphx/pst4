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
    <style>
        :root {
            --bg: #0F172A; --bg2: #1E293B; --card: #1E293B; --borde: #334155;
            --input: #0F172A; --texto: #F1F5F9; --suave: #94A3B8;
            --azul: #2563EB; --azul-h: #1D4ED8; --teal: #14B8A6; --rojo: #F87171;
        }
        @media (prefers-color-scheme: light) {
            :root {
                --bg: #E8ECF2; --bg2: #F8FAFC; --card: #FFFFFF; --borde: #E2E8F0;
                --input: #F8FAFC; --texto: #0F172A; --suave: #64748B; --rojo: #DC2626;
            }
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 1.5rem 1rem; font-family: 'Inter', system-ui, sans-serif; color: var(--texto);
            background:
                radial-gradient(circle at 15% 20%, rgba(37, 99, 235, .18), transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(20, 184, 166, .14), transparent 40%),
                var(--bg);
        }
        .tarjeta {
            width: 100%; max-width: 400px; background: var(--card); border: 1px solid var(--borde);
            border-radius: 16px; padding: 2.25rem 2rem 1.75rem; box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
        }
        .marca { display: flex; align-items: center; gap: .75rem; justify-content: center; margin-bottom: 1.75rem; }
        .logo {
            width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center;
            background: linear-gradient(135deg, var(--azul), var(--teal)); color: #fff;
            font-weight: 800; font-size: 1.05rem; letter-spacing: -.02em;
        }
        .marca-texto strong { display: block; font-size: 1.1rem; font-weight: 800; letter-spacing: -.01em; }
        .marca-texto span { font-size: .78rem; color: var(--suave); }
        h1 { font-size: 1.35rem; font-weight: 700; text-align: center; }
        .sub { text-align: center; color: var(--suave); font-size: .875rem; margin: .35rem 0 1.5rem; }
        .alerta {
            border: 1px solid rgba(248, 113, 113, .4); background: rgba(248, 113, 113, .1); color: var(--rojo);
            border-radius: 10px; padding: .7rem .85rem; font-size: .85rem; margin-bottom: 1rem;
        }
        .campo { margin-bottom: 1rem; }
        label { display: block; font-size: .82rem; font-weight: 600; margin-bottom: .4rem; }
        .control { position: relative; }
        input[type=email], input[type=password], input[type=text] {
            width: 100%; padding: .75rem .9rem; border-radius: 10px; border: 1px solid var(--borde);
            background: var(--input); color: var(--texto); font: inherit; font-size: .95rem;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus { outline: none; border-color: var(--azul); box-shadow: 0 0 0 3px rgba(37, 99, 235, .25); }
        .control input { padding-right: 4.5rem; }
        .ver {
            position: absolute; right: .5rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--suave); font: inherit; font-size: .78rem;
            font-weight: 600; cursor: pointer; padding: .35rem .5rem; border-radius: 6px;
        }
        .ver:hover { color: var(--texto); }
        .error { color: var(--rojo); font-size: .8rem; margin-top: .35rem; }
        .captcha { margin: 1.25rem 0 .25rem; display: flex; flex-direction: column; align-items: center; }
        .boton {
            width: 100%; margin-top: 1rem; padding: .8rem; border: none; border-radius: 10px;
            background: var(--azul); color: #fff; font: inherit; font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: background .15s;
        }
        .boton:hover { background: var(--azul-h); }
        .boton:disabled { opacity: .7; cursor: wait; }
        .pie { margin-top: 1.5rem; text-align: center; font-size: .75rem; color: var(--suave); line-height: 1.6; }
    </style>
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
