<?php

use App\Http\Controllers\AiItineraryController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryCollaboratorController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\ItineraryItemController;
use App\Http\Controllers\NearbyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Profile\TwoFactorSecurityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SessionPingController;
use App\Http\Controllers\TodayItineraryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::view('/offline', 'offline')->name('offline');

Route::get('/katalog', [DestinationController::class, 'index'])->name('destinations.index');
Route::get('/kategori/{category:slug}', [DestinationController::class, 'byCategory'])->name('destinations.by-category');
Route::get('/destinasi/{destination:slug}', [DestinationController::class, 'show'])->name('destinations.show');

Route::get('/terdekat', [NearbyController::class, 'index'])->name('nearby.index');
Route::get('/terdekat/cari', [NearbyController::class, 'search'])->name('nearby.search');

Route::get('/rekomendasi-ai', [RecommendationController::class, 'create'])->name('recommendations.create');
Route::post('/rekomendasi-ai', [RecommendationController::class, 'store'])->name('recommendations.store');

// Bawaan Breeze: tidak dipakai sebagai halaman tersendiri, cukup diarahkan ke Beranda.
Route::get('/dashboard', fn () => redirect()->route('home'))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/sesi/ping', SessionPingController::class)->name('session.ping');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/profile/keamanan/recovery-codes', [TwoFactorSecurityController::class, 'regenerateRecoveryCodes'])->name('profile.recovery-codes.regenerate');
    Route::delete('/profile/keamanan/perangkat/{trustedDevice}', [TwoFactorSecurityController::class, 'destroyDevice'])->whereNumber('trustedDevice')->name('profile.trusted-devices.destroy');
    Route::delete('/profile/keamanan/perangkat', [TwoFactorSecurityController::class, 'destroyAllDevices'])->name('profile.trusted-devices.destroy-all');

    Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/destinasi/{destination}/favorit', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/destinasi/{destination}/favorit', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    Route::post('/destinasi/{destination}/ulasan', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/itinerary', [ItineraryController::class, 'index'])->name('itineraries.index');
    Route::get('/itinerary/buat', [ItineraryController::class, 'create'])->name('itineraries.create');
    Route::post('/itinerary', [ItineraryController::class, 'store'])->name('itineraries.store');
    Route::get('/itinerary/buat-ai', [AiItineraryController::class, 'create'])->name('itineraries.ai-create');
    Route::post('/itinerary/buat-ai', [AiItineraryController::class, 'store'])->name('itineraries.ai-store');
    Route::get('/itinerary/{itinerary}', [ItineraryController::class, 'show'])->name('itineraries.show');
    Route::get('/itinerary/{itinerary}/edit', [ItineraryController::class, 'edit'])->name('itineraries.edit');
    Route::put('/itinerary/{itinerary}', [ItineraryController::class, 'update'])->name('itineraries.update');
    Route::delete('/itinerary/{itinerary}', [ItineraryController::class, 'destroy'])->name('itineraries.destroy');
    Route::get('/itinerary/{itinerary}/pdf', [ItineraryController::class, 'pdf'])->name('itineraries.pdf');

    Route::post('/itinerary/{itinerary}/kolaborator', [ItineraryCollaboratorController::class, 'store'])->name('itinerary-collaborators.store');
    Route::delete('/itinerary/{itinerary}/kolaborator/{collaborator}', [ItineraryCollaboratorController::class, 'destroy'])->name('itinerary-collaborators.destroy');

    Route::post('/itinerary/{itinerary}/item', [ItineraryItemController::class, 'store'])->name('itinerary-items.store');
    Route::put('/itinerary-item/{itineraryItem}', [ItineraryItemController::class, 'update'])->name('itinerary-items.update');
    Route::delete('/itinerary-item/{itineraryItem}', [ItineraryItemController::class, 'destroy'])->name('itinerary-items.destroy');
    Route::post('/itinerary/{itinerary}/urutan', [ItineraryItemController::class, 'reorder'])->name('itinerary-items.reorder');
    Route::post('/itinerary/{itinerary}/urutkan-otomatis', [ItineraryItemController::class, 'autoSort'])->name('itinerary-items.auto-sort');

    Route::post('/itinerary-hari-ini', [TodayItineraryController::class, 'store'])->name('today-itinerary.store');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{notification}/baca', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifikasi/baca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
