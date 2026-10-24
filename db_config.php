<?php
require_once __DIR__ . '/security.php';

// // Database Configuration
// define('DB_HOST', '127.0.0.1');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'leoantigra_db');



define('DB_HOST', '127.0.0.1');
define('DB_USER', 'u815853083_leosurface');
define('DB_PASS', '8op~@$/l&gv#@Ou0DoC=');
define('DB_NAME', ' u815853083_leosurface');


function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            die("Database Connection Error: Connection failed securely.");
        }
    }
    return $pdo;
}
?>
