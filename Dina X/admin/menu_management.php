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

// Handle add/edit/delete actions
$action = $_GET['action'] ?? '';
$item_id = $_GET['id'] ?? null;
$message = '';

// Fetch categories for menu items
$categories_stmt = $pdo->query('SELECT id, category_name FROM menu_categories');
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Add new menu item
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $availability = isset($_POST['availability']) ? 1 : 0;

    if ($name && $price && $category_id) {
        $insert_stmt = $pdo->prepare('INSERT INTO menu_items (name, description, price, category_id, availability) VALUES (?, ?, ?, ?, ?)');
        $insert_stmt->execute([$name, $description, $price, $category_id, $availability]);
        $message = 'Menu item added successfully.';

        // Insert notifications for all clients
        $clients_stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'client')");
        $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
        $notification_message = "New menu item '{$name}' has been added.";
        $notif_insert = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        foreach ($clients as $client) {
            $notif_insert->execute([$client['id'], $notification_message]);
        }
    } else {
        $message = 'Please fill all required fields.';
    }
}

// Update menu item
if ($action === 'edit' && $item_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $price = $_POST['price'] ?? '';
    $category_id = $_POST['category_id'] ?? '';
    $availability = isset($_POST['availability']) ? 1 : 0;

    if ($name && $price && $category_id) {
        $update_stmt = $pdo->prepare('UPDATE menu_items SET name = ?, description = ?, price = ?, category_id = ?, availability = ? WHERE id = ?');
        $update_stmt->execute([$name, $description, $price, $category_id, $availability, $item_id]);
        $message = 'Menu item updated successfully.';

        // Insert notifications for all clients
        $clients_stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'client')");
        $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
        $notification_message = "Menu item '{$name}' has been updated.";
        $notif_insert = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        foreach ($clients as $client) {
            $notif_insert->execute([$client['id'], $notification_message]);
        }
    } else {
        $message = 'Please fill all required fields.';
    }
}

// Delete menu item
if ($action === 'delete' && $item_id) {
    // Get the name of the item to be deleted for notification
    $name_stmt = $pdo->prepare('SELECT name FROM menu_items WHERE id = ?');
    $name_stmt->execute([$item_id]);
    $item_name = $name_stmt->fetchColumn();

    $delete_stmt = $pdo->prepare('DELETE FROM menu_items WHERE id = ?');
    $delete_stmt->execute([$item_id]);
    $message = 'Menu item deleted successfully.';

    // Insert notifications for all clients
    if ($item_name) {
        $clients_stmt = $pdo->query("SELECT id FROM users WHERE role_id = (SELECT id FROM roles WHERE role_name = 'client')");
        $clients = $clients_stmt->fetchAll(PDO::FETCH_ASSOC);
        $notification_message = "Menu item '{$item_name}' has been removed.";
        $notif_insert = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        foreach ($clients as $client) {
            $notif_insert->execute([$client['id'], $notification_message]);
        }
    }
}

// Fetch all menu items
$items_stmt = $pdo->query('SELECT mi.id, mi.name, mi.description, mi.price, mi.availability, mc.category_name FROM menu_items mi LEFT JOIN menu_categories mc ON mi.category_id = mc.id ORDER BY mi.id DESC');
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Menu Management - Admin Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen">
  <div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-3xl font-bold text-white">Menu Management</h1>
      <nav class="space-x-4">
        <a href="dashboard.php" class="text-orange-400 hover:underline">Dashboard</a>
        <a href="logout.php" class="text-orange-400 hover:underline">Logout</a>
      </nav>
    </div>

    <?php if ($message): ?>
      <div class="bg-green-600 text-white px-4 py-2 rounded mb-4 shadow"> <?= htmlspecialchars($message) ?> </div>
    <?php endif; ?>

    <div class="overflow-x-auto bg-gray-800 shadow rounded-lg">
      <table class="min-w-full text-left">
        <thead class="bg-gray-700 text-gray-200">
          <tr>
            <th class="px-4 py-3">ID</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Description</th>
            <th class="px-4 py-3">Price</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Available</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <?php foreach ($items as $item): ?>
            <tr>
              <td class="px-4 py-2"><?= htmlspecialchars($item['id']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($item['name']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($item['description']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($item['price']) ?></td>
              <td class="px-4 py-2"><?= htmlspecialchars($item['category_name']) ?></td>
              <td class="px-4 py-2"><?= $item['availability'] ? 'Yes' : 'No' ?></td>
              <td class="px-4 py-2 space-x-2">
                <a href="?action=edit&id=<?= $item['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded">Edit</a>
                <a href="?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Are you sure?');" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="mt-10 bg-gray-800 p-6 rounded shadow">
      <h2 class="text-xl font-semibold mb-4">Add New Menu Item</h2>
      <form method="POST" action="?action=add" class="space-y-4">
        <div>
          <label for="name" class="block mb-1">Name</label>
          <input type="text" id="name" name="name" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white" />
        </div>

        <div>
          <label for="description" class="block mb-1">Description</label>
          <textarea id="description" name="description" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white"></textarea>
        </div>

        <div>
          <label for="price" class="block mb-1">Price</label>
          <input type="number" step="0.01" id="price" name="price" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white" />
        </div>

        <div>
          <label for="category_id" class="block mb-1">Category</label>
          <select id="category_id" name="category_id" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white">
            <option value="">Select Category</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="flex items-center">
          <input type="checkbox" id="availability" name="availability" class="mr-2" checked />
          <label for="availability">Available</label>
        </div>

        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded">Add Menu Item</button>
      </form>
    </div>

    <?php if ($action === 'edit' && $item_id): ?>
      <?php
        $edit_stmt = $pdo->prepare('SELECT * FROM menu_items WHERE id = ?');
        $edit_stmt->execute([$item_id]);
        $edit_item = $edit_stmt->fetch(PDO::FETCH_ASSOC);
      ?>
      <?php if ($edit_item): ?>
        <div class="mt-10 bg-gray-800 p-6 rounded shadow">
          <h2 class="text-xl font-semibold mb-4">Edit Menu Item</h2>
          <form method="POST" action="?action=edit&id=<?= $item_id ?>" class="space-y-4">
            <div>
              <label for="name" class="block mb-1">Name</label>
              <input type="text" id="name" name="name" value="<?= htmlspecialchars($edit_item['name']) ?>" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white" />
            </div>

            <div>
              <label for="description" class="block mb-1">Description</label>
              <textarea id="description" name="description" class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white"><?= htmlspecialchars($edit_item['description']) ?></textarea>
            </div>

            <div>
              <label for="price" class="block mb-1">Price</label>
              <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($edit_item['price']) ?>" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white" />
            </div>

            <div>
              <label for="category_id" class="block mb-1">Category</label>
              <select id="category_id" name="category_id" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 text-white">
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= $category['id'] ?>" <?= $category['id'] == $edit_item['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="flex items-center">
              <input type="checkbox" id="availability" name="availability" class="mr-2" <?= $edit_item['availability'] ? 'checked' : '' ?> />
              <label for="availability">Available</label>
            </div>

            <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2 rounded">Update Menu Item</button>
          </form>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
