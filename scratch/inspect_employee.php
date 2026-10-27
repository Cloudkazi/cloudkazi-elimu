<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$emp = Illuminate\Support\Facades\DB::table('employees')->first();
echo json_encode($emp, JSON_PRETTY_PRINT) . PHP_EOL;
