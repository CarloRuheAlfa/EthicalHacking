<?php
// Beveiligde sessie-initialisatie
// Gebruik strict mode en veilige cookie-instellingen waar mogelijk
ini_set('session.use_strict_mode', 1);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zorg dat er een CSRF-token aanwezig is
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        // Terugvaloptie: gebruik openssl als random_bytes niet beschikbaar is
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// Helper om uitvoer te ontsmetten (XSS-veilig maken)
function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

?>
