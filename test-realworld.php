<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Livewire\Dashboard\LembarKerja;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

DB::enableQueryLog();

echo "=== REAL WORLD TEST - LEMBAR KERJA ===\n\n";

// Test sebagai OPD user
$opdUser = User::whereHas('role', function ($q) {
    $q->where('jenis', 'opd');
})->first();

if (!$opdUser) {
    echo "Error: Tidak ada user dengan role OPD\n";
    exit;
}

echo "Testing sebagai OPD User: {$opdUser->name} (OPD ID: {$opdUser->opd_id})\n\n";

// Simulate authentication
Auth::login($opdUser);

// Set tahun session
$tahun = \App\Models\Tahun::first();
if (!$tahun) {
    echo "Error: Tidak ada data tahun\n";
    exit;
}
session(['tahun_session' => $tahun->id]);

$startTime = microtime(true);
$startMemory = memory_get_usage();
DB::flushQueryLog();

// Create component instance
$component = new LembarKerja();
$component->mount();

echo "1. Component mounted\n";
$mountQueries = count(DB::getQueryLog());
echo "   Queries: {$mountQueries}\n\n";

// Call komponenOptions (main heavy operation)
echo "2. Loading komponenOptions...\n";
DB::flushQueryLog();
$startKomponen = microtime(true);

$komponenList = $component->komponenOptions();

$komponenTime = round((microtime(true) - $startKomponen) * 1000, 2);
$komponenQueries = count(DB::getQueryLog());
echo "   Queries: {$komponenQueries}\n";
echo "   Time: {$komponenTime}ms\n";
echo "   Komponen count: " . $komponenList->count() . "\n\n";

// Show slow queries
$queries = DB::getQueryLog();
$slowQueries = array_filter($queries, fn($q) => $q['time'] > 10);
if (count($slowQueries) > 0) {
    echo "3. SLOW QUERIES (>10ms):\n";
    foreach ($slowQueries as $q) {
        echo "   - {$q['time']}ms: " . substr($q['query'], 0, 100) . "...\n";
    }
    echo "\n";
}

$totalTime = round((microtime(true) - $startTime) * 1000, 2);
$totalMemory = round((memory_get_usage() - $startMemory) / 1024 / 1024, 2);
$totalQueries = $mountQueries + $komponenQueries;

echo "=== TOTAL ===\n";
echo "Time: {$totalTime}ms\n";
echo "Memory: {$totalMemory}MB\n";
echo "Queries: {$totalQueries}\n\n";

// Test sebagai Admin/Non-OPD (yang load opdList)
$adminUser = User::whereHas('role', function ($q) {
    $q->where('jenis', 'admin');
})->first();

if ($adminUser) {
    echo "\n=== Testing sebagai Admin (dengan opdList) ===\n\n";
    Auth::login($adminUser);

    DB::flushQueryLog();
    $startAdmin = microtime(true);

    $component2 = new LembarKerja();
    $component2->mount();

    echo "1. Loading opdList untuk 40 OPD...\n";
    $opdList = $component2->opdList();

    $adminTime = round((microtime(true) - $startAdmin) * 1000, 2);
    $adminQueries = count(DB::getQueryLog());

    echo "   Queries: {$adminQueries}\n";
    echo "   Time: {$adminTime}ms\n";
    echo "   OPD count: " . $opdList->count() . "\n";

    // Check for N+1
    $queryTypes = [];
    foreach (DB::getQueryLog() as $q) {
        $type = preg_replace('/\s+/', ' ', substr($q['query'], 0, 50));
        $queryTypes[$type] = ($queryTypes[$type] ?? 0) + 1;
    }

    echo "\n2. Query patterns:\n";
    foreach ($queryTypes as $type => $count) {
        if ($count > 5) {
            echo "   - [{$count}x] {$type}...\n";
        }
    }
}
