<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $new_phone = trim($_POST['phone']); // Get the new phone number

    // Check username uniqueness
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$new_username, $user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $msg = "Username already taken.";
    } else {
        // Handle profile pic
        if (!empty($_FILES['profile_pic']['name'])) {
            $uploadDir = '../uploads/';
            $filename = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
            $targetFile = $uploadDir . $filename;
            move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile);
        } else {
            $targetFile = $user['profile_pic'] ?? ''; // Keep current or set to empty if null
        }

        // Update user
        $hashedPassword = !empty($new_password) ? password_hash($new_password, PASSWORD_DEFAULT) : $user['password_hash'];

        $update = $pdo->prepare("UPDATE users SET username = ?, password_hash = ?, profile_pic = ?, phone = ? WHERE id = ?");
        $update->execute([$new_username, $hashedPassword, $targetFile, $new_phone, $user_id]);

        $msg = "Profile updated!";
        // Refresh user info
        $user['username'] = $new_username;
        $user['profile_pic'] = $targetFile;
        $user['password_hash'] = $hashedPassword;
        $user['phone'] = $new_phone; // Update the phone number in the user array
    }
}
?>


<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <title>Your Profile</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
    <style>
        body.dark {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark .bg-white {
            background-color: #1e1e1e;
        }

        body.dark .border-gray-300 {
            border-color: #444 !important;
        }

        body.dark .bg-gray-200 {
            background-color: #2a2a2a;
        }

        body.dark .text-gray-700 {
            color: #e0e0e0 !important;
        }

        /* Smooth transition for dark mode toggle */
        body, input, button, select, textarea {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100">
    

    <main class="container mx-auto mt-12 max-w-3xl bg-white rounded-lg shadow-lg p-8">
       <a href="dashboard.php" class="flex items-center mb-4">
        <button 
            class="flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-md transition-colors duration-300"
        >
            <span class="mr-2">&larr;</span> <!-- Backward arrow -->
            Go Back to Dashboard
        </button>
    </a>
        <h2 class="text-3xl font-bold mb-6 text-center text-gray-800 dark:text-gray-100">Personal Information</h2>

        <?php if ($msg): ?>
            <div class="text-green-600 bg-green-100 border border-green-400 rounded-md p-3 mb-6 text-center font-semibold dark:bg-green-900 dark:text-green-300 dark:border-green-700">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <div class="text-center mb-8">
            <img src="<?= htmlspecialchars($user['profile_pic'] ?? '') ?>" alt="Profile Picture" class="mx-auto rounded-full w-36 h-36 object-cover shadow-md border-4 border-blue-500" />
        </div>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div>
                <label for="username" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">Username</label>
                <input 
                    id="username"
                    type="text" 
                    name="username" 
                    value="<?= htmlspecialchars($user['username'] ?? '') ?>" 
                    required 
                    class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
                />
            </div>

            <div>
                <label for="password" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">New Password</label>
                <input 
                    id="password"
                    type="password" 
                    name="password" 
                    placeholder="Leave blank to keep current password" 
                    class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
                />
            </div>

            <div>
                <label for="profile_pic" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">Upload New Profile Picture</label>
                <input 
                    id="profile_pic"
                    type="file" 
                    name="profile_pic" 
                    accept="image/*" 
                    class="w-full border border-gray-300 rounded-md px-4 py-2 cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
                />
            </div>

            <div>
                <label for="email" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">Email</label>
                <input 
                    id="email"
                    type="text" 
                    readonly 
                    value="<?= htmlspecialchars($user['email'] ?? '') ?>" 
                    class="w-full bg-gray-200 border border-gray-300 rounded-md px-4 py-2 cursor-not-allowed dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400"
                />
            </div>

            <div>
    <label for="phone" class="block mb-1 font-semibold text-gray-700 dark:text-gray-300">Phone</label>
    <!-- Added name attribute for form submission -->
    <input 
        id="phone"
        type="text" 
        name="phone" 
        value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
        class="w-full border border-gray-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200"
    />
</div>

            <div class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                Dining Dollars (D$): 
                <span class="ml-2 text-blue-600 dark:text-blue-400">D$ <?= number_format($user['wallet_balance'] ?? 0, 2) ?></span> 
                <small class="text-gray-500 dark:text-gray-400">(1 D$ = $1 USD)</small>
            </div>

            <button 
                type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-md transition-colors duration-300"
            >
                Save Changes
            </button>
        </form>
    </main>
</body>
</html>
