<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');
$settings = getSettings();
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Обработка заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $delivery = $_POST['delivery_method'] ?? 'standard';
    $payment = $_POST['payment_method'] ?? 'card';
    $discount_code = $_POST['discount_code'] ?? null;
    $discount_amount = $_POST['discount_amount'] ?? 0;
    $is_paid = ($payment === 'card') ? 1 : 0;

    // Валидация
    if (empty($name) || empty($email) || empty($address)) {
        $error_message = 'Все поля обязательны для заполнения.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Неверный формат email.';
    } else {
        // Получение товаров из корзины
        $queryCart = "SELECT ci.product_id, ci.quantity, p.price, p.stock
                      FROM cart_items ci
                      JOIN products p ON ci.product_id = p.id
                      WHERE ci.user_id = ?";
        $stmtCart = $conn->prepare($queryCart);
        $stmtCart->bind_param("i", $userId);
        $stmtCart->execute();
        $resultCart = $stmtCart->get_result();
        $cartItems = $resultCart->fetch_all(MYSQLI_ASSOC);
        $stmtCart->close();

        if (empty($cartItems)) {
            header("Location: cart.php?error=empty_cart");
            exit;
        }

        // Проверка наличия товаров на складе
        foreach ($cartItems as $item) {
            if ($item['stock'] < $item['quantity']) {
                header("Location: cart.php?error=insufficient_stock");
                exit;
            }
        }

        // Расчет суммы
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        // Применение скидки
        $total = $total - $discount_amount;

        // Сохранение заказа
        $orderDate = date('Y-m-d H:i:s');
        $stmtOrder = $conn->prepare("INSERT INTO orders (customer_id, order_date, total_amount, delivery_method, payment_method, address, is_paid, discount_code, discount_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtOrder->bind_param("isdsssiss", $userId, $orderDate, $total, $delivery, $payment, $address, $is_paid, $discount_code, $discount_amount);
        if ($stmtOrder->execute()) {
            $orderId = $stmtOrder->insert_id;

            // Сохранение позиций заказа
            $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($cartItems as $item) {
                $stmtItem->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
                $stmtItem->execute();
                // Обновление остатков
                $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $updateStock->bind_param("ii", $item['quantity'], $item['product_id']);
                $updateStock->execute();
            }
            $stmtItem->close();

            // Очистка корзины
            $deleteQuery = "DELETE FROM cart_items WHERE user_id = ?";
            $stmtDelete = $conn->prepare($deleteQuery);
            $stmtDelete->bind_param("i", $userId);
            $stmtDelete->execute();
            $stmtDelete->close();
            
            // Создание уведомления
            createNotification(
                $userId,
                "Заказ #$orderId оформлен",
                "Ваш заказ на сумму " . formatPrice($total) . " успешно оформлен. Номер заказа: $orderId"
            );

            header("Location: /user/orders.php?order_id=$orderId&success=order_placed");
            exit;
        } else {
            $error_message = "Ошибка при оформлении заказа. Повторите попытку.";
        }
        $stmtOrder->close();
    }
}

// Загрузка данных пользователя
$queryUser = "SELECT name, email, address FROM customers WHERE id = ?";
$stmtUser = $conn->prepare($queryUser);
$stmtUser->bind_param("i", $userId);
$stmtUser->execute();
$resultUser = $stmtUser->get_result();
$userData = $resultUser->fetch_assoc();
$stmtUser->close();

// Загрузка корзины
$queryCart = "SELECT ci.id AS cart_item_id, p.id AS product_id, p.name, p.price, p.image_path, ci.quantity 
              FROM cart_items ci 
              JOIN products p ON ci.product_id = p.id 
              WHERE ci.user_id = ?";
$stmtCart = $conn->prepare($queryCart);
$stmtCart->bind_param("i", $userId);
$stmtCart->execute();
$resultCart = $stmtCart->get_result();
$cartItems = $resultCart->fetch_all(MYSQLI_ASSOC);
$stmtCart->close();

$totalAmount = array_reduce($cartItems, function ($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

if (empty($cartItems)) {
    header("Location: cart.php?error=empty_cart");
    exit;
}
?>

<main>
    <section class="checkout">
        <h1>Оформление заказа</h1>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>
        <form method="POST" id="checkout-form" onsubmit="return validateCard()">
<div class="user-info">
    <h2>Ваши данные</h2>
    <label for="name">Имя:</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($userData['name'] ?? '') ?>" required>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
    
    <label for="address">Адрес доставки:</label>
    <select id="address" name="address" required>
        <?php 
        $address_query = "SELECT address FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
        $address_stmt = $conn->prepare($address_query);
        $address_stmt->bind_param("i", $userId);
        $address_stmt->execute();
        $address_result = $address_stmt->get_result();
        
        while ($address = $address_result->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($address['address']) ?>"><?= htmlspecialchars($address['address']) ?></option>
        <?php endwhile; ?>
        <option value="new">Добавить новый адрес...</option>
    </select>
    
    <div id="new-address-container" style="display: none;">
        <label for="new_address">Новый адрес:</label>
        <input type="text" id="new_address" name="new_address">
        <label>
            <input type="checkbox" name="save_address"> Сохранить этот адрес
        </label>
    </div>
</div>

            <div class="delivery">
                <h2>Способ доставки</h2>
                <label><input type="radio" name="delivery_method" value="standard" checked> Стандартная доставка</label>
                <label><input type="radio" name="delivery_method" value="express"> Экспресс-доставка</label>
            </div>

            <div class="payment">
                <h2>Способ оплаты</h2>
                <label><input type="radio" name="payment_method" value="card" onclick="toggleCardFields(true)" checked> Банковская карта</label>
                <label><input type="radio" name="payment_method" value="cash" onclick="toggleCardFields(false)"> Наличные при получении</label>
                <div id="card-details" style="display: block;">
                    <label for="card_number">Номер карты:</label>
                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                    <label for="card_expiry">Срок действия (MM/YY):</label>
                    <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY">
                    <label for="card_cvv">CVV:</label>
                    <input type="text" id="card_cvv" name="card_cvv" placeholder="123">
                </div>
            </div>
            
            <div class="promo-section">
                <h3>Промокод</h3>
                <div class="promo-input">
                    <input type="text" id="promo_code" placeholder="Введите промокод">
                    <button type="button" id="apply_promo" class="btn">Применить</button>
                </div>
                <div id="promo_message" style="margin-top: 10px;"></div>
                <input type="hidden" name="discount_code" id="promo_code_field">
                <input type="hidden" name="discount_amount" id="discount_amount_field">
            </div>

            <div class="order-summary">
                <h2>Состав заказа</h2>
                <ul>
                    <?php foreach ($cartItems as $item): ?>
                        <li><?= htmlspecialchars($item['name']) ?> - <?= $item['quantity'] ?> шт. (<?= formatPrice($item['price'] * $item['quantity']) ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Общая сумма: <span id="total_amount"><?= formatPrice($totalAmount) ?></span></strong></p>
            </div>

            <button type="submit" class="btn">Подтвердить заказ</button>
        </form>
    </section>
</main>

<script>
function toggleCardFields(show) {
    document.getElementById('card-details').style.display = show ? 'block' : 'none';
}

function validateCard() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    if (paymentMethod !== 'card') return true;

    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
    const cardExpiry = document.getElementById('card_expiry').value;
    const cardCvv = document.getElementById('card_cvv').value;

    // Валидация номера карты (16 цифр)
    if (!/^\d{16}$/.test(cardNumber)) {
        alert('Номер карты должен состоять из 16 цифр.');
        return false;
    }

    // Валидация срока действия (MM/YY)
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(cardExpiry)) {
        alert('Срок действия должен быть в формате MM/YY.');
        return false;
    }

    // Валидация CVV (3 цифры)
    if (!/^\d{3}$/.test(cardCvv)) {
        alert('CVV должен состоять из 3 цифр.');
        return false;
    }

    return true;
}
document.getElementById('address').addEventListener('change', function() {
    const newAddressContainer = document.getElementById('new-address-container');
    if (this.value === 'new') {
        newAddressContainer.style.display = 'block';
        document.getElementById('new_address').setAttribute('required', '');
    } else {
        newAddressContainer.style.display = 'none';
        document.getElementById('new_address').removeAttribute('required');
    }
});

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
                        document.getElementById('promo_code_field').value = data.discount_code;
                        document.getElementById('discount_amount_field').value = data.discount_amount;
                    } else {
                        promoMessage.style.color = 'red';
                        promoMessage.textContent = data.message;
                        document.getElementById('promo_code_field').value = '';
                        document.getElementById('discount_amount_field').value = 0;
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