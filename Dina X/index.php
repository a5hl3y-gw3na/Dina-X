<?php
session_start();
require_once 'config.php';

// Redirect users based on session role
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'general_admin' || $_SESSION['role'] === 'superior_admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['role'] === 'client') {
        header('Location: client/dashboard.php');
        exit;
    }
}

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT u.id, u.username, u.password_hash, r.role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role_name'];

            if ($user['role_name'] === 'general_admin' || $user['role_name'] === 'superior_admin') {
                header('Location: admin/dashboard.php');
                exit;
            } elseif ($user['role_name'] === 'client') {
                header('Location: client/dashboard.php');
                exit;
            }
        } else {
            $login_error = 'Invalid username or password.';
        }
    } else {
        $login_error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>DinaX - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background: #fafafa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-wrapper {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .image-side {
            flex: 1;
            background: url('sadza.jpg') center/cover no-repeat;
            min-height: 400px;
        }
        .form-side {
            flex: 1;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-side h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        input {
            padding: 12px 16px;
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            font-size: 14px;
            background: #fafafa;
        }
        input:focus {
            outline: none;
            border-color: #aaa;
        }
        .error-message {
            color: #e74c3c;
            background: #fdecea;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        button {
            padding: 12px;
            border: none;
            background-color: #0095f6;
            color: white;
            font-weight: 600;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #007edc;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        .register-link a {
            color: #0095f6;
            text-decoration: none;
            font-weight: 500;
        }
        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                border-radius: 0;
            }
            .image-side {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="image-side"></div>
        <div class="form-side">
            <h1>DinaX Login</h1>
            <?php if ($login_error): ?>
                <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
            <div class="register-link">
                If you are an unregistered client, <a href="client/register.php">click here</a>.
            </div>
        </div>
    </div>
</body>
</html>
