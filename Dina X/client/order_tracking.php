<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch orders for the client, including custom order details and price
$orders_stmt = $pdo->prepare('SELECT id, status, order_date, total_amount, custom_order, price FROM orders WHERE user_id = ? ORDER BY order_date DESC');
$orders_stmt->execute([$user_id]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle AJAX POST requests for accepting or rejecting custom orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['order_id'])) {
    $action = $_POST['action'];
    $order_id = intval($_POST['order_id']);

    header('Content-Type: application/json');

    // Verify the order belongs to the logged-in user
    $order_check = $pdo->prepare('SELECT custom_order, price FROM orders WHERE id = ? AND user_id = ?');
    $order_check->execute([$order_id, $user_id]);
    $order = $order_check->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found.']);
        exit;
    }

    // Only allow accept/reject if custom_order is not empty
    if (empty($order['custom_order'])) {
        echo json_encode(['success' => false, 'message' => 'This action is not allowed on this order.']);
        exit;
    }
if ($action === 'accept') {
    // Deduct the price from the user's wallet balance
    $price = $order['price'] ?? 0; // Default to 0 if price is null
    $deduct_stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
    $deduct_stmt->execute([$price, $user_id]);

    // Update order: set total_amount = price, status = 'Preparing', and clear custom_order
    $update_stmt = $pdo->prepare('UPDATE orders SET total_amount = ?, status = ?, custom_order = NULL WHERE id = ?');
    $update_stmt->execute([$price, 'Preparing', $order_id]); // Use $price for total_amount

    // Update the payment record to set user_id for the accepted order
    $payment_update_stmt = $pdo->prepare('UPDATE payments SET user_id = ? WHERE order_id = ?');
    $payment_update_stmt->execute([$user_id, $order_id]);

    echo json_encode(['success' => true, 'message' => 'You have accepted the price. Order updated and is now being prepared.']);
    exit;
}
 elseif ($action === 'reject') {
        // Delete the order
        $delete_stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
        $delete_stmt->execute([$order_id]);
        echo json_encode(['success' => true, 'message' => 'You have rejected the price. The order has been deleted.']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        exit;
    }
}

// If not AJAX POST, render the page normally
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Order Tracking - Usavi System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: white;
            position: relative;
            overflow: hidden;
        }
        .btn {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-accept {
            background-color: #22c55e;
            color: white;
        }
        .btn-accept:hover {
            background-color: #16a34a;
        }
        .btn-reject {
            background-color: #ef4444;
            color: white;
        }
        .btn-reject:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">

<div class="max-w-7xl mx-auto p-6">
    <h1 class="text-3xl font-bold mb-4">Your Orders</h1>
    <nav class="mb-4">
        <a href="dashboard.php" class="text-blue-600 hover:underline mr-4">Dashboard</a>
        <a href="logout.php" class="text-red-600 hover:underline">Logout</a>
    </nav>

    <?php if (count($orders) === 0): ?>
        <p class="text-gray-600">You have no orders yet.</p>
    <?php else: ?>
        <table class="min-w-full bg-white border border-gray-300 rounded-lg shadow-md">
    <thead>
        <tr class="bg-gray-800 text-white">
            <!-- Removed Order ID header -->
            <th class="py-2 px-4 border-b">Status</th>
            <th class="py-2 px-4 border-b">Order Date</th>
            <th class="py-2 px-4 border-b">Total Amount</th>
            <th class="py-2 px-4 border-b">Custom Order</th>
            <th class="py-2 px-4 border-b">Price</th>
            <th class="py-2 px-4 border-b">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($orders as $order): ?>
        <tr class="hover:bg-gray-100">
            <!-- Removed Order ID cell -->
            <td class="py-2 px-4 border-b"><?= htmlspecialchars($order['status']) ?></td>
            <td class="py-2 px-4 border-b"><?= htmlspecialchars($order['order_date']) ?></td>
            <td class="py-2 px-4 border-b">$<?= htmlspecialchars(number_format($order['total_amount'] ?? 0, 2)) ?></td>
            <td class="py-2 px-4 border-b"><?= nl2br(htmlspecialchars($order['custom_order'] ?? '')) ?></td>
            <td class="py-2 px-4 border-b">
                <?php if (!empty($order['custom_order'])): ?>
                    $<?= htmlspecialchars(number_format($order['price'] ?? 0, 2)) ?>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
            <td class="py-2 px-4 border-b">
                <?php if (!empty($order['custom_order'])): ?>
                    <button onclick="handleAction(<?= $order['id'] ?>, 'accept')" class="btn btn-accept">Accept</button>
                    <button onclick="handleAction(<?= $order['id'] ?>, 'reject')" class="btn btn-reject">Reject</button>
                <?php else: ?>
                    &mdash;
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <?php endif; ?>
</div>

<script>
function handleAction(orderId, action) {
    const confirmation = confirm(`Are you sure you want to ${action} this custom order price?`);
    if (!confirmation) return;

    const formData = new URLSearchParams();
    formData.append('order_id', orderId);
    formData.append('action', action);

    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData.toString()
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            location.reload();
        }
    })
    .catch(() => {
        alert('An error occurred. Please try again.');
    });
}
</script>

</body>
</html>
