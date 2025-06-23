<?php
session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /login.php");
    exit();
}

include_once($_SERVER['DOCUMENT_ROOT'] . '/config/database.php');

// Получаем статистику
$stats = [];

// Общие показатели
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM customers) as total_customers,
    (SELECT COUNT(*) FROM orders) as total_orders,
    (SELECT SUM(total_amount) FROM orders) as total_revenue");
$stats['general'] = $result->fetch_assoc();

// Продажи по категориям
$result = $conn->query("SELECT p.category, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
                        FROM order_items oi
                        JOIN products p ON oi.product_id = p.id
                        GROUP BY p.category
                        ORDER BY total_sold DESC");
$stats['by_category'] = $result->fetch_all(MYSQLI_ASSOC);

// Последние заказы
$result = $conn->query("SELECT o.id, o.order_date, o.total_amount, o.status, c.name as customer_name
                        FROM orders o
                        JOIN customers c ON o.customer_id = c.id
                        ORDER BY o.order_date DESC
                        LIMIT 5");
$stats['recent_orders'] = $result->fetch_all(MYSQLI_ASSOC);

// Популярные товары
$result = $conn->query("SELECT p.id, p.name, COUNT(pv.id) as views, SUM(oi.quantity) as sold
                        FROM products p
                        LEFT JOIN product_views pv ON p.id = pv.product_id
                        LEFT JOIN order_items oi ON p.id = oi.product_id
                        GROUP BY p.id
                        ORDER BY sold DESC, views DESC
                        LIMIT 5");
$stats['popular_products'] = $result->fetch_all(MYSQLI_ASSOC);

// Активные скидки
$result = $conn->query("SELECT code, discount_type, discount_value, end_date 
                        FROM discounts 
                        WHERE is_active = TRUE AND end_date >= NOW() 
                        ORDER BY end_date ASC 
                        LIMIT 5");
$stats['active_discounts'] = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once '../includes/meta.php'; ?>
    <title>Админ панель - Дашборд</title>
    <link rel="stylesheet" href="/assets/css/admin_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include_once($_SERVER['DOCUMENT_ROOT'] . '/includes/admin_navbar.php'); ?>
    <div class="admin-container">
        <h1>Дашборд</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Товары</h3>
                <p><?= $stats['general']['total_products'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Пользователи</h3>
                <p><?= $stats['general']['total_customers'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Заказы</h3>
                <p><?= $stats['general']['total_orders'] ?></p>
            </div>
            <div class="stat-card">
                <h3>Общий доход</h3>
                <p><?= number_format($stats['general']['total_revenue'], 2) ?> руб.</p>
            </div>
        </div>
        
        <div class="dashboard-row">
            <div class="dashboard-card">
                <h3>Продажи по категориям</h3>
                <canvas id="salesByCategoryChart"></canvas>
            </div>
            
            <div class="dashboard-card">
                <h3>Последние заказы</h3>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Клиент</th>
                            <th>Сумма</th>
                            <th>Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['recent_orders'] as $order): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td><?= number_format($order['total_amount'], 2) ?> руб.</td>
                                <td><?= $order['status'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-row">
            <div class="dashboard-card">
                <h3>Популярные товары</h3>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>Товар</th>
                            <th>Просмотры</th>
                            <th>Продажи</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['popular_products'] as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= $product['views'] ?></td>
                                <td><?= $product['sold'] ?? 0 ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="dashboard-card">
                <h3>Статистика заказов</h3>
                <canvas id="ordersChart"></canvas>
            </div>
        </div>
        
        <div class="dashboard-row">
            <div class="dashboard-card">
                <h3>Активные скидки</h3>
                <table class="compact-table">
                    <thead>
                        <tr>
                            <th>Код</th>
                            <th>Тип</th>
                            <th>Значение</th>
                            <th>Действует до</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['active_discounts'] as $discount): ?>
                            <tr>
                                <td><?= htmlspecialchars($discount['code']) ?></td>
                                <td><?= $discount['discount_type'] === 'percentage' ? 'Процент' : 'Фиксированная' ?></td>
                                <td><?= $discount['discount_type'] === 'percentage' ? 
                                    $discount['discount_value'] . '%' : 
                                    $discount['discount_value'] . ' руб.' ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($discount['end_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    // График продаж по категориям
    const categoryCtx = document.getElementById('salesByCategoryChart').getContext('2d');
    const categoryChart = new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($stats['by_category'], 'category')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($stats['by_category'], 'total_sold')) ?>,
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                    '#FF9F40', '#8AC24A', '#607D8B', '#E91E63', '#3F51B5'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
    
    // График заказов за последние 30 дней
    fetch('/admin/api/get_order_stats.php?days=30')
        .then(response => response.json())
        .then(data => {
            const ordersCtx = document.getElementById('ordersChart').getContext('2d');
            const ordersChart = new Chart(ordersCtx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [
                        {
                            label: 'Количество заказов',
                            data: data.order_counts,
                            borderColor: '#36A2EB',
                            backgroundColor: 'rgba(54, 162, 235, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Сумма заказов',
                            data: data.order_amounts,
                            borderColor: '#4BC0C0',
                            backgroundColor: 'rgba(75, 192, 192, 0.1)',
                            tension: 0.1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Количество заказов'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Сумма (руб)'
                            },
                            grid: {
                                drawOnChartArea: false
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>