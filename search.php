<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

$searchQuery = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';

$products = [];
if ($searchQuery) {
    $query = "SELECT * FROM products WHERE name LIKE '%$searchQuery%' OR description LIKE '%$searchQuery%'";
    $result = mysqli_query($conn, $query);
    $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
}
?>
<main>
    <section class="search">
        <h1>Поиск</h1>
        <form method="GET" action="search.php">
            <input type="text" name="q" placeholder="Введите запрос..." value="<?php echo htmlspecialchars($searchQuery); ?>">
            <button type="submit" class="btn">Искать</button>
        </form>
    </section>

    <section class="search-results">
        <h2>Результаты поиска</h2>
        <?php if ($searchQuery && !empty($products)): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="/assets/images/<?php echo $product['image_path']; ?>" alt="<?php echo $product['name']; ?>">
                        <h3><?php echo $product['name']; ?></h3>
                        <p><?php echo formatPrice($product['price']); ?></p>
                        <a href="/product_details.php?id=<?php echo $product['id']; ?>" class="btn">Подробнее</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($searchQuery): ?>
            <p>По вашему запросу ничего не найдено.</p>
        <?php else: ?>
            <p>Введите запрос в строку поиска, чтобы найти товары.</p>
        <?php endif; ?>
    </section>
</main>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
