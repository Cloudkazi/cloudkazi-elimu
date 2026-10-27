<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ACADEMIC YEARS TABLE ===\n";
$years = App\Models\AcademicYear::withoutGlobalScopes()->get();
if ($years->count()) {
    foreach ($years as $y) {
        echo "  AY ID={$y->id}, school_id={$y->school_id}, name={$y->name}, start={$y->start_date}, end={$y->end_date}, is_current=" . ($y->is_current ? 'YES' : 'no') . "\n";
    }
} else {
    echo "  (no records)\n";
}

echo "\n=== ACADEMIC TERMS TABLE ===\n";
$terms = App\Models\AcademicTerm::withoutGlobalScopes()->get();
if ($terms->count()) {
    foreach ($terms as $t) {
        echo "  Term ID={$t->id}, school_id={$t->school_id}, ay_id={$t->academic_year_id}, name={$t->name}, start={$t->start_date}, end={$t->end_date}, is_current=" . ($t->is_current ? 'YES' : 'no') . "\n";
    }
} else {
    echo "  (no records)\n";
}

echo "\n=== SCHOOLS TABLE ===\n";
$schools = App\Models\School::all();
foreach ($schools as $s) {
    echo "  School ID={$s->id}, name={$s->name}, status={$s->status}\n";
}

echo "\n=== USERS (demo + admin accounts) ===\n";
$users = App\Models\User::withoutGlobalScopes()
    ->whereHas('role', fn($q) => $q->whereIn('slug', ['demo', 'super-admin', 'school-admin', 'principal']))
    ->with(['role','school'])
    ->get();
foreach ($users as $u) {
    echo "  User ID={$u->id}, email={$u->email}, role={$u->role?->slug}, school_id={$u->school_id}, is_demo={$u->is_demo}\n";
}
