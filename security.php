<?php
// Global Security Headers & Helpers for Leo Surface Coatings

function setSecurityHeaders() {
    if (headers_sent()) return;

    // Prevent Clickjacking
    header("X-Frame-Options: SAMEORIGIN");

    // Prevent MIME-type Sniffing
    header("X-Content-Type-Options: nosniff");

    // Enable Browser XSS Protection
    header("X-XSS-Protection: 1; mode=block");

    // Referrer Policy
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // Content Security Policy (allows Tailwind CDN, FontAwesome, Google Fonts, and local assets)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com data:; img-src 'self' data: https:; video-src 'self'; frame-ancestors 'self';");
}

// Sanitize Output against XSS
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Sanitize Slug to prevent Path Traversal / LFI
function sanitizeSlug($slug) {
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9_-]/', '', $slug);
    return basename($slug);
}

setSecurityHeaders();
?>
