<?php
// database.php
$host = 'localhost'; // Хост базы данных
$dbname = 'pet_shop'; // Имя базы данных
$user = 'root'; // Имя пользователя базы данных
$password = 'root'; // Пароль пользователя базы данных

// Подключение к базе данных
$conn = mysqli_connect($host, $user, $password, $dbname);

// Проверка подключения
if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

// Установка кодировки соединения
mysqli_set_charset($conn, 'utf8');
?>