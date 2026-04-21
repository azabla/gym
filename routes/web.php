<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Livewire\Volt\Volt;

Route::get('/run-migrations/{password}', function ($password) {
    // Replace 'mysecret123' with a password of your choice
    if ($password !== 'secret123') {
        return response('Unauthorized.', 403);
    }

    try {
        // This runs 'php artisan migrate --force'
        // The --force is REQUIRED in production/hosting
        Artisan::call('migrate', ['--force' => true]);
        
        return "Migrations successful! <br><br> Output: <br>" . Artisan::output();
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::redirect('/dashboard', '/admin')->name('dashboard');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::redirect('/login', '/admin/login')->name('login');
Route::redirect('/register', '/admin/register')->name('register');

// require __DIR__.'/auth.php';
