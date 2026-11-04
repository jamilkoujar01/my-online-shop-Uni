<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

// Gesamtzahl aller Produkte
$productCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM products')
    ->fetchColumn();

// Anzahl aktiver Produkte
$activeProductCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM products WHERE active = 1')
    ->fetchColumn();

// Gesamtzahl aller Bestellungen
$orderCount = (int) $pdo
    ->query('SELECT COUNT(*) FROM orders')
    ->fetchColumn();

// Anzahl der noch offenen Bestellungen
$pendingOrderCount = (int) $pdo
    ->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")
    ->fetchColumn();

// Summe aller gespeicherten Bestellwerte.
// Das ist noch kein bestätigter Zahlungseingang.
$orderVolume = (float) $pdo
    ->query('SELECT COALESCE(SUM(total_amount), 0) FROM orders')
    ->fetchColumn();

// Neueste Bestellungen
$recentOrders = $pdo->query(
    'SELECT id, customer_name, customer_email,
            total_amount, status, created_at
     FROM orders
     ORDER BY created_at DESC, id DESC
     LIMIT 10'
)->fetchAll(PDO::FETCH_ASSOC);

function adminEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin-Dashboard – Software-Shop</title>
    <link rel="stylesheet" href="../assets/css/setup_admin.css">
</head>
<body class="admin-page">
<header class="admin-header">
    <div>
        <a class="admin-brand" href="dashboard.php">Software-Shop Admin</a>
    </div>

    <nav class="admin-nav" aria-label="Admin-Navigation">
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="products.php">Produkte</a>
        <a href="orders.php">Bestellungen</a>

        <form action="logout.php" method="POST" class="admin-logout-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= adminEscape(adminCsrfToken()) ?>"
            >
            <button type="submit" class="admin-logout-button">
                Abmelden
            </button>
        </form>
    </nav>
</header>

<main class="admin-dashboard">
    <section class="admin-welcome">
        <p class="admin-eyebrow">VERWALTUNG</p>
        <h1>Willkommen, <?= adminEscape((string) $_SESSION['admin_name']) ?>!</h1>
        <p>Hier findest du eine Übersicht über deinen Software-Shop.</p>
    </section>

    <section class="admin-stats" aria-label="Shop-Kennzahlen">
        <article class="admin-stat-card">
            <span>Produkte insgesamt</span>
            <strong><?= $productCount ?></strong>
            <small><?= $activeProductCount ?> davon aktiv</small>
        </article>

        <article class="admin-stat-card">
            <span>Bestellungen insgesamt</span>
            <strong><?= $orderCount ?></strong>
            <small><?= $pendingOrderCount ?> mit Status „pending“</small>
        </article>

        <article class="admin-stat-card">
            <span>Gespeicherter Bestellwert</span>
            <strong><?= number_format($orderVolume, 2, ',', '.') ?> €</strong>
            <small>Keine Aussage über tatsächliche Zahlungseingänge</small>
        </article>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-heading">
            <div>
                <h2>Neueste Bestellungen</h2>
                <p>Die zehn zuletzt angelegten Bestellungen.</p>
            </div>
            <a href="orders.php">Alle anzeigen →</a>
        </div>

        <?php if (empty($recentOrders)): ?>
            <p>Es wurden noch keine Bestellungen aufgegeben.</p>
        <?php else: ?>
            <div class="admin-table-wrapper">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th>Nr.</th>
                        <th>Kunde</th>
                        <th>Datum</th>
                        <th>Betrag</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= (int) $order['id'] ?></td>
                            <td>
                                <strong><?= adminEscape($order['customer_name']) ?></strong>
                                <small class="admin-table-email">
                                    <?= adminEscape($order['customer_email']) ?>
                                </small>
                            </td>
                            <td>
                                <?= adminEscape(
                                    date('d.m.Y H:i', strtotime($order['created_at']))
                                ) ?>
                            </td>
                            <td>
                                <?= number_format(
                                    (float) $order['total_amount'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> €
                            </td>
                            <td><?= adminEscape($order['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-quick-links">
        <a href="products.php" class="admin-quick-link">
            <strong>Produkte verwalten</strong>
            <span>Produkte hinzufügen und bearbeiten →</span>
        </a>
        <a href="orders.php" class="admin-quick-link">
            <strong>Bestellungen verwalten</strong>
            <span>Bestellungen ansehen und Status ändern →</span>
        </a>
    </section>
</main>
</body>
</html>
