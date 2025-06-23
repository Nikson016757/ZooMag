<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

$code = trim($_GET['code'] ?? '');
$total = floatval($_GET['total'] ?? 0);
$user_id = $_SESSION['user_id'];

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Введите промокод']);
    exit;
}

// Начинаем транзакцию для обеспечения атомарности
$conn->begin_transaction();

try {
    // Проверяем промокод с блокировкой строки
    $query = "SELECT * FROM discounts WHERE code = ? AND is_active = TRUE 
              AND start_date <= NOW() AND end_date >= NOW() 
              AND (max_uses IS NULL OR current_uses < max_uses)
              AND min_order_amount <= ? FOR UPDATE";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sd", $code, $total);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Промокод недействителен или истек');
    }

    $discount = $result->fetch_assoc();

    // Проверяем, не использовал ли уже пользователь этот промокод
    $usage_query = "SELECT COUNT(*) as used FROM orders 
                    WHERE customer_id = ? AND discount_code = ?";
    $usage_stmt = $conn->prepare($usage_query);
    $usage_stmt->bind_param("is", $user_id, $code);
    $usage_stmt->execute();
    $usage_result = $usage_stmt->get_result()->fetch_assoc();

    if ($usage_result['used'] > 0 && $discount['is_single_use']) {
        throw new Exception('Вы уже использовали этот промокод');
    }

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

    // Фиксируем транзакцию
    $conn->commit();

    echo json_encode([
        'success' => true,
        'discount_code' => $discount['code'],
        'discount_amount' => $discount_amount,
        'new_total' => $total - $discount_amount
    ]);
} catch (Exception $e) {
    // Откатываем транзакцию в случае ошибки
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>