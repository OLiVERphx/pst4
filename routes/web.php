<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;


// Admin authentication routes
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('admin.logout');

// Admin protected routes
Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // El dashboard en sí puede ser visto si pasó el middleware admin, pero lo aseguramos con pedidos.ver como base general
        Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware('can:pedidos.ver');

        Route::get('products', [\App\Http\Controllers\Admin\ProductController::class, 'index'])
            ->name('productos.lista')
            ->middleware('can:productos.ver');

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

        Route::get('settings', [\App\Http\Controllers\Admin\SettingsController::class, 'index'])
            ->name('settings.index')
            ->middleware('can:configuracion.editar');

        Route::post('settings', [\App\Http\Controllers\Admin\ConfiguracionController::class, 'update'])
            ->name('configuracion.update')
            ->middleware('can:configuracion.editar');

        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])
            ->name('reports.index')
            ->middleware('can:reportes.ver');

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
    });


// -- TIENDA WEB P�BLICA ----------------------------------
Route::middleware('guest:web')->group(function () {
    Route::get('/login',    [\App\Http\Controllers\Web\AuthController::class, 'showLogin'])
        ->name('web.login');
    Route::post('/login',   [\App\Http\Controllers\Web\AuthController::class, 'login']);
    Route::get('/registro', [\App\Http\Controllers\Web\AuthController::class, 'showRegister'])
        ->name('web.register');
    Route::post('/registro', [\App\Http\Controllers\Web\AuthController::class, 'register']);
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
    Route::get('/{id}/relacionados', [\App\Http\Controllers\Api\CatalogController::class, 'related']);
    Route::get('/{id}',     [\App\Http\Controllers\Api\CatalogController::class, 'show']);
});

