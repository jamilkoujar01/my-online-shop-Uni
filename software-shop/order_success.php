
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$orderId = $_SESSION['last_order_id'] ?? null;

if (
    !is_int($orderId) ||
    $orderId < 1
) {
    header('Location: index.php');
    exit;
}

// Bestellung laden
$stmt = $pdo->prepare(
    'SELECT id, customer_name, total_amount, status, created_at
     FROM orders
     WHERE id = ?'
);

$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    unset($_SESSION['last_order_id']);
    header('Location: index.php');
    exit;
}

// Bestellpositionen laden
$itemStmt = $pdo->prepare(
    'SELECT product_name, unit_price, quantity
     FROM order_items
     WHERE order_id = ?'
);

$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

// Bestellnummer nach dem Laden aus der Sitzung entfernen
unset($_SESSION['last_order_id']);

function escapeHtml(string $value): string
{
    return htmlspecialchars(
        $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Bestellung bestätigt – Software-Shop</title>

    <link rel="stylesheet" href="assets/css/order_success.css">
    
</head>

<body class="success-page">

<main class="success-container">

    <section class="success-hero">

        <div class="success-check" aria-hidden="true">
            <svg
                viewBox="0 0 52 52"
                width="52"
                height="52"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <circle
                    cx="26"
                    cy="26"
                    r="24"
                    stroke="currentColor"
                    stroke-width="2.5"
                />
                <path
                    d="M15 27L22 34L37 19"
                    stroke="currentColor"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </div>

        <p class="success-eyebrow">BESTELLUNG ERFOLGREICH GESPEICHERT</p>

        <h1>Vielen Dank für deine Bestellung!</h1>

        <p class="success-intro">
            Hallo <?= escapeHtml($order['customer_name']) ?>,
            deine Bestellung wurde erfolgreich aufgenommen.
        </p>

        <div class="success-order-number">
            <span>Deine Bestellnummer</span>
            <strong>#<?= (int) $order['id'] ?></strong>
        </div>

    </section>

    <section class="success-card">

        <div class="success-card-heading">
            <div>
                <h2>Bestellübersicht</h2>
                <p>Hier findest du die Details deiner Bestellung.</p>
            </div>

            <span class="success-status">
                <?= escapeHtml($order['status']) ?>
            </span>
        </div>

        <div class="success-meta">
            <div>
                <span>Bestelldatum</span>
                <strong>
                    <?= escapeHtml(
                        date(
                            'd.m.Y, H:i',
                            strtotime($order['created_at'])
                        )
                    ) ?>
                </strong>
            </div>

            <div>
                <span>Bestellnummer</span>
                <strong>#<?= (int) $order['id'] ?></strong>
            </div>
        </div>

        <div class="success-items-heading">
            <h3>Deine Produkte</h3>
        </div>

        <div class="success-items">

            <?php foreach ($items as $item): ?>
                <div class="success-item">

                    <div class="success-product-icon" aria-hidden="true">
                        <svg
                            viewBox="0 0 24 24"
                            width="25"
                            height="25"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect
                                x="3"
                                y="4"
                                width="18"
                                height="16"
                                rx="2"
                            />
                            <path d="M3 9H21" />
                            <path d="M7 6.5H7.01" />
                            <path d="M10 6.5H10.01" />
                        </svg>
                    </div>

                    <div class="success-product-info">
                        <strong>
                            <?= escapeHtml($item['product_name']) ?>
                        </strong>

                        <span>
                            Menge: <?= (int) $item['quantity'] ?>
                            · Einzelpreis:
                            <?= number_format(
                                (float) $item['unit_price'],
                                2,
                                ',',
                                '.'
                            ) ?> €
                        </span>
                    </div>

                    <strong class="success-item-price">
                        <?= number_format(
                            (float) $item['unit_price']
                                * (int) $item['quantity'],
                            2,
                            ',',
                            '.'
                        ) ?> €
                    </strong>

                </div>
            <?php endforeach; ?>

        </div>

        <div class="success-total">
            <span>Gesamtbetrag</span>

            <strong>
                <?= number_format(
                    (float) $order['total_amount'],
                    2,
                    ',',
                    '.'
                ) ?> €
            </strong>
        </div>

        <div class="success-payment-note">
            <span aria-hidden="true">ℹ</span>
            <p>
                Die Bestellung wurde gespeichert.
                Eine Online-Zahlung wurde noch nicht durchgeführt.
            </p>
        </div>

    </section>

    <section class="success-next-steps">
        <span class="success-next-icon" aria-hidden="true">✉</span>

        <div>
            <h2>Wie geht es weiter?</h2>
            <p>
                Deine Bestellung wurde in unserem Shop gespeichert.
                Ein automatischer E-Mail-Versand und die Online-Zahlung
                sind derzeit noch nicht eingerichtet.
            </p>
        </div>
    </section>

    <div class="success-actions">
        <a href="index.php" class="success-primary-button">
            Weiter einkaufen
            <span aria-hidden="true">→</span>
        </a>

        <a href="cart.php" class="success-secondary-button">
            Zum Warenkorb
        </a>
    </div>

    <p class="success-footer">
        Vielen Dank für deinen Besuch in unserem Software-Shop.
    </p>

</main>

</body>
</html>
