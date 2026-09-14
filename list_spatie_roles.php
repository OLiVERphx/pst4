<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=smartphone_world', 'root', '');
    $stmt = $pdo->query("SELECT id, name, guard_name FROM spatie_roles");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo $r['id']." - ".$r['name']." (".$r['guard_name'].")".PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
}
