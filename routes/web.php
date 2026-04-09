<?php

use App\Http\Controllers\Catalogo\CatalogoController;
use App\Http\Controllers\Catalogo\CategoryController;
use App\Http\Controllers\Catalogo\ProductoController;
use App\Http\Controllers\Catalogo\StockController;
use App\Http\Controllers\Catalogo\VarianteController;
use App\Http\Controllers\AdminPanel\AdminBalancesController;
use App\Http\Controllers\AdminPanel\AdminDashboardController;
use App\Http\Controllers\AdminPanel\AdminEmpresaController;
use App\Http\Controllers\AdminPanel\AdminSettingsController;
use App\Http\Controllers\AdminPanel\AdminTarjetasController;
use App\Http\Controllers\AdminPanel\AdminUsersController;
use App\Http\Controllers\AdminPanel\AdminVentasController;
use App\Http\Controllers\Caja\CajaController;
use App\Http\Controllers\Caja\TicketController;
use App\Http\Controllers\CuentasCorrientes\ClienteController;
use App\Http\Controllers\CuentasCorrientes\CuentaCorrienteController;
use App\Http\Controllers\Fiscal\ComprobanteController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('catalogo')->name('catalogo.')->group(function (): void {
        Route::get('/', [CatalogoController::class, 'index'])->name('index');
        Route::get('/productos/table', [CatalogoController::class, 'productsTable'])->name('productos.table');
        Route::get('/productos/{producto}/panel', [CatalogoController::class, 'productPanel'])->name('productos.panel');
        Route::get('/productos/{producto}/stock-modal', [CatalogoController::class, 'stockModal'])->name('productos.stock-modal');

        Route::post('/categorias', [CategoryController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [CategoryController::class, 'update'])->name('categorias.update');
        Route::patch('/categorias/{categoria}/toggle', [CategoryController::class, 'toggle'])->name('categorias.toggle');
        Route::delete('/categorias/{categoria}', [CategoryController::class, 'destroy'])->name('categorias.destroy');

        Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos.create');
        Route::post('/productos', [ProductoController::class, 'store'])->name('productos.store');
        Route::get('/productos/{producto}', [ProductoController::class, 'show'])->name('productos.show');
        Route::get('/productos/{producto}/edit', [ProductoController::class, 'edit'])->name('productos.edit');
        Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos.update');
        Route::patch('/productos/{producto}/toggle', [ProductoController::class, 'toggle'])->name('productos.toggle');

        Route::get('/productos/{producto}/variantes/create', [VarianteController::class, 'create'])->name('variantes.create');
        Route::post('/productos/{producto}/variantes', [VarianteController::class, 'store'])->name('variantes.store');
        Route::get('/productos/{producto}/variantes/generador', [VarianteController::class, 'generator'])->name('variantes.generator');
        Route::post('/productos/{producto}/variantes/generate', [VarianteController::class, 'generate'])->name('variantes.generate');
        Route::get('/variantes/{variante}/edit', [VarianteController::class, 'edit'])->name('variantes.edit');
        Route::put('/variantes/{variante}', [VarianteController::class, 'update'])->name('variantes.update');
        Route::delete('/variantes/{variante}', [VarianteController::class, 'destroy'])->name('variantes.destroy');

        Route::put('/productos/{producto}/stock', [StockController::class, 'update'])->name('stock.update');
    });

    Route::prefix('cuentas-corrientes')->name('cuentas-corrientes.')->group(function (): void {
        Route::get('/', [CuentaCorrienteController::class, 'index'])->name('index');
        Route::get('/lookup-dni', [CuentaCorrienteController::class, 'lookupDni'])->name('lookup-dni');
        Route::post('/', [CuentaCorrienteController::class, 'store'])->name('store');
        Route::get('/{cuentaCorriente}/pagos/create', [CuentaCorrienteController::class, 'createPayment'])->name('payments.create');
        Route::get('/{cuentaCorriente}', [CuentaCorrienteController::class, 'show'])->name('show');
        Route::patch('/{cuentaCorriente}/toggle', [CuentaCorrienteController::class, 'toggle'])->name('toggle');
        Route::post('/{cuentaCorriente}/pagos', [CuentaCorrienteController::class, 'registerPayment'])->name('payments.store');
        Route::patch('/clientes/{cliente}/toggle', [ClienteController::class, 'toggle'])->name('clientes.toggle');
    });

    Route::prefix('caja')->name('caja.')->group(function (): void {
        Route::get('/', [CajaController::class, 'index'])->name('pos');
        Route::get('/buscar', [CajaController::class, 'search'])->name('buscar');
        Route::post('/abrir', [CajaController::class, 'open'])->name('abrir');
        Route::post('/cerrar', [CajaController::class, 'close'])->name('cerrar');
        Route::post('/scan', [CajaController::class, 'scan'])->name('scan');
        Route::post('/carrito/agregar/{variante}', [CajaController::class, 'addVariant'])->name('carrito.agregar');
        Route::post('/carrito/qty/{variante}', [CajaController::class, 'setQuantity'])->name('carrito.qty');
        Route::post('/carrito/precio/{variante}', [CajaController::class, 'setPrice'])->name('carrito.precio');
        Route::post('/carrito/quitar/{variante}', [CajaController::class, 'removeVariant'])->name('carrito.quitar');
        Route::post('/carrito/vaciar', [CajaController::class, 'clearCart'])->name('carrito.vaciar');
        Route::post('/pagos/agregar', [CajaController::class, 'addPayment'])->name('pagos.agregar');
        Route::post('/pagos/vaciar', [CajaController::class, 'clearPayments'])->name('pagos.vaciar');
        Route::post('/pagos/{index}', [CajaController::class, 'updatePayment'])->name('pagos.update');
        Route::post('/pagos/{index}/quitar', [CajaController::class, 'deletePayment'])->name('pagos.quitar');
        Route::post('/fiscal-draft', [CajaController::class, 'saveFiscalDraft'])->name('fiscal-draft.save');
        Route::post('/confirmar', [CajaController::class, 'confirmSale'])->name('confirmar');
        Route::post('/confirmar-preview', [CajaController::class, 'confirmSale'])->name('confirmar.preview');
        Route::post('/fiscal/reintentar/{ventaComprobante}', [CajaController::class, 'retryFiscalDocument'])->name('fiscal.retry');
        Route::get('/ticket/{venta}', TicketController::class)->name('ticket');
    });

    Route::prefix('fiscal')->name('fiscal.')->group(function (): void {
        Route::get('/comprobantes/{ventaComprobante}', ComprobanteController::class)->name('comprobantes.show');
    });

    Route::prefix('admin-panel')->name('admin-panel.')->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/ventas', [AdminVentasController::class, 'index'])->name('ventas.index');
        Route::get('/ventas/{venta}', [AdminVentasController::class, 'show'])->name('ventas.show');
        Route::get('/balances', [AdminBalancesController::class, 'index'])->name('balances.index');
        Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::post('/settings/arca/generate-csr', [AdminSettingsController::class, 'generateArcaCsr'])->name('settings.arca.generate-csr');
        Route::post('/settings/arca/upload-certificate', [AdminSettingsController::class, 'uploadArcaCertificate'])->name('settings.arca.upload-certificate');
        Route::post('/settings/arca/validate-credentials', [AdminSettingsController::class, 'validateArcaCredentials'])->name('settings.arca.validate-credentials');
        Route::post('/settings/arca/probe', [AdminSettingsController::class, 'probeArca'])->name('settings.arca.probe');
        Route::get('/empresa', [AdminEmpresaController::class, 'index'])->name('empresa.index');
        Route::put('/empresa', [AdminEmpresaController::class, 'updateCompany'])->name('empresa.update');
        Route::post('/sucursales', [AdminEmpresaController::class, 'storeBranch'])->name('sucursales.store');
        Route::put('/sucursales/{sucursal}', [AdminEmpresaController::class, 'updateBranch'])->name('sucursales.update');
        Route::patch('/sucursales/{sucursal}/toggle', [AdminEmpresaController::class, 'toggleBranch'])->name('sucursales.toggle');
        Route::get('/tarjetas', [AdminTarjetasController::class, 'index'])->name('tarjetas.index');
        Route::post('/tarjetas', [AdminTarjetasController::class, 'store'])->name('tarjetas.store');
        Route::put('/tarjetas/{planCuota}', [AdminTarjetasController::class, 'update'])->name('tarjetas.update');
        Route::delete('/tarjetas/{planCuota}', [AdminTarjetasController::class, 'destroy'])->name('tarjetas.destroy');
        Route::get('/usuarios', [AdminUsersController::class, 'index'])->name('users.index');
        Route::post('/usuarios', [AdminUsersController::class, 'storeUser'])->name('users.store');
        Route::put('/usuarios/{user}', [AdminUsersController::class, 'updateUser'])->name('users.update');
        Route::patch('/usuarios/{user}/toggle', [AdminUsersController::class, 'toggleUser'])->name('users.toggle');
        Route::put('/usuarios/{user}/password', [AdminUsersController::class, 'updatePassword'])->name('users.password');
        Route::post('/roles', [AdminUsersController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [AdminUsersController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [AdminUsersController::class, 'destroyRole'])->name('roles.destroy');
    });
});

require __DIR__.'/auth.php';
