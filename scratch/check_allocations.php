<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TeacherSubjectAllocation;
use App\Models\Stream;
use App\Models\Student;
use App\Models\Subject;

echo "Total allocations in DB: " . TeacherSubjectAllocation::count() . "\n";
echo "Total streams in DB: " . Stream::count() . "\n";
echo "Total students in DB: " . Student::count() . "\n";
echo "Total subjects in DB: " . Subject::count() . "\n";
