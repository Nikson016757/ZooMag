<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

// Получение популярных товаров
$query = "SELECT * FROM products ORDER BY stock DESC LIMIT 6";
$result = mysqli_query($conn, $query);
$popularProducts = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<main>
    <section class="hero">
        <h1>Добро пожаловать в Зоомагазин!</h1>
        <p>У нас вы найдете все необходимое для ваших питомцев.</p>
        <a href="/products.php" class="btn">Смотреть каталог</a>
    </section>

    <section class="popular-products">
        <h2>Популярные товары</h2>
        <div class="product-grid">
            <?php foreach ($popularProducts as $product): ?>
                <div class="product-card">
                    <?php
                    // Determine image path
                    $imagePath = $product['image_path'];
                    if (strpos($imagePath, '/uploads/products/') === 0) {
                        $imageSrc = htmlspecialchars($imagePath);
                    } else {
                        $imageSrc = '/assets/images/' . htmlspecialchars($imagePath);
                    }
                    ?>
                    <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><?php echo number_format($product['price'], 2, ',', ' ') . ' руб.'; ?></p>
                    <a href="/product_details.php?id=<?php echo $product['id']; ?>" class="btn">Подробнее</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="news">
        <h2>Последние новости</h2>
        <p>Скоро открытие нового филиала в Санкт-Петербурге! Следите за обновлениями.</p>
    </section>
</main>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>