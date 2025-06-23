<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Параметры для пагинации
$products_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $products_per_page;

// Динамический поиск
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Запрос для получения продуктов
$sql = "SELECT * FROM products WHERE name LIKE ? LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$search_param = "%" . $search_query . "%";
$stmt->bind_param("sii", $search_param, $products_per_page, $offset);
$stmt->execute();
$products = $stmt->get_result();

// Запрос для подсчета общего числа продуктов (для пагинации)
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM products WHERE name LIKE ?");
$count_stmt->bind_param("s", $search_param);
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];

// Обработка формы добавления/редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $description = $_POST['description'];
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : null;

    // Работа с изображением
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true); // Создаёт папку с правами доступа
        }
        $image_path = $upload_dir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
            die("Ошибка загрузки файла.");
        }
        $image_path = '/uploads/products/' . basename($_FILES['image']['name']);
    }

    if ($product_id) {
        // Обновление продукта
        $update_sql = "UPDATE products SET name = ?, category = ?, price = ?, stock = ?, description = ?"
                      . ($image_path ? ", image_path = ?" : "") . " WHERE id = ?";
        $params = [$name, $category, $price, $stock, $description];
        $types = "ssdssi";
        if ($image_path) {
            $params[] = $image_path;
            $types .= "s";
        }
        $params[] = $product_id;
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param($types, ...$params);
    } else {
        // Добавление нового продукта
        $insert_sql = "INSERT INTO products (name, category, price, stock, description, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssdiss", $name, $category, $price, $stock, $description, $image_path);
    }
    $stmt->execute();
    header("Location: manage_products.php");
    exit();
}

// Удаление продукта
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Получить путь к изображению
    $image_stmt = $conn->prepare("SELECT image_path FROM products WHERE id = ?");
    $image_stmt->bind_param("i", $delete_id);
    $image_stmt->execute();
    $image_path = $image_stmt->get_result()->fetch_assoc()['image_path'];

    // Удалить файл, если он существует
    if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
        unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
    }

    // Удалить продукт
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: manage_products.php");
    exit();
}

// Получение данных для редактирования
$product_data = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $product_data = $edit_stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Управление продуктами</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <script defer src="/assets/js/admin.js"></script>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Управление продуктами</h1>
        <div class="search-bar">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Поиск продуктов" value="<?= htmlspecialchars($search_query) ?>">
                <button type="submit">Поиск</button>
            </form>
        </div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Склад</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($product = $products->fetch_assoc()): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category']) ?></td>
                        <td><?= number_format($product['price'], 2) ?> руб.</td>
                        <td><?= $product['stock'] ?></td>
                        <td>
                            <a href="?edit_id=<?= $product['id'] ?>">Редактировать</a>
                            <a href="?delete_id=<?= $product['id'] ?>" onclick="return confirm('Удалить этот продукт?')">Удалить</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <!-- Пагинация -->
        <div class="pagination">
            <?php for ($i = 1; $i <= ceil($total_products / $products_per_page); $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i === $current_page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <!-- Форма добавления/редактирования -->
        <div class="product-form">
            <h2><?= isset($_GET['edit_id']) ? 'Редактировать продукт' : 'Добавить продукт' ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?= $product_data['id'] ?? '' ?>">
                <div class="form-group">
                    <label for="name">Название:</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($product_data['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Категория:</label>
                    <input type="text" id="category" name="category" value="<?= htmlspecialchars($product_data['category'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="price">Цена:</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?= htmlspecialchars($product_data['price'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="stock">Склад:</label>
                    <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($product_data['stock'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Описание:</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($product_data['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="image">Изображение:</label>
                    <input type="file" id="image" name="image">
                </div>
                <button type="submit"><?= isset($_GET['edit_id']) ? 'Обновить' : 'Добавить' ?></button>
            </form>
        </div>
    </div>
</body>
</html>
