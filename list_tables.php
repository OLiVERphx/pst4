<?php
try {
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $dbname = 'smartphone_world';

    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->query("SHOW TABLES");
    $rows = $stmt->fetchAll(PDO::FETCH_NUM);
    if (!count($rows)) {
        echo "No tables found in $dbname\n";
        exit(0);
    }
    echo "Tables in $dbname:\n";
    foreach ($rows as $r) {
        echo " - " . $r[0] . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
