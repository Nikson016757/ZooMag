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
    redirect('/product_details.php?id=' . (isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0) . '&error=method_not_allowed');
}

// Получение и валидация входных данных
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

if ($product_id <= 0 || $quantity <= 0) {
    redirect('/product_details.php?id=' . $product_id . '&error=invalid_data');
}

// Проверка существования товара
$query = "SELECT id, stock FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('/product_details.php?id=' . $product_id . '&error=product_not_found');
}

$product = $result->fetch_assoc();
if ($product['stock'] < $quantity) {
    redirect('/product_details.php?id=' . $product_id . '&error=insufficient_stock');
}

// Проверка, есть ли товар уже в корзине
$user_id = (int)$_SESSION['user_id'];
$query = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Обновление количества, если товар уже в корзине
    $cart_item = $result->fetch_assoc();
    $new_quantity = $cart_item['quantity'] + $quantity;
    if ($product['stock'] < $new_quantity) {
        redirect('/product_details.php?id=' . $product_id . '&error=insufficient_stock');
    }
    $update_query = "UPDATE cart_items SET quantity = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
} else {
    // Добавление нового товара в корзину
    $insert_query = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iii", $user_id, $product_id, $quantity);
}

// Выполнение запроса
if ($stmt->execute()) {
    redirect('/product_details.php?id=' . $product_id . '&success=added_to_cart');
} else {
    redirect('/product_details.php?id=' . $product_id . '&error=add_to_cart_failed');
}

$stmt->close();
$conn->close();
?>