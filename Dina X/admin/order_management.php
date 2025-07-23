<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../index.php');
    exit;
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $allowed_statuses = ['Preparing', 'Ready', 'Delivered'];
    if (in_array($status, $allowed_statuses)) {
        $update_stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $update_stmt->execute([$status, $order_id]);
        $message = 'Order status updated successfully.';
    }
}

// Handle custom order pricing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_order_id'], $_POST['custom_price'])) {
    $custom_order_id = $_POST['custom_order_id'];
    $custom_price = $_POST['custom_price'];

    // Update the order with the custom price and set status to Pending Approval
    $update_custom_stmt = $pdo->prepare('UPDATE orders SET price = ?, status = "Pending Approval" WHERE id = ?');
    $update_custom_stmt->execute([$custom_price, $custom_order_id]);

    // Insert or update the payment record for the order
    $payment_stmt = $pdo->prepare('INSERT INTO payments (order_id, amount, payment_method, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE amount = ?, payment_method = ?, status = ?');
    $payment_stmt->execute([$custom_order_id, $custom_price, 'wallet', 'Pending Approval', $custom_price, 'wallet', 'Pending Approval']);

    $message = 'Custom order price set successfully. Awaiting client approval.';
}
// Filters
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_student = $_GET['student'] ?? '';


// Build query with filters, excluding rows where dining_hall is NULL
$query = 'SELECT o.id, o.user_id, u.username, o.status, o.order_date, o.price, o.custom_order, o.dining_hall, o.total_amount
          FROM orders o
          JOIN users u ON o.user_id = u.id
          WHERE o.dining_hall IS NOT NULL';
$params = [];

// Add filters to the query
if ($filter_date) {
    $query .= ' AND DATE(o.order_date) = ?';
    $params[] = $filter_date;
}
if ($filter_status && in_array($filter_status, ['Preparing', 'Ready', 'Delivered', 'Pending Approval'])) {
    $query .= ' AND o.status = ?';
    $params[] = $filter_status;
}
if ($filter_student) {
    $query .= ' AND u.username LIKE ?';
    $params[] = '%' . $filter_student . '%';
}

$query .= ' ORDER BY o.order_date DESC';

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Order Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        /* Reset and base styles */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 3rem auto;
            background: #fff;
            padding: 2rem 2.5rem;
            border-radius: 12px;
            box-shadow:
                0 8px 24px rgba(149, 157, 165, 0.2),
                0 24px 48px rgba(149, 157, 165, 0.1);
        }
        h1 {
            font-weight: 700;
            font-size: 2.8rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #222;
        }
        nav {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        nav a {
            text-decoration: none;
            color: #fff;
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            padding: 0.7rem 1.6rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: 0 8px 15px rgba(255, 75, 43, 0.3);
            transition:
                background 0.4s ease,
                transform 0.3s ease,
                box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        nav a::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            width: 300%;
            height: 300%;
            background: rgba(255,255,255,0.15);
            transform: translate(-50%, -50%) scale(0);
            border-radius: 50%;
            transition: transform 0.6s ease;
            z-index: 1;
        }
        nav a:hover::before {
            transform: translate(-50%, -50%) scale(1);
        }
        nav a:hover {
            background: linear-gradient(45deg, #ff4b2b, #ff416c);
            transform: translateY(-4px);
            box-shadow: 0 12px 20px rgba(255, 70, 20, 0.6);
            z-index: 10;
        }
        .message {
            text-align: center;
            background-color: #28a745;
            color: white;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.5);
            animation: fadeInMessage 1s ease forwards;
            opacity: 0;
        }
        @keyframes fadeInMessage {
            to {
                opacity: 1;
            }
        }
        form.filter-form {
            background: #222831;
            color: #eee;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.25rem;
            align-items: center;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
        }
        form.filter-form label {
            font-weight: 600;
            margin-bottom: 0.4rem;
            display: block;
        }
        form.filter-form input[type="date"],
        form.filter-form input[type="text"],
        form.filter-form select {
            width: 100%;
            padding: 0.55rem 0.8rem;
            border-radius: 8px;
            border: none;
            background: #393e46;
            color: #eee;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }
        form.filter-form input[type="date"]:focus,
        form.filter-form input[type="text"]:focus,
        form.filter-form select:focus {
            background-color: #4e555f;
            outline: none;
        }
        form.filter-form button {
            grid-column: 1 / -1;
            max-width: 200px;
            justify-self: center;
            padding: 0.75rem 0;
            background: #ff416c;
            background: linear-gradient(45deg, #ff4b2b, #ff416c);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(255,75,43,0.5);
            transition: background 0.4s ease, transform 0.3s ease;
            user-select: none;
        }
        form.filter-form button:hover {
            background: linear-gradient(45deg, #ff4b2b, #ff416c);
            transform: translateY(-3px);
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
            table-layout: auto;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        thead th {
            background: #393e46;
            color: #eeeeee;
            font-weight: 700;
            text-align: left;
            padding: 1rem 1.25rem;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            user-select: none;
        }
        tbody tr {
            background: #fefefe;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border-radius: 10px;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        tbody tr:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        tbody td {
            padding: 1rem 1.25rem;
            vertical-align: middle;
            color: #555;
        }
        tbody td:first-child {
            font-weight: 600;
            color: #222;
        }
        form.status-form {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }
        form.status-form select {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-weight: 600;
            background: #fff;
            color: #333;
            transition: border-color 0.3s ease;
            cursor: pointer;
            min-width: 100px;
            height: 36px;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3e%3cpath d='M12 6L8 10 4 6z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px 12px;
        }
        form.status-form select:hover,
        form.status-form select:focus {
            border-color: #ff4b2b;
            outline: none;
        }
        form.status-form button {
            padding: 0.5rem 0.8rem;
            background-image: linear-gradient(45deg, #ff416c, #ff4b2b);
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 700;
            letter-spacing: 0.05em;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(255,75,43,0.5);
            transition: background 0.4s ease, transform 0.2s ease;
            user-select: none;
            height: 36px;
        }
        form.status-form button:hover {
            background-image: linear-gradient(45deg, #ff4b2b, #ff416c);
            transform: translateY(-3px);
        }
        form.custom-order-form {
            margin-top: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        form.custom-order-form input[type="number"] {
            padding: 0.55rem 0.8rem;
            border-radius: 8px;
            border: none;
            background: #393e46;
            color: #eee;
            font-size: 1rem;
        }
        form.custom-order-form button {
            max-width: 200px;
            align-self: flex-start;
        }
        @media screen and (max-width: 768px) {
            form.filter-form {
                grid-template-columns: 1fr;
            }
            form.filter-form button {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Order Management</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form class="filter-form" method="GET" action="">
            <div>
                <label for="date">Filter by Date:</label><br />
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>" />
            </div>
            
            <div>
                <label for="status">Filter by Status:</label><br />
                <select id="status" name="status" >
                    <option value="">All</option>
                    <option value="Preparing" <?= $filter_status === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                    <option value="Ready" <?= $filter_status === 'Ready' ? 'selected' : '' ?>>Ready</option>
                    <option value="Delivered" <?= $filter_status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Pending Approval" <?= $filter_status === 'Pending Approval' ? 'selected' : '' ?>>Pending Approval</option>
                </select>
            </div>
            
            <div>
                <label for="student">Filter by Student:</label><br />
                <input type="text" id="student" name="student" placeholder="Student username" value="<?= htmlspecialchars($filter_student) ?>" />
            </div>
            
            <div style="align-self: flex-end;">
                <button type="submit">Filter</button>
            </div>
        </form>

        <h2>Customized Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Dining Hall</th>
                    <th>Custom Order Details</th>
                    <th>Total Amount</th>
                    <th>Set Price</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $customOrdersFound = false;
                foreach ($orders as $order): 
                    if (!empty($order['custom_order'])): 
                        $customOrdersFound = true;
                        ?>
                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($order['order_date'])) ?></td>
                        <td><?= htmlspecialchars($order['dining_hall']) ?></td>
                        <td><?= nl2br(htmlspecialchars($order['custom_order'])) ?></td>
                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <form class="custom-order-form" method="POST" action="">
                                <input type="hidden" name="custom_order_id" value="<?= $order['id'] ?>" />
                                <input type="number" name="custom_price" placeholder="Set Price" step="0.01" required />
                                <button type="submit">Send to Client</button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endif; 
                endforeach; 
                if (!$customOrdersFound): ?>
                    <tr><td colspan="8" style="text-align:center; color:#888;">No customized orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>All Orders</h2>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Dining Hall</th>
                    <th>Total Amount</th>
                    <th>Change Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $normalOrdersFound = false;
                foreach ($orders as $order): 
    if (empty($order['custom_order'])): 
        $normalOrdersFound = true;
        ?>


                    <tr>
                        <td><?= htmlspecialchars($order['id']) ?></td>
                        <td><?= htmlspecialchars($order['username']) ?></td>
                        <td><?= htmlspecialchars($order['status']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($order['order_date'])) ?></td>
                        <td><?= htmlspecialchars($order['dining_hall']) ?></td>
                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                        <td>
                            <form class="status-form" method="POST" action="">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>" />
                                <select name="status">
                                    <option value="Preparing" <?= $order['status'] === 'Preparing' ? 'selected' : '' ?>>Preparing</option>
                                    <option value="Ready" <?= $order['status'] === 'Ready' ? 'selected' : '' ?>>Ready</option>
                                    <option value="Delivered" <?= $order['status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                </select>
                                <button type="submit">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php 
                    endif; 
                endforeach; 
                if (!$normalOrdersFound): ?>
                    <tr><td colspan="7" style="text-align:center; color:#888;">No orders found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

