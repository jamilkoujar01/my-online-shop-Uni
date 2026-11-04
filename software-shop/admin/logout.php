<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

if (!verifyAdminCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Ungültige Anfrage. Bitte lade die Seite neu.');
}

// Session-Daten entfernen.
$_SESSION = [];

// Session-Cookie im Browser ungültig machen.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

header('Location: login.php');
exit;
