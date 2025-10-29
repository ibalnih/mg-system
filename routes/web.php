<?php

use App\Http\Controllers\CashierController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::prefix('cashier')->name('cashier.')->group(function () {
    Route::get('login', [CashierController::class, 'showLogin'])->name('login');
    Route::post('login', [CashierController::class, 'login'])->name('login.post');
    Route::post('logout', [CashierController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [CashierController::class, 'index'])->name('index');
        Route::post('orders', [CashierController::class, 'storeOrder'])->name('orders.store');
        Route::get('orders/pending', [CashierController::class, 'pendingOrders'])->name('orders.pending');
        Route::post('orders/{order}/confirm', [CashierController::class, 'confirmOrder'])->name('orders.confirm');
    });
});

Route::prefix('customer')->name('customer.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('orders', [CustomerController::class, 'storeOrder'])->name('orders.store');
});
