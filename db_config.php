<?php
require_once __DIR__ . '/security.php';

// Automatic Environment Detection (Localhost vs Live Production Server)
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local = (
    empty($http_host) || 
    in_array($http_host, ['localhost', '127.0.0.1', '::1']) ||
    strpos($http_host, 'localhost:') === 0 ||
    strpos($http_host, '127.0.0.1:') === 0
);

if ($is_local) {
    // Localhost / XAMPP Database
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'leo');
} else {
    // Live Server Database (Hostinger)
    define('DB_HOST', '127.0.0.1');
    define('DB_USER', 'u815853083_leosurface');
    define('DB_PASS', '8op~@$/l&gv#@Ou0DoC=');
    define('DB_NAME', 'u815853083_leosurface');
}

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
