<?php

use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\LembarKerja;
use App\Livewire\Dashboard\Mapping;
use App\Livewire\Dashboard\Pengaturan;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::middleware(['guest'])->group(function () {
//     Route::get('/login', Login::class)->name('login');
// });

Route::get('/', Login::class);
Route::get('/dashboard', Dashboard::class);
Route::get('/mapping', Mapping::class);
Route::get('/lembar-kerja', LembarKerja::class);
Route::get('/pengaturan', Pengaturan::class);

