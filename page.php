<?php
require_once __DIR__ . '/db_config.php';

$raw_slug = $_GET['slug'] ?? 'home';
$slug = sanitizeSlug($raw_slug);

$db = getDB();
$stmt = $db->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'published'");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if ($page) {
    echo $page['content_html'];
} else {
    // Fallback to static file if present, with path traversal guard
    $file_basename = ($slug === 'home' || $slug === '') ? 'index.html' : $slug . '.html';
    $static_file = __DIR__ . '/' . $file_basename;
    
    if (file_exists($static_file) && strpos(realpath($static_file), realpath(__DIR__)) === 0) {
        echo file_get_contents($static_file);
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body style='font-family:sans-serif; text-align:center; padding:50px;'><h1>404 Page Not Found</h1><p>The requested page could not be found.</p></body></html>";
    }
}
?>
