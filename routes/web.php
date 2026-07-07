<?php

use App\Http\Controllers\CashflowActualController;
use App\Http\Controllers\CashflowController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyTaxController;
use App\Http\Controllers\SelectedPropertyController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::put('selected-property', [SelectedPropertyController::class, 'update'])->name('selected-property.update');

    Route::resource('properties', PropertyController::class)->except(['create', 'edit']);

    Route::get('income', [IncomeController::class, 'index'])->name('income.index');
    Route::post('income', [IncomeController::class, 'store'])->name('income.store');
    Route::put('income/{income}', [IncomeController::class, 'update'])->name('income.update');
    Route::delete('income/{income}', [IncomeController::class, 'destroy'])->name('income.destroy');

    Route::get('expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::post('expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::put('expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');

    Route::get('property-taxes', [PropertyTaxController::class, 'index'])->name('property-taxes.index');
    Route::post('property-taxes', [PropertyTaxController::class, 'store'])->name('property-taxes.store');
    Route::put('property-taxes/{propertyTax}', [PropertyTaxController::class, 'update'])->name('property-taxes.update');
    Route::delete('property-taxes/{propertyTax}', [PropertyTaxController::class, 'destroy'])->name('property-taxes.destroy');

    Route::get('cashflow', [CashflowController::class, 'index'])->name('cashflow.index');
    Route::get('cashflow-actual', [CashflowActualController::class, 'index'])->name('cashflow.actual');
});

require __DIR__.'/settings.php';
