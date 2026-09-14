<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=smartphone_world', 'root', '');
    $tables = ['spatie_roles','spatie_permissions','spatie_model_has_permissions','spatie_model_has_roles','spatie_role_has_permissions'];
    foreach ($tables as $t) {
        $stmt = $pdo->query("SHOW TABLES LIKE '".$t."'");
        $found = $stmt->fetchAll();
        echo $t . ': ' . (count($found) ? 'exists' : 'missing') . PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
