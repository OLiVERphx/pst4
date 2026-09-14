<aside class="main-sidebar {{ config('adminlte.classes_sidebar', 'sidebar-dark-primary elevation-4') }}">

    {{-- Sidebar brand logo --}}
    @if(config('adminlte.logo_img_xl'))
        @include('adminlte::partials.common.brand-logo-xl')
    @else
        @include('adminlte::partials.common.brand-logo-xs')
    @endif

    {{-- Sidebar menu --}}
    <div class="sidebar">
        <nav class="pt-2">
            <ul class="nav nav-pills nav-sidebar flex-column {{ config('adminlte.classes_sidebar_nav', '') }}"
                data-widget="treeview" role="menu"
                @if(config('adminlte.sidebar_nav_animation_speed') != 300)
                    data-animation-speed="{{ config('adminlte.sidebar_nav_animation_speed') }}"
                @endif
                @if(!config('adminlte.sidebar_nav_accordion'))
                    data-accordion="false"
                @endif>
                {{-- Configured sidebar links --}}
                @each('adminlte::partials.sidebar.menu-item', $adminlte->menu('sidebar'), 'item')

                {{-- Custom Smartphone World links --}}
                <li class="nav-header">Smartphone World</li>
                <li class="nav-item">
                    <a href="{{ route('admin.inventario.lista') }}" class="nav-link">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>Inventario</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.inventario.alertas') }}" class="nav-link">
                        <i class="nav-icon fas fa-bell"></i>
                        <p>Alertas stock</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pedidos.lista') }}" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>Pedidos</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.pagos.lista') }}" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>Pagos</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.clientes.lista') }}" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Clientes</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

</aside>
