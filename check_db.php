<?php

try {
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $dbs = ['smartphone-world','smartphone_world'];

    $dbh = new PDO("mysql:host=$host;port=$port", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    foreach ($dbs as $db) {
        $stmt = $dbh->query("SHOW DATABASES LIKE '$db'");
        $rows = $stmt->fetchAll(PDO::FETCH_NUM);
        echo $db . ": " . (count($rows) ? "EXISTS" : "NOT FOUND") . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
