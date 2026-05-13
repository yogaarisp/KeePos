<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tables = Illuminate\Support\Facades\DB::select('SHOW TABLES');
$dbname = config('database.connections.mysql.database');
$key = 'Tables_in_' . $dbname;

foreach ($tables as $t) {
    $name = $t->$key;
    if (Illuminate\Support\Facades\Schema::hasColumn($name, 'tenant_id')) {
        echo $name . "\n";
    }
}
