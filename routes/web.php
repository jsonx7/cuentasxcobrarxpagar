<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PayablePaymentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceivableController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::resource('clientes', ClientController::class)
        ->only(['index', 'show'])
        ->parameters(['clientes' => 'client'])
        ->where(['client' => '[0-9]+'])
        ->names([
            'index' => 'clients.index',
            'show' => 'clients.show',
        ]);

    Route::resource('materiales', MaterialController::class)
        ->only(['index', 'show'])
        ->parameters(['materiales' => 'material'])
        ->where(['material' => '[0-9]+'])
        ->names([
            'index' => 'materials.index',
            'show' => 'materials.show',
        ]);

    Route::resource('proveedores', SupplierController::class)
        ->only(['index', 'show'])
        ->parameters(['proveedores' => 'supplier'])
        ->where(['supplier' => '[0-9]+'])
        ->names([
            'index' => 'suppliers.index',
            'show' => 'suppliers.show',
        ]);

    Route::resource('cuentas-por-cobrar', ReceivableController::class)
        ->only(['index', 'show'])
        ->parameters(['cuentas-por-cobrar' => 'receivable'])
        ->where(['receivable' => '[0-9]+'])
        ->names([
            'index' => 'receivables.index',
            'show' => 'receivables.show',
        ]);
    Route::get('cuentas-por-cobrar/{receivable}/pdf', [ReceivableController::class, 'pdf'])
        ->name('receivables.pdf')
        ->whereNumber('receivable');
    Route::get('cuentas-por-cobrar/{receivable}/estado/pdf', [ReceivableController::class, 'estadoPdf'])
        ->name('receivables.estado.pdf')
        ->whereNumber('receivable');

    Route::resource('abonos', PaymentController::class)
        ->only(['index'])
        ->names(['index' => 'payments.index']);

    Route::resource('cuentas-por-pagar', PayableController::class)
        ->only(['index', 'show'])
        ->parameters(['cuentas-por-pagar' => 'payable'])
        ->where(['payable' => '[0-9]+'])
        ->names([
            'index' => 'payables.index',
            'show' => 'payables.show',
        ]);

    Route::get('cuentas-por-pagar/vencidas', [PayableController::class, 'overdue'])
        ->name('payables.overdue');

    Route::get('cuentas-por-pagar/{payable}/pdf', [PayableController::class, 'pdf'])
        ->name('payables.pdf')
        ->whereNumber('payable');

    Route::resource('pagos', PayablePaymentController::class)
        ->only(['index'])
        ->names(['index' => 'payable-payments.index']);

    Route::get('reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reportes/consolidado', [ReportController::class, 'consolidated'])->name('reports.consolidated');
    Route::get('reportes/consolidado/pdf', [ReportController::class, 'consolidatedPdf'])->name('reports.consolidated.pdf');
    Route::get('reportes/vencidas', [ReportController::class, 'overdue'])->name('reports.overdue');
    Route::get('reportes/vencidas/pdf', [ReportController::class, 'overduePdf'])->name('reports.overdue.pdf');

    Route::middleware('role:admin,operador')->group(function () {
        Route::resource('clientes', ClientController::class)
            ->except(['index', 'show'])
            ->parameters(['clientes' => 'client'])
            ->where(['client' => '[0-9]+'])
            ->names([
                'create' => 'clients.create',
                'store' => 'clients.store',
                'edit' => 'clients.edit',
                'update' => 'clients.update',
                'destroy' => 'clients.destroy',
            ]);

        Route::resource('materiales', MaterialController::class)
            ->except(['index', 'show'])
            ->parameters(['materiales' => 'material'])
            ->where(['material' => '[0-9]+'])
            ->names([
                'create' => 'materials.create',
                'store' => 'materials.store',
                'edit' => 'materials.edit',
                'update' => 'materials.update',
                'destroy' => 'materials.destroy',
            ]);

        Route::resource('proveedores', SupplierController::class)
            ->except(['index', 'show'])
            ->parameters(['proveedores' => 'supplier'])
            ->where(['supplier' => '[0-9]+'])
            ->names([
                'create' => 'suppliers.create',
                'store' => 'suppliers.store',
                'edit' => 'suppliers.edit',
                'update' => 'suppliers.update',
                'destroy' => 'suppliers.destroy',
            ]);

        Route::resource('cuentas-por-cobrar', ReceivableController::class)
            ->only(['create', 'store', 'destroy'])
            ->parameters(['cuentas-por-cobrar' => 'receivable'])
            ->where(['receivable' => '[0-9]+'])
            ->names([
                'create' => 'receivables.create',
                'store' => 'receivables.store',
                'destroy' => 'receivables.destroy',
            ]);

        Route::post('cuentas-por-cobrar/{receivable}/cancelar', [ReceivableController::class, 'cancel'])
            ->name('receivables.cancel')
            ->whereNumber('receivable');
        Route::post('cuentas-por-cobrar/{receivable}/abonos', [PaymentController::class, 'store'])
            ->name('payments.store')
            ->whereNumber('receivable');
        Route::delete('abonos/{payment}', [PaymentController::class, 'destroy'])
            ->name('payments.destroy')
            ->whereNumber('payment');

        Route::resource('cuentas-por-pagar', PayableController::class)
            ->only(['create', 'store', 'destroy'])
            ->parameters(['cuentas-por-pagar' => 'payable'])
            ->where(['payable' => '[0-9]+'])
            ->names([
                'create' => 'payables.create',
                'store' => 'payables.store',
                'destroy' => 'payables.destroy',
            ]);

        Route::post('cuentas-por-pagar/{payable}/cancelar', [PayableController::class, 'cancel'])
            ->name('payables.cancel')
            ->whereNumber('payable');
        Route::post('cuentas-por-pagar/{payable}/pagos', [PayablePaymentController::class, 'store'])
            ->name('payable-payments.store')
            ->whereNumber('payable');
        Route::delete('pagos/{payablePayment}', [PayablePaymentController::class, 'destroy'])
            ->name('payable-payments.destroy')
            ->whereNumber('payablePayment');

        Route::middleware('role:admin')->group(function () {
            Route::resource('usuarios', UserController::class)
                ->except(['show', 'destroy'])
                ->parameters(['usuarios' => 'user'])
                ->where(['user' => '[0-9]+'])
                ->names([
                    'index' => 'users.index',
                    'create' => 'users.create',
                    'store' => 'users.store',
                    'edit' => 'users.edit',
                    'update' => 'users.update',
                ]);
        });
    });
});
