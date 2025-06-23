<?php
session_start();
include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] == 'true';

$query = "SELECT id, title, message, is_read, created_at 
          FROM notifications 
          WHERE user_id = ?" . ($unread_only ? " AND is_read = FALSE" : "") . "
          ORDER BY created_at DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>