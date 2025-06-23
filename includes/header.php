<?php
ob_start();
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Зоомагазин - товары для ваших питомцев: корма, игрушки, аксессуары и многое другое.">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php include('meta.php'); ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <title><?php echo htmlspecialchars($settings['site_name'] ?? 'Зоомагазин'); ?></title>
</head>
<body>
<header>
    <?php include 'navbar.php'; ?>
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="notification-bell">
        <i class="fas fa-bell"></i>
        <span class="notification-count" id="notification-count">0</span>
        <div class="notification-dropdown" id="notification-dropdown">
            <div class="notification-header">
                <h4>Уведомления</h4>
                <a href="/user/notifications.php">Все уведомления</a>
            </div>
            <div class="notification-list" id="notification-list">
                <!-- Уведомления будут загружены через AJAX -->
            </div>
        </div>
    </div>
    <?php endif; ?>
</header>
<main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Загрузка уведомлений
    function loadNotifications() {
        fetch('/api/get_notifications.php?unread_only=true')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notification-list');
                const notificationCount = document.getElementById('notification-count');
                
                notificationCount.textContent = data.filter(n => !n.is_read).length;
                
                if (data.length === 0) {
                    notificationList.innerHTML = '<div class="notification-item">Нет новых уведомлений</div>';
                } else {
                    notificationList.innerHTML = data.map(notification => `
                        <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" 
                             data-id="${notification.id}">
                            <div class="notification-title">${notification.title}</div>
                            <div class="notification-message">${notification.message}</div>
                            <div class="notification-time">${new Date(notification.created_at).toLocaleString()}</div>
                        </div>
                    `).join('');
                }
            });
    }
    
    // Обновляем уведомления каждые 30 секунд
    loadNotifications();
    setInterval(loadNotifications, 30000);
    
    // Пометить как прочитанное при клике
    if (document.getElementById('notification-list')) {
        document.getElementById('notification-list').addEventListener('click', function(e) {
            const item = e.target.closest('.notification-item');
            if (item && !item.classList.contains('read')) {
                const notificationId = item.getAttribute('data-id');
                fetch('/api/mark_notification_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `notification_id=${notificationId}`
                }).then(() => {
                    item.classList.add('read');
                    loadNotifications();
                });
            }
        });
    }
    
    // Показать/скрыть dropdown
    if (document.querySelector('.notification-bell')) {
        document.querySelector('.notification-bell').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('notification-dropdown').classList.toggle('show');
        });
        
        // Закрыть dropdown при клике вне его
        document.addEventListener('click', function() {
            document.getElementById('notification-dropdown').classList.remove('show');
        });
    }
});
</script>