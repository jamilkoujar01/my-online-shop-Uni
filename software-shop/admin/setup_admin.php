<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';

// Einrichtung nur erlauben, wenn noch kein Admin existiert.
$stmt = $pdo->query('SELECT COUNT(*) FROM admins');
$adminCount = (int) $stmt->fetchColumn();

if ($adminCount > 0) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['setup_csrf_token'])) {
    $_SESSION['setup_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['setup_csrf_token'], $token)
    ) {
        $error = 'Die Anfrage ist ungültig. Bitte lade die Seite neu.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if ($name === '' || strlen($name) > 100) {
            $error = 'Bitte gib einen gültigen Namen ein.';
        } elseif (
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            strlen($email) > 254
        ) {
            $error = 'Bitte gib eine gültige E-Mail-Adresse ein.';
        } elseif (!is_string($password) || strlen($password) < 12) {
            $error = 'Das Passwort muss mindestens 12 Zeichen lang sein.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare(
                'INSERT INTO admins (name, email, password_hash)
                 VALUES (?, ?, ?)'
            );

            try {
                $insert->execute([$name, $email, $passwordHash]);

                unset($_SESSION['setup_csrf_token']);

                header('Location: login.php?created=1');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    $error = 'Diese E-Mail-Adresse wird bereits verwendet.';
                } else {
                    error_log($e->getMessage());
                    $error = 'Der Admin konnte nicht erstellt werden.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin einrichten – Software-Shop</title>
    <link rel="stylesheet" href="../assets/css/setup_admin.css">
</head>
<body>
<main class="admin-auth-container">
    <h1>Administrator einrichten</h1>
    <p>Erstelle das erste Admin-Konto für deinen Software-Shop.</p>

    <?php if ($error !== ''): ?>
        <p class="admin-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="setup_admin.php">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['setup_csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="name">Name</label>
        <input
            type="text"
            id="name"
            name="name"
            maxlength="100"
            required
            autocomplete="name"
        >

        <label for="email">E-Mail-Adresse</label>
        <input
            type="email"
            id="email"
            name="email"
            maxlength="254"
            required
            autocomplete="email"
        >

        <label for="password">Passwort (mindestens 12 Zeichen)</label>
        <input
            type="password"
            id="password"
            name="password"
            minlength="12"
            required
            autocomplete="new-password"
        >

        <label for="password_confirm">Passwort wiederholen</label>
        <input
            type="password"
            id="password_confirm"
            name="password_confirm"
            minlength="12"
            required
            autocomplete="new-password"
        >

        <button type="submit">Admin-Konto erstellen</button>
    </form>
</main>
</body>
</html>
