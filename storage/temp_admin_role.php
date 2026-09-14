<?php
// Temporary script to query admin role id using Laravel's DB facade
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$role = DB::table('users')->where('email','admin@swworld.com')->value('rol_id');
echo $role === null ? 'NULL' : $role;
