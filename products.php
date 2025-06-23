<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

// Фильтрация и сортировка
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort = isset($_GET['sort']) ? mysqli_real_escape_string($conn, $_GET['sort']) : 'price_asc';

// Базовый запрос
$query = "SELECT * FROM products WHERE 1";

// Условия фильтрации
if ($category) {
    $query .= " AND category = '$category'";
}
if ($search) {
    $query .= " AND name LIKE '%$search%'";
}

// Условия сортировки
if ($sort == 'price_asc') {
    $query .= " ORDER BY price ASC";
} elseif ($sort == 'price_desc') {
    $query .= " ORDER BY price DESC";
} elseif ($sort == 'name_asc') {
    $query .= " ORDER BY name ASC";
} elseif ($sort == 'name_desc') {
    $query .= " ORDER BY name DESC";
}

// Получение данных
$result = mysqli_query($conn, $query);
$products = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<main>
    <section class="product-filters">
        <form method="GET" action="products.php">
            <input type="text" name="search" placeholder="Поиск товаров..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="category">
                <option value="">Все категории</option>
                <option value="Корма" <?php echo $category == 'Корма' ? 'selected' : ''; ?>>Корма</option>
                <option value="Игрушки" <?php echo $category == 'Игрушки' ? 'selected' : ''; ?>>Игрушки</option>
                <option value="Аксессуары" <?php echo $category == 'Аксессуары' ? 'selected' : ''; ?>>Аксессуары</option>
                <option value="Наполнители" <?php echo $category == 'Наполнители' ? 'selected' : ''; ?>>Наполнители</option>
                <option value="Клетки" <?php echo $category == 'Клетки' ? 'selected' : ''; ?>>Клетки</option>
            </select>
            <select name="sort">
                <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>Цена (по возрастанию)</option>
                <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Цена (по убыванию)</option>
                <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Название (А-Я)</option>
                <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Название (Я-А)</option>
            </select>
            <button type="submit" class="btn">Применить</button>
        </form>
    </section>

    <section class="product-list">
        <h2>Список товаров</h2>
        <div class="product-grid">
            <?php if ($products): ?>
                <?php foreach ($products as $product): ?>
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
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn">Подробнее</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Товары не найдены.</p>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>