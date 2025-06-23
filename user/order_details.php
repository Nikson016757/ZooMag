<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
$user_id = intval($_SESSION['user_id']);
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: /user/orders.php');
    exit;
}

// Получаем информацию о заказе
$order_query = "SELECT o.*, c.name AS customer_name, c2.name AS courier_name 
                FROM orders o 
                JOIN customers c ON o.customer_id = c.id 
                LEFT JOIN customers c2 ON o.courier_id = c2.id 
                WHERE o.id = ? AND o.customer_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("ii", $order_id, $user_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: /user/orders.php');
    exit;
}

// Получаем товары в заказе
$items_query = "SELECT oi.*, p.name, p.image_path 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Детали заказа #<?= $order_id ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
    
    <main class="order-details">
        <h1>Детали заказа #<?= $order_id ?></h1>
        
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Дата заказа:</span>
                <span class="info-value"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Статус:</span>
                <span class="info-value status-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Сумма заказа:</span>
                <span class="info-value"><?= number_format($order['total_amount'], 2) ?> руб.</span>
            </div>
            <?php if ($order['discount_amount'] > 0): ?>
                <div class="info-row">
                    <span class="info-label">Скидка:</span>
                    <span class="info-value"><?= number_format($order['discount_amount'], 2) ?> руб. (<?= $order['discount_code'] ?>)</span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Способ оплаты:</span>
                <span class="info-value"><?= $order['payment_method'] === 'card' ? 'Карта' : 'Наличные' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Статус оплаты:</span>
                <span class="info-value"><?= $order['is_paid'] ? 'Оплачено' : 'Не оплачено' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Способ доставки:</span>
                <span class="info-value"><?= $order['delivery_method'] === 'express' ? 'Экспресс' : 'Стандартная' ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Статус доставки:</span>
                <span class="info-value"><?= $order['delivery_status'] ?></span>
            </div>
            <?php if ($order['courier_name']): ?>
                <div class="info-row">
                    <span class="info-label">Курьер:</span>
                    <span class="info-value"><?= htmlspecialchars($order['courier_name']) ?></span>
                </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="info-label">Адрес доставки:</span>
                <span class="info-value"><?= htmlspecialchars($order['address']) ?></span>
            </div>
        </div>
        
        <h2>Состав заказа</h2>
        <div class="order-items">
            <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <img src="/assets/images/<?= htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                    <div class="item-details">
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <p>Количество: <?= $item['quantity'] ?></p>
                        <p>Цена: <?= number_format($item['price'], 2) ?> руб.</p>
                        <p>Сумма: <?= number_format($item['price'] * $item['quantity'], 2) ?> руб.</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <a href="/user/orders.php" class="btn">Вернуться к списку заказов</a>
    </main>
    
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
</body>
</html>
<?php
$order_stmt->close();
$items_stmt->close();
$conn->close();
?>