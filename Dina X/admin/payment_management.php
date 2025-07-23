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

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['status'])) {
    $payment_id = $_POST['payment_id'];
    $status = $_POST['status'];
    $allowed_statuses = ['Pending', 'Completed', 'Failed'];

    // Check if payment method is card before updating
    $payment_stmt = $pdo->prepare('SELECT payment_method FROM payments WHERE id = ?');
    $payment_stmt->execute([$payment_id]);
    $payment = $payment_stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment && in_array($payment['payment_method'], ['card']) && in_array($status, $allowed_statuses)) {
        $update_stmt = $pdo->prepare('UPDATE payments SET status = ? WHERE id = ?');
        $update_stmt->execute([$status, $payment_id]);
        $message = 'Payment status updated successfully.';
    } else {
        $message = 'Cannot update payment status for this payment method.';
    }
}

// Handle payment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_id'])) {
    $delete_payment_id = $_POST['delete_payment_id'];
    $delete_stmt = $pdo->prepare('DELETE FROM payments WHERE id = ?');
    $delete_stmt->execute([$delete_payment_id]);
    $message = 'Payment deleted successfully.';
}

// Fetch payments with order and user info
$query = 'SELECT p.id, p.order_id, p.user_id, u.username, p.amount, p.payment_method, p.status, p.payment_date, o.total_amount FROM payments p JOIN users u ON p.user_id = u.id JOIN orders o ON p.order_id = o.id ORDER BY p.payment_date DESC';
$stmt = $pdo->query($query);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment Management - Admin Panel</title>
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
            box-shadow: 0 8px 24px rgba(149, 157, 165, 0.2);
        }
        h1 {
            font-weight: 700;
            font-size: 2.8rem;
            text-align: center;
            margin-bottom: 1.5rem;
            color: #222;
        }
        /* Navigation */
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
        /* Message */
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
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
            table-layout: auto;
            font-size: 1rem;
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
        /* Status update form */
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
        /* Delete form */
        form.delete-form {
            display: inline;
        }
        form.delete-form button {
            padding: 0.5rem 0.8rem;
            background-color: #dc3545;
            border: none;
            border-radius: 50px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            height: 36px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Management</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Order ID</th>
                    <th>Student</th>
                    <th>Amount</th>
                    <th>Payment Method</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                    <th>Change Status</th>
                    <th>Delete Payment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?= htmlspecialchars($payment['id']) ?></td>
                    <td><?= htmlspecialchars($payment['order_id']) ?></td>
                    <td><?= htmlspecialchars($payment['username']) ?></td>
                    <td>$<?= number_format($payment['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($payment['payment_method']) ?></td>
                    <td><?= htmlspecialchars($payment['status']) ?></td>
                    <td><?= date('d M Y, H:i', strtotime($payment['payment_date'])) ?></td>
                    <td>
                        <?php if ($payment['payment_method'] === 'card'): ?>
                        <form class="status-form" method="POST" action="">
                            <input type="hidden" name="payment_id" value="<?= $payment['id'] ?>" />
                            <select name="status">
                                <option value="Pending" <?= $payment['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Completed" <?= $payment['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="Failed" <?= $payment['status'] === 'Failed' ? 'selected' : '' ?>>Failed</option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                        <?php else: ?>
                        <span>N/A</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form class="delete-form" method="POST" action="">
                            <input type="hidden" name="delete_payment_id" value="<?= $payment['id'] ?>" />
                            <button type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
