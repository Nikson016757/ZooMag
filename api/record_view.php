<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

if (!isset($_SESSION['user_id'])) {
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($product_id <= 0) {
    exit;
}

// Проверяем, есть ли уже запись о просмотре
$query = "SELECT id FROM product_views 
          WHERE user_id = ? AND product_id = ? 
          AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Добавляем новую запись
    $insert_query = "INSERT INTO product_views (user_id, product_id) VALUES (?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $insert_stmt->execute();
    $insert_stmt->close();
}

$stmt->close();
?>