<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pokemon_db');
define('DB_USER', 'root');
define('DB_PASS', 'Dosmiltres_2003');
define('IMAGE_PATH', '/scripts/');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
