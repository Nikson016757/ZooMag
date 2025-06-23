<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$notification_id = (int)$_POST['notification_id'];
$user_id = (int)$_SESSION['user_id'];

$query = "UPDATE notifications SET is_read = TRUE 
          WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $notification_id, $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
?>