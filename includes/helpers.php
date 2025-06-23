<?php
function redirect($url) {
    header("Location: $url");
    exit;
}

function formatPrice($price) {
    global $settings;
    return number_format($price, 2, ',', ' ') . ' ' . $settings['currency'];
}

function getSettings() {
    return include($_SERVER['DOCUMENT_ROOT'] . '/config/settings.php');
}

function createNotification($user_id, $title, $message) {
    global $conn;
    
    $query = "INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $title, $message);
    $stmt->execute();
    $stmt->close();
    
    return $conn->insert_id;
}
?>