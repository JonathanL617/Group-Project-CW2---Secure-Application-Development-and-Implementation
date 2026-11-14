<?php

if (session_status() === PHP_SESSION_NONE) {

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

// Ensure PHP version banner is removed from HTTP response headers
header_remove('X-Powered-By');

// Defensive HTTP Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Content Security Policy: permit application domain, Bootstrap CDN, and Google Fonts
    $csp = "default-src 'self' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com; "
         . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
         . "font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com; "
         . "img-src 'self' data:; "
         . "connect-src 'self'; "
         . "frame-ancestors 'self';";
    header("Content-Security-Policy: $csp");

    // Enforce HSTS if accessing over HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // Default Cache-Control to prevent browser caching of sensitive medical & patient records
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Anti-CSRF Token Generation and Helper Functions
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('getCsrfToken')) {
    function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
