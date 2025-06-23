<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$settings = getSettings();

$userId = (int)$_SESSION['user_id'];

// Получение товаров из корзины
$query = "SELECT ci.id AS cart_item_id, p.id AS product_id, p.name, p.price, p.image_path, ci.quantity 
          FROM cart_items ci 
          JOIN products p ON ci.product_id = p.id 
          WHERE ci.user_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

// Вычисление общей суммы
$totalAmount = array_reduce($cartItems, function ($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

$stmt->close();

// Обработка сообщений
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'updated':
            $success_message = 'Количество успешно обновлено!';
            break;
        case 'removed':
            $success_message = 'Товар удален из корзины!';
            break;
    }
}
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'method_not_allowed':
            $error_message = 'Неверный метод запроса.';
            break;
        case 'invalid_data':
            $error_message = 'Неверные данные.';
            break;
        case 'not_found':
            $error_message = 'Элемент корзины не найден.';
            break;
        case 'insufficient_stock':
            $error_message = 'Недостаточно товара на складе.';
            break;
        case 'query_failed':
            $error_message = 'Ошибка сервера.';
            break;
        case 'update_failed':
            $error_message = 'Не удалось обновить количество.';
            break;
        case 'remove_failed':
            $error_message = 'Не удалось удалить товар.';
            break;
    }
}
?>

<main>
    <section class="cart">
        <h1>Ваша корзина</h1>
        <?php if ($success_message): ?>
            <p style="color: green;"><?php echo htmlspecialchars($success_message); ?></p>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($cartItems)): ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                                <div class="product-image">
            <?php
            // Determine image path
            $imagePath = $item['image_path'];
            if (strpos($imagePath, '/uploads/products/') === 0) {
                $imageSrc = htmlspecialchars($imagePath);
            } else {
                $imageSrc = '/assets/images/' . htmlspecialchars($imagePath);
            }
            ?>
            <img src="<?php echo $imageSrc; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
        </div>
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p>Цена: <?php echo formatPrice($item['price']); ?></p>
                            <form method="POST" action="/api/update_cart.php">
                                <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                <label for="quantity_<?php echo $item['cart_item_id']; ?>">Количество:</label>
                                <input type="number" name="quantity" id="quantity_<?php echo $item['cart_item_id']; ?>" value="<?php echo $item['quantity']; ?>" min="1">
                                <button type="submit" class="btn">Обновить</button>
                            </form>
                            <form method="POST" action="/api/remove_from_cart.php">
                                <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                <button type="submit" class="btn remove">Удалить</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-summary">
                <h2>Итого</h2>
                <p>Общая сумма: <span id="total_amount"><?php echo formatPrice($totalAmount); ?></span></p>
                <a href="checkout.php" class="btn">Оформить заказ</a>
            </div>
        <?php else: ?>
            <p>Ваша корзина пуста. <a href="products.php">Перейти в каталог</a>.</p>
        <?php endif; ?>
    </section>
</main>

<script>
// Применение промокода
document.addEventListener('DOMContentLoaded', function() {
    const applyPromoBtn = document.getElementById('apply_promo');
    if (applyPromoBtn) {
        applyPromoBtn.addEventListener('click', function() {
            const promoCode = document.getElementById('promo_code').value;
            const totalAmount = <?php echo $totalAmount; ?>;
            
            fetch(`/api/check_promo.php?code=${promoCode}&total=${totalAmount}`)
                .then(response => response.json())
                .then(data => {
                    const promoMessage = document.getElementById('promo_message');
                    if (data.success) {
                        promoMessage.style.color = 'green';
                        promoMessage.textContent = `Промокод применен! Скидка: ${data.discount_amount.toFixed(2)} руб.`;
                        document.getElementById('total_amount').textContent = formatPrice(data.new_total);
                    } else {
                        promoMessage.style.color = 'red';
                        promoMessage.textContent = data.message;
                        document.getElementById('total_amount').textContent = formatPrice(totalAmount);
                    }
                });
        });
    }
});

function formatPrice(price) {
    return price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$& ') + ' руб.';
}
</script>

<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>