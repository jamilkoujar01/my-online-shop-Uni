<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); /*Ein CSRF-Token ist ein zufälliger, serverseitig geprüfter Wert, der bei jeder schreibenden Formularaktion mitgesendet wird. Fehlt der richtige Token, lehnt PHP die Anfrage ab.*/
}
$cartCount = array_sum($_SESSION['cart'] ?? []);
require_once __DIR__ . '/config/database.php';

$shopName = 'SoftwareShop';

$sql = "
    SELECT
        products.id,
        products.name,
        products.description,
        products.price,
        categories.name AS category
    FROM products
    INNER JOIN categories
        ON products.category_id = categories.id
    WHERE products.active = 1
      AND categories.active = 1
    ORDER BY products.name ASC 
";

$stmt = $pdo->query($sql);
$products = $stmt->fetchAll();

/*Dieser Code fuehrt eine Datenbankabfrage aus. INNER JOIN verbindet Produkte mit ihren Kategorien, sodass wir beide Informationen gemeinsam erhalten.*/

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>

<header class="site-header">
    <a class="logo" href="index.php">SoftwareShop</a>

    <nav aria-label="Hauptnavigation">
        <a href="#home">Startseite</a>
        <a href="#products">Produkte</a>
        <a href="#categories">Kategorien</a>
    </nav>

    <a class="cart.php" href="cart.php">
        Warenkorb (<span id="cart-count">
             <?= (int) $cartCount ?>
        </span>)
    </a>
</header>

<main>
    <section class="hero" id="home">
        <div class="hero-content">
            <p class="eyebrow">SOFTWARE FÜR UNTERNEHMEN</p>
            <h1>Die passende Software für dein Unternehmen.</h1>
            <p>
                Entdecke ausgewählte Softwareprodukte für
                Produktivität und professionelle Arbeitsplätze.
            </p>
            <a class="button" href="#products">Produkte entdecken</a>
        </div>
    </section>

    <section class="categories" id="categories">
        <h2>Unsere Kategorien</h2>

        <div class="category-list">
            <a class="category-card" href="#products">Microsoft 365</a>
            <a class="category-card" href="#products">Windows</a>
            <a class="category-card" href="#products">Office</a>
        </div>
    </section>

    <section class="products-section" id="products">
        <div class="section-heading">
            <div>
                <p class="eyebrow">UNSER SORTIMENT</p>
                <h2>Software entdecken</h2>
            </div>
            <p>Finde die passende Lösung für deinen Bedarf.</p>
        </div>

        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <article class="product-card">
                    <div class="product-icon" aria-hidden="true">
                        <?= htmlspecialchars(
                            mb_substr($product["category"], 0, 1),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </div>

                    <p class="product-category">
                        <?= htmlspecialchars(
                            $product["category"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <h3>
                        <?= htmlspecialchars(
                            $product["name"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p class="product-description">
                        <?= htmlspecialchars(
                            $product["description"],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p class="product-price">
                        <?= number_format(
                            $product["price"],
                            2,
                            ',',
                            '.'
                        ) ?> €
                        <span>Beispielpreis</span>
                    </p>

                   <form action="cart_add.php" method="post" class="add-to-cart-form">
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?= (int) $product['id'] ?>"
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
                     <button type="submit" class="add-to-cart">                     
                        In den Warenkorb
                     </button>
                 </form>
                </article>
                <!--Was passiert hier?
- product_id übermittelt die ID des Produkts aus der Datenbank.
- method="post" sendet die Daten an das Backend.
- cart_add.php prüft die Produkt-ID und fügt das Produkt zum Warenkorb hinzu.-->
            <?php endforeach; ?>
        </div>
    </section>
</main>

<footer class="site-footer">
    <p>
        &copy; <?= date("Y") ?>
        <?= htmlspecialchars($shopName, ENT_QUOTES, 'UTF-8') ?>
        – Universitätsprojekt
    </p>
    <p>Demonstrationsshop mit fiktiven Beispielpreisen.</p>
</footer>




<p id="cart-message" role="status" aria-live="polite"></p>

<script>
document.querySelectorAll('.add-to-cart-form').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        const message = document.getElementById('cart-message');
        const cartLink = document.querySelector('.cart-link');

        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: `product_id=${encodeURIComponent(form.elements.product_id.value)}&csrf_token=${encodeURIComponent(form.elements.csrf_token.value)}`,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            });

            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Hinzufügen fehlgeschlagen.');
            }

            if (cartLink) {
                cartLink.textContent = `Warenkorb (${data.cartCount})`;
            }

            message.textContent = data.message;
        } catch (error) {
            message.textContent =
                error.message || 'Es ist ein Fehler aufgetreten.';
        } finally {
            button.disabled = false;
        }
    });
});
</script>
</body>
</html>
