<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

$csrfToken = adminCsrfToken();
$error = '';
$name = '';
$price = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyAdminCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Ungültige Anfrage. Bitte lade die Seite neu.');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $priceInput = trim((string) ($_POST['price'] ?? ''));
    $price = $priceInput;

    if ($name === '' || mb_strlen($name) > 255) {
        $error = 'Der Produktname muss zwischen 1 und 255 Zeichen lang sein.';
    } elseif (
        $priceInput === '' ||
        !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $priceInput)
    ) {
        $error = 'Bitte gib einen gültigen Preis ein, z. B. 49.99.';
    } else {
        $active = isset($_POST['active']) ? 1 : 0;

        $stmt = $pdo->prepare(
            'INSERT INTO products (name, price, active)
             VALUES (?, ?, ?)'
        );

        try {
            $stmt->execute([$name, $priceInput, $active]);

            header('Location: products.php?created=1');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Das Produkt konnte nicht gespeichert werden. Prüfe die Datenbankstruktur.';
        }
    }
}

function createEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produkt hinzufügen – Software-Shop</title>
    <link rel="stylesheet" href="../assets/css/product_create.css">
</head>
<body class="admin-page">

<header class="admin-header">
    <a class="admin-brand" href="dashboard.php">Software-Shop Admin</a>
    <nav class="admin-nav" aria-label="Admin-Navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php" aria-current="page">Produkte</a>
        <a href="orders.php">Bestellungen</a>
        <form action="logout.php" method="POST" class="admin-logout-form">
            <input type="hidden" name="csrf_token"
                   value="<?= createEscape(adminCsrfToken()) ?>">
            <button type="submit" class="admin-logout-button">Abmelden</button>
        </form>
    </nav>
</header>

<main class="admin-dashboard">
    <section class="admin-welcome">
        <p class="admin-eyebrow">PRODUKTVERWALTUNG</p>
        <h1>Neues Produkt hinzufügen</h1>
        <p>Trage die Informationen für dein neues Software-Produkt ein.</p>
    </section>

    <section class="admin-form-card">
        <?php if ($error !== ''): ?>
            <p class="admin-error"><?= createEscape($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="product_create.php">
            <input type="hidden" name="csrf_token"
                   value="<?= createEscape($csrfToken) ?>">

            <label for="name">Produktname</label>
            <input type="text" id="name" name="name"
                   maxlength="255" required
                   value="<?= createEscape($name) ?>">

            <label for="price">Preis in Euro</label>
            <input type="number" id="price" name="price"
                   min="0" max="99999999.99" step="0.01"
                   required value="<?= createEscape($price) ?>">
            <small>Beispiel: 49.99</small>

            <label class="admin-checkbox-label">
                <input type="checkbox" name="active" value="1" checked>
                Produkt direkt im Shop veröffentlichen
            </label>

            <div class="admin-form-actions">
                <button type="submit" class="admin-action-link">
                    Produkt speichern
                </button>
                <a href="products.php" class="admin-cancel-link">Abbrechen</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
