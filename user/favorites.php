<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
$user_id = intval($_SESSION['user_id']);

$query = "SELECT p.id, p.name, p.price, p.image_path 
          FROM products p 
          JOIN favorites f ON p.id = f.product_id 
          WHERE f.user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Ошибка подготовки запроса: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$favorites = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
        <?php require_once '../includes/meta.php'; ?>
    <link rel="stylesheet" href="/assets/css/style.css">
    <title>Избранное</title>
</head>
<body>
    <header>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/navbar.php'); ?>
    </header>
    <main>
        <div class="profile-section">
            <h1>Мои избранные товары</h1>
            <?php if ($favorites->num_rows === 0): ?>
                <p>У вас пока нет избранных товаров.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php while ($item = $favorites->fetch_assoc()): ?>
                        <div class="product-card">
                            <img src="/assets/images/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo number_format($item['price'], 2, ',', ' ') . ' руб.'; ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
            <p><a href="profile.php">← Назад в профиль</a></p>
        </div>
    </main>
    <footer>
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
    </footer>
</body>
</html>
<?php $stmt->close(); ?>