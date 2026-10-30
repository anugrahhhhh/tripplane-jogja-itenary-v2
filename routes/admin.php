<?php

use App\Http\Controllers\Admin\AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DestinationController as AdminDestinationController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

// Pintu masuk khusus Admin, terpisah dari /login milik wisatawan.
Route::prefix('admin')->name('admin.')->middleware('guest')->group(function () {
    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store']);
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('kategori', CategoryController::class)
        ->except('show')
        ->parameters(['kategori' => 'category'])
        ->names('categories');

    Route::resource('destinasi', AdminDestinationController::class)
        ->except('show')
        ->parameters(['destinasi' => 'destination'])
        ->names('destinations');

    Route::delete('/destinasi/{destination}/foto/{photo}', [AdminDestinationController::class, 'destroyPhoto'])
        ->name('destinations.photos.destroy');

    Route::get('/ulasan', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::patch('/ulasan/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/ulasan/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/pengguna/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/pengguna/{user}/reset-2fa', [AdminUserController::class, 'resetTwoFactor'])->name('users.reset-two-factor');
});
