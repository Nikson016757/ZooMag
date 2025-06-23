<nav>
    <ul>
        <li><a href="/index.php">Главная</a></li>
        <li><a href="/products.php">Каталог</a></li>
        <li><a href="/cart.php">Корзина</a></li>
        <?php
        if (isset($_SESSION['user_id'])) {
            echo '<li><a href="/user/profile.php">Личный кабинет</a></li>';
            echo '<li><a href="/logout.php">Выйти</a></li>';
        } else {
            echo '<li><a href="/login.php">Авторизация</a></li>';
            echo '<li><a href="/register.php">Регистрация</a></li>';
        }
        ?>
        <li><a href="/contacts.php">Контакты</a></li>
    </ul>
</nav>