<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Параметры для пагинации
$orders_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $orders_per_page;

// Динамический поиск
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Запрос для получения заказов
$sql = "SELECT o.id, o.customer_id, o.order_date, o.total_amount, o.status, o.delivery_status, o.is_paid, c.name AS customer_name, c2.name AS courier_name 
        FROM orders o 
        JOIN customers c ON o.customer_id = c.id 
        LEFT JOIN customers c2 ON o.courier_id = c2.id 
        WHERE (o.id LIKE ? OR c.name LIKE ?)";
if ($status_filter) {
    $sql .= " AND o.status = ?";
}
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$search_param = "%" . $search_query . "%";
if ($status_filter) {
    $stmt->bind_param("sssii", $search_param, $search_param, $status_filter, $orders_per_page, $offset);
} else {
    $stmt->bind_param("ssii", $search_param, $search_param, $orders_per_page, $offset);
}
$stmt->execute();
$orders = $stmt->get_result();

// Подсчет общего числа заказов
$count_sql = "SELECT COUNT(*) AS total 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.id 
              WHERE (o.id LIKE ? OR c.name LIKE ?)";
if ($status_filter) {
    $count_sql .= " AND o.status = ?";
}
$count_stmt = $conn->prepare($count_sql);
if ($status_filter) {
    $count_stmt->bind_param("sss", $search_param, $search_param, $status_filter);
} else {
    $count_stmt->bind_param("ss", $search_param, $search_param);
}
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];

// Получение списка курьеров
$couriers_sql = "SELECT id, name FROM customers WHERE role = 'courier'";
$couriers_result = $conn->query($couriers_sql);
$couriers = $couriers_result->fetch_all(MYSQLI_ASSOC);

// Обновление статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];
    $update_sql = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_status, $order_id);
    $update_stmt->execute();
    header("Location: manage_orders.php");
    exit();
}

// Назначение/переназначение курьера
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_courier'])) {
    $order_id = intval($_POST['order_id']);
    $courier_id = $_POST['courier_id'] ? intval($_POST['courier_id']) : null;
    $update_sql = "UPDATE orders SET courier_id = ?, delivery_status = 'pending' WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $courier_id, $order_id);
    $update_stmt->execute();
    header("Location: manage_orders.php");
    exit();
}

// Удаление заказа
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_orders.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Управление заказами</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <script defer src="/assets/js/admin.js"></script>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Управление заказами</h1>
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Поиск по ID заказа или имени клиента" value="<?= htmlspecialchars($search_query) ?>">
                <select name="status">
                    <option value="">Все статусы</option>
                    <option value="Новый" <?= $status_filter === 'Новый' ? 'selected' : '' ?>>Новый</option>
                    <option value="В обработке" <?= $status_filter === 'В обработке' ? 'selected' : '' ?>>В обработке</option>
                    <option value="Завершен" <?= $status_filter === 'Завершен' ? 'selected' : '' ?>>Завершен</option>
                    <option value="Отменен" <?= $status_filter === 'Отменен' ? 'selected' : '' ?>>Отменен</option>
                </select>
                <button type="submit">Поиск</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя клиента</th>
                    <th>Дата заказа</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Курьер</th>
                    <th>Статус доставки</th>
                    <th>Оплата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td><?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                        <td><?= htmlspecialchars($order['order_date']) ?></td>
                        <td><?= number_format($order['total_amount'], 2) ?> руб.</td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status">
                                    <option value="Новый" <?= $order['status'] === 'Новый' ? 'selected' : '' ?>>Новый</option>
                                    <option value="В обработке" <?= $order['status'] === 'В обработке' ? 'selected' : '' ?>>В обработке</option>
                                    <option value="Завершен" <?= $order['status'] === 'Завершен' ? 'selected' : '' ?>>Завершен</option>
                                    <option value="Отменен" <?= $order['status'] === 'Отменен' ? 'selected' : '' ?>>Отменен</option>
                                </select>
                                <button type="submit" name="update_status">Обновить</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="courier_id">
                                    <option value="">Не назначен</option>
                                    <?php foreach ($couriers as $courier): ?>
                                        <option value="<?= $courier['id'] ?>" <?= $order['courier_id'] == $courier['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($courier['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="assign_courier">Назначить</button>
                            </form>
                        </td>
                        <td><?= htmlspecialchars($order['delivery_status']) ?></td>
                        <td><?= $order['is_paid'] ? 'Оплачено' : 'Не оплачено' ?></td>
                        <td>
                            <a href="?delete_id=<?= $order['id'] ?>" onclick="return confirm('Удалить этот заказ?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= ceil($total_orders / $orders_per_page); $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$count_stmt->close();
$conn->close();
?>