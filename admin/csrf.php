<?php
// CSRF Protection Utility

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function validateCsrfToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            header('HTTP/1.1 403 Forbidden');
            http_response_code(403);
            die('<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body style="font-family:sans-serif; padding:40px; text-align:center; color:#e11d48;">
                <h2>403 Forbidden: Invalid CSRF Token</h2>
                <p>Security validation failed for your request. Please go back and try again.</p>
                <a href="javascript:history.back()" style="color:#2563eb; text-decoration:underline;">Return to Previous Page</a>
            </body></html>');
        }
    }
}
?>
