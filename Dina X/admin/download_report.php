<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is superior admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superior_admin') {
    header('Location: ../index.php');
    exit;
}

// Get report parameters
$report_type = $_POST['report_type'] ?? '';
$start_date = $_POST['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? '';

// Prepare data for reports
$report_data = [];

if ($report_type && $start_date && $end_date) {
    try {
        switch ($report_type) {
            case 'sales':
                $query = 'SELECT DATE(o.order_date) as date, SUM(o.total_amount) as total_sales FROM orders o WHERE o.order_date BETWEEN ? AND ? GROUP BY DATE(o.order_date)';
                $stmt = $pdo->prepare($query);
                $stmt->execute([$start_date, $end_date]);
                $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'inventory_usage':
                $query = 'SELECT name, quantity FROM inventory_items';
                $stmt = $pdo->query($query);
                $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            case 'student_activity':
                $query = 'SELECT u.username, COUNT(o.id) as orders_count, SUM(p.amount) as total_payments FROM users u LEFT JOIN orders o ON u.id = o.user_id LEFT JOIN payments p ON u.id = p.user_id WHERE u.role_id = (SELECT id FROM roles WHERE role_name = "client") AND o.order_date BETWEEN ? AND ? GROUP BY u.id';
                $stmt = $pdo->prepare($query);
                $stmt->execute([$start_date, $end_date]);
                $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;

            default:
                die('Invalid report type selected.');
        }
    } catch (PDOException $e) {
        die('Database error: ' . htmlspecialchars($e->getMessage()));
    } catch (Exception $e) {
        die('Error generating report: ' . htmlspecialchars($e->getMessage()));
    }

    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report.csv"');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Write header row based on report type
    if ($report_type === 'sales') {
        fputcsv($output, ['Date', 'Total Sales']);
    } elseif ($report_type === 'inventory_usage') {
        fputcsv($output, ['Item Name', 'Quantity']);
    } elseif ($report_type === 'student_activity') {
        fputcsv($output, ['Student Username', 'Orders Count', 'Total Payments']);
    }

    // Write data rows
    foreach ($report_data as $row) {
        if ($report_type === 'sales') {
            fputcsv($output, [htmlspecialchars($row['date']), number_format($row['total_sales'], 2)]);
        } elseif ($report_type === 'inventory_usage') {
            fputcsv($output, [htmlspecialchars($row['name']), htmlspecialchars($row['quantity'])]);
        } elseif ($report_type === 'student_activity') {
            fputcsv($output, [htmlspecialchars($row['username']), htmlspecialchars($row['orders_count']), number_format($row['total_payments'], 2)]);
        }
    }

    // Close output stream
    fclose($output);
    exit;
}
?>
