<?php
session_start();
require_once '../config.php';

$message = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $address = $_POST['address'] ?? '';
    $residence = $_POST['residence'] ?? '';
    $client_id = $_POST['client_id'] ?? '';
    $level = $_POST['level'] ?? '';

    if ($username && $email && $password && $confirm_password && $phone_number && $address && $residence && $client_id && $level) {
        if ($password !== $confirm_password) {
            $message = '❌ Passwords do not match.';
        } elseif (!preg_match('/^\d{10}$/', $phone_number)) {
            $message = '❌ Phone number must be exactly 10 digits.';
        } elseif (!preg_match('/^\d{8}$/', $client_id)) {
            $message = '❌ Client ID must be exactly 8 digits.';
        } else {
            // Check if username or email already exists
            $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
            $check_stmt->execute([$username, $email]);
            if ($check_stmt->fetchColumn() > 0) {
                $message = '❌ Username or email already exists. Please choose a unique username.';
            } else {
                // Proceed with registration
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $role_stmt = $pdo->prepare('SELECT id FROM roles WHERE role_name = ?');
                $role_stmt->execute(['client']);
                $role_id = $role_stmt->fetchColumn();

                $insert_stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id, phone, address, residence, client_id, level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $insert_stmt->execute([
                    $username, $email, $password_hash, $role_id,
                    $phone_number, $address, $residence, $client_id, $level
                ]);

                $message = '✅ Registration successful. <a href="../index.php">Login here</a>.';
            }
        }
    } else {
        $message = '❌ Please fill in all fields.';
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Client Registration</title>
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,800" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
        background: url('https://images.unsplash.com/photo-1506748686214-e9df14d4d9d0?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=MnwzNjUyOXwwfDF8c2VhcmNofDF8fGJhY2tncm91bmR8ZW58MHx8fHwxNjYyMjY5MjY0&ixlib=rb-1.2.1&q=80&w=1080') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        font-family: 'Montserrat', sans-serif;
        margin: 0;
    }

    .container {
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 15px;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 400px;
        text-align: center;
    }

    h1 {
        font-weight: bold;
        margin-bottom: 20px;
        color: #333;
    }

    input {
        background-color: #f0f0f0;
        border: none;
        padding: 12px 15px;
        margin: 10px 0;
        width: 100%;
        border-radius: 25px;
        transition: background-color 0.3s;
    }

    input:focus {
        background-color: #e0e0e0;
        outline: none;
    }

    button {
        border-radius: 25px;
        border: none;
        background-color: #4CAF50;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        padding: 12px 0;
        margin-top: 10px;
        width: 100%;
        cursor: pointer;
        transition: background-color 0.3s;
    }

    button:hover {
        background-color: #45a049;
    }

    .message {
        margin-top: 15px;
        font-weight: bold;
        color: #e74c3c;
        padding: 10px;
        text-align: center;
    }

    .message a {
        color: #007BFF;
        text-decoration: none;
    }

    .message a:hover {
        text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>Create Account</h1>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required />
        <input type="email" name="email" placeholder="Email" required />
        <input type="password" name="password" placeholder="Password" required />
        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
        <input type="text" name="phone_number" placeholder="Phone Number (10 digits)" required />
        <input type="text" name="address" placeholder="Address" required />
        <input type="text" name="residence" placeholder="Residence" required />
        <input type="text" name="client_id" placeholder="Client ID (8 digits)" required />
        <input type="text" name="level" placeholder="Education Level" required />
        <button type="submit">Register</button>
        <div class="message"><?= $message ?></div>
    </form>
    <p>
        <a href="../index.php" style="color: #007BFF; text-decoration: none;">If you are already registered please click here</a>.
    </p>
</div>

</body>
</html>
