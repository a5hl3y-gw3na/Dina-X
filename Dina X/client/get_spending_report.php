<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? 'daily';
$response = ['labels' => [], 'values' => []];

try {
    switch ($type) {
        case 'daily':
            $query = "
                SELECT DATE(payment_date) as date, 
                       SUM(amount) as total 
                FROM payments 
                WHERE user_id = ? 
                AND payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(payment_date)
                ORDER BY date";
            break;

        case 'weekly':
            $query = "
                SELECT YEARWEEK(payment_date) as week,
                       MIN(DATE(payment_date)) as start_date,
                       SUM(amount) as total
                FROM payments 
                WHERE user_id = ?
                AND payment_date >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
                GROUP BY YEARWEEK(payment_date)
                ORDER BY week";
            break;

        case 'monthly':
            $query = "
                SELECT DATE_FORMAT(payment_date, '%Y-%m') as month,
                       SUM(amount) as total
                FROM payments 
                WHERE user_id = ?
                AND payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
                ORDER BY month";
            break;

        default:
            throw new Exception('Invalid type parameter');
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
    
    // Check if any rows were returned
    if ($stmt->rowCount() === 0) {
        echo json_encode(['error' => 'No data found']);
        exit;
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        switch ($type) {
            case 'daily':
                $response['labels'][] = date('M j', strtotime($row['date']));
                break;
            case 'weekly':
                $response['labels'][] = 'Week of ' . date('M j', strtotime($row['start_date']));
                break;
            case 'monthly':
                $response['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
                break;
        }
        $response['values'][] = floatval($row['total']);
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}
?>
