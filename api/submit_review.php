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
    http_response_code(405);
    exit;
}

// Получение и валидация входных данных
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$customer_id = $_SESSION['user_id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($product_id <= 0 || $rating <= 0 || $rating > 5 || empty($comment)) {
    redirect('/product_details.php?id=' . $product_id . '&error=invalid_review');
    exit;
}

// Проверка существования товара
$query = "SELECT id FROM products WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('/product_details.php?id=' . $product_id . '&error=product_not_found');
    exit;
}

// Сохранение отзыва
$created_at = date('Y-m-d H:i:s');
$insert_query = "INSERT INTO reviews (product_id, customer_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param("iiiss", $product_id, $customer_id, $rating, $comment, $created_at);

if ($stmt->execute()) {
    redirect('/product_details.php?id=' . $product_id . '&success=review_added');
} else {
    redirect('/product_details.php?id=' . $product_id . '&error=review_failed');
}

$stmt->close();
$conn->close();
?>