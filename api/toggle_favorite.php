<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    redirect('/login.php');
}

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/product_details.php?id=' . (isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0) . '&error=method_not_allowed');
    exit;
}

// Получение и валидация данных
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($product_id <= 0) {
    redirect('/product_details.php?id=' . $product_id . '&error=invalid_data');
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

// Проверка, есть ли уже в избранном
$checkQuery = "SELECT id FROM favorites WHERE user_id = ? AND product_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ii", $user_id, $product_id);
$checkStmt->execute();
$resultCheck = $checkStmt->get_result();

if ($resultCheck->num_rows > 0) {
    // Удаление из избранного
    $deleteQuery = "DELETE FROM favorites WHERE user_id = ? AND product_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ii", $user_id, $product_id);
    $deleteStmt->execute();
    $deleteStmt->close();
    $message = 'removed_from_favorites';
} else {
    // Добавление в избранное
    $insertQuery = "INSERT INTO favorites (user_id, product_id) VALUES (?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    $insertStmt->bind_param("ii", $user_id, $product_id);
    $insertStmt->execute();
    $insertStmt->close();
    $message = 'added_to_favorites';
}

$checkStmt->close();
$stmt->close();
$conn->close();

redirect('/product_details.php?id=' . $product_id . '&success=' . $message);
?>