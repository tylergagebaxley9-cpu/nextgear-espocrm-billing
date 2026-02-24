<?php
// Diagnostic: show DB config from EspoCRM
$paths = [
    '/var/www/html/data/config.php',
    '/var/www/html/config.php',
];

foreach ($paths as $p) {
    if (file_exists($p)) {
        echo "Found config at: $p\n";
        $c = include $p;
        if (isset($c['database'])) {
            echo "  host: " . ($c['database']['host'] ?? 'NOT SET') . "\n";
            echo "  port: " . ($c['database']['port'] ?? 'NOT SET') . "\n";
            echo "  dbname: " . ($c['database']['dbname'] ?? 'NOT SET') . "\n";
            echo "  user: " . ($c['database']['user'] ?? 'NOT SET') . "\n";
        } else {
            echo "  No 'database' key. Top-level keys: " . implode(', ', array_keys($c)) . "\n";
        }
    } else {
        echo "Not found: $p\n";
    }
}
