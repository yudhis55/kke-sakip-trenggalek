<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "Checking bukti_dukung table columns:\n";
$columns = DB::getSchemaBuilder()->getColumns('bukti_dukung');
$columnNames = array_column($columns, 'name');

echo "is_n_minus_1 exists: " . (in_array('is_n_minus_1', $columnNames) ? 'YES' : 'NO') . "\n";
echo "\nChecking opd table - predecessor_opd_id column:\n";

$opdColumns = DB::getSchemaBuilder()->getColumns('opd');
foreach ($opdColumns as $col) {
    if ($col['name'] === 'predecessor_opd_id') {
        echo "  predecessor_opd_id: " . $col['type'] . " (nullable: " . ($col['nullable'] ? 'YES' : 'NO') . ")\n";
    }
}
