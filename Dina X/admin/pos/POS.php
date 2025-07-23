<?php
session_start();
require_once '../../config.php';

// Check user role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../../index.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch menu items
$menu_items_stmt = $pdo->query('SELECT id, name, description, price, availability, calories, protein, fat, carbs FROM menu_items WHERE availability = 1');
$menu_items = $menu_items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle optional client ID search
$search_client_id = '';
$whereClause = '';
$params = [];
if (isset($_GET['search_client_id']) && is_numeric($_GET['search_client_id'])) {
    $search_client_id = $_GET['search_client_id'];
    $whereClause = 'AND o.client_id = ?';
    $params[] = $search_client_id;
}

// Fetch today's POS transactions with their status and user info (including client id)
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$sql = '
    SELECT o.id, o.order_date, o.total_amount, o.status, o.client_id
    FROM orders o
    WHERE o.order_date BETWEEN ? AND ?
    ' . $whereClause . '
    ORDER BY o.order_date DESC
';
$params = array_merge([$today_start, $today_end], $params);
$transactions_stmt = $pdo->prepare($sql);
$transactions_stmt->execute($params);
$transactions = $transactions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission for order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_order'])) {
    $username_input = $_POST['username']; // Username input
    $order_notes = $_POST['order_notes'] ?? '';
    $custom_order_price = isset($_POST['custom_order_price']) ? (float)$_POST['custom_order_price'] : 0;
    $dining_hall = $_POST['dining_hall'] ?? null;

    // Fetch client ID from users table based on username
    $user_stmt = $pdo->prepare('SELECT client_id, wallet_balance FROM users WHERE username = ?');
    $user_stmt->execute([$username_input]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "<script>alert('Username not found. Please enter a valid username.');</script>";
        exit;
    }

    $client_id = $user['client_id']; // Get client ID from user data

    // Calculate total amount
    $total_amount = 0;
    foreach ($_POST['items'] as $item) {
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];
        if ($quantity > 0) {
            $total_amount += $quantity * $price;
        }
    }
    // Add custom order price if provided and > 0
    if ($custom_order_price > 0) {
        $total_amount += $custom_order_price;
    }

    // Determine payment method
    $payment_method = $_POST['payment_method'];

    // Default status can be 'completed' for successful processing here
    $status = 'completed';

    // Use current datetime for order_date
    $order_date = date('Y-m-d H:i:s');

    // Insert into orders table: client_id, status, order_date, total_amount, dining_hall, order_notes
    $stmt = $pdo->prepare('INSERT INTO orders (client_id, status, order_date, total_amount, dining_hall, order_notes) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$client_id, $status, $order_date, $total_amount, $dining_hall, $order_notes]);
    $order_id = $pdo->lastInsertId();

    // Insert order items
    foreach ($_POST['items'] as $item_id => $item) {
        $quantity = (int)$item['quantity'];
        $price = (float)$item['price'];
        if ($quantity > 0) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
            $stmt->execute([$order_id, $item_id, $quantity, $price]);
        }
    }
    // Insert custom order item if custom_order_price > 0
    if ($custom_order_price > 0) {
        $stmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)');
        // Use menu_item_id 0 to indicate custom order
        $stmt->execute([$order_id, 0, 1, $custom_order_price]);
    }

// Handle payment
if ($payment_method === 'dina_dollars') {
    // Fetch the user's current wallet balance
    $user_stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE client_id = ?');
    $user_stmt->execute([$client_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && round($user['wallet_balance'], 2) >= round($total_amount, 2)) {
        // Deduct the total amount from the wallet balance
        $new_balance = $user['wallet_balance'] - $total_amount;
        $update_stmt = $pdo->prepare('UPDATE users SET wallet_balance = ? WHERE client_id = ?');
        $update_stmt->execute([$new_balance, $client_id]);

        // Record the payment as Dina Dollars with completed status
        $stmt = $pdo->prepare('INSERT INTO payments (order_id, cash_received, change_given, payment_method, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$order_id, $total_amount, 0, 'dina_dollars', 'completed']);

        // Redirect or display a message instead of generating a PDF
        echo "<script>alert('Payment successful using Dina Dollars. No PDF receipt will be generated.');</script>";
        exit; // Stop further execution
    } else {
        // Handle insufficient balance
        echo "<script>alert('Insufficient Dina Dollars balance. Please choose another payment method.');</script>";
        exit;
    }
} else {
    // Handle cash payment
    $cash_received = (float)$_POST['cash_received'];
    $change_given = $cash_received - $total_amount;

    // Insert payment record with completed status
    $stmt = $pdo->prepare('INSERT INTO payments (order_id, cash_received, change_given, payment_method, status) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$order_id, $cash_received, $change_given, 'cash', 'completed']);
}


// Generate PDF receipt
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="receipt_order_' . $order_id . '.pdf"');
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
header('Pragma: no-cache'); // HTTP 1.0
header('Expires: 0'); // Proxies

// (Continue with the PDF generation code...)


    // Simple PDF content with improved formatting
    $pdf_content = "
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >>
endobj
4 0 obj
<< /Length 5 0 R >>
stream
BT
/F1 24 Tf
100 700 Td
(Dina X - Payment Receipt) Tj
ET
BT
/F1 16 Tf
100 650 Td
(----------------------------------------) Tj
ET
BT
/F1 12 Tf
100 620 Td
(Order ID: {$order_id}) Tj
ET
BT
/F1 12 Tf
100 600 Td
(Date: {$order_date}) Tj
ET
BT
/F1 12 Tf
100 580 Td
(Admin: {$username}) Tj
ET
BT
/F1 12 Tf
100 560 Td
(Client ID: {$client_id}) Tj
ET
BT
/F1 12 Tf
100 540 Td
(Order Notes: {$order_notes}) Tj
ET
BT
/F1 12 Tf
100 520 Td
(Dining Hall: {$dining_hall}) Tj
ET
BT
/F1 16 Tf
100 500 Td
(----------------------------------------) Tj
ET
BT
/F1 12 Tf
100 480 Td
(Items:) Tj
ET
";

    foreach ($_POST['items'] as $item_id => $item) {
        $quantity = (int)$item['quantity'];
        if ($quantity > 0) {
            $line_total = $quantity * $item['price'];
            $pdf_content .= "
BT
/F1 12 Tf
100 460 Td
(" . htmlspecialchars($item['name']) . " x " . $quantity . " - \$$line_total) Tj
ET
";
        }
    }

    if ($custom_order_price > 0) {
        $pdf_content .= "
BT
/F1 12 Tf
100 440 Td
(Custom Order x 1 - \$$custom_order_price) Tj
ET
";
    }

    $pdf_content .= "
BT
/F1 16 Tf
100 420 Td
(----------------------------------------) Tj
ET
BT
/F1 12 Tf
100 400 Td
(Total: \$$total_amount) Tj
ET
BT
/F1 12 Tf
100 380 Td
(Cash Received: \$$cash_received) Tj
ET
BT
/F1 12 Tf
100 360 Td
(Change Given: \$$change_given) Tj
ET
BT
/F1 16 Tf
100 340 Td
(Thank you for your order!) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000060 00000 n 
0000000117 00000 n 
0000000210 00000 n 
0000000410 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
510
%%EOF
";

    echo $pdf_content;
    exit; // Ensure no further output is sent
}


// Helper function for status icon & label
function status_label($status) {
    if (strtolower($status) === 'completed') {
        $icon = '✅'; // check mark
        $label = 'Completed';
    } else if (strtolower($status) === 'failed') {
        $icon = '❌'; // cross mark
        $label = 'Failed';
    } else {
        $icon = 'ℹ️'; // info
        $label = ucfirst($status);
    }
    return "<span title='$label'>$icon $label</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        /* Reset and base styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f2f5;
            color: #333;
            padding: 40px 20px;
            line-height: 1.6;
        }
        h1 {
            text-align: center;
            font-weight: 600;
            margin-bottom: 40px;
            color: #2c3e50;
        }
        h2 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
            margin-top: 40px;
        }
        /* Success message */
        .success-message {
            max-width: 700px;
            margin: 0 auto 30px auto;
            background-color: #dff0d8;
            color: #3c763d;
            border-radius: 8px;
            padding: 15px 20px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        /* Form container */
        form {
            max-width: 700px;
            background: #fff;
            margin: 0 auto 50px auto;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.1);
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        select, input[type="number"], textarea, input[type="text"] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1.8px solid #ddd;
            font-size: 16px;
            transition: border-color 0.3s ease;
            resize: vertical;
        }
        select:focus, input[type="number"]:focus, textarea:focus, input[type="text"]:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
        }
        /* Menu items layout */
        #menu-items {
            display: grid;
            grid-template-columns: repeat(auto-fit,minmax(300px,1fr));
            gap: 20px;
        }
        .menu-item {
            background: #f9fbfd;
            border: 1px solid #e1e8f0;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: box-shadow 0.3s ease;
        }
        .menu-item:hover {
            box-shadow: 0 6px 15px rgba(52, 152, 219, 0.3);
        }
        .menu-item h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .menu-item p {
            font-size: 0.9rem;
            color: #555;
            margin-bottom: 6px;
            line-height: 1.3;
            flex-grow: 1;
        }
        .nutrition {
            font-size: 0.85rem;
            color: #777;
            margin-bottom: 10px;
        }
        .quantity-input {
            margin-top: 10px;
            align-self: flex-start;
            width: 80px;
            font-size: 1rem;
            padding: 6px 10px;
            border: 1.5px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.3s ease;
        }
        .quantity-input:focus {
            border-color: #3498db;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
            outline: none;
        }
        /* Buttons */
        button, .add-funds-btn {
            background-color: #3498db;
            border: none;
            color: white;
            font-size: 18px;
            font-weight: 600;
            padding: 15px 25px;
            border-radius: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            width: 100%;
            margin-top: 10px;
            box-shadow: 0 4px 14px rgba(52,152,219,0.4);
            user-select: none;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        button:hover, .add-funds-btn:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(41,128,185,0.5);
        }
        /* Transaction table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        table th {
            background-color: #3498db;
            color: #fff;
        }
        table tbody tr:nth-child(even) {
            background-color: #f7f9fc;
        }
        /* Responsive tweaks */
        @media (max-width: 768px) {
            form {
                padding: 20px;
            }
            button, .add-funds-btn {
                font-size: 16px;
                padding: 12px 20px;
            }
            table th, table td {
                padding: 10px;
            }
        }
        @media (max-width: 480px) {
            #menu-items {
                grid-template-columns: 1fr;
            }
            button, .add-funds-btn {
                font-size: 16px;
            }
            table th, table td {
                font-size: 14px;
                padding: 8px;
            }
        }
        .nav-button {
  display: inline-block;
  padding: 0.6rem 1.2rem;
  margin: 0.5rem;
  background-color: #007BFF;
  color: #fff;
  text-decoration: none;
  border-radius: 6px;
  border: 1px solid transparent;
  font-weight: 500;
  font-size: 1rem;
  transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

.nav-button:hover,
.nav-button:focus {
  background-color: #0056b3;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.3);
  outline: none;
}

.nav-button.logout {
  background-color: #dc3545;
}

.nav-button.logout:hover,
.nav-button.logout:focus {
  background-color: #a71d2a;
  box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.3);
}

    </style>
</head>
<body>
    <h1>Point of Sale</h1>
    <nav role="navigation" aria-label="Main navigation">
  <a href="../dashboard.php" class="nav-button" tabindex="0">Dashboard</a>
  <a href="../logout.php" class="nav-button logout" tabindex="0" aria-describedby="logout-desc">Logout</a>
</nav>
<small id="logout-desc" hidden>This will log you out of your session</small>



    <?php if (isset($_GET['success'])): ?>
        <div class="success-message">Order processed successfully! Receipt should download now.</div>
    <?php endif; ?>

    <!-- Order form -->
    <form method="POST" action="" style="max-width:700px; margin: 0 auto;">
        <label for="username">Enter Username:</label>
        <input type="text" name="username" id="username" required placeholder="Enter Username" />

        <h2>Menu Items</h2>
        <div id="menu-items" role="list">
            <?php foreach ($menu_items as $item): ?>
                                 <div class="menu-item" role="listitem" tabindex="0" aria-label="<?= htmlspecialchars($item['name']) ?>">
                    <h3><?= htmlspecialchars($item['name']) ?></h3>
                    <p><?= htmlspecialchars($item['description']) ?></p>
                    <p class="nutrition">Price: $<?= number_format($item['price'], 2) ?></p>
                    <p class="nutrition">Calories: <?= htmlspecialchars($item['calories'] ?? 'N/A') ?> kcal</p>
                    <p class="nutrition">Protein: <?= htmlspecialchars($item['protein'] ?? 'N/A') ?> g</p>
                    <p class="nutrition">Fat: <?= htmlspecialchars($item['fat'] ?? 'N/A') ?> g</p>
                    <p class="nutrition">Carbs: <?= htmlspecialchars($item['carbs'] ?? 'N/A') ?> g</p>
                    <input
                        type="number"
                        name="items[<?= $item['id'] ?>][quantity]"
                        min="0"
                        value="0"
                        class="quantity-input"
                        aria-label="Quantity for <?= htmlspecialchars($item['name']) ?>"
                    />
                    <input type="hidden" name="items[<?= $item['id'] ?>][price]" value="<?= htmlspecialchars($item['price']) ?>" />
                    <input type="hidden" name="items[<?= $item['id'] ?>][name]" value="<?= htmlspecialchars($item['name']) ?>" />
                </div>
            <?php endforeach; ?>
        </div>

        <h2>Custom Order</h2>
        <textarea name="order_notes" placeholder="Enter custom order details..." rows="4"></textarea>
        <label for="custom_order_price">Custom Order Price (optional):</label>
        <input type="number" name="custom_order_price" id="custom_order_price" min="0" step="0.01" placeholder="Enter price for custom order" />

        <h2>Dining Hall</h2>
        <label for="dining_hall">Dining Hall:</label>
<select id="dining_hall" name="dining_hall" required>
    <option value="" disabled <?= empty($_POST['dining_hall']) ? 'selected' : '' ?>>Select Dining Hall</option>
    <option value="Main Campus" <?= (($_POST['dining_hall'] ?? '') == 'Main Campus') ? 'selected' : '' ?>>Main Campus</option>
    <option value="Mt Darwin" <?= (($_POST['dining_hall'] ?? '') == 'Mt Darwin') ? 'selected' : '' ?>>Mt Darwin</option>
    <option value="New Dining Hall" <?= (($_POST['dining_hall'] ?? '') == 'New Dining Hall') ? 'selected' : '' ?>>New Dining Hall</option>
</select>


        <h2>Payment</h2>
        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="cash">Cash</option>
            <option value="dina_dollars">Dina Dollars</option>
        </select>

        <label for="cash_received">Cash Received:</label>
        <input type="number" name="cash_received" id="cash_received" min="0" step="0.01" required />

        <script>
            // Toggle required attribute on 'cash_received' based on payment method selected
            function toggleCashReceivedRequirement() {
                const paymentMethod = document.getElementById('payment_method').value;
                const cashReceivedInput = document.getElementById('cash_received');

                if (paymentMethod === 'dina_dollars') {
                    cashReceivedInput.required = false;
                    cashReceivedInput.value = '';
                } else {
                    cashReceivedInput.required = true;
                }
            }

            document.getElementById('payment_method').addEventListener('change', toggleCashReceivedRequirement);

            // Set initial state on page load
            toggleCashReceivedRequirement();
        </script>

        <button type="submit" name="process_order" style="margin-top: 10px;">Process Order</button>
    </form>

    <!-- Search form -->
    <form method="GET" action="" style="max-width:700px; margin: 20px auto;">
        <label for="search_client_id">Search Transactions by Client ID:</label>
        <input type="text" name="search_client_id" id="search_client_id" value="<?= htmlspecialchars($search_client_id) ?>" placeholder="Enter Client ID" />
        <button type="submit">Search</button>
    </form>

    <!-- POS Transactions Table -->
    <h2 style="max-width:700px; margin: 40px auto 15px auto;">Today's  Transactions</h2>
    <?php if (count($transactions) === 0): ?>
        <p style="max-width:700px; margin: 0 auto 30px auto;">No POS transactions found for today.</p>
    <?php else: ?>
        <table style="max-width:700px; margin: 0 auto 40px auto;">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Client ID</th>
                    <th>Total Amount ($)</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?= htmlspecialchars($trans['id']); ?></td>
                        <td><?= htmlspecialchars($trans['order_date']); ?></td>
                        <td><?= htmlspecialchars($trans['client_id']); ?></td>
                        <td><?= number_format($trans['total_amount'], 2); ?></td>
                        <td><?= status_label($trans['status']); ?></td>
                        <td>
                            <form method="POST" action="edit_order.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($trans['id']); ?>" />
                                <button type="submit" class="edit-btn" title="Edit Order">Edit</button>
                            </form>
                            <form method="POST" action="delete_order.php" onsubmit="return confirm('Are you sure you want to delete this order?');" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($trans['id']); ?>" />
                                <button type="submit" title="Delete Order">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Add Funds Button -->
    <a href="../add_funds.php" class="add-funds-btn" role="button" aria-label="Add funds to wallet" style="max-width:700px; display:block; margin: 0 auto 30px auto; text-align:center;">Add Funds</a>

</body>
</html>
