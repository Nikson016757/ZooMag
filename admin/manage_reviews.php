<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Параметры для пагинации
$reviews_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $reviews_per_page;

// Динамический поиск
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Запрос для получения отзывов
$sql = "SELECT r.id, r.rating, r.comment, r.created_at, p.name AS product_name, c.name AS customer_name 
        FROM reviews r 
        JOIN products p ON r.product_id = p.id 
        JOIN customers c ON r.customer_id = c.id 
        WHERE p.name LIKE ? OR c.name LIKE ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$search_param = "%" . $search_query . "%";
$stmt->bind_param("ssii", $search_param, $search_param, $reviews_per_page, $offset);
$stmt->execute();
$reviews = $stmt->get_result();

// Подсчет общего числа отзывов
$count_sql = "SELECT COUNT(*) AS total FROM reviews r 
              JOIN products p ON r.product_id = p.id 
              JOIN customers c ON r.customer_id = c.id 
              WHERE p.name LIKE ? OR c.name LIKE ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("ss", $search_param, $search_param);
$count_stmt->execute();
$total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];

// Удаление отзыва
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_reviews.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Управление отзывами</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <script defer src="/assets/js/admin.js"></script>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Управление отзывами</h1>
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Поиск по товару или пользователю" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Поиск</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Товар</th>
                    <th>Пользователь</th>
                    <th>Рейтинг</th>
                    <th>Комментарий</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <tr>
                        <td><?= $review['id'] ?></td>
                        <td><?= htmlspecialchars($review['product_name']) ?></td>
                        <td><?= htmlspecialchars($review['customer_name']) ?></td>
                        <td><?= $review['rating'] ?></td>
                        <td><?= htmlspecialchars($review['comment'] ?? '') ?></td>
                        <td><?= htmlspecialchars($review['created_at']) ?></td>
                        <td>
                            <a href="?delete_id=<?= $review['id'] ?>" onclick="return confirm('Удалить этот отзыв?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="pagination">
            <?php for ($i = 1; $i <= ceil($total_reviews / $reviews_per_page); $i++): ?>
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