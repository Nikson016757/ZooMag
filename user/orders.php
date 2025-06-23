<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
$user_id = intval($_SESSION['user_id']);

// Получаем заказы пользователя с более подробной информацией
$query = "SELECT o.id, o.order_date, o.total_amount, o.status, o.delivery_status, 
                 COUNT(oi.id) AS items_count, 
                 GROUP_CONCAT(p.name SEPARATOR ', ') AS products
          FROM orders o
          JOIN order_items oi ON o.id = oi.order_id
          JOIN products p ON oi.product_id = p.id
          WHERE o.customer_id = ?
          GROUP BY o.id
          ORDER BY o.order_date DESC";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Ошибка подготовки запроса: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] === 'order_placed') {
    $success_message = 'Заказ успешно оформлен! Номер вашего заказа: ' . htmlspecialchars($_GET['order_id'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Мои заказы</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <?php require_once '../includes/meta.php'; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
    
    <main class="orders-container">
        <h1>Мои заказы</h1>
        
        <?php if ($success_message): ?>
            <div style="background-color: #d1fae5; color: #065f46; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($orders->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>У вас пока нет заказов</h3>
                <p>Начните делать покупки, чтобы увидеть здесь свои заказы</p>
                <a href="/products.php" class="btn btn-primary">Перейти в каталог</a>
            </div>
        <?php else: ?>
            <?php while ($order = $orders->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Заказ #<?= $order['id'] ?></span>
                            <span class="order-date">от <?= date('d.m.Y', strtotime($order['order_date'])) ?></span>
                        </div>
                        <span class="order-status status-<?= strtolower($order['status']) ?>">
                            <?= $order['status'] ?>
                        </span>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <span class="detail-label">Сумма заказа</span>
                            <span class="detail-value"><?= number_format($order['total_amount'], 2, ',', ' ') ?> ₽</span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Количество товаров</span>
                            <span class="detail-value"><?= $order['items_count'] ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">Статус доставки</span>
                            <span class="detail-value">
                                <?php 
                                $delivery_status = $order['delivery_status'];
                                echo $delivery_status === 'pending' ? 'Ожидает обработки' : 
                                     ($delivery_status === 'in_progress' ? 'В пути' : 
                                     ($delivery_status === 'delivered' ? 'Доставлен' : $delivery_status));
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="/user/order_details.php?id=<?= $order['id'] ?>" class="btn btn-primary">
                            Подробнее о заказе
                        </a>
                        <button class="btn btn-outline">
                            Повторить заказ
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </main>
    
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
</body>
</html>
<?php $stmt->close(); ?>