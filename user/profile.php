<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

$user_id = intval($_SESSION['user_id']);
$query = "SELECT name, email, phone, address FROM customers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get unread notifications count
$unread_count = 0;
$count_query = "SELECT COUNT(*) as count FROM notifications 
               WHERE user_id = ? AND is_read = FALSE";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("i", $user_id);
$count_stmt->execute();
$unread_count = $count_stmt->get_result()->fetch_assoc()['count'];
$count_stmt->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль | Зоомагазин</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <?php require_once '../includes/meta.php'; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'; ?>
    
    <div class="profile-container">
        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="avatar-container">
                    <div class="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3><?= htmlspecialchars($user['name']) ?></h3>
                </div>
                
                <ul class="action-list">
                    <li>
                        <a href="/user/profile.php">
                            <i class="fas fa-user"></i>
                            <span>Мой профиль</span>
                        </a>
                    </li>
                    <li>
                        <a href="/user/edit_profile.php">
                            <i class="fas fa-user-edit"></i>
                            <span>Редактировать профиль</span>
                        </a>
                    </li>
                    <li>
                        <a href="/user/orders.php">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Мои заказы</span>
                        </a>
                    </li>
                    <li>
                        <a href="/user/favorites.php">
                            <i class="fas fa-heart"></i>
                            <span>Избранное</span>
                        </a>
                    </li>
                    <li>
                        <a href="/user/notifications.php">
                            <i class="fas fa-bell"></i>
                            <span>Уведомления</span>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a href="/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Выйти</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="profile-content">
                <h1>Личная информация</h1>
                
                <div class="detail-section">
                    <div class="detail-row">
                        <span class="detail-label">Имя:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Телефон:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['phone']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Адрес:</span>
                        <span class="detail-value"><?= htmlspecialchars($user['address']) ?></span>
                    </div>
                </div>
                
                <div class="recent-orders">
                    <h2>Последние заказы</h2>
                    <?php
                    $orders_query = "SELECT id, order_date, total_amount, status 
                                    FROM orders 
                                    WHERE customer_id = ? 
                                    ORDER BY order_date DESC 
                                    LIMIT 3";
                    $stmt = $conn->prepare($orders_query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $orders = $stmt->get_result();
                    
                    if ($orders->num_rows > 0): ?>
                        <div class="orders-mini-list">
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <div class="mini-order-card">
                                    <div class="mini-order-header">
                                        <span>Заказ #<?= $order['id'] ?></span>
                                        <span class="status-badge"><?= $order['status'] ?></span>
                                    </div>
                                    <div class="mini-order-details">
                                        <span><?= date('d.m.Y', strtotime($order['order_date'])) ?></span>
                                        <span><?= number_format($order['total_amount'], 2) ?> руб.</span>
                                    </div>
                                    <a href="/user/order_details.php?id=<?= $order['id'] ?>" class="btn btn-small">Подробнее</a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <a href="/user/orders.php" class="btn">Все заказы</a>
                    <?php else: ?>
                        <p>У вас пока нет заказов</p>
                        <a href="/products.php" class="btn">Перейти в каталог</a>
                    <?php endif; 
                    $stmt->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
</body>
</html>