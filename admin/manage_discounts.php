<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Обработка формы добавления/редактирования скидки
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $min_order_amount = (float)$_POST['min_order_amount'];
    $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id) {
        // Обновление существующей скидки
        $stmt = $conn->prepare("UPDATE discounts SET 
            code = ?, discount_type = ?, discount_value = ?, 
            start_date = ?, end_date = ?, min_order_amount = ?, 
            max_uses = ?, is_active = ? 
            WHERE id = ?");
        $stmt->bind_param("ssdssdiii", 
            $code, $discount_type, $discount_value,
            $start_date, $end_date, $min_order_amount,
            $max_uses, $is_active, $id);
    } else {
        // Добавление новой скидки
        $stmt = $conn->prepare("INSERT INTO discounts (
            code, discount_type, discount_value, 
            start_date, end_date, min_order_amount, 
            max_uses, is_active) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdssdii", 
            $code, $discount_type, $discount_value,
            $start_date, $end_date, $min_order_amount,
            $max_uses, $is_active);
    }
    $stmt->execute();
    header("Location: manage_discounts.php");
    exit();
}

// Удаление скидки
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM discounts WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_discounts.php");
    exit();
}

// Получение списка скидок
$query = "SELECT * FROM discounts ORDER BY is_active DESC, end_date DESC";
$result = $conn->query($query);
$discounts = $result->fetch_all(MYSQLI_ASSOC);

// Получение данных для редактирования
$edit_discount = null;
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_discount = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Управление скидками</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Управление скидками</h1>
        
        <div class="admin-form">
            <h2><?= $edit_discount ? 'Редактировать скидку' : 'Добавить новую скидку' ?></h2>
            <form method="POST">
                <input type="hidden" name="id" value="<?= $edit_discount['id'] ?? '' ?>">
                
                <div class="form-group">
                    <label for="code">Код промокода:</label>
                    <input type="text" id="code" name="code" 
                           value="<?= htmlspecialchars($edit_discount['code'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="discount_type">Тип скидки:</label>
                    <select id="discount_type" name="discount_type" required>
                        <option value="percentage" <?= ($edit_discount['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Процентная</option>
                        <option value="fixed" <?= ($edit_discount['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Фиксированная</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="discount_value">Значение скидки:</label>
                    <input type="number" id="discount_value" name="discount_value" 
                           value="<?= $edit_discount['discount_value'] ?? '' ?>" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="start_date">Дата начала:</label>
                    <input type="datetime-local" id="start_date" name="start_date" 
                           value="<?= isset($edit_discount['start_date']) ? str_replace(' ', 'T', $edit_discount['start_date']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="end_date">Дата окончания:</label>
                    <input type="datetime-local" id="end_date" name="end_date" 
                           value="<?= isset($edit_discount['end_date']) ? str_replace(' ', 'T', $edit_discount['end_date']) : '' ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="min_order_amount">Минимальная сумма заказа:</label>
                    <input type="number" id="min_order_amount" name="min_order_amount" 
                           value="<?= $edit_discount['min_order_amount'] ?? '0' ?>" step="0.01" min="0">
                </div>
                
                <div class="form-group">
                    <label for="max_uses">Макс. использований (оставьте пустым для безлимита):</label>
                    <input type="number" id="max_uses" name="max_uses" 
                           value="<?= $edit_discount['max_uses'] ?? '' ?>" min="1">
                </div>
                
                <div class="form-group checkbox">
                    <input type="checkbox" id="is_active" name="is_active" 
                           <?= isset($edit_discount['is_active']) && $edit_discount['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Активна</label>
                </div>
                
                <button type="submit" class="btn"><?= $edit_discount ? 'Обновить' : 'Добавить' ?></button>
                
                <?php if ($edit_discount): ?>
                    <a href="manage_discounts.php" class="btn btn-cancel">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="admin-table">
            <h2>Список скидок</h2>
            <table>
                <thead>
                    <tr>
                        <th>Код</th>
                        <th>Тип</th>
                        <th>Значение</th>
                        <th>Действует с</th>
                        <th>Действует до</th>
                        <th>Использований</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($discounts as $discount): ?>
                        <tr class="<?= $discount['is_active'] ? 'active' : 'inactive' ?>">
                            <td><?= htmlspecialchars($discount['code']) ?></td>
                            <td><?= $discount['discount_type'] === 'percentage' ? 'Процент' : 'Фиксированная' ?></td>
                            <td><?= $discount['discount_type'] === 'percentage' ? 
                                $discount['discount_value'] . '%' : 
                                $discount['discount_value'] . ' руб.' ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($discount['start_date'])) ?></td>
                            <td><?= date('d.m.Y H:i', strtotime($discount['end_date'])) ?></td>
                            <td><?= $discount['current_uses'] ?>/<?= $discount['max_uses'] ?? '∞' ?></td>
                            <td><?= $discount['is_active'] ? 
                                (strtotime($discount['end_date']) > time() ? 'Активна' : 'Истекла') : 
                                'Неактивна' ?></td>
                            <td>
                                <a href="?edit_id=<?= $discount['id'] ?>">Редактировать</a>
                                <a href="?delete_id=<?= $discount['id'] ?>" onclick="return confirm('Удалить эту скидку?')">Удалить</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>