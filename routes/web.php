<?php

use App\Http\Controllers\FinanzasController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
})->name('home');

Route::get('inicio', function () {
    return Auth::check()
        ? to_route('interfaz')
        : view('user-related.inicio');
})->name('inicio');

Route::get('registro', function () {
    return Auth::check()
        ? to_route('interfaz')
        : view('user-related.registro');
})->name('registro');

Route::middleware('auth')->group(function () {
    Route::get('interfaz', [FinanzasController::class, 'index'])->name('interfaz');
    Route::get('seguimiento', [FinanzasController::class, 'seguimiento'])->name('seguimiento');
    Route::post('cuentas', [FinanzasController::class, 'storeCuenta'])->name('cuentas.store');
    Route::patch('cuentas/{cuenta}', [FinanzasController::class, 'updateCuenta'])->name('cuentas.update');
    Route::delete('cuentas/{cuenta}', [FinanzasController::class, 'destroyCuenta'])->name('cuentas.destroy');
    Route::post('categorias', [FinanzasController::class, 'storeCategoria'])->name('categorias.store');
    Route::patch('categorias/{categoria}', [FinanzasController::class, 'updateCategoria'])->name('categorias.update');
    Route::delete('categorias/{categoria}', [FinanzasController::class, 'destroyCategoria'])->name('categorias.destroy');
    Route::post('movimientos', [FinanzasController::class, 'storeMovimiento'])->name('movimientos.store');
    Route::patch('movimientos/{tipo}/{movimiento}', [FinanzasController::class, 'updateMovimiento'])->name('movimientos.update');
    Route::delete('movimientos/{tipo}/{movimiento}', [FinanzasController::class, 'destroyMovimiento'])->name('movimientos.destroy');
});

require __DIR__.'/settings.php';
