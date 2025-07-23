<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is superior admin (only superior admin has access)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superior_admin') {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'];
$message = '';

// Handle add, update, retrieve, delete inventory and suppliers
$action = $_GET['action'] ?? '';
$item_id = $_GET['id'] ?? null;
$supplier_action = $_GET['supplier_action'] ?? '';
$supplier_id = $_GET['supplier_id'] ?? null;

// Handle Delete supplier
if ($supplier_action === 'delete' && $supplier_id) {
    $delete_supplier_stmt = $pdo->prepare('DELETE FROM suppliers WHERE id = ?');
    $delete_supplier_stmt->execute([$supplier_id]);
    $message = "Supplier deleted successfully.";
}

// Handle Delete inventory item
if ($action === 'delete' && $item_id) {
    $delete_stmt = $pdo->prepare('DELETE FROM inventory_items WHERE id = ?');
    $delete_stmt->execute([$item_id]);
    $message = 'Inventory item deleted successfully.';
}

// Handle POST requests for add, update, retrieve inventory and add supplier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';

    if ($mode === 'add') {
        $name = trim($_POST['name'] ?? '');
        $quantity = $_POST['quantity'] ?? '';
        $unit = trim($_POST['unit'] ?? '');
        $supplier_id = $_POST['supplier_id'] ?? null;
        $reorder_level = $_POST['reorder_level'] ?? 0;

        if ($name !== '' && is_numeric($quantity) && $quantity >= 0) {
            $insert_stmt = $pdo->prepare('INSERT INTO inventory_items (name, quantity, unit, supplier_id, reorder_level) VALUES (?, ?, ?, ?, ?)');
            $insert_stmt->execute([$name, $quantity, $unit, $supplier_id ?: null, $reorder_level]);
            $message = 'Inventory item added successfully.';
        } else {
            $message = 'Please fill all required fields correctly.';
        }
    }

    if ($mode === 'update') {
        $update_item_id = $_POST['update_id'] ?? null;
        $name = trim($_POST['name'] ?? '');
        $quantity = $_POST['quantity'] ?? '';
        $unit = trim($_POST['unit'] ?? '');
        $supplier_id = $_POST['supplier_id'] ?? null;
        $reorder_level = $_POST['reorder_level'] ?? 0;

        if ($update_item_id && $name !== '' && is_numeric($quantity) && $quantity >= 0) {
            $update_stmt = $pdo->prepare('UPDATE inventory_items SET name = ?, quantity = ?, unit = ?, supplier_id = ?, reorder_level = ? WHERE id = ?');
            $update_stmt->execute([$name, $quantity, $unit, $supplier_id ?: null, $reorder_level, $update_item_id]);
            $message = 'Inventory item updated successfully.';
        } else {
            $message = 'Please fill all required fields correctly.';
        }
    }

    if ($mode === 'retrieve') {
        $retrieve_item_id = $_POST['retrieve_id'] ?? null;
        $retrieve_quantity = $_POST['retrieve_quantity'] ?? 0;

        if ($retrieve_item_id && is_numeric($retrieve_quantity) && $retrieve_quantity > 0) {
            // Fetch current quantity
            $current_item_stmt = $pdo->prepare('SELECT quantity FROM inventory_items WHERE id = ?');
            $current_item_stmt->execute([$retrieve_item_id]);
            $current_item = $current_item_stmt->fetch(PDO::FETCH_ASSOC);

            if ($current_item && $current_item['quantity'] >= $retrieve_quantity) {
                $new_quantity = $current_item['quantity'] - $retrieve_quantity;
                $update_stmt = $pdo->prepare('UPDATE inventory_items SET quantity = ? WHERE id = ?');
                $update_stmt->execute([$new_quantity, $retrieve_item_id]);
                $message = 'Inventory item quantity updated after retrieval.';
            } else {
                $message = 'Insufficient quantity available to retrieve.';
            }
        } else {
            $message = 'Please enter a valid quantity to retrieve.';
        }
    }

    if ($mode === 'add_supplier') {
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $supplier_contact = trim($_POST['supplier_contact'] ?? '');

        if ($supplier_name !== '') {
            $insert_supplier_stmt = $pdo->prepare('INSERT INTO suppliers (name, contact_info) VALUES (?, ?)');
            $insert_supplier_stmt->execute([$supplier_name, $supplier_contact]);
            $message = 'Supplier added successfully.';
        } else {
            $message = 'Supplier name is required.';
        }
    }
}

// Fetch suppliers for selection and display
$suppliers_stmt = $pdo->query('SELECT id, name, contact_info FROM suppliers ORDER BY name');
$suppliers = $suppliers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all inventory items for display and select options
$items_stmt = $pdo->query("SELECT ii.id, ii.name, ii.quantity, ii.unit, COALESCE(s.name, 'N/A') AS supplier_name, ii.supplier_id, ii.reorder_level FROM inventory_items ii LEFT JOIN suppliers s ON ii.supplier_id = s.id ORDER BY ii.id DESC");
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Search functionality for table display
$search_query = $_GET['search'] ?? '';
$search_condition = '';
$params = [];

if ($search_query) {
    $search_condition = 'WHERE ii.name LIKE ?';
    $params[] = "%$search_query%";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Inventory Management - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
    <style>
        /* Base Reset */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            background: #eef2f7;
            font-family: 'Montserrat', sans-serif;
            color: #222;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .container {
            width: 100%;
            max-width: 1100px;
            background: white;
            border-radius: 12px;
            padding: 2.5rem 3rem;
            box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            font-size: 2.8rem;
            font-weight: 700;
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }
        nav {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        nav a {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            color: white;
            padding: 0.75rem 1.8rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 6px 14px rgba(255, 75, 43, 0.4);
            transition: background 0.3s ease, transform 0.3s ease;
            position: relative;
        }
        nav a:hover {
            background: linear-gradient(135deg, #ff4b2b, #ff416c);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(255, 75, 43, 0.7);
            z-index: 10;
        }
        .message {
            max-width: 500px;
            margin: 0 auto 1.8rem;
            background: #28a745;
            color: white;
            padding: 0.9rem 1.2rem;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.6);
            animation: fadeIn 0.7s ease forwards;
            opacity: 0;
        }
        .message.show {
            opacity: 1;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.8rem;
            font-size: 1rem;
            min-width: 700px;
            margin-bottom: 2rem;
        }
        thead th {
            background: #444857;
            color: #fafafa;
            font-weight: 700;
            padding: 1rem 1.5rem;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            user-select: none;
            text-align: left;
        }
        tbody tr {
            background: #f9f9fb;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        tbody tr:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        tbody td {
            padding: 1rem 1.5rem;
            vertical-align: middle;
            color: #444;
        }
        tbody td:first-child {
            font-weight: 600;
            color: #222;
        }
        .actions a {
            background: #ff416c;
            color: white;
            padding: 0.4rem 0.85rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .actions a:hover {
            background: #e33a59;
        }
        form {
            background: linear-gradient(135deg, #ff4b2b, #ff416c);
            padding: 2rem 2.5rem;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(255,75,43,0.4);
            color: white;
            margin-bottom: 2rem;
        }
        form h2 {
            margin-top: 0;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 1.3rem;
            text-align: center;
        }
        form label {
            display: block;
            margin-top: 1.15rem;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        form input[type="text"],
        form input[type="number"],
        form select,
        form input[type="email"],
        form input[type="tel"] {
            width: 100%;
            padding: 0.55rem 0.9rem;
            margin-top: 0.3rem;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: #222;
            background: #fff;
            transition: box-shadow 0.3s ease;
        }
        form input[type="text"]:focus,
        form input[type="number"]:focus,
        form select:focus,
        form input[type="email"]:focus,
        form input[type="tel"]:focus {
            outline: none;
            box-shadow: 0 0 8px 2px rgba(255, 75, 43, 0.7);
        }
        form button {
            margin-top: 2rem;
            width: 100%;
            background: #fafafa;
            color: #ff416c;
            font-weight: 700;
            font-size: 1.25rem;
            border: none;
            border-radius: 50px;
            padding: 0.75rem 0;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(250, 250, 250, 0.8);
            transition: background 0.3s ease, color 0.3s ease;
        }
        form button:hover {
            background: #ff4b2b;
            color: white;
            box-shadow: 0 10px 30px rgba(255, 75, 43, 0.9);
        }
        /* Mode switch buttons */
        .mode-switch {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1.5rem;
            flex-wrap: wrap;
        }
        .mode-switch button {
            background: linear-gradient(135deg, #ff416c, #ff4b2b);
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(255, 75, 43, 0.4);
            transition: background 0.3s ease, transform 0.3s ease;
            user-select: none;
        }
        .mode-switch button:hover,
        .mode-switch button.active {
            background: linear-gradient(135deg, #ff4b2b, #ff416c);
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(255, 75, 43, 0.7);
            z-index: 10;
        }
        /* Show/hide forms */
        form.mode-form {
            display: none;
        }
        form.mode-form.active {
            display: block;
        }
        /* Responsive adjustments */
        @media (max-width: 850px) {
            .container {
                padding: 1.5rem 1.75rem;
            }
            table {
                min-width: 100%;
                font-size: 0.95rem;
            }
            form h2 {
                font-size: 1.7rem;
            }
            form button {
                font-size: 1.1rem;
            }
            .mode-switch {
                flex-direction: column;
                gap: 1rem;
            }
        }
       
        /* Supplier management section */
        .supplier-section {
            margin-top: 3rem;
        }
        .supplier-section h2 {
            color: #ff4b2b;
            margin-bottom: 1rem;
            text-align: center;
        }
        .supplier-list {
            margin-bottom: 1.5rem;
            border-collapse: separate;
            border-spacing: 0 0.8rem;
            width: 100%;
            font-size: 1rem;
        }
        .supplier-list th, .supplier-list td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .supplier-list th {
            background: #444857;
            color: white;
            font-weight: 700;
            border-top-left-radius: 14px;
            border-top-right-radius: 14px;
            user-select: none;
        }
        .supplier-list tbody tr {
            background: #f9f9fb;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .supplier-list tbody tr:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.1);
        }
        .supplier-list tbody td {
            color: #444;
        }
        .supplier-actions a {
            background: #ff416c;
            color: white;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .supplier-actions a:hover {
            background: #e33a59;
        }
    </style>
    <style>
  .search-input::placeholder {
    color: #ff6f61aa;
    font-weight: 500;
  }
  .search-input:focus {
    background-color: #fff;
    box-shadow: 0 0 18px 3px #ff4b2bcc;
  }
  .search-button {
    background: linear-gradient(135deg, #ff416c, #ff4b2b);
    border: none;
    padding: 0 1.3rem;
    border-radius: 9999px;
    font-size: 1.25rem;
    font-weight: 700;
    color: white;
    cursor: pointer;
    box-shadow: 0 6px 24px rgba(255, 75, 43, 0.6);
    transition: background 0.3s ease, box-shadow 0.3s ease, transform 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .search-button:hover,
  .search-button:focus {
    background: linear-gradient(135deg, #ff4b2b, #ff416c);
    box-shadow: 0 8px 30px rgba(255, 75, 43, 0.8);
    transform: scale(1.05);
    outline: none;
  }
</style>
</head>
<body>
    <div class="container">
        <h1>Inventory Management</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        </nav>

        <?php if ($message): ?>
            <div class="message show"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Search Form: Insert this right before your inventory table in body -->
<form method="GET" action="" class="search-form" aria-label="Search inventory items">
  <input 
    type="text" 
    name="search" 
    placeholder="Search by item name" 
    value="<?= htmlspecialchars($search_query ?? '') ?>" 
    class="search-input"
    aria-label="Search inventory items by name"
    autocomplete="off"
  />
  <button type="submit" class="search-button" aria-label="Search">
    🔍
  </button>
</form>

        <table aria-label="Inventory items table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Supplier</th>
                    <th>Reorder Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Display searched or all items
                $displayed_items = $items;
                if ($search_query) {
                    $displayed_items = array_filter($items, fn($item) => stripos($item['name'], $search_query) !== false);
                }
                ?>
                <?php if (empty($displayed_items)): ?>
                    <tr><td colspan="7" style="text-align: center; color: #666; padding: 1.5rem;">No inventory items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($displayed_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['id']) ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['unit'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($item['supplier_name']) ?></td>
                        <td><?= htmlspecialchars($item['reorder_level']) ?></td>
                        <td class="actions">
                            <a href="?action=delete&id=<?= $item['id'] ?>" onclick="return confirm('Are you sure you want to delete this inventory item?');" aria-label="Delete inventory item <?= htmlspecialchars($item['name']) ?>">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Mode switch buttons -->
        <div class="mode-switch" role="tablist" aria-label="Inventory management modes">
            <button type="button" id="btn-add" class="active" role="tab" aria-selected="true" aria-controls="form-add">Add</button>
            <button type="button" id="btn-update" role="tab" aria-selected="false" aria-controls="form-update">Update</button>
            <button type="button" id="btn-retrieve" role="tab" aria-selected="false" aria-controls="form-retrieve">Retrieve</button>
        </div>

        <!-- Add Inventory Form -->
        <form id="form-add" class="mode-form active" method="POST" action="" aria-label="Add new inventory item form">
            <input type="hidden" name="mode" value="add" />
            <h2>Add New Inventory Item</h2>

            <label for="add-name">Name <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <input type="text" id="add-name" name="name" required placeholder="Item name" />

            <label for="add-quantity">Quantity <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <input type="number" id="add-quantity" name="quantity" required min="0" step="any" placeholder="Quantity" />

            <label for="add-unit">Unit</label>
            <input type="text" id="add-unit" name="unit" placeholder="e.g., pcs, kg" />

            <label for="add-supplier_id">Supplier</label>
            <select id="add-supplier_id" name="supplier_id" >
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="add-reorder_level">Reorder Level</label>
            <input type="number" id="add-reorder_level" name="reorder_level" min="0" step="1" value="0" />

            <button type="submit">Add Inventory Item</button>
        </form>

        <!-- Update Inventory Form -->
        <form id="form-update" class="mode-form" method="POST" action="" aria-label="Update existing inventory item form">
            <input type="hidden" name="mode" value="update" />
            <h2>Update Inventory Item</h2>

            <label for="update-id">Select Item to Update <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <select id="update-id" name="update_id" required aria-required="true" aria-describedby="update-item-desc">
                <option value="">-- Select Item --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id'] ?>"
                        data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>"
                        data-quantity="<?= htmlspecialchars($item['quantity'], ENT_QUOTES) ?>"
                        data-unit="<?= htmlspecialchars($item['unit'], ENT_QUOTES) ?>"
                        data-supplier_id="<?= $item['supplier_id'] ?>"
                        data-reorder_level="<?= $item['reorder_level'] ?>"
                    ><?= htmlspecialchars($item['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <span id="update-item-desc" style="font-size: 0.9rem; color: #eee;">Select an item to auto-fill details for update.</span>

            <label for="update-name">Name <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <input type="text" id="update-name" name="name" required placeholder="Item name" />

            <label for="update-quantity">Quantity <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <input type="number" id="update-quantity" name="quantity" required min="0" step="any" placeholder="Quantity" />

            <label for="update-unit">Unit</label>
            <input type="text" id="update-unit" name="unit" placeholder="e.g., pcs, kg" />

            <label for="update-supplier_id">Supplier</label>
            <select id="update-supplier_id" name="supplier_id" >
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="update-reorder_level">Reorder Level</label>
            <input type="number" id="update-reorder_level" name="reorder_level" min="0" step="1" value="0" />

            <button type="submit">Update Inventory Item</button>
        </form>

        <!-- Retrieve Inventory Form -->
        <form id="form-retrieve" class="mode-form" method="POST" action="" aria-label="Retrieve quantity from inventory item form">
            <input type="hidden" name="mode" value="retrieve" />
            <h2>Retrieve Inventory Item</h2>

            <label for="retrieve-id">Select Item to Retrieve <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <select id="retrieve-id" name="retrieve_id" required aria-required="true">
                <option value="">-- Select Item --</option>
                <?php foreach ($items as $item): ?>
                    <option value="<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="retrieve-quantity">Quantity to Retrieve <span aria-hidden="true" style="color:#ff6f61">*</span></label>
            <input type="number" id="retrieve-quantity" name="retrieve_quantity" min="1" required placeholder="Enter quantity" />

            <button type="submit">Retrieve</button>
        </form>

        <!-- Supplier Management Section -->
        <section class="supplier-section" aria-label="Manage suppliers">
            <h2>Manage Suppliers</h2>

            <table class="supplier-list" aria-label="Suppliers list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Info</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr><td colspan="3" style="text-align: center; color: #666; padding: 1rem;">No suppliers found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr>
                                <td><?= htmlspecialchars($supplier['name']) ?></td>
                                <td><?= htmlspecialchars($supplier['contact_info'] ?? '') ?></td>
                                <td class="supplier-actions">
                                    <a href="?supplier_action=delete&supplier_id=<?= $supplier['id'] ?>" onclick="return confirm('Are you sure you want to delete this supplier?');" aria-label="Delete supplier <?= htmlspecialchars($supplier['name']) ?>">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <form method="POST" action="" aria-label="Add new supplier form">
                <input type="hidden" name="mode" value="add_supplier" />
                <h2>Add New Supplier</h2>

                <label for="supplier-name">Name <span aria-hidden="true" style="color:#ff6f61">*</span></label>
                <input type="text" id="supplier-name" name="supplier_name" required placeholder="Supplier name" />

                <label for="supplier-contact">Contact Info</label>
                <input type="text" id="supplier-contact" name="supplier_contact" placeholder="e.g., email, phone" />

                <button type="submit">Add Supplier</button>
            </form>
        </section>

    </div>

    <script>
        // Form mode switching
        const btnAdd = document.getElementById('btn-add');
        const btnUpdate = document.getElementById('btn-update');
        const btnRetrieve = document.getElementById('btn-retrieve');

        const formAdd = document.getElementById('form-add');
        const formUpdate = document.getElementById('form-update');
        const formRetrieve = document.getElementById('form-retrieve');

        const modeButtons = [btnAdd, btnUpdate, btnRetrieve];
        const forms = [formAdd, formUpdate, formRetrieve];

        function setActiveMode(modeIndex) {
            forms.forEach((form, idx) => {
                if(idx === modeIndex) {
                    form.classList.add('active');
                    modeButtons[idx].classList.add('active');
                    modeButtons[idx].setAttribute('aria-selected', 'true');
                } else {
                    form.classList.remove('active');
                    modeButtons[idx].classList.remove('active');
                    modeButtons[idx].setAttribute('aria-selected', 'false');
                }
            });
        }

        btnAdd.addEventListener('click', () => setActiveMode(0));
        btnUpdate.addEventListener('click', () => setActiveMode(1));
        btnRetrieve.addEventListener('click', () => setActiveMode(2));

        // Auto fill update form when item selected
        const updateSelect = document.getElementById('update-id');

        updateSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value === '') {
                document.getElementById('update-name').value = '';
                document.getElementById('update-quantity').value = '';
                document.getElementById('update-unit').value = '';
                document.getElementById('update-supplier_id').value = '';
                document.getElementById('update-reorder_level').value = '0';
            } else {
                document.getElementById('update-name').value = selectedOption.getAttribute('data-name');
                document.getElementById('update-quantity').value = selectedOption.getAttribute('data-quantity');
                document.getElementById('update-unit').value = selectedOption.getAttribute('data-unit');
                document.getElementById('update-supplier_id').value = selectedOption.getAttribute('data-supplier_id');
                document.getElementById('update-reorder_level').value = selectedOption.getAttribute('data-reorder_level');
            }
        });
    </script>
</body>
</html>

