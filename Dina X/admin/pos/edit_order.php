<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../index.php');
    exit;
}

$errors = [];
$success = false;

// Get the logged-in user's ID
$logged_in_user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save updates
    $order_id = $_POST['order_id'] ?? null;
    $order_notes = $_POST['order_notes'] ?? '';
    $dining_hall = $_POST['dining_hall'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $total_amount = (float)($_POST['total_amount'] ?? 0);

    // Basic validation
    if (!$order_id) {
        $errors[] = "Order ID is required.";
    }

    if (empty($errors)) {
        // Update order
        $stmt = $pdo->prepare("UPDATE orders SET client_id = ?, order_notes = ?, dining_hall = ?, status = ?, total_amount = ? WHERE id = ?");
        $stmt->execute([$logged_in_user_id, $order_notes, $dining_hall, $status, $total_amount, $order_id]);
        $success = true;
    }
} else {
    // GET: Load existing order info for editing
    if (!isset($_POST['order_id']) && isset($_GET['order_id'])) {
        $_POST['order_id'] = $_GET['order_id'];
    }

    $order_id = $_POST['order_id'] ?? null;
    if (!$order_id) {
        die('No order ID specified.');
    }

    // Fetch order
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Order not found.');
    }

    // Prepare values to populate form
    $_POST = array_merge($_POST, $order);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Order #<?= htmlspecialchars($_POST['order_id'] ?? '') ?></title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f6ff;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 60px auto;
            background: white;
            border-radius: 12px;
            padding: 30px 40px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }

        h1 {
            text-align: center;
            color: #2980b9;
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #34495e;
        }

        input[type=text],
        input[type=number],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 18px;
            border-radius: 6px;
            border: 1px solid #ccd6dd;
            font-size: 15px;
        }

        textarea {
            resize: vertical;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #2980b9;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #2471a3;
        }

        .success, .error {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 5px solid #2e7d32;
        }

        .error {
            background: #fcebea;
            color: #c0392b;
            border-left: 5px solid #c0392b;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #2980b9;
        }

        a:hover {
            text-decoration: underline;
        }

        .back-btn {
            display: inline-block;
            margin-top: 25px;
            padding: 10px 20px;
            background-color: #ecf5ff;
            color: #2980b9;
            border: 2px solid #2980b9;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.3s, color 0.3s;
        }

        .back-btn:hover {
            background-color: #2980b9;
            color: white;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Edit Order #<?= htmlspecialchars($_POST['order_id'] ?? '') ?></h1>

    

    <?php if (!empty($errors)): ?>
        <div class="error"><?= implode('<br>', $errors) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit_order.php">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>" />

        <label for="order_notes">Order Notes:</label>
        <textarea id="order_notes" name="order_notes" rows="4"><?= htmlspecialchars($_POST['order_notes'] ?? '') ?></textarea>

        <label for="dining_hall">Dining Hall:</label>
        <input type="text" id="dining_hall" name="dining_hall" value="<?= htmlspecialchars($_POST['dining_hall'] ?? '') ?>" />

        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <option value="pending" <?= (($_POST['status'] ?? '') == 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="completed" <?= (($_POST['status'] ?? '') == 'completed') ? 'selected' : '' ?>>Completed</option>
            <option value="failed" <?= (($_POST['status'] ?? '') == 'failed') ? 'selected' : '' ?>>Failed</option>
        </select>

        <label for="total_amount">Total Amount ($):</label>
        <input type="number" id="total_amount" name="total_amount" min="0" step="0.01" value="<?= htmlspecialchars($_POST['total_amount'] ?? '0.00') ?>" required />

        <button type="submit">💾 Save Changes</button>
    </form>
    <a href="POS.php" class="back-btn">← Back to POS</a>

</div>

</body>
</html>
