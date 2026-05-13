<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
$database = config('database.connections.mysql.database');
$key = "Tables_in_" . $database;

$tenantTables = [];
$globalTables = [];

foreach ($tables as $table) {
    $name = $table->$key;
    $columns = Illuminate\Support\Facades\Schema::getColumnListing($name);
    if (in_array('tenant_id', $columns)) {
        $tenantTables[] = $name;
    } else {
        $globalTables[] = $name;
    }
}

echo "TENANT_TABLES:" . implode(',', $tenantTables) . "\n";
echo "GLOBAL_TABLES:" . implode(',', $globalTables) . "\n";
