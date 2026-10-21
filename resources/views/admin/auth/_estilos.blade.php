{{-- Estilos compartidos por las pantallas de acceso al panel (login y código de verificación). --}}
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
        .codigo { text-align: center; font-size: 1.5rem !important; letter-spacing: .4em; font-variant-numeric: tabular-nums; }
        .enlace { background: none; border: none; color: var(--suave); font: inherit; font-size: .82rem; cursor: pointer; text-decoration: underline; }
        .enlace:hover { color: var(--texto); }
        .acciones { margin-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; gap: .75rem; flex-wrap: wrap; }
    </style>
