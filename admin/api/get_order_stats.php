<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');
header('Content-Type: application/json');

$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime("-$days days"));

// Получаем данные для графика
$labels = [];
$order_counts = [];
$order_amounts = [];

// Генерируем все даты в диапазоне
$period = new DatePeriod(
    new DateTime($start_date),
    new DateInterval('P1D'),
    new DateTime($end_date)
);

foreach ($period as $date) {
    $date_str = $date->format('Y-m-d');
    $labels[] = $date->format('d.m');
    $order_counts[$date_str] = 0;
    $order_amounts[$date_str] = 0;
}

// Заполняем данные из базы
$query = "SELECT DATE(order_date) as order_day, 
                 COUNT(*) as order_count, 
                 SUM(total_amount) as order_amount
          FROM orders
          WHERE order_date BETWEEN ? AND ?
          GROUP BY DATE(order_date)";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $order_counts[$row['order_day']] = (int)$row['order_count'];
    $order_amounts[$row['order_day']] = (float)$row['order_amount'];
}

echo json_encode([
    'labels' => $labels,
    'order_counts' => array_values($order_counts),
    'order_amounts' => array_values($order_amounts)
]);
?>