<?php
// user/profile_sidebar.php

// Получаем количество непрочитанных уведомлений
$unread_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = (int)$_SESSION['user_id'];
    $count_query = "SELECT COUNT(*) as count FROM notifications 
                   WHERE user_id = ? AND is_read = FALSE";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $unread_count = $count_stmt->get_result()->fetch_assoc()['count'];
    $count_stmt->close();
}
?>

<div class="profile-sidebar">
    <div class="avatar-container">
        <div class="profile-avatar">
            <i class="fas fa-user"></i>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            $user_query = "SELECT name FROM customers WHERE id = ?";
            $user_stmt = $conn->prepare($user_query);
            $user_stmt->bind_param("i", $_SESSION['user_id']);
            $user_stmt->execute();
            $user = $user_stmt->get_result()->fetch_assoc();
            $user_stmt->close();
            ?>
            <h3><?= htmlspecialchars($user['name']) ?></h3>
        <?php endif; ?>
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