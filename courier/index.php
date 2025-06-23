<?php
session_start();
require_once '../config/database.php';

// Проверка роли курьера
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'courier') {
    $stmt = $conn->prepare("SELECT role FROM customers WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    if ($user['role'] !== 'courier') {
        header("Location: /login.php");
        exit();
    }
    $_SESSION['user_role'] = $user['role'];
    $stmt->close();
}

$courier_id = intval($_SESSION['user_id']);

// Получаем данные курьера
$courier_query = "SELECT name, email, phone FROM customers WHERE id = ?";
$courier_stmt = $conn->prepare($courier_query);
$courier_stmt->bind_param("i", $courier_id);
$courier_stmt->execute();
$courier_data = $courier_stmt->get_result()->fetch_assoc();

// Получаем статистику курьера
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
    SUM(total_amount) as total_earnings
    FROM orders 
    WHERE courier_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $courier_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Получаем заказы курьера
$orders_query = "SELECT o.id, o.order_date, o.total_amount, o.status, o.delivery_status, o.is_paid, 
                 c.name AS customer_name, o.address, 
                 GROUP_CONCAT(p.name SEPARATOR ', ') AS products
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id 
          LEFT JOIN order_items oi ON o.id = oi.order_id
          LEFT JOIN products p ON oi.product_id = p.id
          WHERE o.courier_id = ? 
          GROUP BY o.id
          ORDER BY FIELD(o.delivery_status, 'pending', 'accepted', 'in_progress', 'delivered', 'rejected'), o.order_date DESC";
$orders_stmt = $conn->prepare($orders_query);
$orders_stmt->bind_param("i", $courier_id);
$orders_stmt->execute();
$orders = $orders_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Панель курьера</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="courier-container">
        <div class="courier-header">
            <h1>Панель курьера</h1>
            <div class="courier-actions">
                <a href="/logout.php" class="btn btn-danger">Выйти</a>
            </div>
        </div>
        
        <div class="courier-profile">
            <h2>Мой профиль</h2>
            <div class="profile-info">
                <div class="info-item">
                    <span class="info-label">Имя:</span>
                    <span class="info-value"><?= htmlspecialchars($courier_data['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($courier_data['email']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Телефон:</span>
                    <span class="info-value"><?= htmlspecialchars($courier_data['phone'] ?? 'Не указан') ?></span>
                </div>
            </div>
            
            <div class="courier-stats">
                <div class="stat-card">
                    <h3>Всего заказов</h3>
                    <p><?= $stats['total_orders'] ?></p>
                </div>
                <div class="stat-card">
                    <h3>Доставлено</h3>
                    <p><?= $stats['delivered_orders'] ?></p>
                </div>
                <div class="stat-card">
                    <h3>Общий доход</h3>
                    <p><?= number_format($stats['total_earnings'], 2) ?> руб.</p>
                </div>
            </div>
        </div>
        
        <h2>Назначенные заказы</h2>
        <?php if ($orders->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>Нет назначенных заказов</h3>
                <p>Когда вам будут назначены новые заказы, они появятся здесь</p>
            </div>
        <?php else: ?>
            <div class="courier-orders-grid">
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <span class="order-id">Заказ #<?= $order['id'] ?></span>
                            <span class="order-status status-<?= str_replace(' ', '_', strtolower($order['delivery_status'])) ?>">
                                <?= $order['delivery_status'] === 'in_progress' ? 'В процессе' : 
                                    ($order['delivery_status'] === 'pending' ? 'Ожидает' : 
                                    ($order['delivery_status'] === 'accepted' ? 'Принят' : 
                                    ($order['delivery_status'] === 'delivered' ? 'Доставлен' : 'Отклонен'))) ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="detail-label">Клиент:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['customer_name']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Адрес:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['address']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Товары:</span>
                                <span class="detail-value"><?= htmlspecialchars($order['products']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Сумма:</span>
                                <span class="detail-value"><?= number_format($order['total_amount'], 2) ?> руб.</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Дата:</span>
                                <span class="detail-value"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Оплата:</span>
                                <span class="detail-value">
                                    <?= $order['is_paid'] ? 'Оплачено' : 'Не оплачено' ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <form method="POST" action="/api/update_delivery_status.php">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="delivery_status" class="select-small">
                                    <option value="pending" <?= $order['delivery_status'] === 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                    <option value="accepted" <?= $order['delivery_status'] === 'accepted' ? 'selected' : '' ?>>Принят</option>
                                    <option value="in_progress" <?= $order['delivery_status'] === 'in_progress' ? 'selected' : '' ?>>В процессе</option>
                                    <option value="delivered" <?= $order['delivery_status'] === 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                                    <option value="rejected" <?= $order['delivery_status'] === 'rejected' ? 'selected' : '' ?>>Отклонен</option>
                                </select>
                                <button type="submit" class="btn btn-small">Обновить</button>
                            </form>
                            
                            <?php if ($order['delivery_status'] === 'pending'): ?>
                                <form method="POST" action="/api/accept_order.php">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-primary">Принять</button>
                                </form>
                                <form method="POST" action="/api/reject_order.php">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" class="btn btn-small btn-danger">Отклонить</button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($order['delivery_status'] === 'delivered' && !$order['is_paid']): ?>
                                <form method="POST" action="/api/update_payment_status.php">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="is_paid" value="1">
                                    <button type="submit" class="btn btn-small btn-success">Подтвердить оплату</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
$orders_stmt->close();
$courier_stmt->close();
$stats_stmt->close();
$conn->close();
?>