<?php
$content = file_get_contents('app/Http/Controllers/ExaminationController.php');
$lines = explode("\n", $content);
foreach ($lines as $num => $line) {
    if (stripos($line, 'Subject') !== false) {
        echo ($num + 1) . ": " . trim($line) . "\n";
    }
}
