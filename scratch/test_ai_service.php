<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new App\Services\AIHelpService();

$questions = [
    'How do I register a student?',
    'How do I record fees?',
    'How do I generate report cards?',
    'Can you tell me how to make an automatic timetable?',
    'Where is the cafeteria sandwich recipe 99999xyz?',
];

foreach ($questions as $q) {
    echo "====================================\n";
    echo "Q: $q\n";
    $res = $service->answerQuestion($q);
    echo "Answer: " . $res['answer'] . "\n";
    echo "Category: " . $res['category'] . "\n";
    echo "Source: " . $res['source'] . "\n";
}
