<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

// CSRF-Token prüfen
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit('Ungültige Anfrage. Bitte lade die Kassenseite neu.');
}

// Kundendaten prüfen
$name = trim($_POST['customer_name'] ?? '');
$email = trim($_POST['customer_email'] ?? '');

if (
    $name === '' ||
    mb_strlen($name) > 150 ||
    mb_strlen($email) > 254 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {
    exit('Bitte gib einen gültigen Namen und eine gültige E-Mail-Adresse ein.');
}

// Warenkorb prüfen
if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$cart = $_SESSION['cart'];

try {
    $pdo->beginTransaction();

    $orderItems = [];
    $total = 0;

    // Produktpreise immer aus der Datenbank laden
    $productStmt = $pdo->prepare(
        'SELECT id, name, price
         FROM products
         WHERE id = ? AND active = 1'
    );

    foreach ($cart as $productId => $quantity) {
        $productId = filter_var($productId, FILTER_VALIDATE_INT);
        $quantity = filter_var($quantity, FILTER_VALIDATE_INT);

        if (
            $productId === false ||
            $productId < 1 ||
            $quantity === false ||
            $quantity < 1 ||
            $quantity > 10
        ) {
            throw new RuntimeException('Ungültiger Warenkorb.');
        }

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new RuntimeException(
                'Ein Produkt ist nicht mehr verfügbar. Bitte prüfe deinen Warenkorb.'
            );
        }

        $price = (float) $product['price'];
        $subtotal = round($price * $quantity, 2);
        $total = round($total + $subtotal, 2);

        $orderItems[] = [
            'product_id' => (int) $product['id'],
            'product_name' => $product['name'],
            'unit_price' => $price,
            'quantity' => $quantity
        ];
    }

    if (empty($orderItems)) {
        throw new RuntimeException('Dein Warenkorb ist leer.');
    }

    // Hauptbestellung speichern
    $orderStmt = $pdo->prepare(
        'INSERT INTO orders
         (customer_name, customer_email, total_amount, status)
         VALUES (?, ?, ?, ?)'
    );

    $orderStmt->execute([
        $name,
        $email,
        $total,
        'pending'
    ]);

    $orderId = (int) $pdo->lastInsertId();

    // Einzelne Bestellpositionen speichern
    $itemStmt = $pdo->prepare(
        'INSERT INTO order_items
         (order_id, product_id, product_name, unit_price, quantity)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($orderItems as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['product_name'],
            $item['unit_price'],
            $item['quantity']
        ]);
    }

    $pdo->commit();

    // Warenkorb nach erfolgreicher Bestellung leeren
    $_SESSION['cart'] = [];

    // Bestellnummer in der Session speichern
    $_SESSION['last_order_id'] = $orderId;

    header('Location: order_success.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Bestellfehler: ' . $e->getMessage());

    http_response_code(400);
    echo 'Die Bestellung konnte nicht abgeschlossen werden. '
       . 'Bitte prüfe deinen Warenkorb und versuche es erneut.';
}
