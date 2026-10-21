<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;


// Ruta del panel: ADMIN_PATH (por defecto "admin"). Cambiarla no reemplaza a las
// contraseñas ni al límite de intentos; solo saca al panel de la ruta que todos prueban.
$rutaPanel = config('app.admin_path');

// Admin authentication routes
Route::get("/{$rutaPanel}/login", [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post("/{$rutaPanel}/login", [AdminAuthController::class, 'login'])->middleware('throttle:5,1');
Route::post("/{$rutaPanel}/logout", [AdminAuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

// Admin protected routes
Route::middleware(['auth', 'active', 'admin', 'dos_factores'])
    ->prefix($rutaPanel)
    ->name('admin.')
    ->group(function () {
        // Verificación en dos pasos (MiddlewareDosFactores decide cuáles se pueden ver antes del código)
        Route::controller(\App\Http\Controllers\Admin\DosFactoresController::class)
            ->prefix('verificacion')
            ->name('2fa.')
            ->group(function () {
                Route::get('codigo', 'desafio')->name('desafio');
                Route::post('codigo', 'verificar')->name('verificar')->middleware('throttle:5,1');
                Route::get('activar', 'configurar')->name('configurar');
                Route::post('activar', 'activar')->name('activar')->middleware('throttle:5,1');
                Route::get('codigos-recuperacion', 'codigos')->name('codigos');
                Route::post('codigos-recuperacion', 'regenerar')->name('regenerar')->middleware('throttle:5,1');
                Route::delete('/', 'desactivar')->name('desactivar')->middleware('throttle:5,1');
            });

        // El dashboard en sí puede ser visto si pasó el middleware admin, pero lo aseguramos con pedidos.ver como base general
        Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('can:pedidos.ver');

        Route::get('products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])
            ->name('productos.lista')
            ->middleware('can:productos.ver');

        Route::get('products/importar', [\App\Http\Controllers\Admin\ProductController::class, 'importar'])
            ->name('productos.importar')
            ->middleware('can:productos.editar');

        Route::get('products/importar/plantilla', [\App\Http\Controllers\Admin\ProductController::class, 'plantillaImportacion'])
            ->name('productos.importar.plantilla')
            ->middleware('can:productos.editar');

        Route::get('inventory', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])
            ->name('inventario.lista')
            ->middleware('can:inventario.ver');

        Route::get('inventory/alerts', [\App\Http\Controllers\Admin\InventoryController::class, 'alerts'])
            ->name('inventario.alertas')
            ->middleware('can:inventario.ver');

        Route::get('orders', [\App\Http\Controllers\Admin\OrderController::class, 'index'])
            ->name('pedidos.lista')
            ->middleware('can:pedidos.ver');

        Route::get('payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])
            ->name('payments.lista')
            ->middleware('can:pagos.ver');

        Route::get('payments/{payment}/receipt', [\App\Http\Controllers\Admin\PaymentController::class, 'receipt'])
            ->name('payments.receipt')
            ->middleware('can:pagos.ver');

        Route::get('clients', [\App\Http\Controllers\Admin\ClientController::class, 'index'])
            ->name('clients.lista')
            ->middleware('can:clientes.ver');

        // Buscador de la barra superior (cada sección filtra por permisos)
        Route::get('buscar', \App\Http\Controllers\Admin\BuscarController::class)->name('buscar');

        // Cuenta propia: cualquier usuario del panel
        Route::get('perfil', [\App\Http\Controllers\Admin\PerfilController::class, 'show'])->name('perfil');
        Route::put('perfil/password', [\App\Http\Controllers\Admin\PerfilController::class, 'actualizarPassword'])
            ->name('perfil.password')
            ->middleware('throttle:6,1');
        Route::put('perfil/notificaciones', [\App\Http\Controllers\Admin\PerfilController::class, 'actualizarNotificaciones'])
            ->name('perfil.notificaciones');

        Route::post('settings/limpiar-cache', [\App\Http\Controllers\Admin\SettingsController::class, 'limpiarCache'])
            ->name('settings.limpiar_cache')
            ->middleware(['can:configuracion.editar', 'throttle:3,1']);

        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])
            ->name('settings.index')
            ->middleware('can:configuracion.editar');

        Route::post('settings', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'update'])
            ->name('configuracion.update')
            ->middleware('can:configuracion.editar');

        Route::post('settings/datos-tienda', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'actualizarDatosTienda'])
            ->name('configuracion.datos_tienda')
            ->middleware('can:configuracion.editar');

        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
            ->name('reports.index')
            ->middleware('can:reportes.ver');

        Route::post('reports/ml/recalcular', [\App\Http\Controllers\Admin\ReportController::class, 'recalcularMl'])
            ->name('reports.ml.recalcular')
            ->middleware(['can:reportes.ver', 'throttle:6,1']);

        // Exportar reportes (CSV por defecto). Excel solo si librería instalada.
        Route::get('reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])
            ->name('reports.export')
            ->middleware('can:reportes.ver');

        Route::get('auditoria', [\App\Http\Controllers\Admin\AuditController::class, 'index'])
            ->name('auditoria.index')
            ->middleware('can:auditoria.ver');

        // Ruta para la venta en local (Livewire)
        Route::get('venta-local', function () {
            return view('admin.venta-local.index');
        })
            ->name('ventalocal.index')
            ->middleware('can:pedidos.crear');

        // Control de Usuarios (Solo Superadmin)
        Route::get('usuarios', function () {
            return view('admin.usuarios.index');
        })
            ->name('usuarios.index')
            ->middleware('can:usuarios.gestionar');

        // Respaldos de Base de Datos (Solo Superadmin)
        Route::get('respaldos', function () {
            return view('admin.respaldos.index');
        })
            ->name('respaldos.index')
            ->middleware('can:respaldos.gestionar');

        Route::get('respaldos/descargar/{archivo}', function (string $archivo, \App\Services\ServicioRespaldos $servicio) {
            return $servicio->descargarRespaldo($archivo);
        })
            ->name('respaldos.descargar')
            ->middleware('can:respaldos.gestionar');
    });


// -- TIENDA WEB P�BLICA ----------------------------------
Route::middleware('guest:web')->group(function () {
    Route::get('/login',    [\App\Http\Controllers\Web\AuthController::class, 'showLogin'])
        ->name('web.login');
    Route::post('/login',   [\App\Http\Controllers\Web\AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/registro', [\App\Http\Controllers\Web\AuthController::class, 'showRegister'])
        ->name('web.register');
    Route::post('/registro', [\App\Http\Controllers\Web\AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::post('/logout', [\App\Http\Controllers\Web\AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('web.logout');

Route::get('/',                  [\App\Http\Controllers\Web\CatalogController::class, 'home'])
    ->name('web.home');
Route::get('/catalogo',          [\App\Http\Controllers\Web\CatalogController::class, 'catalog'])
    ->name('web.catalog');
Route::get('/producto/{product:slug}', [\App\Http\Controllers\Web\CatalogController::class, 'show'])
    ->name('web.product');

Route::middleware('auth')->group(function () {
    Route::get('/checkout',  [\App\Http\Controllers\Web\CheckoutController::class, 'index'])
        ->name('web.checkout');
    Route::post('/checkout/reserve', [\App\Http\Controllers\Web\CheckoutController::class, 'reserve'])
        ->name('web.checkout.reserve');
    Route::post('/checkout', [\App\Http\Controllers\Web\CheckoutController::class, 'store'])
        ->name('web.checkout.store');
    Route::get('/pedido/{numero}/confirmado', [\App\Http\Controllers\Web\CheckoutController::class, 'confirmed'])
        ->name('web.order.confirmed');
    Route::get('/mi-cuenta', [\App\Http\Controllers\Web\AccountController::class, 'index'])
        ->name('web.account');
    Route::match(['post', 'put'], '/mi-cuenta/perfil', [\App\Http\Controllers\Web\AccountController::class, 'updateProfile'])
        ->name('web.account.update');

    // Carrito persistente API (usuario o invitado via token en sesión)
    Route::post('/cart/add', [\App\Http\Controllers\Web\CartController::class, 'add'])
        ->name('web.cart.add');
    Route::get('/cart', [\App\Http\Controllers\Web\CartController::class, 'current'])
        ->name('web.cart.current');
});

// -- API P�BLICA (cat�logo + b�squeda) -------------------
Route::prefix('api/catalogo')->group(function () {
    Route::get('/',         [\App\Http\Controllers\Api\CatalogController::class, 'index']);
    Route::get('/buscar',   [\App\Http\Controllers\Api\CatalogController::class, 'search']);
    // {id} solo numerico: otro texto (p. ej. "1 AND 1=1") da 404 y no un error 500
    Route::get('/{id}/relacionados', [\App\Http\Controllers\Api\CatalogController::class, 'related'])->whereNumber('id');
    Route::get('/{id}',     [\App\Http\Controllers\Api\CatalogController::class, 'show'])->whereNumber('id');
});

