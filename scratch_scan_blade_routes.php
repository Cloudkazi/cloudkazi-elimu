<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$routes = \Illuminate\Support\Facades\Route::getRoutes();
$definedRouteNames = [];
foreach ($routes as $route) {
    if ($route->getName()) {
        $definedRouteNames[$route->getName()] = true;
    }
}

// Find all blade files
$bladeFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/resources/views')
);

$missingRoutes = [];

foreach ($bladeFiles as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
        $content = file_get_contents($file->getPathname());
        // Match route('...') or route("...")
        if (preg_match_all('/route\(\s*[\'"]([a-zA-Z0-9._-]+)[\'"]/i', $content, $matches)) {
            foreach ($matches[1] as $calledRoute) {
                if (!isset($definedRouteNames[$calledRoute])) {
                    $relPath = str_replace(__DIR__, '', $file->getPathname());
                    $missingRoutes[$calledRoute][] = $relPath;
                }
            }
        }
    }
}

echo "=== SCAN OF ALL BLADE ROUTE CALLS ===\n";
if (empty($missingRoutes)) {
    echo "All route calls in blade files exist!\n";
} else {
    foreach ($missingRoutes as $routeName => $files) {
        echo "Missing Route: '$routeName' called in:\n  - " . implode("\n  - ", array_unique($files)) . "\n";
    }
}
