<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== LIBRARIANS ===\n";
print_r(\App\Models\User::where('role_id', 10)->get(['id', 'name', 'email', 'role_id', 'school_id'])->toArray());

echo "=== BOOKS ===\n";
print_r(\App\Models\Book::all(['id', 'school_id', 'title', 'author', 'available_copies', 'total_copies'])->toArray());

echo "=== BOOK ISSUES ===\n";
print_r(\App\Models\BookIssue::all()->toArray());

echo "=== STUDENTS ===\n";
print_r(\App\Models\Student::take(3)->get(['id', 'school_id', 'admission_number', 'first_name', 'last_name', 'user_id'])->toArray());
