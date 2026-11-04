
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// CSRF-Token erstellen
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Warenkorb prüfen
if (
    empty($_SESSION['cart']) ||
    !is_array($_SESSION['cart'])
) {
    header('Location: cart.php');
    exit;
}

// Produkte und Preise aus der Datenbank laden
$cartItems = [];
$total = 0;

$ids = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "SELECT id, name, price
        FROM products
        WHERE id IN ($placeholders)
        AND active = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    $id = (int) $product['id'];
    $quantity = filter_var(
        $_SESSION['cart'][$id] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $quantity === false ||
        $quantity < 1 ||
        $quantity > 10
    ) {
        continue;
    }

    $product['quantity'] = $quantity;
    $product['subtotal'] =
        (float) $product['price'] * $quantity;

    $cartItems[] = $product;
    $total += $product['subtotal'];
}

if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Kasse – Software-Shop</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="checkout-page">

<main class="checkout-container">

    <header class="checkout-header">
        <a href="cart.php" class="checkout-back">
            <span aria-hidden="true">←</span>
            Zurück zum Warenkorb
        </a>

        <h1>Bestellung abschließen</h1>

        <p>
            Nur noch wenige Schritte bis zu deiner Bestellung.
        </p>

        <div class="checkout-progress">
            <div class="progress-step completed">
                <span>✓</span>
                Warenkorb
            </div>

            <div class="progress-line"></div>

            <div class="progress-step current">
                <span>2</span>
                Kasse
            </div>

            <div class="progress-line"></div>

            <div class="progress-step">
                <span>3</span>
                Bestätigung
            </div>
        </div>
    </header>

    <div class="checkout-layout">

        <section class="checkout-main">

            <section class="checkout-card">
                <div class="checkout-section-heading">
                    <span class="checkout-icon" aria-hidden="true">
                        ♙
                    </span>

                    <div>
                        <h2>Deine Kontaktdaten</h2>
                        <p>Du brauchst kein Kundenkonto.</p>
                    </div>
                </div>

                <form
                    action="place_order.php"
                    method="POST"
                    id="checkout-form"
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

                    <div class="checkout-field">
                        <label for="customer_name">
                            Vor- und Nachname
                        </label>
                        <input
                            type="text"
                            id="customer_name"
                            name="customer_name"
                            maxlength="150"
                            autocomplete="name"
                            placeholder="Max Mustermann"
                            required
                        >
                    </div>

                    <div class="checkout-field">
                        <label for="customer_email">
                            E-Mail-Adresse
                        </label>

                        <input
                            type="email"
                            id="customer_email"
                            name="customer_email"
                            maxlength="254"
                            autocomplete="email"
                            placeholder="name@beispiel.de"
                            required
                        >

                        <small>
                            Hierüber können später Bestellinformationen
                            versendet werden.
                        </small>
                    </div>

                    <div class="checkout-divider"></div>

                    <div class="checkout-section-heading">
                        <span class="checkout-icon" aria-hidden="true">
                            ♧
                        </span>

                        <div>
                            <h2>Zahlungsmethode</h2>
                            <p>Die Online-Zahlung wird noch eingerichtet.</p>
                        </div>
                    </div>

                    <div class="payment-options">

                        <div class="payment-option unavailable">
                            <span class="payment-logo paypal-logo">
                                PayPal
                            </span>

                            <div class="payment-description">
                                <strong>PayPal</strong>
                                <small>Noch nicht verfügbar</small>
                            </div>

                            <span class="payment-status">Demnächst</span>
                        </div>

                        <div class="payment-option unavailable">
                            <span class="payment-logo klarna-logo">
                                Klarna.
                            </span>

                            <div class="payment-description">
                                <strong>Klarna</strong>
                                <small>Noch nicht verfügbar</small>
                            </div>

                            <span class="payment-status">Demnächst</span>
                        </div>

                        <div class="payment-option unavailable">
                            <span class="payment-logo card-logo">
                                VISA
                            </span>

                            <div class="payment-description">
                                <strong>Kreditkarte</strong>
                                <small>Noch nicht verfügbar</small>
                            </div>

                            <span class="payment-status">Demnächst</span>
                        </div>

                    </div>

                    <p class="payment-notice">
                        Aktuell wird nur eine Bestellung ohne Online-Zahlung
                        gespeichert. Es werden keine Zahlungsdaten erfasst.
                    </p>

                    <button
                        type="submit"
                        class="checkout-submit-button"
                    >
                        Bestellung absenden
                        <span aria-hidden="true">→</span>
                    </button>

                    <p class="checkout-legal-note">
                        Mit dem Absenden wird deine Bestellung gespeichert.
                        Eine Online-Zahlung wird dadurch nicht ausgelöst.
                    </p>

                </form>
            </section>

        </section>

        <aside class="checkout-sidebar">

            <section class="checkout-card order-summary-card">

                <h2>Deine Bestellung</h2>

                <p class="order-summary-intro">
                    <?= count($cartItems) ?>
                    <?= count($cartItems) === 1
                        ? 'Produkt'
                        : 'Produkte' ?>
                    im Warenkorb
                </p>

                <div class="checkout-products">

                    <?php foreach ($cartItems as $item): ?>
                        <div class="checkout-product">

                            <div class="checkout-product-info">
                                <strong>
                                    <?= htmlspecialchars(
                                        $item['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                                <span>
                                    Menge: <?= (int) $item['quantity'] ?>
                                </span>
                            </div>

                            <span class="checkout-product-price">
                                <?= number_format(
                                    $item['subtotal'],
                                    2,
                                    ',',
                                    '.'
                                ) ?> €
                            </span>

                        </div>
                    <?php endforeach; ?>

                </div>

                <div class="checkout-total-row">
                    <span>Gesamtbetrag</span>

                    <strong>
                        <?= number_format(
                            $total,
                            2,
                            ',',
                            '.'
                        ) ?> €
                    </strong>
                </div>

                <a
                    href="cart.php"
                    class="edit-cart-link"
                >
                    Warenkorb bearbeiten
                </a>

            </section>

            <div class="checkout-security">
                <span aria-hidden="true">🔒</span>

                <div>
                    <strong>Deine Daten</strong>
                    <p>
                        Deine Kontaktdaten werden über das Bestellformular
                        an den lokalen Shop übermittelt.
                    </p>
                </div>
            </div>

        </aside>

    </div>

</main>
<<style>
    
/* ==================================
   CHECKOUT – MODERNES DESIGN
================================== */

.checkout-page {
    margin: 0;
    background: #f5f7fb;
    color: #172033;
    font-family: inherit;
}

.checkout-container {
    width: min(1120px, calc(100% - 36px));
    margin: 0 auto;
    padding: 40px 0 64px;
}

.checkout-header {
    margin-bottom: 34px;
}

.checkout-back {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    color: #2457d6;
    font-weight: 600;
    text-decoration: none;
}

.checkout-back:hover,
.edit-cart-link:hover {
    text-decoration: underline;
}

.checkout-header h1 {
    margin: 0;
    font-size: clamp(1.9rem, 4vw, 2.6rem);
    letter-spacing: -0.04em;
}

.checkout-header > p {
    margin-top: 10px;
    color: #697386;
}

.checkout-progress {
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 560px;
    margin-top: 28px;
}

.progress-step {
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    color: #7a8497;
    font-size: 0.85rem;
}

.progress-step > span {
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    border: 1px solid #d7deeb;
    border-radius: 50%;
    background: #fff;
    font-weight: 700;
}

.progress-step.completed > span {
    border-color: #18794e;
    background: #18794e;
    color: #fff;
}

.progress-step.current {
    color: #2457d6;
    font-weight: 700;
}

.progress-step.current > span {
    border-color: #2457d6;
    background: #2457d6;
    color: #fff;
}

.progress-line {
    flex: 1;
    height: 1px;
    min-width: 14px;
    background: #d7deeb;
}

.checkout-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.5fr) minmax(290px, 0.9fr);
    align-items: start;
    gap: 26px;
}

.checkout-main,
.checkout-sidebar {
    min-width: 0;
}

.checkout-card {
    padding: 28px;
    border: 1px solid #e5e9f2;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 5px 24px rgba(22, 34, 60, 0.04);
}

.checkout-section-heading {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 24px;
}

.checkout-section-heading h2,
.order-summary-card h2 {
    margin: 0;
    font-size: 1.25rem;
}

.checkout-section-heading p {
    margin: 6px 0 0;
    color: #697386;
    font-size: 0.9rem;
}

.checkout-icon {
    display: grid;
    place-items: center;
    flex: 0 0 44px;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #edf3ff;
    color: #2457d6;
    font-size: 1.5rem;
}

.checkout-field {
    margin-bottom: 20px;
}

.checkout-field label {
    display: block;
    margin-bottom: 8px;
    font-size: 0.92rem;
    font-weight: 650;
}

.checkout-field input {
    display: block;
    box-sizing: border-box;
    width: 100%;
    min-height: 48px;
    padding: 12px 14px;
    border: 1px solid #d7deeb;
    border-radius: 10px;
    background: #fff;
    color: #172033;
    font: inherit;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.checkout-field input::placeholder {
    color: #9aa3b3;
}

.checkout-field input:focus {
    border-color: #2457d6;
    outline: none;
    box-shadow: 0 0 0 3px rgba(36, 87, 214, 0.12);
}

.checkout-field small {
    display: block;
    margin-top: 8px;
    color: #697386;
    line-height: 1.5;
}

.checkout-divider {
    height: 1px;
    margin: 30px 0;
    background: #e5e9f2;
}

.payment-options {
    display: grid;
    gap: 12px;
}

.payment-option {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 15px;
    border: 1px solid #e0e5ef;
    border-radius: 12px;
    background: #fff;
}

.payment-option.unavailable {
    background: #fafbfe;
}

.payment-logo {
    display: grid;
    place-items: center;
    flex: 0 0 68px;
    height: 42px;
    border: 1px solid #e5e9f2;
    border-radius: 8px;
    background: #fff;
    font-weight: 800;
}

.paypal-logo {
    color: #174a9c;
    font-size: 0.9rem;
    font-style: italic;
}

.klarna-logo {
    background: #ffb3c7;
    color: #171717;
    font-size: 1rem;
}

.card-logo {
    color: #1c4db5;
    font-size: 0.95rem;
    font-style: italic;
}

.payment-description {
    display: grid;
    flex: 1;
    gap: 5px;
    min-width: 0;
}

.payment-description strong {
    font-size: 0.95rem;
}

.payment-description small {
    color: #697386;
    font-size: 0.8rem;
}

.payment-status {
    color: #697386;
    font-size: 0.75rem;
    text-align: right;
}

.payment-notice {
    margin: 18px 0 0;
    padding: 13px 14px;
    border: 1px solid #e5e9f2;
    border-radius: 10px;
    background: #f8faff;
    color: #566176;
    font-size: 0.84rem;
    line-height: 1.6;
}

.checkout-submit-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    min-height: 52px;
    box-sizing: border-box;
    margin-top: 24px;
    padding: 14px 20px;
    border: none;
    border-radius: 12px;
    background: #2457d6;
    color: #fff;
    font: inherit;
    font-weight: 750;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}

.checkout-submit-button:hover {
    background: #1945b5;
    transform: translateY(-1px);
}

.checkout-submit-button:focus-visible {
    outline: 3px solid #8baeff;
    outline-offset: 3px;
}

.checkout-legal-note {
    margin: 14px 0 0;
    color: #697386;
    font-size: 0.78rem;
    line-height: 1.6;
    text-align: center;
}

.checkout-sidebar {
    display: grid;
    gap: 18px;
}

.order-summary-intro {
    margin: 8px 0 20px;
    color: #697386;
    font-size: 0.9rem;
}

.checkout-products {
    display: grid;
}

.checkout-product {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #edf0f6;
}

.checkout-product-info {
    display: grid;
    gap: 7px;
    min-width: 0;
}

.checkout-product-info strong {
    overflow-wrap: anywhere;
    font-size: 0.92rem;
    line-height: 1.5;
}

.checkout-product-info span {
    color: #697386;
    font-size: 0.82rem;
}

.checkout-product-price {
    flex-shrink: 0;
    font-size: 0.9rem;
    font-weight: 650;
}

.checkout-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 14px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e9f2;
}

.checkout-total-row span {
    font-weight: 650;
}

.checkout-total-row strong {
    font-size: 1.4rem;
    letter-spacing: -0.03em;
}

.edit-cart-link {
    display: inline-block;
    margin-top: 22px;
    color: #2457d6;
    font-size: 0.9rem;
    font-weight: 650;
    text-decoration: none;
}

.checkout-security {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 18px;
    border: 1px solid #e5e9f2;
    border-radius: 14px;
    background: #fff;
}

.checkout-security > span {
    font-size: 1.3rem;
}

.checkout-security strong {
    font-size: 0.9rem;
}

.checkout-security p {
    margin: 6px 0 0;
    color: #697386;
    font-size: 0.82rem;
    line-height: 1.6;
}

@media (max-width: 800px) {
    .checkout-layout {
        grid-template-columns: 1fr;
    }

    .checkout-sidebar {
        grid-row: 1;
    }

    .checkout-container {
        padding-top: 26px;
    }
}

@media (max-width: 520px) {
    .checkout-container {
        width: calc(100% - 24px);
    }

    .checkout-card {
        padding: 20px;
        border-radius: 14px;
    }

    .checkout-progress {
        gap: 7px;
    }

    .progress-step {
        gap: 5px;
        font-size: 0.72rem;
    }

    .progress-step > span {
        width: 24px;
        height: 24px;
    }

    .payment-option {
        gap: 10px;
        padding: 11px;
    }

    .payment-logo {
        flex-basis: 55px;
        height: 38px;
        font-size: 0.8rem;
    }

    .payment-status {
        max-width: 65px;
        font-size: 0.68rem;
    }

    .checkout-total-row strong {
        font-size: 1.2rem;
    }
}

</style>
</body>

</html>
