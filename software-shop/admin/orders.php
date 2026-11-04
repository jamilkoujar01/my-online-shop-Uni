<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
requireAdmin();

require_once __DIR__ . '/../config/database.php';

/*
 * Erlaubte Bestellstatus.
 */
$allowedStatuses = [
    'pending',
    'processing',
    'completed',
    'cancelled',
];

/*
 * Statusänderung verarbeiten.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyAdminCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Ungültige Anfrage. Bitte lade die Seite neu.');
    }

    $orderId = filter_var(
        $_POST['order_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    $status = $_POST['status'] ?? '';

    if (
        $orderId === false ||
        !is_string($status) ||
        !in_array($status, $allowedStatuses, true)
    ) {
        http_response_code(400);
        exit('Ungültige Bestelldaten.');
    }

    $stmt = $pdo->prepare(
        'UPDATE orders SET status = ? WHERE id = ?'
    );

    $stmt->execute([$status, $orderId]);

    header('Location: orders.php?updated=1');
    exit;
}

/*
 * Alle Bestellungen laden.
 */
$stmt = $pdo->query(
    'SELECT
        id,
        customer_name,
        customer_email,
        total_amount,
        status,
        created_at
     FROM orders
     ORDER BY created_at DESC, id DESC'
);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
 * Bestellpositionen gesammelt laden.
 */
$stmt = $pdo->query(
    'SELECT
        order_id,
        product_name,
        unit_price,
        quantity
     FROM order_items
     ORDER BY order_id DESC, id ASC'
);

$allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

$itemsByOrder = [];

foreach ($allItems as $item) {
    $itemsByOrder[(int) $item['order_id']][] = $item;
}

/*
 * HTML-Ausgabe absichern.
 */
function orderEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/*
 * Lesbare Statusbezeichnungen.
 */
function orderStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Offen',
        'processing' => 'In Bearbeitung',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Storniert',
        default => 'Unbekannt',
    };
}

function orderStatusClass(string $status): string
{
    return match ($status) {
        'pending' => 'order-status-pending',
        'processing' => 'order-status-processing',
        'completed' => 'order-status-completed',
        'cancelled' => 'order-status-cancelled',
        default => '',
    };
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Bestellungen – Software-Shop</title>

    <link rel="stylesheet" href="../assets/css/orders.css">
</head>

<body class="admin-page">

<header class="admin-header">
    <a class="admin-brand" href="dashboard.php">
        Software-Shop Admin
    </a>

    <nav class="admin-nav" aria-label="Admin-Navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="products.php">Produkte</a>
        <a href="orders.php" aria-current="page">Bestellungen</a>

        <form
            action="logout.php"
            method="POST"
            class="admin-logout-form"
        >
            <input
                type="hidden"
                name="csrf_token"
                value="<?= orderEscape(adminCsrfToken()) ?>"
            >

            <button
                type="submit"
                class="admin-logout-button"
            >
                Abmelden
            </button>
        </form>
    </nav>
</header>

<main class="admin-dashboard">

    <section class="admin-welcome">
        <p class="admin-eyebrow">SHOP-VERWALTUNG</p>
        <h1>Bestellungen</h1>
        <p>
            Hier kannst du Bestellungen und ihre Positionen ansehen
            und den Bearbeitungsstatus verwalten.
        </p>
    </section>

    <section class="admin-panel">

        <div class="admin-panel-heading">
            <div>
                <h2>Alle Bestellungen</h2>
                <p>
                    <?= count($orders) ?>
                    <?= count($orders) === 1
                        ? 'Bestellung'
                        : 'Bestellungen' ?>
                    in der Datenbank
                </p>
            </div>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <p class="admin-success">
                Der Bestellstatus wurde erfolgreich aktualisiert.
            </p>
        <?php endif; ?>

        <?php if (empty($orders)): ?>

            <div class="orders-empty">
                <h3>Noch keine Bestellungen vorhanden</h3>
                <p>
                    Sobald ein Kunde eine Bestellung abschließt,
                    wird sie hier angezeigt.
                </p>
            </div>

        <?php else: ?>

            <div class="admin-table-wrapper">
                <table class="admin-table orders-table">
                    <thead>
                    <tr>
                        <th>Bestellung</th>
                        <th>Kunde</th>
                        <th>Datum</th>
                        <th>Gesamtbetrag</th>
                        <th>Status</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($orders as $order): ?>

                        <?php
                        $orderId = (int) $order['id'];
                        $status = (string) $order['status'];
                        $items = $itemsByOrder[$orderId] ?? [];
                        ?>

                        <tr>
                            <td>
                                <strong>
                                    #<?= $orderId ?>
                                </strong>
                            </td>

                            <td>
                                <div class="order-customer">
                                    <strong>
                                        <?= orderEscape(
                                            (string) $order['customer_name']
                                        ) ?>
                                    </strong>

                                    <span>
                                        <?= orderEscape(
                                            (string) $order['customer_email']
                                        ) ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <?= orderEscape(
                                    date(
                                        'd.m.Y H:i',
                                        strtotime(
                                            (string) $order['created_at']
                                        )
                                    )
                                ) ?>
                            </td>

                            <td class="order-total">
                                <?= number_format(
                                    (float) $order['total_amount'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> €
                            </td>

                            <td>
                                <span class="order-status <?= orderEscape(
                                    orderStatusClass($status)
                                ) ?>">
                                    <?= orderEscape(
                                        orderStatusLabel($status)
                                    ) ?>
                                </span>
                            </td>
                        </tr>

                        <tr class="order-details-row">
                            <td colspan="5">
                                <details class="order-details">
                                    <summary>
                                        Bestellpositionen anzeigen
                                        (<?= count($items) ?>)
                                    </summary>

                                    <?php if (empty($items)): ?>

                                        <p>
                                            Für diese Bestellung wurden
                                            keine Positionen gefunden.
                                        </p>

                                    <?php else: ?>

                                        <div class="order-items-wrapper">
                                            <table class="order-items-table">
                                                <thead>
                                                <tr>
                                                    <th>Produkt</th>
                                                    <th>Einzelpreis</th>
                                                    <th>Menge</th>
                                                    <th>Summe</th>
                                                </tr>
                                                </thead>

                                                <tbody>
                                                <?php foreach ($items as $item): ?>

                                                    <tr>
                                                        <td>
                                                            <?= orderEscape(
                                                                (string) $item['product_name']
                                                            ) ?>
                                                        </td>

                                                        <td>
                                                            <?= number_format(
                                                                (float) $item['unit_price'],
                                                                2,
                                                                ',',
                                                                '.'
                                                            ) ?> €
                                                        </td>

                                                        <td>
                                                            <?= (int) $item['quantity'] ?>
                                                        </td>

                                                        <td>
                                                            <?= number_format(
                                                                (float) $item['unit_price']
                                                                * (int) $item['quantity'],
                                                                2,
                                                                ',',
                                                                '.'
                                                            ) ?> €
                                                        </td>
                                                    </tr>

                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                    <?php endif; ?>
                                </details>

                                <form
                                    method="POST"
                                    action="orders.php"
                                    class="order-status-form"
                                >
                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= orderEscape(
                                            adminCsrfToken()
                                        ) ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?= $orderId ?>"
                                    >

                                    <label for="status-<?= $orderId ?>">
                                        Bestellstatus ändern
                                    </label>

                                    <select
                                        id="status-<?= $orderId ?>"
                                        name="status"
                                        required
                                    >
                                        <?php foreach (
                                            $allowedStatuses as $option
                                        ): ?>
                                            <option
                                                value="<?= orderEscape($option) ?>"
                                                <?= $status === $option
                                                    ? 'selected'
                                                    : '' ?>
                                            >
                                                <?= orderEscape(
                                                    orderStatusLabel($option)
                                                ) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button
                                        type="submit"
                                        class="admin-action-link"
                                    >
                                        Status speichern
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </section>

    <p>
        <a href="dashboard.php">← Zurück zum Dashboard</a>
    </p>

</main>
</body>
</html>
