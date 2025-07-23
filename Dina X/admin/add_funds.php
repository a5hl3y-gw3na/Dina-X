<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../index.php');
    exit;
}

$message = '';

// Fetch clients
$clients_stmt = $pdo->query('SELECT id, username, wallet_balance FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = "client") ORDER BY username ASC');
$clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;

    if ($client_id && is_numeric($amount) && $amount > 0) {
        try {
            $pdo->beginTransaction();

            // Update wallet balance
            $update_stmt = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
            $update_stmt->execute([$amount, $client_id]);

            // Insert wallet transaction
            $insert_stmt = $pdo->prepare('INSERT INTO wallet_transactions (user_id, amount, transaction_type, description) VALUES (?, ?, "credit", ?)');
            $insert_stmt->execute([$client_id, $amount, 'Admin added funds']);

            $pdo->commit();
            $message = 'Funds added successfully.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Failed to add funds: ' . $e->getMessage();
        }
    } else {
        $message = 'Please select a client and enter a valid amount.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Add Funds to Client Wallet - Admin Panel</title>
    <link rel="stylesheet" href="../styles.css" />
    <style>
       body {
  background: #f0f8ff;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
}

.container {
  max-width: 600px;
  margin: 3rem auto;
  background: #ffffff;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 8px 20px rgba(0, 123, 255, 0.1);
  color: #003366;
}

h1 {
  text-align: center;
  color: #007BFF;
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-top: 1rem;
  color: #003366;
  font-weight: 500;
}

select, input {
  width: 100%;
  padding: 0.7rem;
  margin-top: 0.3rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  background: #f9f9f9;
  color: #003366;
  font-size: 1rem;
}

button {
  margin-top: 1.5rem;
  width: 100%;
  background-color: #007BFF;
  border: none;
  padding: 0.75rem;
  color: white;
  border-radius: 8px;
  cursor: pointer;
  font-size: 1rem;
  transition: background-color 0.3s ease;
}

button:hover {
  background-color: #0056b3;
}

.message {
  margin-top: 1rem;
  padding: 0.75rem;
  background-color: #e9f7fe;
  border: 1px solid #b8e2f8;
  border-radius: 6px;
  color: #0056b3;
  font-weight: 500;
}

nav {
  text-align: center;
  margin-bottom: 2rem;
}

nav a {
  color: #007BFF;
  text-decoration: none;
  margin: 0 1rem;
  font-weight: 500;
}

nav a:hover {
  text-decoration: underline;
}

    </style>
</head>
<body>
    <div class="container">
        <h1>Add Funds to Client Wallet</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="client_id">Select Client:</label>
            <select id="client_id" name="client_id" required>
                <option value="">-- Select Client --</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['username']) ?> (Balance: $<?= number_format($client['wallet_balance'], 2) ?>)</option>
                <?php endforeach; ?>
            </select>

            <label for="amount">Amount to Add:</label>
            <input type="number" id="amount" name="amount" min="0.01" step="0.01" required />

            <button type="submit">Add Funds</button>
        </form>
    </div>
</body>
</html>
