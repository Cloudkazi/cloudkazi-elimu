<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$rows = DB::table('legal_documents')->get();
foreach ($rows as $row) {
    echo $row->type . ': chars=' . strlen($row->content) . PHP_EOL;
    echo '  title=' . $row->title . PHP_EOL;
    echo '  version=' . $row->version . PHP_EOL;
    echo '  is_active=' . $row->is_active . PHP_EOL;
    echo '  content_start=' . substr($row->content, 0, 80) . PHP_EOL;
    echo PHP_EOL;
}
