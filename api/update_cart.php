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
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

if ($cart_item_id <= 0 || $quantity <= 0) {
    redirect('/cart.php?error=invalid_data');
    exit;
}

// Проверка, принадлежит ли элемент корзины пользователю и достаточно ли товара на складе
$user_id = (int)$_SESSION['user_id'];
$query = "SELECT ci.quantity, p.stock FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.id = ? AND ci.user_id = ?";
$stmt = $conn->prepare($query);
if ($stmt === false) {
    redirect('/cart.php?error=query_failed');
    exit;
}
$stmt->bind_param("ii", $cart_item_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('/cart.php?error=not_found');
    exit;
}

$cart_item = $result->fetch_assoc();
if ($cart_item['stock'] < $quantity) {
    redirect('/cart.php?error=insufficient_stock');
    exit;
}

// Обновление количества
$update_query = "UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($update_query);
if ($stmt === false) {
    redirect('/cart.php?error=query_failed');
    exit;
}
$stmt->bind_param("iii", $quantity, $cart_item_id, $user_id);

if ($stmt->execute()) {
    redirect('/cart.php?success=updated');
} else {
    redirect('/cart.php?error=update_failed');
}

$stmt->close();
$conn->close();
?>