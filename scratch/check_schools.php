<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$schools = App\Models\School::all(['id', 'name']);
echo "Total schools: " . $schools->count() . "\n";
foreach ($schools as $s) {
    echo "ID {$s->id}: {$s->name}\n";
}
