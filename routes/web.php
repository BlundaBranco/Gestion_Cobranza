<?php

use App\Http\Controllers\Api\ClientInstallmentController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\LotController;
use App\Http\Controllers\LotTransferController;
use App\Http\Controllers\PaymentPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\OwnerTransferController;
use App\Http\Controllers\BulkInstallmentUpdateController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Recursos CRUD Principales
    Route::resource('clients', ClientController::class);
    Route::resource('lots', LotController::class);
    Route::resource('services', ServiceController::class);

    // Planes de Pago (anidado en Lotes)
    Route::post('lots/{lot}/payment-plans', [PaymentPlanController::class, 'store'])->name('lots.payment-plans.store');
    Route::delete('payment-plans/{plan}', [PaymentPlanController::class, 'destroy'])->name('payment-plans.destroy');
    
    // Transacciones
    Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('transactions/{transaction}/pdf', [TransactionController::class, 'showPdf'])->name('transactions.pdf');
    
    // Ruta tipo API para obtener cuotas
    Route::get('/clients/{client}/pending-installments', [ClientInstallmentController::class, 'index'])->name('clients.pending-installments');

    // Acciones Específicas
    Route::post('/installments/{installment}/condone-interest', [InstallmentController::class, 'condoneInterest'])->name('installments.condone');
    Route::post('lots/{lot}/transfer', [LotTransferController::class, 'transfer'])->name('lots.transfer');
    
    // Documentos (anidado en Clientes)
    Route::post('clients/{client}/documents', [ClientDocumentController::class, 'store'])->name('clients.documents.store');
    Route::delete('documents/{document}', [ClientDocumentController::class, 'destroy'])->name('documents.destroy');

    // Reportes 
    Route::get('reports/income', [ReportController::class, 'incomeReport'])->name('reports.income');

    // Propietarios
    Route::resource('owners', OwnerController::class);
    Route::post('lots/{lot}/transfer-owner', [OwnerTransferController::class, 'transfer'])->name('lots.transfer-owner');

    Route::put('/installments/{installment}', [InstallmentController::class, 'update'])->name('installments.update');
    Route::post('/payment-plans/{plan}/bulk-update-installments', [BulkInstallmentUpdateController::class, 'update'])->name('installments.bulk-update');
    Route::get('reports/overdue-installments', [ReportController::class, 'overdueInstallments'])->name('reports.overdue');

});

require __DIR__.'/auth.php';