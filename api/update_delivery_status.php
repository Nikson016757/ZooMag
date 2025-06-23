<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/helpers.php');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'courier') {
    redirect('/login.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/courier/index.php?error=method_not_allowed');
}

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$delivery_status = $_POST['delivery_status'] ?? '';
$courier_id = intval($_SESSION['user_id']);

if ($order_id <= 0 || !in_array($delivery_status, ['pending', 'accepted', 'in_progress', 'delivered', 'rejected'])) {
    redirect('/courier/index.php?error=invalid_data');
}

$stmt = $conn->prepare("UPDATE orders SET delivery_status = ? WHERE id = ? AND courier_id = ?");
$stmt->bind_param("sii", $delivery_status, $order_id, $courier_id);
if ($stmt->execute()) {
    redirect('/courier/index.php?success=status_updated');
} else {
    redirect('/courier/index.php?error=update_failed');
}

$stmt->close();
$conn->close();
?>