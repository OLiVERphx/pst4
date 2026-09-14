<?php

try {
    $host = '127.0.0.1';
    $port = 3306;
    $user = 'root';
    $pass = '';
    $dbname = 'smartphone_world';

    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database `$dbname` created or already exists\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
