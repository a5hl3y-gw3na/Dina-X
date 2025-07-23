<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is superior admin (only superior admin has access)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superior_admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';

/// Handle report generation request
$report_type = $_GET['report_type'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Convert date format to YYYY/MM/DD
if ($start_date) {
    $start_date = date('Y/m/d', strtotime($start_date));
}
if ($end_date) {
    $end_date = date('Y/m/d', strtotime($end_date));
}

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
                $message = 'Invalid report type selected.';
        }
    } catch (PDOException $e) {
        $message = 'Database error: ' . htmlspecialchars($e->getMessage());
    } catch (Exception $e) {
        $message = 'Error generating report: ' . htmlspecialchars($e->getMessage());
    }
}


// Fetch categories for filtering
$categories_stmt = $pdo->query('SELECT id, category_name FROM menu_categories');
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reporting - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        /* Base Reset */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: #eef2f7;
            font-family: 'Montserrat', sans-serif;
            color: #222;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .container {
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: 12px;
            padding: 2.5rem 3rem;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            font-size: 2.8rem;
            font-weight: 700;
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }
        nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        nav a {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            padding: 0.75rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 14px rgba(255, 75, 43, 0.4);
            transition: background 0.3s ease, transform 0.3s ease;
            position: relative;
        }
        nav a:hover {
            background: linear-gradient(135deg, #ff4b2b, #ff416c);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(255, 75, 43, 0.7);
            z-index: 10;
        }
        .message {
            max-width: 500px;
            margin: 0 auto 1.8rem;
            background: #28a745;
            color: white;
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.6);
            animation: fadeIn 0.7s ease forwards;
            opacity: 0;
        }
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        form.report-form {
            margin-top: 1rem;
            background: #222;
            padding: 1.5rem;
            border-radius: 8px;
            color: #eee;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        form.report-form label {
            margin-right: 0.5rem;
        }
        form.report-form input, form.report-form select {
            margin-right: 1rem;
            padding: 0.4rem;
            border-radius: 4px;
            border: none;
            background: #333;
            color: #eee;
            flex: 1 1 45%; /* Responsive flex */
            min-width: 200px; /* Minimum width for smaller screens */
        }
        form.report-form button {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
            margin-top: 1rem;
            flex: 1 1 100%; /* Full width on smaller screens */
        }
        form.report-form button:hover {
            background: linear-gradient(135deg, #ff4b2b, #ff416c);
            transform: translateY(-2px);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #444;
            padding: 0.75rem;
            text-align: left;
        }
        th {
            background-color: #333;
            color: #fff;
        }
        tbody tr {
            transition: background 0.3s ease;
        }
        tbody tr:hover {
            background: #f1f1f1;
        }
        form button {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    color: white;
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1.1rem;
    cursor: pointer;
    box-shadow: 0 6px 14px rgba(255, 75, 43, 0.6);
    transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    user-select: none;
    margin-top: 1rem;
}

form button:hover,
form button:focus {
    background: linear-gradient(135deg, #ff4b2b, #ff416c);
    box-shadow: 0 8px 24px rgba(255, 75, 43, 0.8);
    transform: translateY(-3px);
    outline: none;
}

    </style>
</head>
<body>
    <div class="container">
        <h1>Reporting</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form class="report-form" method="GET" action="">
            <label for="report_type">Report Type:</label>
            <select id="report_type" name="report_type" required>
                <option value="">Select Report</option>
                <option value="sales" <?= $report_type === 'sales' ? 'selected' : '' ?>>Daily/Weekly/Monthly Sales</option>
                <option value="inventory_usage" <?= $report_type === 'inventory_usage' ? 'selected' : '' ?>>Inventory Usage Trends</option>
                <option value="student_activity" <?= $report_type === 'student_activity' ? 'selected' : '' ?>>Student Order and Payment Activity</option>
            </select>

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required />

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required />

            <button type="submit">Generate Report</button>
        </form>

        <?php if ($report_data): ?>
            <table>
                <thead>
                    <tr>
                        <?php if ($report_type === 'sales'): ?>
                            <th>Date</th>
                            <th>Total Sales</th>
                        <?php elseif ($report_type === 'inventory_usage'): ?>
                            <th>Item Name</th>
                            <th>Quantity</th>
                        <?php elseif ($report_type === 'student_activity'): ?>
                            <th>Student Username</th>
                            <th>Orders Count</th>
                            <th>Total Payments</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $row): ?>
                    <tr>
                        <?php if ($report_type === 'sales'): ?>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td>$<?= number_format($row['total_sales'], 2) ?></td>
                        <?php elseif ($report_type === 'inventory_usage'): ?>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                        <?php elseif ($report_type === 'student_activity'): ?>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['orders_count']) ?></td>
                            <td>$<?= number_format($row['total_payments'], 2) ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
             <!-- Download Report Button -->
    <form method="POST" action="download_report.php">
        <input type="hidden" name="report_type" value="<?= htmlspecialchars($report_type) ?>">
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        <button type="submit">Download Report</button>
    </form>
        <?php endif; ?>
    </div>
</body>
</html>
