<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); /*Ein CSRF-Token ist ein zufälliger, serverseitig geprüfter Wert, der bei jeder schreibenden Formularaktion mitgesendet wird. Fehlt der richtige Token, lehnt PHP die Anfrage ab.*/
}

require_once __DIR__ . '/config/database.php';

const MAX_PRODUCT_QUANTITY = 10;

$cart = $_SESSION['cart'] ?? [];

if (!is_array($cart)) {
    $cart = [];
    $_SESSION['cart'] = [];
}

// POST-Anfragen verarbeiten.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF-Token überprüfen
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        http_response_code(403);
        exit('Ungültige Anfrage. Bitte lade die Seite neu.');
    }

    $action = $_POST['action'] ?? '';

    $productId = filter_input(
        INPUT_POST,
        'product_id',
        FILTER_VALIDATE_INT
    );

    // Produkt-ID prüfen.
    if (
        $productId === false ||
        $productId === null ||
        $productId < 1
    ) {
        header('Location: cart.php');
        exit;
    }

    // Nur Produkte bearbeiten, die im Warenkorb liegen.
    if (!array_key_exists($productId, $_SESSION['cart'])) {
        header('Location: cart.php');
        exit;
    }

    // Artikel entfernen.
    if ($action === 'remove') {
        unset($_SESSION['cart'][$productId]);

        header('Location: cart.php');
        exit;
    }

    // Produktmenge ändern.
    if ($action === 'update_quantity') {
        $change = $_POST['change'] ?? '';

        $currentQuantity = filter_var(
            $_SESSION['cart'][$productId],
            FILTER_VALIDATE_INT
        );

        if (
            $currentQuantity === false ||
            $currentQuantity < 1 ||
            $currentQuantity > MAX_PRODUCT_QUANTITY
        ) {
            $_SESSION['cart'][$productId] = 1;
            $currentQuantity = 1;
        }

        if ($change === 'plus') {
            if ($currentQuantity < MAX_PRODUCT_QUANTITY) {
                $_SESSION['cart'][$productId] =
                    $currentQuantity + 1;
            }
        } elseif ($change === 'minus') {
            if ($currentQuantity > 1) {
                $_SESSION['cart'][$productId] =
                    $currentQuantity - 1;
            }
        }

        header('Location: cart.php');
        exit;
    }

    // Unbekannte Aktionen nicht verarbeiten.
    header('Location: cart.php');
    exit;
}

// Warenkorbartikel und Preise aus der Datenbank laden.
$cartItems = [];
$total = 0;

foreach ($_SESSION['cart'] as $productId => $quantity) {
    $productId = filter_var(
        $productId,
        FILTER_VALIDATE_INT
    );

    if ($productId === false || $productId < 1) {
        continue;
    }

    $stmt = $pdo->prepare(
        'SELECT id, name, price
         FROM products
         WHERE id = :id AND active = 1'
    );

    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        continue;
    }

    $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

    if (
        $quantity === false ||
        $quantity < 1 ||
        $quantity > MAX_PRODUCT_QUANTITY
    ) {
        $quantity = 1;
        $_SESSION['cart'][$productId] = 1;
    }

    $product['quantity'] = $quantity;
    $product['subtotal'] =
        (float) $product['price'] * $quantity;

    $total += $product['subtotal'];
    $cartItems[] = $product;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mein Warenkorb</title>
    <link rel="stylesheet" href="assets/css/style.css">
    
</head>
<body class="cart-page">
    <main class="container cart-container">

        <header class="cart-header">
            <h1>Mein Warenkorb</h1>

            <a class="continue-shopping" href="index.php">
                <span aria-hidden="true">←</span>
                Weiter einkaufen
            </a>
        </header>

        <?php if (empty($cartItems)): ?>

            <section class="empty-cart">
                <div class="empty-cart-icon" aria-hidden="true">
                    🛒
                </div>

                <h2>Dein Warenkorb ist noch leer</h2>

                <p>Entdecke unsere Microsoft-Softwareprodukte.</p>

                <a class="continue-shopping" href="index.php">
                    Produkte entdecken
                </a>
            </section>

        <?php else: ?>

            <div class="cart-items">

                <?php foreach ($cartItems as $item): ?>
                    <section class="product-card cart-item">

                        <div class="cart-item-details">
                            <h2>
                                <?= htmlspecialchars(
                                    $item['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h2>

                            <p class="cart-unit-price">
                                Einzelpreis:
                                <strong>
                                    <?= number_format(
                                        (float) $item['price'],
                                        2, ',', '.'
                                    ) ?> €
                                </strong>
                            </p>

                            
<div class="cart-quantity-control">
    <span class="quantity-label">Menge:</span>

    <form action="cart.php" method="post">

    <input
         type="hidden"
         name="csrf_token"
         value="<?= htmlspecialchars(
         $_SESSION['csrf_token'],
         ENT_QUOTES,
         'UTF-8'
    ) ?>"
>
        <input
            type="hidden"
            name="action"
            value="update_quantity"
        >
        <input
            type="hidden"
            name="product_id"
            value="<?= (int) $item['id'] ?>"
        >
        <input
            type="hidden"
            name="change"
            value="minus"
        >

        <button
            type="submit"
            class="quantity-button"
            aria-label="Menge verringern"
            <?= $item['quantity'] <= 1 ? 'disabled' : '' ?>
        >−</button>
    </form>

    <span class="quantity-value">
        <?= (int) $item['quantity'] ?>
    </span>

    <form action="cart.php" method="post">
        <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
        $_SESSION['csrf_token'],
        ENT_QUOTES,
        'UTF-8'
    ) ?>"
>
        <input
            type="hidden"
            name="action"
            value="update_quantity"
        >
        <input
            type="hidden"
            name="product_id"
            value="<?= (int) $item['id'] ?>"
        >
        <input
            type="hidden"
            name="change"
            value="plus"
        >

        <button
            type="submit"
            class="quantity-button"
            aria-label="Menge erhöhen"
            <?= $item['quantity'] >= MAX_PRODUCT_QUANTITY ? 'disabled' : '' ?>
        >+</button>
    </form>
</div>


                            <p class="cart-subtotal">
                                Zwischensumme:
                                <strong>
                                    <?= number_format(
                                        $item['subtotal'],
                                        2, ',', '.'
                                    ) ?> €
                                </strong>
                            </p>
                        </div>

                        <form
                            class="remove-item-form"
                            action="cart.php"
                            method="post"
                        >
                        <input
                         type="hidden"
                         name="csrf_token"
                         value="<?= htmlspecialchars(
                         $_SESSION['csrf_token'],
                         ENT_QUOTES,
                         'UTF-8'
                        ) ?>"
                        >
                            <input
                                type="hidden"
                                name="action"
                                value="remove"
                            >

                            <input
                                type="hidden"
                                name="product_id"
                                value="<?= (int) $item['id'] ?>"
                            >

                            <button
                                class="remove-item-button"
                                type="submit"
                            >
                                Artikel entfernen
                            </button>
                        </form>

                    </section>
                <?php endforeach; ?>

            </div>

            
<section class="cart-summary">
    <div class="cart-summary-header">
        <h2>Bestellübersicht</h2>
    </div>

    <div class="cart-summary-row">
        <span>Artikel</span>
        <span><?= count($cartItems) ?></span>
    </div>

    <div class="cart-summary-row cart-summary-total">
        <span>Gesamtsumme</span>
        <span>
            <?= number_format($total, 2, ',', '.') ?> €
        </span>
    </div>

    <p class="checkout-note">
        Versandkosten entfallen bei digitalen Softwareprodukten.
    </p>

   
<button
    type="button"
    class="checkout-button"
    onclick="window.location.href='checkout.php'"
>
    Weiter zur Kasse
    <span aria-hidden="true">→</span>
</button>
    <div class="payment-methods">
        <p>Geplante Zahlungsmethoden</p>
        <div class="payment-method-labels">
            <span>PayPal</span>
            <span>Klarna</span>
            <span>Kreditkarte</span>
        </div>
        <small>Die tatsächliche Zahlungsabwicklung wird noch eingerichtet.</small>
    </div>
</section>


        <?php endif; ?>

    </main>
    <style>
            .checkout-button {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
    width: 100%;
    box-sizing: border-box;
    margin-top: 20px;
    padding: 16px 20px;

    border: none;
    border-radius: 12px;

    background: #2457d6;
    color: #fff;

    font-family: inherit;
    font-size: 1rem;
    font-weight: 700;

    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}

.checkout-button:hover {
    background: #1945b5;
    transform: translateY(-1px);
}

.checkout-button:focus-visible {
    outline: 3px solid #8baeff;
    outline-offset: 3px;
}

.checkout-button:active {
    transform: translateY(0);
}
    </style>
</body>
</html>
