<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Enable query log
\Illuminate\Support\Facades\DB::enableQueryLog();

echo "=== PERFORMA TEST - LEMBAR KERJA ===\n\n";

$startTime = microtime(true);
$startMemory = memory_get_usage();

// Simulate component load
$tahun_id = 1;

echo "TEST 1: Loading komponenOptions...\n";
$queryCount1 = count(\DB::getQueryLog());
\DB::flushQueryLog();

$komponenList = \App\Models\Komponen::where('tahun_id', $tahun_id)
    ->with([
        'sub_komponen' => function ($q) {
            $q->withCount('kriteria_komponen');
        },
        'sub_komponen.kriteria_komponen',
        'sub_komponen.kriteria_komponen.sub_komponen' => function ($q) {
            $q->withCount('kriteria_komponen');
        }
    ])
    ->get();

echo "  - After komponen load: " . count(\DB::getQueryLog()) . " queries\n";

$opdId = 1; // OPD pertama

// Preload penilaian seperti di LembarKerja
$kriteriaIds = [];
foreach ($komponenList as $komponen) {
    foreach ($komponen->sub_komponen as $subKomponen) {
        foreach ($subKomponen->kriteria_komponen as $kriteria) {
            $kriteriaIds[] = $kriteria->id;
        }
    }
}
$roleIds = \App\Models\Role::whereIn('jenis', ['opd', 'penilai', 'penjamin'])->pluck('id')->toArray();
echo "  - Kriteria IDs: " . count($kriteriaIds) . ", OPD: {$opdId}, Roles: " . count($roleIds) . "\n";

$queryCountBeforePreload = count(\DB::getQueryLog());
if (!empty($kriteriaIds)) {
    $cachedCount = \App\Models\KriteriaKomponen::preloadPenilaian($kriteriaIds, [$opdId], $roleIds);
    echo "  - Preloaded {$cachedCount} penilaian records into cache\n";
}
$queryCountAfterPreload = count(\DB::getQueryLog());
echo "  - Preload queries: " . ($queryCountAfterPreload - $queryCountBeforePreload) . "\n";

// OPTIMASI: Skip calculation jika tidak ada data
if ($cachedCount == 0) {
    echo "  - No penilaian data found, skipping calculations\n";
    $komponenList->each(function ($k) {
        $k->nilai_rata_rata = 0;
        $k->progress = 0;
    });
    $queryCount1End = count(\DB::getQueryLog());
    $time1 = round((microtime(true) - $startTime) * 1000, 2);
    echo "  - Queries: " . $queryCount1End . "\n";
    echo "  - Time: {$time1}ms\n";
    echo "  - Komponen loaded: " . $komponenList->count() . "\n\n";
} else {
    \App\Models\KriteriaKomponen::resetCacheStats();

    $komponenList->each(function ($komponen) use ($opdId) {
        $komponen->nilai_rata_rata = $komponen->getNilaiRataRata($opdId);
        $komponen->progress = $komponen->getProgress($opdId);
    });

    $cacheStats = \App\Models\KriteriaKomponen::getCacheStats();
    echo "  - Cache Stats: {$cacheStats['hits']} hits, {$cacheStats['misses']} misses, {$cacheStats['hit_rate']}% hit rate\n";

    // Show some recent queries to see pattern
    $recentQueries = array_slice(\DB::getQueryLog(), -5);
    echo "  - Recent query types:\n";
    foreach ($recentQueries as $q) {
        $shortQuery = substr($q['query'], 0, 80);
        echo "    * {$shortQuery}...\n";
    }

    $queryCount1End = count(\DB::getQueryLog());
    $time1 = round((microtime(true) - $startTime) * 1000, 2);
    echo "  - Queries: " . $queryCount1End . "\n";
    echo "  - Time: {$time1}ms\n";
    echo "  - Komponen loaded: " . $komponenList->count() . "\n\n";
}

// Reset for test 2
\DB::flushQueryLog();
$startTime2 = microtime(true);

echo "TEST 2: Loading subKomponenOptions...\n";
$subKomponenList = \App\Models\SubKomponen::where('tahun_id', $tahun_id)
    ->where('komponen_id', $komponenList->first()->id)
    ->with('kriteria_komponen')
    ->get();

// Preload penilaian
$kriteriaIds2 = [];
foreach ($subKomponenList as $subKomponen) {
    foreach ($subKomponen->kriteria_komponen as $kriteria) {
        $kriteriaIds2[] = $kriteria->id;
    }
}
$cachedCount2 = 0;
if (!empty($kriteriaIds2)) {
    $cachedCount2 = \App\Models\KriteriaKomponen::preloadPenilaian($kriteriaIds2, [$opdId], $roleIds);
}

if ($cachedCount2 == 0) {
    $subKomponenList->each(function ($s) {
        $s->nilai_rata_rata = 0;
        $s->progress = 0;
    });
} else {
    $subKomponenList->each(function ($subKomponen) use ($opdId) {
        $subKomponen->nilai_rata_rata = $subKomponen->getNilaiRataRata($opdId);
        $subKomponen->progress = $subKomponen->getProgress($opdId);
    });
}

$queryCount2 = count(\DB::getQueryLog());
$time2 = round((microtime(true) - $startTime2) * 1000, 2);
echo "  - Queries: {$queryCount2}\n";
echo "  - Time: {$time2}ms\n";
echo "  - Sub Komponen loaded: " . $subKomponenList->count() . "\n\n";

// Reset for test 3 - OpdList (HEAVY!)
\DB::flushQueryLog();
$startTime3 = microtime(true);

echo "TEST 3: Loading opdList (BERAT!)...\n";
$komponenListForOpd = \App\Models\Komponen::where('tahun_id', $tahun_id)
    ->with(['sub_komponen.kriteria_komponen'])
    ->get();

$opdList = \App\Models\Opd::limit(5)->get(); // Hanya 5 OPD untuk test

// Preload semua penilaian untuk semua OPD
$kriteriaIds3 = [];
foreach ($komponenListForOpd as $komponen) {
    foreach ($komponen->sub_komponen as $subKomponen) {
        foreach ($subKomponen->kriteria_komponen as $kriteria) {
            $kriteriaIds3[] = $kriteria->id;
        }
    }
}
$opdIds = $opdList->pluck('id')->toArray();
$cachedCount3 = 0;
if (!empty($kriteriaIds3)) {
    $cachedCount3 = \App\Models\KriteriaKomponen::preloadPenilaian($kriteriaIds3, $opdIds, $roleIds);
}

if ($cachedCount3 == 0) {
    // Skip calculation if no data
    $opdList->each(function ($o) {
        $o->progress = 0;
        $o->nilai_total = 0;
    });
} else {
    $opdList->each(function ($opd) use ($komponenListForOpd, $tahun_id) {
        $opd->progress = $opd->getProgress($tahun_id);

        $totalNilai = 0;
        foreach ($komponenListForOpd as $komponen) {
            $totalNilai += $komponen->getNilaiRataRata($opd->id);
        }
        $opd->nilai_total = round($totalNilai, 2);
    });
}

$queryCount3 = count(\DB::getQueryLog());
$time3 = round((microtime(true) - $startTime3) * 1000, 2);
echo "  - Queries: {$queryCount3}\n";
echo "  - Time: {$time3}ms\n";
echo "  - OPD loaded: " . $opdList->count() . "\n";
echo "  - Estimated for 40 OPD: " . round($time3 * 8, 2) . "ms\n\n";

$endTime = microtime(true);
$endMemory = memory_get_usage();

$totalTime = round(($endTime - $startTime) * 1000, 2);
$totalMemory = round(($endMemory - $startMemory) / 1024 / 1024, 2);

echo "=== TOTAL ===\n";
echo "Total Time: {$totalTime}ms\n";
echo "Total Memory: {$totalMemory}MB\n";
echo "Total Queries: " . ($queryCount1End + $queryCount2 + $queryCount3) . "\n";

echo "\n=== RECENT QUERIES (last 10) ===\n";
$queries = \DB::getQueryLog();
$recentQueries = array_slice($queries, -10);
foreach ($recentQueries as $i => $query) {
    echo ($i + 1) . ". " . substr($query['query'], 0, 100) . "...\n";
    echo "   Time: " . round($query['time'], 2) . "ms\n";
}
