<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');

$settings = getSettings();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    echo "<p>Неверный идентификатор товара.</p>";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
    exit;
}

$query = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo "Ошибка подготовки запроса: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<p>Товар не найден.</p>";
    include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php');
    exit;
}

$reviewsQuery = "SELECT r.*, c.name AS customer_name FROM reviews r JOIN customers c ON r.customer_id = c.id WHERE r.product_id = ? ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviewsQuery);
if (!$stmt) {
    echo "Ошибка подготовки запроса: " . $conn->error;
    exit;
}
$stmt->bind_param("i", $productId);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = $reviewsResult ? mysqli_fetch_all($reviewsResult, MYSQLI_ASSOC) : [];
$stmt->close();

$success_message = '';
$error_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'added_to_cart') {
    $success_message = 'Товар успешно добавлен в корзину!';
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'method_not_allowed':
            $error_message = 'Неверный метод запроса.';
            break;
        case 'invalid_data':
            $error_message = 'Неверные данные о товаре или количестве.';
            break;
        case 'product_not_found':
            $error_message = 'Товар не найден.';
            break;
        case 'insufficient_stock':
            $error_message = 'Недостаточно товара на складе.';
            break;
        case 'add_to_cart_failed':
            $error_message = 'Не удалось добавить товар в корзину.';
            break;
    }
}

// Проверка, добавлен ли товар в избранное
$isFavorite = false;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    $checkFav = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $checkFav->bind_param("ii", $userId, $productId);
    $checkFav->execute();
    $resultFav = $checkFav->get_result();
    $isFavorite = $resultFav->num_rows > 0;
    $checkFav->close();
}
?>

<main>
    <section class="product-details">
        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <div class="product-image">
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
        </div>
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            
            <div class="product-rating">
                <div class="stars">
                    <?php
                    $avg_rating = $product['average_rating'] ?? 0;
                    $full_stars = floor($avg_rating);
                    $half_star = ($avg_rating - $full_stars) >= 0.5;
                    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                    
                    for ($i = 0; $i < $full_stars; $i++) {
                        echo '<i class="fas fa-star"></i>';
                    }
                    if ($half_star) {
                        echo '<i class="fas fa-star-half-alt"></i>';
                    }
                    for ($i = 0; $i < $empty_stars; $i++) {
                        echo '<i class="far fa-star"></i>';
                    }
                    ?>
                    <span class="rating-value"><?= number_format($avg_rating, 1) ?></span>
                </div>
                <span class="rating-count">(<?= count($reviews) ?> отзывов)</span>
            </div>
            
            <p class="price"><?php echo formatPrice($product['price']); ?></p>
            <p class="description"><?php echo htmlspecialchars($product['description']); ?></p>
            <form method="POST" action="/api/add_to_cart.php">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity">Количество:</label>
                <input type="number" name="quantity" id="quantity" value="1" min="1" required>
                <button type="submit" class="btn">Добавить в корзину</button>
            </form>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form method="POST" action="/api/toggle_favorite.php">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="btn <?= $isFavorite ? 'favorite-active' : 'favorite' ?>">
                        <?= $isFavorite ? 'Удалить из избранного' : 'Добавить в избранное' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <section class="product-reviews">
        <h2>Отзывы</h2>
        <?php if ($reviews): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review">
                    <p><strong><?php echo htmlspecialchars($review['customer_name']); ?></strong> (<?php echo htmlspecialchars($review['created_at']); ?>) - Рейтинг: <?php echo $review['rating']; ?>/5</p>
                    <p><?php echo htmlspecialchars($review['comment']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Отзывов пока нет.</p>
        <?php endif; ?>

        <?php if (isset($_SESSION['user_id'])): ?>
            <h3>Добавить отзыв</h3>
            <form method="POST" action="/api/submit_review.php">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="rating">Рейтинг (1-5):</label>
                <input type="number" name="rating" id="rating" min="1" max="5" required>
                <label for="comment">Комментарий:</label>
                <textarea name="comment" id="comment" rows="4" required></textarea>
                <button type="submit" class="btn">Отправить</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<script>
// Запись просмотра товара
document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_SESSION['user_id'])): ?>
    const productId = <?php echo $product['id']; ?>;
    fetch('/api/record_view.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}`
    });
    <?php endif; ?>
});
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>