<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');

// Проверка авторизации пользователя
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cart.php?error=method_not_allowed');
    exit;
}

// Получение и валидация входных данных
$cart_item_id = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;

if ($cart_item_id <= 0) {
    redirect('/cart.php?error=invalid_data');
    exit;
}

// Удаление элемента корзины
$user_id = (int)$_SESSION['user_id'];
$query = "DELETE FROM cart_items WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    redirect('/cart.php?error=query_failed');
    exit;
}
$stmt->bind_param("ii", $cart_item_id, $user_id);

if ($stmt->execute()) {
    redirect('/cart.php?success=removed');
} else {
    redirect('/cart.php?error=remove_failed');
}

$stmt->close();
$conn->close();
?>