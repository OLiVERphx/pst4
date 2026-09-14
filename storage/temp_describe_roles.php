<?php
// Bootstrap Laravel and describe the 'roles' table
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $cols = DB::select('DESCRIBE roles');
    $fields = array_map(fn($c) => $c->Field, $cols);
    echo json_encode(['exists' => true, 'fields' => $fields, 'columns' => $cols], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['exists' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
