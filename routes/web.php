<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';

// ⚠️ TEMPORARY ROUTE — DELETE AFTER USE!
Route::get('/run-migrations', function () {
    // Optional: Restrict access to your IP only (replace with your public IP)
    // if ($_SERVER['REMOTE_ADDR'] !== 'YOUR.IP.ADDRESS.HERE') {
    //     abort(403, 'Forbidden');
    // }

    try {
        // Clear any cached config (in case .env changed)
        if (file_exists(__DIR__ . '/../bootstrap/cache/config.php')) {
            unlink(__DIR__ . '/../bootstrap/cache/config.php');
        }

        // Run migrations
        Artisan::call('migrate', ['--force' => true]);
        
        // Run seeders (remove this line if you don't have seeders)
        Artisan::call('db:seed', ['--force' => true]);

        return "<h1 style='color:green;'>✅ Success!</h1><p>Migrations and seeding completed.</p>";
    } catch (\Exception $e) {
        return "<h1 style='color:red;'>❌ Error!</h1><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
});