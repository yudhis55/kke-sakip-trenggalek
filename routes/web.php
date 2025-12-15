<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard\Dashboard;
use App\Livewire\Dashboard\LembarKerja;
use App\Livewire\Dashboard\LembarKerja\KriteriaKomponen;
use App\Livewire\Dashboard\LembarKerja\KriteriaKomponen\BuktiDukung;
use App\Livewire\Dashboard\Mapping;
use App\Livewire\Dashboard\Pengaturan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::middleware(['guest'])->group(function () {
    Route::get('/', function () {
        return redirect('/login');
    });
    Route::get('/login', Login::class)->name('login');
});

// Route::middleware(['auth'])->group(function () {
//     Route::get('/dashboard', Dashboard::class)->name('dashboard');
//     Route::get('/mapping', Mapping::class)->name('mapping');
//     Route::get('/lembar-kerja', LembarKerja::class)->name('lembar-kerja');
//     Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen/{kriteria_komponen_id}/bukti-dukung', BuktiDukung::class)->name('lembar-kerja.kriteria-komponen.bukti-dukung');
//     Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen', KriteriaKomponen::class)->name('lembar-kerja.kriteria-komponen');
//     Route::get('/pengaturan', Pengaturan::class)->name('pengaturan');
// });

Route::middleware([EnsureUserHasRole::class . ':admin,verifikator_bappeda,verifikator_bag_organisasi,verifikator_inspektorat,penjamin,penilai,opd'])->group(function () {
    // Admin only routes can be defined here
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/mapping', Mapping::class)->name('mapping');
    Route::get('/lembar-kerja', LembarKerja::class)->name('lembar-kerja');
    Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen/{kriteria_komponen_id}/bukti-dukung', BuktiDukung::class)->name('lembar-kerja.kriteria-komponen.bukti-dukung');
    Route::get('/lembar-kerja/sub-komponen/{sub_komponen_id}/kriteria-komponen', KriteriaKomponen::class)->name('lembar-kerja.kriteria-komponen');
    Route::get('/pengaturan', Pengaturan::class)->name('pengaturan');
});

