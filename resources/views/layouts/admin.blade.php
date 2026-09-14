<!doctype html>
<html x-data="adminApp()" :class="darkMode ? 'dark' : ''" lang="es">
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
<body class="min-h-screen bg-[#0B0E17] text-[#F1F5F9]">

<!-- Alpine app component -->
<script>
function adminApp() {
    return {
        darkMode: (localStorage.getItem('theme') !== 'light'),
        sidebarOpen: window.innerWidth > 768,
        toggleTheme() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        },
        toggleSidebar() { this.sidebarOpen = !this.sidebarOpen }
    }
}
</script>

<div class="flex">
    <!-- Sidebar -->
    <aside x-show="sidebarOpen" x-cloak class="fixed top-0 left-0 h-screen w-60 bg-[#131720] border-r border-[#384457] flex flex-col z-40">
        <div class="flex-shrink-0 p-4 border-b border-[#384457]">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-[#1D4ED8] to-[#0D9488] flex items-center justify-center text-white font-bold">SW</div>
                <div class="ml-3">
                    <div class="text-sm font-semibold text-[#F1F5F9]">Smartphone World</div>
                    <div class="text-xs text-[#94A3B8]">Panel de Administración</div>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-2">
            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-2">PRINCIPAL</div>
            <ul class="mb-2">
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">📊</span>
                        <span class="font-medium">Dashboard</span>
                    </a>
                </li>
            </ul>

            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-2">TIENDA WEB</div>
            <ul class="mb-2">
                <li>
                    <a href="{{ route('admin.pedidos.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">📦</span>
                        <span class="font-medium">Pedidos</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $stats['pedidos_pendientes'] ?? 0 }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.payments.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">💳</span>
                        <span class="font-medium">Verificar Pagos</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $stats['pagos_por_verificar'] ?? 0 }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.clients.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">👥</span>
                        <span class="font-medium">Clientes</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.ventalocal.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">🏷️</span>
                        <span class="font-medium">Venta Local</span>
                    </a>
                </li>
            </ul>

            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-2">INVENTARIO</div>
            <ul class="mb-2">
                <li>
                    <a href="{{ route('admin.productos.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">🗂️</span>
                        <span class="font-medium">Productos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.inventario.lista') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">📋</span>
                        <span class="font-medium">Movimientos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ url('/admin/inventory/alerts') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]">
                        <span class="text-xl">⚠️</span>
                        <span class="font-medium">Alertas Stock</span>
                        <span class="ml-auto bg-[#EA580C] text-white text-[11px] font-bold rounded-full px-2 py-0.5">{{ $stats['alertas_stock'] ?? 0 }}</span>
                    </a>
                </li>
            </ul>

            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-2">INTELIGENCIA</div>
            <ul class="mb-2">
                <li>
                    <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.reports.*') ? 'border-[#1D4ED8] bg-[#1C2130] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]' }}">
                        <span class="text-xl">🤖</span>
                        <span class="font-medium">Reportes ML</span>
                    </a>
                </li>
            </ul>

            <div class="text-[11px] font-bold text-[#94A3B8] uppercase tracking-wider px-4 py-2">SISTEMA</div>
            <ul>
                @can('auditoria.ver')
                <li>
                    <a href="{{ route('admin.auditoria.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.auditoria.*') ? 'border-[#1D4ED8] bg-[#1C2130] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]' }}">
                        <span class="text-xl">🛡️</span>
                        <span class="font-medium">Auditoría</span>
                    </a>
                </li>
                @endcan
                <li>
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm border-l-[3px] {{ request()->routeIs('admin.settings.*') ? 'border-[#1D4ED8] bg-[#1C2130] text-[#F1F5F9] font-semibold' : 'border-transparent hover:bg-[#1C2130] hover:text-[#F1F5F9] text-[#94A3B8]' }}">
                        <span class="text-xl">⚙️</span>
                        <span class="font-medium">Configuración</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="flex-shrink-0 p-4 border-t border-[#2A3047]">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#7C3AED] to-[#1D4ED8] flex items-center justify-center text-sm text-white font-bold">{{ strtoupper(substr(auth()->user()?->name ?? 'A',0,1)) }}</div>
                <div>
                    <div class="text-sm font-semibold text-[#F1F5F9]">{{ auth()->user()?->name ?? 'Admin' }}</div>
                    <div class="text-xs text-[#94A3B8]">{{ auth()->user()?->role?->nombre ?? 'Admin' }}</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main content wrapper -->
    <div :class="sidebarOpen ? 'ml-60' : 'ml-0'" class="flex-1 min-h-screen transition-all duration-200">
        <!-- Topbar -->
        <header class="sticky top-0 ml-60 h-14 bg-[#131720] border-b border-[#384457] flex items-center px-6 gap-4 z-20">
            <h2 class="text-lg font-semibold text-[#F1F5F9]">@yield('pageTitle', 'Panel')</h2>

            <div class="flex-1 flex items-center justify-center">
                <div class="relative w-full max-w-xs">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">🔍</span>
                    <input type="text" placeholder="Buscar..." class="w-full pl-10 pr-4 py-1.5 bg-[#1C2130] border border-[#2A3047] rounded-lg text-sm text-[#F1F5F9] focus:border-[#1D4ED8]" />
                </div>
            </div>

            <div class="ml-auto flex items-center gap-3">
                <button @click="toggleTheme()" class="w-8 h-8 bg-[#1C2130] border border-[#2A3047] rounded-lg hover:bg-[#242A3D] flex items-center justify-center">🌙</button>
                <button class="relative w-8 h-8 bg-[#1C2130] border border-[#2A3047] rounded-lg hover:bg-[#242A3D] flex items-center justify-center">
                    🔔
                    <span class="absolute -top-1 -right-1 bg-[#EA580C] text-white text-[10px] font-bold rounded-full px-1">{{ $stats['notificaciones'] ?? 0 }}</span>
                </button>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="ml-2 text-sm text-[#EA580C]">Logout</button>
                </form>
            </div>
        </header>

        <main class="pt-14 p-6 min-h-[calc(100vh-56px)]">
            @yield('content')
        </main>
    </div>
</div>

<!-- Alpine.js CDN -->
@livewireScripts
</body>
</html>