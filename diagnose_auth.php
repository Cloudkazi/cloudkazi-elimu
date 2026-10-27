<?php
/**
 * CloudKazi Elimu — Authorization Diagnostics
 * Run: php diagnose_auth.php
 */
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\School;
use App\Models\SchoolFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== CLOUDKAZI ELIMU — AUTH DIAGNOSTICS ===\n\n";

// ─── 1. Check tables exist ───────────────────────────────────────────────────
echo "--- TABLE EXISTENCE ---\n";
$tables = ['users','roles','schools','school_features','packages','package_features','subscriptions'];
foreach ($tables as $t) {
    $exists = Schema::hasTable($t);
    echo "  $t: " . ($exists ? "EXISTS" : "MISSING!") . "\n";
}
echo "\n";

// ─── 2. Check demo users ─────────────────────────────────────────────────────
echo "--- DEMO USERS (is_demo=1) ---\n";
$demoUsers = User::where('is_demo', 1)->with('role','school')->get();
if ($demoUsers->isEmpty()) {
    echo "  (none found with is_demo=1)\n";
}
foreach ($demoUsers as $u) {
    echo "  [{$u->id}] {$u->name} | email: {$u->email} | role: ".($u->role->slug ?? 'NULL')." | school_id: ".($u->school_id ?? 'NULL')."\n";
}
echo "\n";

// ─── 3. Check demo users by @demo.com email ──────────────────────────────────
echo "--- DEMO USERS (@demo.com email) ---\n";
$emailDemo = User::where('email','like','%@demo.com')->with('role','school')->get();
foreach ($emailDemo as $u) {
    echo "  [{$u->id}] {$u->name} | email: {$u->email} | role: ".($u->role->slug ?? 'NULL')." | school_id: ".($u->school_id ?? 'NULL')." | is_demo: ".($u->is_demo ? 'true':'false')."\n";
}
if ($emailDemo->isEmpty()) {
    echo "  (none found)\n";
}
echo "\n";

// ─── 4. All user roles summary ───────────────────────────────────────────────
echo "--- ALL USERS SUMMARY ---\n";
$allUsers = User::with('role','school')->get();
foreach ($allUsers as $u) {
    $role = $u->role->slug ?? 'NO_ROLE';
    $school = $u->school->name ?? ($u->school_id ? "school_id:{$u->school_id}" : 'NO SCHOOL');
    $demo = $u->is_demo ? ' [DEMO]' : '';
    echo "  [{$u->id}] {$u->name} | {$u->email} | role: {$role} | school: {$school}{$demo}\n";
}
echo "\n";

// ─── 5. Check school_features data ───────────────────────────────────────────
echo "--- SCHOOL_FEATURES TABLE ---\n";
if (Schema::hasTable('school_features')) {
    $features = DB::table('school_features')->get();
    if ($features->isEmpty()) {
        echo "  (table is EMPTY — no feature overrides stored)\n";
    }
    foreach ($features as $f) {
        $school = School::find($f->school_id);
        echo "  [{$f->id}] school: ".($school->name ?? "id:{$f->school_id}")." | feature: {$f->feature_key} | enabled: ".($f->enabled ? 'YES':'NO')." | source: {$f->source}\n";
    }
} else {
    echo "  TABLE DOES NOT EXIST!\n";
}
echo "\n";

// ─── 6. Check packages ───────────────────────────────────────────────────────
echo "--- PACKAGES ---\n";
if (Schema::hasTable('packages')) {
    $packages = DB::table('packages')->get();
    foreach ($packages as $p) {
        echo "  [{$p->id}] {$p->name} | active: ".($p->is_active ? 'YES':'NO')."\n";
    }
    if ($packages->isEmpty()) echo "  (no packages)\n";
} else {
    echo "  TABLE MISSING!\n";
}
echo "\n";

// ─── 7. Check subscriptions ──────────────────────────────────────────────────
echo "--- SUBSCRIPTIONS ---\n";
if (Schema::hasTable('subscriptions')) {
    $subs = DB::table('subscriptions')->get();
    foreach ($subs as $s) {
        $school = School::find($s->school_id);
        echo "  [{$s->id}] school: ".($school->name ?? "id:{$s->school_id}")." | status: {$s->status} | package_id: ".($s->package_id ?? 'NULL')."\n";
    }
    if ($subs->isEmpty()) echo "  (no subscriptions)\n";
} else {
    echo "  TABLE MISSING!\n";
}
echo "\n";

// ─── 8. What the requireSuperAdmin() message says ────────────────────────────
echo "--- ABORT MESSAGE SOURCE ---\n";
echo "  SuperAdminController::requireSuperAdmin() aborts with:\n";
echo "  403 'This area is restricted to platform administrators.'\n";
echo "  This triggers when Auth::user()->isSuperAdmin() returns false.\n";
echo "  isSuperAdmin() checks: user->role->slug === 'super-admin'\n\n";

// ─── 9. Check schools and status ─────────────────────────────────────────────
echo "--- SCHOOLS ---\n";
$schools = School::all();
foreach ($schools as $s) {
    echo "  [{$s->id}] {$s->name} | status: {$s->status}\n";
}
if ($schools->isEmpty()) echo "  (no schools)\n";

echo "\n=== DIAGNOSTICS COMPLETE ===\n\n";
