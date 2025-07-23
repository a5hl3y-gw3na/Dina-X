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

// Initialize message variables
$message = '';
$message_type = '';

// Fetch roles for assigning
$roles_stmt = $pdo->query('SELECT id, role_name FROM roles');
$roles = $roles_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action and user id from GET
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? null;

// Handle Add User
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $client_id = trim($_POST['client_id'] ?? ''); // Updated to client_id
    $wallet_balance = trim($_POST['wallet_balance'] ?? '0');

    // Validate phone: exactly 10 digits
    if (!preg_match('/^\d{10}$/', $phone)) {
        $message = 'Phone number must be exactly 10 digits.';
        $message_type = 'error';
    }
    // Validate client_id: exactly 8 digits
    elseif (!preg_match('/^\d{8}$/', $client_id)) {
        $message = 'Client ID must be exactly 8 digits.';
        $message_type = 'error';
    }
    // Validate wallet_balance numeric
    elseif (!is_numeric($wallet_balance) || floatval($wallet_balance) < 0) {
        $message = 'Wallet balance must be a positive number.';
        $message_type = 'error';
    }
    elseif ($new_username && $email && $password && $role_id) {
        // Check if username or email exists
        $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
        $check_stmt->execute([$new_username, $email]);
        if ($check_stmt->fetchColumn() > 0) {
            $message = '❌ Username or email already exists. Please choose a unique username or email';
            $message_type = 'error';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role_id, phone, address, client_id, wallet_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $insert_stmt->execute([$new_username, $email, $password_hash, $role_id, $phone, $address, $client_id, $wallet_balance]);
            $message = 'User  added successfully.';
            $message_type = 'success';
        }
    } else {
        $message = 'Please fill all required fields.';
        $message_type = 'error';
    }
}

// Handle Delete User
if ($action === 'delete' && $user_id) {
    // Prevent deleting self
    $self_check_stmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
    $self_check_stmt->execute([$user_id]);
    $user_to_delete = $self_check_stmt->fetchColumn();
    if ($user_to_delete === $username) {
        $message = 'You cannot delete your own account.';
        $message_type = 'error';
    } else {
        $delete_stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $delete_stmt->execute([$user_id]);
        $message = 'User  deleted successfully.';
        $message_type = 'success';
    }
}

// Handle Edit User - Show form
$edit_user = null;
if ($action === 'edit' && $user_id) {
    // Retrieve more fields for role 3 users
    $edit_stmt = $pdo->prepare('SELECT id, username, email, role_id, address, wallet_balance, client_id FROM users WHERE id = ?');
    $edit_stmt->execute([$user_id]);
    $edit_user = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_user) {
        $message = 'User  not found.';
        $message_type = 'error';
        $edit_user = null;
    }
}

// Handle Edit User - Submit
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $edit_id = $_POST['id'];
    $edit_username = trim($_POST['username'] ?? '');
    $edit_email = trim($_POST['email'] ?? '');
    $edit_role_id = $_POST['role_id'] ?? '';
    $edit_address = trim($_POST['address'] ?? '');
    $edit_wallet_balance = $_POST['wallet_balance'] ?? null;
    $edit_client_id = trim($_POST['client_id'] ?? ''); // Updated to client_id
    $edit_password = $_POST['password'] ?? '';

    // Check if the logged-in user is a general admin (role ID 1)
    if ($role === 'general_admin') {
        // Check if the user being edited is a superior admin (role ID 2)
        $check_role_stmt = $pdo->prepare('SELECT role_id FROM users WHERE id = ?');
        $check_role_stmt->execute([$edit_id]);
        $user_role_id = $check_role_stmt->fetchColumn();

        if ($user_role_id == 2) { // If the user being edited is a superior admin
            $message = 'You do not have permission to edit a superior admin.';
            $message_type = 'error';
            $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
        } else {
            // Proceed with update
            if ($edit_role_id == 3) {
                // Validate wallet_balance numeric
                if (!is_numeric($edit_wallet_balance) || floatval($edit_wallet_balance) < 0) {
                    $message = 'Wallet balance must be a positive number.';
                    $message_type = 'error';
                    $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                }
                // Validate client_id: exactly 8 digits
                elseif (!preg_match('/^\d{8}$/', $edit_client_id)) {
                    $message = 'Client ID must be exactly 8 digits.';
                    $message_type = 'error';
                    $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                }
                else {
                    if ($edit_username && $edit_email) {
                        // Check if username/email exists for another user
                        $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?');
                        $check_stmt->execute([$edit_username, $edit_email, $edit_id]);
                        if ($check_stmt->fetchColumn() > 0) {
                            $message = 'Username or email already exists for another user.';
                            $message_type = 'error';
                            $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                        } else {
                            // Update password only if provided
                            if ($edit_password) {
                                $password_hash = password_hash($edit_password, PASSWORD_DEFAULT);
                                $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ?, address = ?, wallet_balance = ?, client_id = ?, password_hash = ? WHERE id = ?');
                                $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_address, $edit_wallet_balance, $edit_client_id, $password_hash, $edit_id]);
                            } else {
                                $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ?, address = ?, wallet_balance = ?, client_id = ? WHERE id = ?');
                                $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_address, $edit_wallet_balance, $edit_client_id, $edit_id]);
                            }
                            $message = 'User  updated successfully.';
                            $message_type = 'success';
                            $edit_user = null;
                            // Reset action to avoid showing edit form after update
                            $action = '';
                        }
                    } else {
                        $message = 'Please fill username and email fields.';
                        $message_type = 'error';
                        $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                    }
                }
            } else {
                // For other roles, previous simpler edit (username, email, role_id)
                if ($edit_username && $edit_email && $edit_role_id) {
                    // Check if username/email exists for another user
                    $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?');
                    $check_stmt->execute([$edit_username, $edit_email, $edit_id]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $message = 'Username or email already exists for another user.';
                        $message_type = 'error';
                        $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id];
                    } else {
                        $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?');
                        $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_id]);
                        $message = 'User  updated successfully.';
                        $message_type = 'success';
                        $edit_user = null;
                        // Reset action to avoid showing edit form after update
                        $action = '';
                    }
                } else {
                    $message = 'Please fill all fields.';
                    $message_type = 'error';
                    $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id];
                }
            }
        }
    } else {
        // If not general admin, allow edit normally with same logic as above
        if ($edit_role_id == 3) {
            // Validate wallet_balance numeric
            if (!is_numeric($edit_wallet_balance) || floatval($edit_wallet_balance) < 0) {
                $message = 'Wallet balance must be a positive number.';
                $message_type = 'error';
                $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
            }
            // Validate client_id: exactly 8 digits
            elseif (!preg_match('/^\d{8}$/', $edit_client_id)) {
                $message = 'Client ID must be exactly 8 digits.';
                $message_type = 'error';
                $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
            }
            else {
                if ($edit_username && $edit_email) {
                    // Check if username/email exists for another user
                    $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?');
                    $check_stmt->execute([$edit_username, $edit_email, $edit_id]);
                    if ($check_stmt->fetchColumn() > 0) {
                        $message = 'Username or email already exists for another user.';
                        $message_type = 'error';
                        $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                    } else {
                        // Update password only if provided
                        if ($edit_password) {
                            $password_hash = password_hash($edit_password, PASSWORD_DEFAULT);
                            $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ?, address = ?, wallet_balance = ?, client_id = ?, password_hash = ? WHERE id = ?');
                            $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_address, $edit_wallet_balance, $edit_client_id, $password_hash, $edit_id]);
                        } else {
                            $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ?, address = ?, wallet_balance = ?, client_id = ? WHERE id = ?');
                            $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_address, $edit_wallet_balance, $edit_client_id, $edit_id]);
                        }
                        $message = 'User  updated successfully.';
                        $message_type = 'success';
                        $edit_user = null;
                        $action = '';
                    }
                } else {
                    $message = 'Please fill username and email fields.';
                    $message_type = 'error';
                    $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id, 'address' => $edit_address, 'wallet_balance' => $edit_wallet_balance, 'client_id' => $edit_client_id];
                }
            }
        } else {
            // For other roles, previous simpler edit (username, email, role_id)
            if ($edit_username && $edit_email && $edit_role_id) {
                // Check if username/email exists for another user
                $check_stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?) AND id != ?');
                $check_stmt->execute([$edit_username, $edit_email, $edit_id]);
                if ($check_stmt->fetchColumn() > 0) {
                    $message = 'Username or email already exists for another user.';
                    $message_type = 'error';
                    $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id];
                } else {
                    $update_stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?');
                    $update_stmt->execute([$edit_username, $edit_email, $edit_role_id, $edit_id]);
                    $message = 'User  updated successfully.';
                    $message_type = 'success';
                    $edit_user = null;
                    $action = '';
                }
            } else {
                $message = 'Please fill all fields.';
                $message_type = 'error';
                $edit_user = ['id' => $edit_id, 'username' => $edit_username, 'email' => $edit_email, 'role_id' => $edit_role_id];
            }
        }
    }
}

// Search functionality
$search_query = $_GET['search'] ?? '';
$search_condition = '';
$params = [];

if ($search_query) {
    $search_condition = 'WHERE u.username LIKE ? OR u.email LIKE ?';
    $params = ["%$search_query%", "%$search_query%"];
}

// Fetch all users with additional fields phone and address
$users_stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.phone, u.address, u.role_id, r.role_name FROM users u JOIN roles r ON u.role_id = r.id $search_condition ORDER BY u.id DESC");
$users_stmt->execute($params);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Management - Admin Panel</title>

<style>
 /* (All your CSS styles unchanged) */
    /* Reset */
    *, *::before, *::after {
        box-sizing: border-box;
    }
    body {
        margin: 0;
        font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
        color: #e0e6f0;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        padding: 2rem;
    }
    .container {
        background: #1a233a;
        box-shadow: 0 2rem 3rem -1.4rem rgba(50,50,93,.25), 0 1rem 1.4rem -1.1rem rgba(0,0,0,.3);
        border-radius: 20px;
        max-width: 1100px;
        width: 100%;
        padding: 3rem 3.5rem 3.5rem;
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }
    h1 {
        font-weight: 800;
        font-size: 2.8rem;
        text-align: center;
        background: linear-gradient(90deg, #ff6f61, #ff9472);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        letter-spacing: 0.05em;
        user-select: none;
        margin-bottom: 0.5rem;
    }
    nav {
        display: flex;
        justify-content: center;
        gap: 2rem;
        font-weight: 600;
        font-size: 1.1rem;
        color: #ff9a8b;
    }
    nav a {
        text-decoration: none;
        padding: 0.6rem 1.4rem;
        border-radius: 8px;
        transition: background-color 0.3s ease;
        color: #ff9a8b;
        box-shadow: inset 0 0 0 0 #ff6f61;
    }
    nav a:hover, nav a:focus {
        background: #ff6f61;
        color: #fff;
        box-shadow: inset 0 0 8px 3px #ff6f61;
        outline: none;
    }
    .message {
        font-weight: 700;
        max-width: 600px;
        margin: 0 auto;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 0 20px;
        user-select: none;
    }
    .message.success {
        background: #28a745cc;
        color: #dff0d8;
        box-shadow: 0 0 15px #28a745cc;
    }
    .message.error {
        background: #dc3545cc;
        color: #f8d7da;
        box-shadow: 0 0 15px #dc3545cc;
    }
    form.search-bar {
        max-width: 400px;
        margin: 0 auto 1.5rem;
        display: flex;
        gap: 0.75rem;
        background: #2f3e66;
        border-radius: 12px;
        padding: 0.5rem 1rem;
        box-shadow: inset 0 0 10px rgba(255,111,97,.3);
    }
    form.search-bar input[type="text"] {
        flex: 1;
        border: none;
        padding: 0.7rem 1rem;
        border-radius: 12px;
        font-size: 1rem;
        color: #eee;
        background: #415573;
        transition: background-color 0.3s ease;
    }
    form.search-bar input[type="text"]::placeholder {
        color: #ccc;
    }
    form.search-bar input[type="text"]:focus {
        outline: none;
        background: #5770b5;
        color: #fff;
        box-shadow: 0 0 6px 2px #ff6f61;
    }
    form.search-bar button {
        background: #ff6f61;
        border: none;
        padding: 0 1.2rem;
        border-radius: 12px;
        color: white;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.3s ease;
        font-size: 1.1rem;
        box-shadow: 0 4px 6px rgba(255,111,97,0.5);
    }
    form.search-bar button:hover, form.search-bar button:focus {
        background: #e94e43;
        box-shadow: 0 6px 10px rgba(233,78,67,0.7);
        outline: none;
    }
    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0 0.9rem;
        background: transparent;
        user-select: none;
    }
    thead tr th {
        background-color: #ff6f61;
        padding: 1rem 1.4rem;
        font-weight: 700;
        font-size: 1rem;
        color: white;
        text-align: left;
        border-radius: 12px 12px 0 0;
        box-shadow: 0 4px 10px rgba(255,111,97,.4);
        letter-spacing: 0.02em;
    }
    tbody tr {
        background: #2b3a5a;
        letter-spacing: 0.01em;
        font-size: 1rem;
        border-radius: 12px;
        box-shadow: 0 0 15px rgba(255,111,97,.2);
        transition: background-color 0.3s ease;
    }
    tbody tr:hover {
        background: #3f517e;
        box-shadow: 0 0 30px #ff6f61cc;
    }
    tbody tr td {
        padding: 1rem 1.4rem;
        color: #d1d9ff;
        vertical-align: middle;
    }
    tbody tr td:first-child {
        font-weight: 700;
        color: #ffcfc4;
        width: 4rem;
    }
    tbody tr td:nth-child(4) {
        font-weight: 600;
        color: #ff9a8b;
        width: 9rem;
    }
    tbody tr td:nth-child(5) {
        width: 12rem;
    }
    a.button, button.button {
        background-color: #ff6f61;
        color: white;
        padding: 0.55rem 1.1rem;
        border-radius: 8px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        font-weight: 700;
        font-size: 0.9rem;
        letter-spacing: 0.05em;
        box-shadow: 0 0 12px #ff6f61bb;
        transition: background-color 0.25s ease, box-shadow 0.25s ease;
        display: inline-block;
        margin-right: 0.4rem;
        user-select: none;
    }
    a.button:hover, button.button:hover, a.button:focus, button.button:focus {
        background-color: #e94e43;
        box-shadow: 0 0 20px #e94e43cc;
        outline: none;
    }
    button.button:disabled {
        background-color: #555;
        cursor: not-allowed;
        box-shadow: none;
    }
    form.add-user, form.edit-user {
        margin-top: 2rem;
        background: #2b3a5a;
        padding: 2rem 2.5rem;
        border-radius: 20px;
        box-shadow: 0 0 30px #ff6f61a0;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }
    form label {
        display: block;
        margin-top: 1rem;
        font-weight: 700;
        font-size: 1.05rem;
        color: #ffcfc4;
        user-select: none;
    }
    form input[type="text"], form input[type="email"], form input[type="password"], form select, form textarea {
        width: 100%;
        padding: 0.7rem 1rem;
        margin-top: 0.4rem;
        border-radius: 14px;
        border: none;
        background: #415573;
        color: #e5e9ff;
        font-size: 1rem;
        transition: background-color 0.3s ease;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        resize: vertical;
        min-height: 3rem;
    }
    form input[type="text"]::placeholder,
    form input[type="email"]::placeholder,
    form input[type="password"]::placeholder {
        color: #aab8ffcc;
    }
    form input[type="text"]:focus, form input[type="email"]:focus, form input[type="password"]:focus, form select:focus, form textarea:focus {
        outline: none;
        background: #5770b5;
        box-shadow: 0 0 10px 2px #ff6f61;
        color: white;
    }
    form button {
        margin-top: 2rem;
        background-color: #ff6f61;
        border: none;
        padding: 0.85rem 2rem;
        color: white;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 800;
        font-size: 1.2rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        user-select: none;
        box-shadow: 0 8px 20px #ff6f61aa;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    form button:hover, form button:focus {
        background-color: #e04e43;
        box-shadow: 0 10px 25px #e04e4388;
        outline: none;
    }
    .btn-cancel {
        background: #404865;
        margin-left: 1rem;
        padding: 0.75rem 1.8rem;
        font-size: 1rem;
        border-radius: 14px;
        box-shadow: 0 5px 15px #404865cc;
        color: #b0b8cc;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        user-select: none;
        border: none;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        line-height: 1.5rem;
    }
    .btn-cancel:hover, .btn-cancel:focus {
        background-color: #2d3650;
        box-shadow: 0 12px 25px #2d3650dd;
        color: #d0d8ff;
        outline: none;
        text-decoration: none;
    }
    /* Modal styles */
    .modal-bg {
        display: none;
        position: fixed;
        top:0; left:0; right:0; bottom:0;
        background: rgba(0,0,0,0.75);
        justify-content: center;
        align-items: center;
        z-index: 1100;
        backdrop-filter: blur(5px);
    }
    .modal {
        background: #243056;
        padding: 2.5rem 3rem;
        border-radius: 20px;
        width: 90%;
        max-width: 440px;
        box-shadow: 0 0 40px #ff6f61dd;
        text-align: center;
        color: #f0f3ff;
        font-weight: 700;
        font-size: 1.2rem;
        user-select: none;
        letter-spacing: 0.03em;
    }
    .modal button {
        margin: 1.2rem 0 0 1rem;
        padding: 0.6rem 1.3rem;
        border-radius: 14px;
        border: none;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        font-size: 1.05rem;
        user-select: none;
    }
    .modal .btn-confirm {
        background-color: #ff6f61;
        color: white;
        box-shadow: 0 0 20px #ff6f61aa;
    }
    .modal .btn-confirm:hover, .modal .btn-confirm:focus {
        background-color: #e94e43;
        box-shadow: 0 0 30px #e94e43cc;
        outline: none;
    }
    .modal .btn-cancel {
        background-color: #55608a;
        color: white;
        box-shadow: 0 0 10px #55608aaa;
    }
    .modal .btn-cancel:hover, .modal .btn-cancel:focus {
        background-color: #434f6a;
        box-shadow: 0 0 20px #434f6aaa;
        outline: none;
    }
    /* Responsive */
    @media (max-width: 720px) {
        .container {
            padding: 2rem 1.5rem 2.5rem;
        }
        table thead tr th, tbody tr td {
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
        }
        form.add-user, form.edit-user {
            padding: 1.5rem 2rem;
            max-width: 100%;
        }
        form.add-user label, form.edit-user label {
            font-size: 1rem;
        }
        form.add-user button, form.edit-user button {
            font-size: 1rem;
        }
        nav {
            flex-direction: column;
            gap: 1rem;
        }
    }

</style>
</head>
<body>
<div class="container" role="main" aria-label="User management panel">
    <h1>User Management</h1>
    <nav role="navigation" aria-label="Main navigation">
        <a href="dashboard.php" tabindex="0">Dashboard</a>
        <a href="logout.php" tabindex="0" aria-describedby="logout-desc">Logout</a>
    </nav>

    <?php if ($message): ?>
        <div role="alert" class="message <?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="GET" action="" class="search-bar" role="search" aria-label="Search users by username or email">
        <input type="text" name="search" placeholder="Search by username or email" value="<?= htmlspecialchars($search_query) ?>" aria-label="Search users" />
        <button type="submit" aria-label="Search">Search</button>
    </form>

    <table role="table" aria-describedby="userTableDesc" cellspacing="0">
        <caption id="userTableDesc" class="sr-only">List of users with ID, username, email, phone, address, role, and action buttons</caption>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Username</th>
                <th scope="col">Email</th>
                <th scope="col">Phone</th>
                <th scope="col">Address</th>
                <th scope="col">Role</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$users): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:#ff6f61;">No users found.</td>
                </tr>
            <?php else: ?>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email']) ?></td>
                <td><?= htmlspecialchars($user['phone'] ?? '') ?></td>
                <td><?= htmlspecialchars($user['address'] ?? '') ?></td>
                <td><?= htmlspecialchars($user['role_name']) ?></td>
                <td>
                    <?php if ($user['username'] !== $username || $role === 'superior_admin'): ?>
                        <a class="button" href="?action=edit&id=<?= $user['id'] ?>" aria-label="Edit user <?= htmlspecialchars($user['username']) ?>">Edit</a>
                        <button class="button btn-delete" data-userid="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['username']) ?>" aria-label="Delete user <?= htmlspecialchars($user['username']) ?>">Delete</button>
                    <?php else: ?>
                        <em aria-label="Current logged in user">Current User</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($action === 'edit' && $edit_user): ?>
    <form class="edit-user" method="POST" action="?action=edit" aria-label="Edit user form">
        <h2>Edit User - ID <?= htmlspecialchars($edit_user['id']) ?></h2>
        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_user['id']) ?>" />

        <label for="edit_username">Username:</label>
        <input type="text" id="edit_username" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" required aria-required="true" />

        <label for="edit_email">Email:</label>
        <input type="email" id="edit_email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required aria-required="true" />

        <label for="edit_role_id">Role:</label>
        <select id="edit_role_id" name="role_id" required aria-required="true">
            <option value="">Select Role</option>
            <?php foreach ($roles as $role_option): ?>
                <option value="<?= $role_option['id'] ?>" <?= $role_option['id'] == $edit_user['role_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($role_option['role_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($edit_user['role_id'] == 3): ?>
            <label for="edit_password">Password (leave blank to keep current):</label>
            <input type="password" id="edit_password" name="password" placeholder="Enter new password if changing" aria-describedby="pwHelp" />

            <label for="edit_address">Address:</label>
            <textarea id="edit_address" name="address" rows="3"><?= htmlspecialchars($edit_user['address'] ?? '') ?></textarea>

            <label for="edit_wallet_balance">Wallet Balance:</label>
            <input type="number" id="edit_wallet_balance" name="wallet_balance" min="0" step="0.01" value="<?= htmlspecialchars($edit_user['wallet_balance'] ?? '0') ?>" />

            <label for="edit_client_id">Client ID:</label>
<input type="text" id="edit_client_id" name="client_id" pattern="\d{8}" title="Client ID must be exactly 8 digits" value="<?= htmlspecialchars($edit_user['client_id'] ?? '') ?>" />

        <?php endif; ?>

        <button type="submit">Save Changes</button>
        <a href="?" class="btn-cancel" role="button" aria-label="Cancel editing">Cancel</a>
    </form>
    <?php else: ?>
   <form class="add-user" method="POST" action="?action=add" aria-label="Add new user form">
    <h2>Add New User</h2>
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required aria-required="true" />

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required aria-required="true" />

    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required aria-required="true" />

    <label for="phone">Phone Number:</label>
    <input type="text" id="phone" name="phone" placeholder="10 digits" pattern="\d{10}" title="Phone number must be exactly 10 digits" required aria-required="true" />

    <label for="address">Address:</label>
    <textarea id="address" name="address" rows="3"></textarea>

    <label for="student_id">Student ID:</label>
    <input type="text" id="student_id" name="student_id" placeholder="8 digits" pattern="\d{8}" title="Student ID must be exactly 8 digits" required aria-required="true" />

    <label for="wallet_balance">Wallet Balance:</label>
    <input type="number" id="wallet_balance" name="wallet_balance" min="0" step="0.01" value="0" />

    <label for="role_id">Role:</label>
    <select id="role_id" name="role_id" required aria-required="true">
        <option value="">Select Role</option>
        <?php foreach ($roles as $role_option): ?>
            <option value="<?= htmlspecialchars($role_option['id']) ?>"><?= htmlspecialchars($role_option['role_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Add User</button>
</form>

    <?php endif; ?>
</div>

<!-- Delete confirmation modal -->
<div class="modal-bg" id="deleteModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDesc">
    <div class="modal">
        <p id="modalDesc">Are you sure you want to delete this user?</p>
        <button class="btn-confirm" id="confirmDelete" aria-label="Confirm delete user">Delete</button>
        <button class="btn-cancel" id="cancelDelete" aria-label="Cancel delete user">Cancel</button>
    </div>
</div>

<script>
    const deleteButtons = document.querySelectorAll('.btn-delete');
    const modalBg = document.getElementById('deleteModal');
    const modalText = document.getElementById('modalDesc');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');

    let userIdToDelete = null;

    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            userIdToDelete = this.getAttribute('data-userid');
            const username = this.getAttribute('data-username');
            modalText.textContent = `Are you sure you want to delete user "${username}"?`;
            modalBg.style.display = 'flex';
            confirmDelete.focus();
        });
    });

    confirmDelete.addEventListener('click', function() {
        if (userIdToDelete) {
            window.location.href = `?action=delete&id=${userIdToDelete}`;
        }
    });

    cancelDelete.addEventListener('click', function() {
        modalBg.style.display = 'none';
        userIdToDelete = null;
    });

    // Close modal on clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modalBg) {
            modalBg.style.display = 'none';
            userIdToDelete = null;
        }
    });

    // Keyboard accessibility for modal (close with ESC)
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modalBg.style.display === 'flex') {
            modalBg.style.display = 'none';
            userIdToDelete = null;
        }
    });
</script>
</body>
</html>

