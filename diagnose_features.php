<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\School;
use App\Services\SchoolFeatureService;
use Illuminate\Support\Facades\DB;

echo "\n=== FEATURE ENFORCEMENT DEEP DIVE ===\n\n";

// Check each school's student_management status
$schools = School::all();
foreach ($schools as $school) {
    $enabled = SchoolFeatureService::isEnabled($school->id, 'student_management');
    $state = SchoolFeatureService::getFeatureState($school->id, 'student_management');
    echo "School [{$school->id}] {$school->name}:\n";
    echo "  isEnabled('student_management'): " . ($enabled ? 'YES' : 'NO') . "\n";
    echo "  source: " . $state['source'] . " | overridden: " . ($state['overridden'] ? 'YES' : 'NO') . "\n\n";
}

// What happens when we call isEnabled for a school with no subscription/package?
echo "--- CHECKING Nairobi Premier Academy (school_id=29) ---\n";
$sub = DB::table('subscriptions')->where('school_id', 29)->first();
echo "  Subscription: " . ($sub ? "id={$sub->id} status={$sub->status} package_id=".($sub->package_id ?? 'NULL') : 'NONE') . "\n";
echo "  student_management enabled: " . (SchoolFeatureService::isEnabled(29, 'student_management') ? 'YES' : 'NO') . "\n\n";

// Simulate disabling student_management for school 29
echo "--- SIMULATING DISABLE FOR school_id=29 ---\n";
SchoolFeatureService::setOverride(29, 'student_management', false, 38);
echo "  Override created.\n";
echo "  student_management now enabled: " . (SchoolFeatureService::isEnabled(29, 'student_management') ? 'YES' : 'NO') . "\n";

// Now reset it back
SchoolFeatureService::resetToPackage(29, 'student_management');
echo "  Reset to package. student_management now enabled: " . (SchoolFeatureService::isEnabled(29, 'student_management') ? 'YES' : 'NO') . "\n\n";

echo "=== COMPLETE ===\n\n";
