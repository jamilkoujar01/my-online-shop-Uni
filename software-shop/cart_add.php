
<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); /*Ein CSRF-Token ist ein zufälliger, serverseitig geprüfter Wert, der bei jeder schreibenden Formularaktion mitgesendet wird. Fehlt der richtige Token, lehnt PHP die Anfrage ab.*/
}
require_once __DIR__ . '/config/database.php';

const MAX_PRODUCT_QUANTITY = 10;

$isAjax = strtolower(
    $_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''
) === 'xmlhttprequest';

function cartResponse(
    bool $isAjax,
    bool $success,
    string $message,
    int $cartCount = 0,
    int $status = 200
): void {
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'success' => $success,
            'message' => $message,
            'cartCount' => $cartCount
        ]);

        exit;
    }

    header('Location: index.php');
    exit;
}

// Nur POST-Anfragen erlauben.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    cartResponse($isAjax, false, 'Ungültige Anfrage.', 0, 405);
}

// Produkt-ID validieren.
$productId = filter_input(
    INPUT_POST,
    'product_id',
    FILTER_VALIDATE_INT
);

if ($productId === false || $productId === null || $productId < 1) {
    cartResponse($isAjax, false, 'Ungültige Produkt-ID.', 0, 400);
}

// Produkt aus der Datenbank prüfen.
$stmt = $pdo->prepare(
    'SELECT id FROM products
     WHERE id = :id AND active = 1'
);

$stmt->execute(['id' => $productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    cartResponse(
        $isAjax,
        false,
        'Dieses Produkt ist nicht verfügbar.',
        0,
        404
    );
}

// Warenkorb initialisieren.
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$currentQuantity = $_SESSION['cart'][$productId] ?? 0;

// Ungültige vorhandene Mengen korrigieren.
if (!is_int($currentQuantity) || $currentQuantity < 0) {
    $currentQuantity = 0;
}

if ($currentQuantity >= MAX_PRODUCT_QUANTITY) {
    $cartCount = array_sum($_SESSION['cart']);

    cartResponse(
        $isAjax,
        false,
        'Maximal 10 Stück pro Produkt erlaubt.',
        (int) $cartCount,
        409
    );
}

// Menge erhöhen.
$_SESSION['cart'][$productId] = $currentQuantity + 1;

$cartCount = array_sum($_SESSION['cart']);

cartResponse(
    $isAjax,
    true,
    'Produkt wurde zum Warenkorb hinzugefügt.',
    (int) $cartCount
);
