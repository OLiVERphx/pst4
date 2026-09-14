<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=smartphone_world', 'root', '');
    $tables = ['roles','users','marcas','categorias','products'];
    foreach ($tables as $t) {
        $c = $pdo->query('SELECT COUNT(*) FROM ' . $t)->fetchColumn();
        echo $t . ': ' . $c . PHP_EOL;
    }
} catch (PDOException $e) {
    echo 'ERROR: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
