<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Validate user role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Initialize total amount
$total_amount = 0;

// Validate cart data
if (isset($data['cart']) && is_array($data['cart'])) {
    foreach ($data['cart'] as $item) {
        if (!isset($item['id'], $item['quantity']) || $item['quantity'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
            exit;
        }

        // Get price from DB
        $stmt = $pdo->prepare('SELECT price FROM menu_items WHERE id = ? AND availability = 1');
        $stmt->execute([$item['id']]);
        $price = $stmt->fetchColumn();

        if ($price === false) {
            echo json_encode(['success' => false, 'message' => 'Menu item not available']);
            exit;
        }
        $total_amount += $price * $item['quantity'];
    }
}

// Dining hall field
$dining_hall = isset($data['dining_hall']) ? $data['dining_hall'] : null;
// Validate dining hall selection
$valid_dining_halls = ['Main Campus', 'Mt Darwin', 'New Dining Hall'];
if (!in_array($dining_hall, $valid_dining_halls)) {
    echo json_encode(['success' => false, 'message' => 'Invalid dining hall selected']);
    exit;
}

// Custom order details field (free text)
$custom_order_details = isset($data['custom_order']) ? trim($data['custom_order']) : '';

// Payment method
$payment_method = $data['payment_method'] ?? '';

// Determine order status
$order_status = 'Preparing';
if (!empty($custom_order_details)) {
    // If there is a custom order, set status to 'Pending Price' or keep it as 'Preparing'
    $order_status = 'Pending Price';
}

// Check wallet balance if payment method is wallet
if ($payment_method === 'wallet') {
    $wallet_stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $wallet_stmt->execute([$user_id]);
    $wallet_balance = $wallet_stmt->fetchColumn();

    if ($wallet_balance === false) {
        echo json_encode(['success' => false, 'message' => 'User  not found']);
        exit;
    }

    if ($wallet_balance < $total_amount) {
        echo json_encode(['success' => false, 'message' => 'Insufficient wallet balance']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Insert order with dining hall and custom order details
    $order_stmt = $pdo->prepare('INSERT INTO orders (user_id, status, total_amount, dining_hall, custom_order) VALUES (?, ?, ?, ?, ?)');
    $order_stmt->execute([$user_id, $order_status, $total_amount, $dining_hall, $custom_order_details]);
    $order_id = $pdo->lastInsertId();

    // Insert order items if there are any
    if (!empty($data['cart'])) {
        $order_item_stmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
        foreach ($data['cart'] as $item) {
            $stmt = $pdo->prepare('SELECT price FROM menu_items WHERE id = ?');
            $stmt->execute([$item['id']]);
            $price = $stmt->fetchColumn();
            $order_item_stmt->execute([$order_id, $item['id'], $item['quantity'], $price]);
        }
    }

    // Insert payment record
    $payment_stmt = $pdo->prepare('INSERT INTO payments (order_id, user_id, amount, payment_method, status) VALUES (?, ?, ?, ?, ?)');
    $payment_stmt->execute([$order_id, $user_id, $total_amount, $payment_method, $payment_method === 'wallet' ? 'Completed' : 'Pending']);

    // Deduct wallet balance if payment method is wallet
    if ($payment_method === 'wallet') {
        $update_wallet_stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?');
        $update_wallet_stmt->execute([$total_amount, $user_id]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
}
?>