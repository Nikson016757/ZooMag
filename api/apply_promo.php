<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$code = trim($_POST['code'] ?? '');
$total = floatval($_POST['total'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Введите промокод']);
    exit;
}

// Проверяем и применяем промокод
$query = "SELECT * FROM discounts WHERE code = ? AND is_active = TRUE 
          AND start_date <= NOW() AND end_date >= NOW() 
          AND (max_uses IS NULL OR current_uses < max_uses)
          AND min_order_amount <= ? FOR UPDATE";
$stmt = $conn->prepare($query);
$stmt->bind_param("sd", $code, $total);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Промокод недействителен или истек']);
    exit;
}

$discount = $result->fetch_assoc();

// Рассчитываем скидку
if ($discount['discount_type'] === 'percentage') {
    $discount_amount = min($total * ($discount['discount_value'] / 100), $total);
} else {
    $discount_amount = min($discount['discount_value'], $total);
}

// Увеличиваем счетчик использований
$update_query = "UPDATE discounts SET current_uses = current_uses + 1 WHERE id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param("i", $discount['id']);
$update_stmt->execute();

echo json_encode([
    'success' => true,
    'discount_code' => $discount['code'],
    'discount_amount' => $discount_amount,
    'new_total' => $total - $discount_amount
]);
?>