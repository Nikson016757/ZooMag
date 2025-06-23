<?php
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $errors[] = "Пароли не совпадают.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $query = "INSERT INTO customers (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param('sssss', $name, $email, $phone, $address, $hashed_password);
            if ($stmt->execute()) {
                header("Location: login.php");
                exit();
            } else {
                $errors[] = "Ошибка при регистрации. Email уже используется.";
            }
        } else {
            $errors[] = "Ошибка запроса: " . $conn->error;
        }
    }
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/header.php');
?>
<main>
    <section class="register">
        <h1>Регистрация</h1>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="register.php">
            <label for="name">Имя:</label>
            <input type="text" id="name" name="name" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Телефон:</label>
            <input type="text" id="phone" name="phone">

            <label for="address">Адрес:</label>
            <textarea id="address" name="address"></textarea>

            <label for="password">Пароль:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Подтвердите пароль:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit" class="btn">Зарегистрироваться</button>
        </form>
    </section>
</main>
<?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/footer.php'); ?>
