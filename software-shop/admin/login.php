<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['login_csrf_token'])) {
    $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$created = isset($_GET['created']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['login_csrf_token'], $token)
    ) {
        $error = 'Die Anfrage ist ungültig. Bitte lade die Seite neu.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';

        if (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !is_string($password)
        ) {
            $error = 'E-Mail-Adresse oder Passwort ist falsch.';
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, name, email, password_hash
                 FROM admins
                 WHERE email = ?
                 LIMIT 1'
            );
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $admin &&
                password_verify($password, $admin['password_hash'])
            ) {
                // Schutz gegen Session-Fixation.
                session_regenerate_id(true);

                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];

                unset($_SESSION['login_csrf_token']);
                $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));

                header('Location: dashboard.php');
                exit;
            }

            // Gleiche Fehlermeldung für unbekannte E-Mail und falsches Passwort.
            $error = 'E-Mail-Adresse oder Passwort ist falsch.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin-Login – Software-Shop</title>
    <link rel="stylesheet" href="../assets/css/setup_admin.css">
</head>
<body>
<main class="admin-auth-container">
    <h1>Admin-Login</h1>
    <p>Melde dich an, um deinen Software-Shop zu verwalten.</p>

    <?php if ($created): ?>
        <p class="admin-success">
            Admin-Konto erstellt. Du kannst dich jetzt anmelden.
        </p>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <p class="admin-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['login_csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="email">E-Mail-Adresse</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="254"
            required
            autocomplete="username"
        >

        <label for="password">Passwort</label>
        <input
            type="password"
            id="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Anmelden</button>
    </form>

    <p><a href="../index.php">Zurück zum Shop</a></p>
</main>
</body>
</html>
