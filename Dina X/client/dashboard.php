<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

// Fetch menu items with nutrition info
$items_stmt = $pdo->query('SELECT mi.id, mi.name, mi.description, mi.price, mi.availability, mc.category_name, mi.calories, mi.protein, mi.fat, mi.carbs FROM menu_items mi LEFT JOIN menu_categories mc ON mi.category_id = mc.id WHERE mi.availability = 1 ORDER BY mi.name ASC');
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch unique categories for filtering
$categories_stmt = $pdo->query('SELECT DISTINCT mc.category_name FROM menu_categories mc');
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch favourite meals (rating 4.5 and above)
$favourites_stmt = $pdo->query('SELECT mi.id, mi.name, AVG(r.rating) as average_rating FROM menu_items mi LEFT JOIN reviews r ON mi.id = r.menu_item_id GROUP BY mi.id HAVING average_rating >= 4.5');
$favourites = $favourites_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user's wallet balance
$wallet_stmt = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
$wallet_stmt->execute([$user_id]);
$wallet_balance = $wallet_stmt->fetchColumn();

// Fetch last two notifications for the user
$notifications_stmt = $pdo->prepare('SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 2');
$notifications_stmt->execute([$user_id]);
$notifications = $notifications_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Client Dashboard - Dina X</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap" rel="stylesheet" />
    <style>
        /* Existing styles... */
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Light background */
            color: #343a40; /* Dark text */
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        header {
            display: flex;
            flex-direction: column; /* Stack items vertically */
            justify-content: space-between; /* Space between items */
            align-items: flex-start; /* Align items to the start */
            margin-bottom: 2rem; /* Space below the header */
            padding: 1rem 2rem; /* Padding around the header */
            background: #007bff; /* Blue background */
            border-radius: 20px; /* Rounded corners */
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.5); /* Shadow effect */
            color: white; /* White text */
        }

        .welcome {
            font-size: 1.5em; /* Larger font size for welcome message */
            margin-bottom: 15px; /* Space below the welcome message */
            font-weight: 800;
            font-size: 1.8rem;
        }

        nav {
            display: flex; /* Use flexbox for navigation */
            gap: 20px; /* Space between navigation links */
        }

        nav a {
            text-decoration: none; /* Remove underline from links */
            color: white; /* White text for links */
            display: flex; /* Use flexbox for icon and text */
            align-items: center; /* Center icon and text vertically */
            transition: color 0.3s; /* Smooth color transition on hover */
            margin-left: 1.5rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.2rem;
            border-radius: 15px;
            color: #007bff; /* Blue text */
        }

        nav a:hover {
            color: #e0e0e0; /* Lighter color on hover */
            background-color: rgba(255, 255, 255, 0.3);
        }

        .nav-icon {
            width: 24px; /* Set a consistent width for icons */
            height: 24px; /* Set a consistent height for icons */
            margin-right: 8px; /* Space between icon and text */
        }

        .search-bar {
            max-width: 600px;
            margin: 1rem auto 2rem;
            display: flex;
            background: white; /* White background */
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .search-bar input {
            flex: 1;
            padding: 0.8rem 1.5rem;
            border: none;
            font-size: 1.1rem;
            color: #343a40; /* Dark text */
            background: transparent;
            font-weight: 600;
            outline: none;
        }

        .search-bar button {
            background: #007bff; /* Blue button */
            border: none;
            padding: 0 1.6rem;
            color: white; /* White text */
            font-size: 1.3rem;
            cursor: pointer;
        }
       
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); /* Responsive grid */
            gap: 1.5rem; /* Space between grid items */
            padding: 1rem; /* Padding around the grid */
        }

        .menu-card {
            background: white; /* White background */
            border-radius: 20px; /* Rounded corners */
            padding: 1.5rem; /* Inner padding */
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); /* Subtle shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Smooth transitions */
            overflow: hidden; /* Prevent overflow of content */
            position: relative; /* Position for pseudo-elements */
        }

        .menu-card:hover {
            transform: translateY(-5px); /* Lift effect on hover */
            box-shadow: 0 15px 40px rgba(0, 123, 255, 0.2); /* Enhanced shadow on hover */
        }

        .menu-card img {
            width: 100%; /* Responsive image */
            height: auto; /* Maintain aspect ratio */
            border-radius: 15px; /* Rounded corners for images */
        }

        .menu-card h3 {
            margin: 1rem 0; /* Space above and below the title */
            font-size: 1.25em; /* Font size for the title */
            color: #333; /* Dark text color */
        }

        .menu-card p {
            color: #666; /* Lighter text color for description */
            line-height: 1.5; /* Improved line height for readability */
        }

        .menu-card .price {
            font-size: 1.2em; /* Font size for price */
            color: #007bff; /* Blue color for price */
            font-weight: bold; /* Bold text for emphasis */
            margin-top: 0.5rem; /* Space above the price */
        }

        .btn-add {
            background: #007bff; /* Blue button */
            color: white; /* White text */
            font-weight: 700;
            padding: 0.75rem;
            border-radius: 15px;
            text-align: center;
            transition: background-color 0.3s ease;
            border: none;
            margin-top: 0.5rem;
            cursor: pointer;
        }

        .btn-add:hover {
            background: #0056b3; /* Darker blue on hover */
        }

        .cart-sidebar {
            background: white; /* White background */
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            color: #343a40; /* Dark text */
        }

        .cart-header {
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-align: center;
            color: #007bff; /* Blue text */
        }

        .cart-total {
            font-size: 1.4rem;
            font-weight: 900;
            margin-bottom: 1rem;
            text-align: center;
            color: #007bff; /* Blue text */
        }

        .btn-submit {
            background: #28a745; /* Green button */
            font-weight: 900;
            padding: 1rem;
            border-radius: 18px;
            text-align: center;
            cursor: pointer;
            color: white; /* White text */
            font-size: 1.1rem;
        }

        .btn-submit:hover {
            background: #218838; /* Darker green on hover */
        }

        #notification-container {
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
            border-radius: 20px;
            padding: 1rem 1.5rem;
            box-shadow: 0 12px 40px rgba(0, 123, 255, 0.2);
            color: #343a40; /* Dark text */
        }

        /* Review Modal */
        #review-modal {
            background: white; /* White background */
            padding: 1rem;
            border-radius: 8px;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 123, 255, 0.2);
            color: #343a40; /* Dark text */
            display: none;
        }

        .star-rating {
            display: flex;
            gap: 5px;
            margin: 10px 0;
            justify-content: center;
        }

        .star {
            cursor: pointer;
            font-size: 2rem;
            color: #007bff; /* Blue stars */
            transition: color 0.3s ease;
        }

        .star.hover,
        .star.selected {
            color: #ffcc00; /* Gold color for selected stars */
        }

        .quick-actions-grid {
            margin: 2rem 0;
            background: rgba(255, 255, 255, 0.1);
            padding: 1.5rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            border-radius: 15px;
            color: white;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-align: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .quick-action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .quick-action-btn .icon {
            font-size: 2rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #1a1a1a;
            padding: 2rem;
            border-radius: 20px;
            max-width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            color: white;
            position: relative;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.3);
        }

        .schedule-table {
            overflow-x: auto;
            margin: 1rem 0;
        }

        .schedule-table table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: rgba(255, 255, 255, 0.05);
        }

        .schedule-table th,
        .schedule-table td {
            border: 1px solid #333;
            padding: 0.75rem;
            text-align: center;
        }

        .schedule-table th {
            background: #333;
            font-weight: 600;
        }

        .schedule-table tr:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .report-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .tab-btn {
            padding: 0.5rem 1rem;
            background: #333;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .tab-btn:hover {
            background: #444;
        }

        .tab-btn.active {
            background: #3b82f6;
        }

        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        #qr-code img {
            border: 8px solid white;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container" role="main" aria-label="Client dashboard">
        <header>
            <div class="welcome" tabindex="0">Welcome back, <?= htmlspecialchars($username) ?>!</div>
            <nav aria-label="Primary">
                <a href="profile.php">
                    <img src="icons/profile.png" alt="Profile" class="nav-icon"> Profile
                </a>
                <a href="payment_history.php">
                    <img src="icons/history_dash.png" alt="Payment History" class="nav-icon"> Payment History
                </a>
                <a href="order_tracking.php">
                    <img src="icons/order.png" alt="Order Tracking" class="nav-icon"> Order Tracking
                </a>
                <a href="logout.php">
                    <img src="icons/bye.png" alt="Logout" class="nav-icon"> Logout
                </a>
            </nav>
        </header>

        <!-- Wallet Balance -->
        <div class="meal-plan-card mb-4" role="region" aria-label="Wallet Balance">
            <header class="meal-plan-header">
                <span class="meal-icon" aria-hidden="true">💰</span> Wallet Balance
            </header>
            <div class="flex justify-between items-center">
                <span>Available Balance</span>
                <span class="text-2xl font-bold">$<?= number_format($wallet_balance, 2) ?></span>
            </div>
        </div>

        <!-- Dining Hall Selection -->
        <div class="mb-4">
            <label for="dining-hall" class="block text-lg font-semibold">Select Dining Hall:</label>
            <select id="dining-hall" class="border rounded-lg p-2">
                <option value="Main Campus">Main Campus</option>
                <option value="Mt Darwin">Mt Darwin</option>
                <option value="New Dining Hall">New Dining Hall</option>
            </select>
        </div>

        <!-- Custom Order Details -->
        <div class="mb-4">
            <label for="custom-order-details" class="block text-lg font-semibold">Custom Order Details:</label>
            <textarea id="custom-order-details" rows="4" class="border rounded-lg p-2 w-full" placeholder="Enter specific details for your custom order..."></textarea>
        </div>

        <!-- Favourites Section -->
        <section class="favorites-section" aria-label="Your Favorites">
            <h2>Your Favorites (Rating 4.5+)</h2>
            <div class="favorites-list">
                <?php if (count($favourites) === 0): ?>
                    <p>No favorite meals available yet.</p>
                <?php else: ?>
                    <?php foreach ($favourites as $fav): ?>
                        <div class="favorite-card">
                            <div class="favorite-name"><?= htmlspecialchars($fav['name']) ?></div>
                            <div class="favorite-rating" aria-label="Average rating: <?= round($fav['average_rating'],1) ?> stars">
                                <?php 
                                $rounded = round($fav['average_rating']);
                                for ($i=1; $i<=5; $i++): 
                                    $filled = $i <= $rounded;
                                ?>
                                    <span class="favorite-star"><?= $filled ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                                <span><?= number_format($fav['average_rating'], 1) ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Category Filter -->
        <div class="flex justify-between items-center mb-4 category-filter">
            <label for="category-filter" class="text-lg font-semibold">Filter by Category:</label>
            <select id="category-filter" onchange="filterMenu()" class="border rounded-lg p-2">
                <option value="all">All</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars(strtolower($category['category_name'])) ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Search Bar -->
        <section aria-label="Search menu items" class="search-bar" role="search">
            <input 
                type="search" 
                id="search" 
                placeholder="Search menu items..." 
                aria-label="Search menu items"
                oninput="filterMenu()" 
                autocomplete="off" 
            />
            <button aria-label="Search button" onclick="filterMenu()">
                <i class="fas fa-search"></i>
            </button>
        </section>
            
        <!-- Menu Grid -->
        <section class="menu-grid" aria-live="polite" aria-relevant="additions removals" id="menu-grid">
            <?php foreach ($items as $item): ?>
                <article class="menu-card" role="region" aria-labelledby="item-title-<?= $item['id'] ?>" data-category="<?= htmlspecialchars(strtolower($item['category_name'])) ?>">
                    <h3 class="menu-title" id="item-title-<?= $item['id'] ?>"><?= htmlspecialchars($item['name']) ?></h3>
                    <div class="menu-category"><?= htmlspecialchars($item['category_name']) ?></div>
                    <p class="menu-description"><?= htmlspecialchars($item['description']) ?></p>               
                    <div class="nutrition" aria-label="Nutritional information">
                        <span>🔥 <?= htmlspecialchars($item['calories'] ?? 'N/A') ?> kcal</span>
                        <span>💪 <?= htmlspecialchars($item['protein'] ?? 'N/A') ?> g Protein</span>
                        <span>🥩 <?= htmlspecialchars($item['fat'] ?? 'N/A') ?> g Fat</span>
                        <span>🍞 <?= htmlspecialchars($item['carbs'] ?? 'N/A') ?> g Carbs</span>
                    </div>
                    <div class="price" aria-label="Price">$<?= htmlspecialchars(number_format($item['price'], 2)) ?></div>
                    <button 
                        id="add-btn-<?= $item['id'] ?>" 
                        class="btn-add" 
                        onclick="addToCart(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['name'])) ?>', <?= $item['price'] ?>)"
                        aria-label="Add <?= htmlspecialchars($item['name']) ?> to cart"
                    >
                        Add to Cart
                    </button>
                    <button 
                        class="btn-add" 
                        onclick="showReviewForm(<?= $item['id'] ?>)"
                        aria-label="Write a review for <?= htmlspecialchars($item['name']) ?>"
                    >
                        Write a Review
                    </button>
                    <div id="reviews-<?= $item['id'] ?>" class="reviews-container"></div>
                </article>
            <?php endforeach; ?>
        </section>
        <!-- Payment Options Section -->
<div class="mb-4">
    <label class="block text-lg font-semibold">Select Payment Method:</label>
    <div>
        <input type="radio" id="payment-wallet" name="payment_method" value="wallet" checked>
        <label for="payment-wallet" class="ml-2">Wallet (Available Balance)</label>
    </div>
    <div>
        <input type="radio" id="payment-card" name="payment_method" value="card">
        <label for="payment-card" class="ml-2">Card (Pending Approval)</label>
    </div>
</div>

        <aside class="cart-sidebar" role="region" aria-label="Shopping cart">
            <h2 class="cart-header">Your Cart</h2>
            <div id="cart-list" class="cart-list" tabindex="0" aria-live="polite" aria-relevant="additions removals"></div>
            <div class="cart-total" aria-live="polite" aria-atomic="true">Total: $<span id="cart-total">0.00</span></div>
            <button class="btn-submit" onclick="submitOrder()" aria-label="Submit your order">Submit Order</button>
        </aside>

        <div id="notification-container" aria-live="polite" aria-atomic="true">
    <?php if (count($notifications) > 0): ?>
        <ul>
            <?php foreach ($notifications as $notification): ?>
                <li>
                    <strong><?= htmlspecialchars($notification['message']) ?></strong>
                    <span class="text-gray-500"><?= date('Y-m-d H:i', strtotime($notification['created_at'])) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No notifications available.</p>
    <?php endif; ?>
</div>

        <!-- Review Modal -->
        <div id="review-modal" aria-modal="true" role="dialog" aria-labelledby="review-modal-title" style="display:none;">
            <h3 id="review-modal-title" class="text-dark text-xl font-bold mb-4">Write a Review</h3>
            <form id="review-form">
                <input type="hidden" id="review-menu-item-id" />
                <div class="star-rating" role="radiogroup" aria-label="Star rating">
                    <span class="star" role="radio" aria-checked="false" tabindex="0" aria-label="1 star">★</span>
                    <span class="star" role="radio" aria-checked="false" tabindex="-1" aria-label="2 stars">★</span>
                    <span class="star" role="radio" aria-checked="false" tabindex="-1" aria-label="3 stars">★</span>
                    <span class="star" role="radio" aria-checked="false" tabindex="-1" aria-label="4 stars">★</span>
                    <span class="star" role="radio" aria-checked="false" tabindex="-1" aria-label="5 stars">★</span>
                </div>
                <input type="number" id="rating" name="rating" min="1" max="5" required hidden />
                <label for="review-text" class="block text-dark font-semibold mt-4">Review:</label>
                <textarea id="review-text" name="review_text" rows="4" class="w-full p-2 rounded bg-light" placeholder="Write your review here..."></textarea>
                <div class="mt-4 flex justify-end gap-3">
                    <button type="submit" class="btn-submit bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">Submit</button>
                    <button type="button" class="btn-submit bg-red-600 hover:bg-red-700 px-4 py-2 rounded" onclick="closeReviewForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Quick Actions Grid -->
        <div class="quick-actions-grid">
            <h2 class="text-2xl font-bold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <button onclick="window.location.href='coming_soon.html';" class="quick-action-btn bg-blue-600">
                    <span class="icon">📱</span>
                    <span>Mobile Pay</span>
                    <span class="text-sm">Use QR code to pay</span>
                </button>
                <button onclick="showDiningSchedule()" class="quick-action-btn bg-orange-500">
                    <span class="icon">📅</span>
                    <span>View Hours</span>
                    <span class="text-sm">Dining hall schedules</span>
                </button>
                <button onclick="showSpendingReport()" class="quick-action-btn bg-purple-600">
                    <span class="icon">📊</span>
                    <span>Spending Report</span>
                    <span class="text-sm">Monthly breakdown</span>
                </button>
            </div>
        </div>

        <!-- Dining Schedule Modal -->
        <div id="schedule-modal" class="modal">
            <div class="modal-content max-w-5xl">
                <h3 class="text-xl font-bold mb-4">Weekly Dining Schedule</h3>
                <div class="schedule-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Breakfast</th>
                                <th>Lunch</th>
                                <th>Supper</th>
                                <th>Snacks (Time)</th>
                                <th>Fast Food (Time)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            foreach ($days as $day): ?>
                            <tr>
                                <td><?= $day ?></td>
                                <td>8:00 AM - 9:00 AM</td>
                                <td>12:00 PM - 1:30 PM</td>
                                <td>6:00 PM - 7:30 PM</td>
                                <td>10:00 AM, 3:00 PM</td>
                                <td>11:00 AM - 2:00 PM<br>5:00 PM - 8:00 PM</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button onclick="closeModal('schedule-modal')" class="modal-close">×</button>
            </div>
        </div>

        <!-- Spending Report Modal -->
        <div id="spending-modal" class="modal">
            <div class="modal-content max-w-4xl">
                <h3 class="text-xl font-bold mb-4">Spending Report</h3>
                <div class="report-tabs mb-4">
  <button class="tab-btn active">Daily</button>
  <button class="tab-btn">Weekly</button>
  <button class="tab-btn">Monthly</button>
</div>

                <div id="report-content" class="h-[400px]"></div>
                <button onclick="closeModal('spending-modal')" class="modal-close">×</button>
            </div>
        </div>

    </div>

    <script>
      let cart = {};
let selectedRating = 0;

function addToCart(id, name, price) {
    if (cart[id]) {
        cart[id].quantity += 1;
    } else {
        cart[id] = { name: name, price: price, quantity: 1 };
    }
    renderCart();
}

function removeFromCart(id) {
    delete cart[id];
    renderCart();
}

function renderCart() {
    const cartList = document.getElementById('cart-list');
    cartList.innerHTML = '';
    let total = 0;

    for (const id in cart) {
        const item = cart[id];
        const li = document.createElement('div');
        li.className = 'cart-item';
        li.innerHTML = `
            ${item.name} x${item.quantity} ($${(item.price * item.quantity).toFixed(2)})
            <button onclick="removeFromCart(${id})">×</button>
        `;
        cartList.appendChild(li);
        total += item.price * item.quantity;
    }

    document.getElementById('cart-total').textContent = total.toFixed(2);
}

async function submitOrder() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const diningHall = document.getElementById('dining-hall').value;
    const customOrderDetails = document.getElementById('custom-order-details').value;

    const cartArray = Object.entries(cart).map(([id, item]) => ({
        id: parseInt(id),
        quantity: item.quantity
    }));

    try {
        const response = await fetch('submit_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                cart: cartArray,
                payment_method: paymentMethod,
                dining_hall: diningHall,
                custom_order: customOrderDetails
            })
        });
        const data = await response.json();
        if (data.success) {
            alert('Order placed successfully!');
            cart = {};
            renderCart();
            document.getElementById('custom-order-details').value = ''; // Clear custom order details
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('Error submitting order: ' + error);
    }
}

function showReviewForm(menuItemId) {
    selectedRating = 0;
    document.getElementById('review-menu-item-id').value = menuItemId;
    document.getElementById('rating').value = '';
    document.getElementById('review-text').value = '';
    updateStarDisplay(0);
    document.getElementById('review-modal').style.display = 'block';
}

function closeReviewForm() {
    document.getElementById('review-modal').style.display = 'none';
}

function updateStarDisplay(rating) {
    selectedRating = rating;
    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        star.classList.toggle('selected', index < rating);
        star.setAttribute('aria-checked', index < rating ? 'true' : 'false');
    });
    document.getElementById('rating').value = rating;
}

function filterMenu() {
    const searchTerm = document.getElementById('search').value.toLowerCase();
    const category = document.getElementById('category-filter').value;
    const menuItems = document.querySelectorAll('.menu-card');

    menuItems.forEach(item => {
        const title = item.querySelector('.menu-title').textContent.toLowerCase();
        const description = item.querySelector('.menu-description').textContent.toLowerCase();
        const itemCategory = item.dataset.category.toLowerCase();

        const matchesSearch = title.includes(searchTerm) || description.includes(searchTerm);
        const matchesCategory = category === 'all' || itemCategory === category;

        item.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    renderCart();
    <?php foreach ($items as $item): ?>
        loadReviews(<?= $item['id'] ?>);
    <?php endforeach; ?>

    const stars = document.querySelectorAll('.star');
    stars.forEach((star, index) => {
        star.addEventListener('click', () => updateStarDisplay(index + 1));
        star.addEventListener('mouseenter', () => {
            stars.forEach((s, i) => s.classList.toggle('hover', i <= index));
        });
        star.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });
    });

    const tabButtons = document.querySelectorAll('.report-tabs .tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            const type = button.textContent.toLowerCase();
            showReport(type);
        });
    });

    showReport('daily'); // Call initially to show daily report
});

document.getElementById('review-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const menuItemId = document.getElementById('review-menu-item-id').value;
    const rating = parseInt(document.getElementById('rating').value);
    const reviewText = document.getElementById('review-text').value.trim();

    if (!rating || rating < 1 || rating > 5) {
        alert('Please select a rating between 1 and 5 stars.');
        return;
    }

    try {
        const response = await fetch('reviews.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                menu_item_id: parseInt(menuItemId),
                rating: rating,
                review_text: reviewText
            })
        });

        const data = await response.json();
        alert(data.message);
        if (data.success) {
            closeReviewForm();
            loadReviews(menuItemId);
        }
    } catch (error) {
        alert('Failed to submit review: ' + error);
    }
});

async function loadReviews(menuItemId) {
    try {
        const response = await fetch('reviews.php?menu_item_id=' + menuItemId);
        const data = await response.json();
        const container = document.getElementById('reviews-' + menuItemId);
        
        container.innerHTML = '';
        if (data.success && data.reviews.length > 0) {
            data.reviews.forEach(review => {
                const div = document.createElement('div');
                div.className = 'review-item';
                div.innerHTML = `
                    <strong>${review.username}</strong>
                    <div class="review-rating">
                        ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                    </div>
                    <div class="review-date">${new Date(review.created_at).toLocaleDateString()}</div>
                    <div class="review-text">${review.review_text}</div>
                `;
                container.appendChild(div);
            });
        } else {
            container.textContent = 'No reviews yet.';
        }
    } catch (error) {
        console.error('Failed to load reviews:', error);
    }
}

function showDiningSchedule() {
    document.getElementById('schedule-modal').style.display = 'flex';
}

function showSpendingReport() {
    document.getElementById('spending-modal').style.display = 'flex';
}

function showReport(type) {
    fetch(`get_spending_report.php?type=${type}`)
        .then(response => response.json())
        .then(data => {
            const reportContent = document.getElementById('report-content');
            reportContent.innerHTML = '';

            const labels = data.labels;
            const values = data.values;

            labels.forEach((label, index) => {
                reportContent.innerHTML += `<p>${label}: $${values[index].toFixed(2)}</p>`;
            });
        })
        .catch(error => {
            console.error('Error fetching spending report:', error);
        });
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

document.querySelectorAll('.modal-close').forEach(button => {
    button.addEventListener('click', () => {
        const modal = button.closest('.modal');
        if (modal) {
            modal.style.display = 'none';
        }
    });
});


    </script>
</body>
</html>

