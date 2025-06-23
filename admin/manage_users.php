<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Параметры для пагинации
$users_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $users_per_page;

// Динамический поиск
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Запрос для получения пользователей
$sql = "SELECT id, name, email, role FROM customers WHERE name LIKE ? OR email LIKE ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$search_param = "%" . $search_query . "%";
$stmt->bind_param("ssii", $search_param, $search_param, $users_per_page, $offset);
$stmt->execute();
$users = $stmt->get_result();

// Подсчет общего числа пользователей
$count_sql = "SELECT COUNT(*) AS total FROM customers WHERE name LIKE ? OR email LIKE ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];

// Обновление роли пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = $_POST['role'];
    $update_sql = "UPDATE customers SET role = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $new_role, $user_id);
    $update_stmt->execute();
    header("Location: manage_users.php");
    exit();
}

// Удаление пользователя
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Управление пользователями</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <script defer src="/assets/js/admin.js"></script>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Управление пользователями</h1>
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Поиск по имени или email" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Поиск</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <select name="role">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
                                    <option value="courier" <?= $user['role'] === 'courier' ? 'selected' : '' ?>>Курьер</option>
                                </select>
                                <button type="submit" name="update_role">Обновить</button>
                            </form>
                        </td>
                        <td>
                            <a href="?delete_id=<?= $user['id'] ?>" onclick="return confirm('Удалить этого пользователя?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= ceil($total_users / $users_per_page); $i++): ?>
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