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

$courier_id = intval($_SESSION['user_id']);
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$transport = trim($_POST['transport'] ?? '');

if (empty($name) || empty($email) || empty($phone)) {
    redirect('/courier/index.php?error=empty_fields');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect('/courier/index.php?error=invalid_email');
}

$stmt = $conn->prepare("UPDATE customers SET name=?, email=?, phone=?, transport=? WHERE id=? AND role='courier'");
$stmt->bind_param("ssssi", $name, $email, $phone, $transport, $courier_id);

if ($stmt->execute()) {
    redirect('/courier/index.php?success=profile_updated');
} else {
    redirect('/courier/index.php?error=update_failed');
}

$stmt->close();
$conn->close();
?>