<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Если пользователь уже вошел, перенаправить его
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header("Location: /admin/index.php");
    exit();
} elseif (isset($_SESSION['user_id'])) {
    header("Location: /user/profile.php");
    exit();
}

// Проверка отправки формы
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Проверка пользователя в базе данных
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Проверка пароля
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            // Проверка роли
            if ($user['role'] === 'admin') {
    $_SESSION['is_admin'] = true;
    $_SESSION['user_role'] = 'admin';
    header("Location: /admin/dashboard.php");
} elseif ($user['role'] === 'courier') {
    $_SESSION['user_role'] = 'courier';
    header("Location: /courier/index.php");
} else {
    $_SESSION['user_role'] = 'user';
    header("Location: /user/profile.php");
}
            exit();
        } else {
            $error_message = 'Неправильный пароль.';
        }
    } else {
        $error_message = 'Пользователь с таким email не найден.';
    }
    $stmt->close();
}
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
</head>
<body>
    <div class="login-container">
        <h1>Вход</h1>
        <?php if ($error_message): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="text" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Войти</button>
        </form>
        <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a>.</p>
    </div>
</body>
</html>