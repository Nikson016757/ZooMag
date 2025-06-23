<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/config/database.php';

// Пометить все уведомления как прочитанные
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $user_id = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Получение уведомлений пользователя
$user_id = (int)$_SESSION['user_id'];
$query = "SELECT id, title, message, is_read, created_at 
          FROM notifications 
          WHERE user_id = ? 
          ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <?php require_once '../includes/meta.php'; ?>
    <title>Мои уведомления</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
    
    <main class="notifications-page">
        <div class="container">
            <h1>Мои уведомления</h1>
            
            <div class="notifications-actions">
                <form method="POST">
                    <button type="submit" name="mark_all_read" class="btn">Пометить все как прочитанные</button>
                </form>
            </div>
            
            <?php if (empty($notifications)): ?>
                <p>У вас пока нет уведомлений.</p>
            <?php else: ?>
                <div class="notifications-list">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification <?= $notification['is_read'] ? 'read' : 'unread' ?>">
                            <div class="notification-header">
                                <h3><?= htmlspecialchars($notification['title']) ?></h3>
                                <span class="notification-date"><?= date('d.m.Y H:i', strtotime($notification['created_at'])) ?></span>
                            </div>
                            <div class="notification-content">
                                <?= nl2br(htmlspecialchars($notification['message'])) ?>
                            </div>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" action="/api/mark_notification_read.php" class="mark-as-read">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" class="btn btn-small">Пометить как прочитанное</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
</body>
</html>