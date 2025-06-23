<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
$user_id = intval($_SESSION['user_id']);
$error = '';
$success = '';

// Получаем адреса пользователя
$addresses_query = "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC";
$address_stmt = $conn->prepare($addresses_query);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$addresses = $address_stmt->get_result();

// Обработка добавления нового адреса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $address = trim($_POST['address']);
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if (empty($address)) {
        $error = 'Адрес не может быть пустым';
    } else {
        // Если адрес помечен как основной, снимаем флаг с других адресов
        if ($is_default) {
            $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        }

        $insert_stmt = $conn->prepare("INSERT INTO user_addresses (user_id, address, is_default) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("isi", $user_id, $address, $is_default);
        if ($insert_stmt->execute()) {
            $success = 'Адрес успешно добавлен';
        } else {
            $error = 'Ошибка при добавлении адреса';
        }
    }
}

// Обработка удаления адреса
if (isset($_GET['delete_address'])) {
    $address_id = intval($_GET['delete_address']);
    $delete_stmt = $conn->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $address_id, $user_id);
    if ($delete_stmt->execute()) {
        $success = 'Адрес успешно удален';
    } else {
        $error = 'Ошибка при удалении адреса';
    }
}

// Обработка установки адреса по умолчанию
if (isset($_GET['set_default'])) {
    $address_id = intval($_GET['set_default']);
    $conn->begin_transaction();
    try {
        $conn->query("UPDATE user_addresses SET is_default = 0 WHERE user_id = $user_id");
        $update_stmt = $conn->prepare("UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $update_stmt->bind_param("ii", $address_id, $user_id);
        $update_stmt->execute();
        $conn->commit();
        $success = 'Основной адрес успешно изменен';
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Ошибка при изменении основного адреса';
    }
}

// Получение текущих данных пользователя
$stmt = $conn->prepare("SELECT name, email, phone FROM customers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php require_once '../includes/meta.php'; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <title>Редактировать профиль</title>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php'); ?>
    <main>
        <div class="profile-container">
            <div class="profile-sidebar">
                <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/user/profile_sidebar.php'); ?>
            </div>
            
            <div class="profile-content">
                <h1>Редактирование профиля</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <div class="profile-section">
                    <h2>Основная информация</h2>
                    <form method="post" action="/user/update_profile.php">
                        <div class="form-group">
                            <label for="name">Имя:</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Телефон:</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        
                        <button type="submit" class="btn">Сохранить изменения</button>
                    </form>
                </div>
                
                <div class="profile-section">
                    <h2>Мои адреса</h2>
                    
                    <div class="address-list">
                        <?php if ($addresses->num_rows > 0): ?>
                            <?php while ($address = $addresses->fetch_assoc()): ?>
                                <div class="address-card <?= $address['is_default'] ? 'default-address' : '' ?>">
                                    <p><?= htmlspecialchars($address['address']) ?></p>
                                    <div class="address-actions">
                                        <?php if (!$address['is_default']): ?>
                                            <a href="?set_default=<?= $address['id'] ?>" class="btn btn-small">Сделать основным</a>
                                        <?php else: ?>
                                            <span class="default-badge">Основной</span>
                                        <?php endif; ?>
                                        <a href="?delete_address=<?= $address['id'] ?>" class="btn btn-small btn-danger" 
                                           onclick="return confirm('Вы уверены, что хотите удалить этот адрес?')">Удалить</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>У вас пока нет сохраненных адресов</p>
                        <?php endif; ?>
                    </div>
                    
                    <h3>Добавить новый адрес</h3>
                    <form method="post">
                        <div class="form-group">
                            <label for="address">Адрес:</label>
                            <textarea id="address" name="address" required></textarea>
                        </div>
                        
                        <div class="form-group checkbox">
                            <input type="checkbox" id="is_default" name="is_default">
                            <label for="is_default">Сделать основным адресом</label>
                        </div>
                        
                        <button type="submit" name="add_address" class="btn">Добавить адрес</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
</body>
</html>