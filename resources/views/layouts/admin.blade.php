<!doctype html>
<html x-data="adminApp()" lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('pageTitle', 'Admin') - Smartphone World</title>

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

        <!-- Tailwind CDN (development) -->
    <script>
        window.tailwind = window.tailwind || {};
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'Arial'],
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Fuente por defecto */
        html, body { font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen bg-[#1E293B] text-[#F1F5F9]" @keydown.escape.window="sidebarOpen = false">

<!-- Alpine app component -->
<script>
// En pantallas md+ el menú lateral está siempre visible; en el celular es un panel
// que se abre con el botón ☰ (sidebarOpen solo aplica ahí).
function adminApp() {
    return {
        sidebarOpen: false,
        toggleSidebar() { this.sidebarOpen = !this.sidebarOpen }
    }
}
</script>

<div class="flex">
    <!-- Fondo oscuro detrás del menú abierto en el celular -->
    <div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
         class="fixed inset-0 z-30 bg-black/60 md:hidden" aria-hidden="true"></div>

    <!-- Sidebar -->
    <aside id="menu-panel" :class="sidebarOpen && '!translate-x-0'"
           class="fixed top-0 left-0 h-screen w-60 max-w-[85vw] bg-[#334155] border-r border-[#475569] flex flex-col z-40 -translate-x-full md:translate-x-0 transition-transform duration-200">
        <div class="flex-shrink-0 p-4 border-b border-[#475569]">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#1D4ED8] to-[#0D9488] flex items-center justify-center text-white font-bold">SW</div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-[#F1F5F9]">Smartphone World</div>
                    <div class="text-xs text-[#A0AEC0]">Panel de Administración</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-2" @click="if ($event.target.closest('a')) sidebarOpen = false">
            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-2">PRINCIPAL</div>
            <ul class="mb-2">
                @can('pedidos.ver')
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">📊</span>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
                @endcan
            </ul>

            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-2">TIENDA WEB</div>
            <ul class="mb-2">
                @can('pedidos.ver')
                <li>
                    <a href="{{ route('admin.pedidos.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">📦</span>
                        <span class="font-medium">Pedidos</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $sidebarStats['pedidos_pendientes'] ?? 0 }}</span>
                    </a>
                </li>
                @endcan
                @can('pagos.ver')
                <li>
                    <a href="{{ route('admin.payments.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">💳</span>
                        <span class="font-medium">Verificar Pagos</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $sidebarStats['pagos_por_verificar'] ?? 0 }}</span>
                    </a>
                </li>
                @endcan
                @can('clientes.ver')
                <li>
                    <a href="{{ route('admin.clients.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">👥</span>
                        <span class="font-medium">Clientes</span>
                    </a>
                </li>
                @endcan
                @can('pedidos.crear')
                <li>
                    <a href="{{ route('admin.ventalocal.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">🏷️</span>
                        <span class="font-medium">Venta Local</span>
                    </a>
                </li>
                @endcan
            </ul>

            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-2">INVENTARIO</div>
            <ul class="mb-2">
                @can('productos.ver')
                <li>
                    <a href="{{ route('admin.productos.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">🗂️</span>
                        <span class="font-medium">Productos</span>
                    </a>
                </li>
                @endcan
                @can('inventario.ver')
                <li>
                    <a href="{{ route('admin.inventario.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">📋</span>
                        <span class="font-medium">Movimientos</span>
                    </a>
                </li>
                @endcan
                @can('inventario.ver')
                <li>
                    <a href="{{ route('admin.inventario.alertas') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]">
                        <span class="text-xl">⚠️</span>
                        <span class="font-medium">Alertas Stock</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $sidebarStats['alertas_stock'] ?? 0 }}</span>
                    </a>
                </li>
                @endcan
            </ul>

            @can('reportes.ver')
            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-2">INTELIGENCIA</div>
            <ul class="mb-2">
                <li>
                    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.reports.*') ? 'border-[#1D4ED8] bg-[#475569] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]' }}">
                        <span class="text-xl">🤖</span>
                        <span class="font-medium">Reportes</span>
                    </a>
                </li>
            </ul>

            @endcan

            @canany(['usuarios.gestionar', 'respaldos.gestionar', 'auditoria.ver', 'configuracion.editar'])
            <div class="text-[11px] font-bold text-[#A0AEC0] uppercase tracking-wider px-4 py-2">SISTEMA</div>
            @endcanany
            <ul>
                @can('usuarios.gestionar')
                <li>
                    <a href="{{ route('admin.usuarios.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.usuarios.*') ? 'border-[#1D4ED8] bg-[#475569] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]' }}">
                        <span class="text-xl">👤</span>
                        <span class="font-medium">Usuarios</span>
                    </a>
                </li>
                @endcan
                @can('respaldos.gestionar')
                <li>
                    <a href="{{ route('admin.respaldos.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.respaldos.*') ? 'border-[#1D4ED8] bg-[#475569] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]' }}">
                        <span class="text-xl">💾</span>
                        <span class="font-medium">Respaldos</span>
                    </a>
                </li>
                @endcan
                @can('auditoria.ver')
                <li>
                    <a href="{{ route('admin.auditoria.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.auditoria.*') ? 'border-[#1D4ED8] bg-[#475569] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]' }}">
                        <span class="text-xl">🛡️</span>
                        <span class="font-medium">Auditoría</span>
                    </a>
                </li>
                @endcan
                @can('configuracion.editar')
                <li>
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.settings.*') ? 'border-[#1D4ED8] bg-[#475569] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#475569] hover:text-[#F1F5F9] text-[#A0AEC0]' }}">
                        <span class="text-xl">⚙️</span>
                        <span class="font-medium">Configuración</span>
                    </a>
                </li>
                @endcan
            </ul>
        </nav>

        <div class="flex-shrink-0 p-4 border-t border-[#475569]">
            <a href="{{ route('admin.perfil') }}" title="Mi cuenta" class="flex items-center gap-3 rounded-lg -m-1 p-1 hover:bg-[#475569]/50">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#7C3AED] to-[#1D4ED8] flex items-center justify-center text-sm text-white font-bold">{{ strtoupper(substr(auth()->user()?->name ?? 'A',0,1)) }}</div>
                <div>
                    <div class="text-sm font-semibold text-[#F1F5F9]">{{ auth()->user()?->name ?? 'Admin' }}</div>
                    <div class="text-xs text-[#A0AEC0]">{{ auth()->user()?->role?->nombre ?? 'Admin' }}</div>
                </div>
            </a>
        </div>
    </aside>

    <!-- Main content wrapper -->
    <div class="flex-1 min-w-0 min-h-screen md:ml-60">
        <!-- Topbar -->
        <header class="sticky top-0 h-14 bg-[#334155] border-b border-[#475569] flex items-center px-3 md:px-6 gap-2 md:gap-4 z-20">
            <button type="button" @click="toggleSidebar()" :aria-expanded="sidebarOpen" aria-controls="menu-panel" title="Menú"
                    class="md:hidden w-9 h-9 shrink-0 rounded-lg bg-[#475569] hover:bg-[#1E293B] flex items-center justify-center text-lg">☰</button>
            <h2 class="min-w-0 truncate text-base md:text-lg font-semibold text-[#F1F5F9]">@yield('pageTitle', 'Panel')</h2>

            <div class="hidden sm:flex flex-1 items-center justify-center">
                <form method="GET" action="{{ route('admin.buscar') }}" role="search" class="relative w-full max-w-xs">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#A0AEC0]">🔍</span>
                    <input type="search" name="q" value="{{ request()->routeIs('admin.buscar') ? request('q') : '' }}" minlength="2" maxlength="100"
                           placeholder="Buscar pedido, producto o cliente..." aria-label="Buscar en el panel"
                           class="w-full pl-10 pr-4 py-1.5 bg-[#475569] border border-[#475569] rounded-lg text-sm text-[#F1F5F9] focus:border-[#1D4ED8]" />
                </form>
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2 md:gap-3">
                <a href="{{ route('admin.buscar') }}" title="Buscar" class="sm:hidden w-8 h-8 bg-[#475569] border border-[#475569] rounded-lg flex items-center justify-center">🔍</a>
                @php $totalAlertas = array_sum(array_column($alertasCampana ?? [], 'cantidad')); @endphp
                <div class="relative" x-data="{ abierto: false }" @keydown.escape.window="abierto = false" @click.outside="abierto = false">
                    <button type="button" @click="abierto = !abierto" :aria-expanded="abierto" title="Notificaciones"
                            class="relative w-8 h-8 bg-[#475569] border border-[#475569] rounded-lg hover:bg-[#334155] flex items-center justify-center">
                        🔔
                        @if ($totalAlertas > 0)
                            <span class="absolute -top-1 -right-1 bg-[#EA580C] text-white text-[10px] font-bold rounded-full px-1">{{ $totalAlertas }}</span>
                        @endif
                    </button>
                    <div x-show="abierto" x-cloak x-transition
                         class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-1.5rem)] rounded-xl border border-[#475569] bg-[#1E293B] p-2 shadow-xl">
                        @forelse ($alertasCampana ?? [] as $alerta)
                            <a href="{{ $alerta['url'] }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-[#F1F5F9] hover:bg-[#334155]">
                                <span>{{ $alerta['icono'] }}</span>
                                <span>{{ $alerta['texto'] }}</span>
                            </a>
                        @empty
                            <p class="px-3 py-2 text-sm text-[#A0AEC0]">No hay avisos pendientes.</p>
                        @endforelse
                        <a href="{{ route('admin.perfil') }}" class="mt-1 block border-t border-[#475569] px-3 pt-2 text-xs text-[#60A5FA] hover:underline">Elegir qué avisos recibir</a>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" title="Cerrar sesión" class="w-8 h-8 bg-[#475569] border border-[#475569] rounded-lg hover:bg-[#334155] flex items-center justify-center text-[#EA580C]">⏻</button>
                </form>
            </div>
        </header>

        <main class="p-4 md:p-6 min-h-[calc(100vh-56px)]">
            @if (session('warning'))
                <div class="mb-4 rounded-lg border border-amber-500/40 bg-amber-500/10 p-3 text-sm text-amber-200" role="alert">{{ session('warning') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</div>

<!-- Alpine.js CDN -->
@livewireScripts
</body>
</html>