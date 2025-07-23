<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../index.php');
    exit;
}

$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Fetch total transactions and revenue
$total_transactions_stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE status = 'completed'");
$total_transactions = $total_transactions_stmt->fetchColumn();

$total_revenue_stmt = $pdo->query("SELECT SUM(amount) as revenue FROM payments WHERE status = 'completed'");
$total_revenue = $total_revenue_stmt->fetchColumn();

// Fetch active students (role_id = 3)
$active_students_stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
$active_students = $active_students_stmt->fetchColumn();

// Fetch menu items count
$menu_items_stmt = $pdo->query("SELECT COUNT(*) as total FROM menu_items");
$menu_items = $menu_items_stmt->fetchColumn();

// Fetch recent activities
$recent_activity = [];

// New student registration
$new_students_stmt = $pdo->query("SELECT username, created_at FROM users WHERE role_id = 3 ORDER BY created_at DESC LIMIT 1");
$new_student = $new_students_stmt->fetch(PDO::FETCH_ASSOC);
if ($new_student) {
    $recent_activity[] = [
        'action' => 'New student registration',
        'description' => htmlspecialchars($new_student['username']) . ' registered',
        'time' => $new_student['created_at']
    ];
}

// Most recent payment
$recent_payment_stmt = $pdo->query("SELECT amount, payment_date FROM payments WHERE status = 'completed' ORDER BY payment_date DESC LIMIT 1");
$recent_payment = $recent_payment_stmt->fetch(PDO::FETCH_ASSOC);
if ($recent_payment) {
    $recent_activity[] = [
        'action' => 'Payment received',
        'description' => 'EcoCash $' . htmlspecialchars($recent_payment['amount']) . ' received',
        'time' => $recent_payment['payment_date']
    ];
}

// Last menu updated
$last_menu_stmt = $pdo->query("SELECT created_at FROM menu_items ORDER BY created_at DESC LIMIT 1");
$last_menu = $last_menu_stmt->fetch(PDO::FETCH_ASSOC);
if ($last_menu) {
    $recent_activity[] = [
        'action' => 'Menu updated',
        'description' => 'Weekly menu refreshed',
        'time' => $last_menu['created_at']
    ];
}
?>

<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin Dashboard - Dina X</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap');

    /* General resets and fonts */
    body {
      font-family: 'Poppins', sans-serif;
      background: #0f172a;
      color: #e2e8f0;
    }
    /* Sidebar */
    aside {
      background: #1e293b;
      border-right: 1px solid #334155;
      min-height: 100vh;
      transition: width 0.3s ease;
    }
    aside:hover {
      width: 20rem;
    }
    aside .logo-circle {
      background: linear-gradient(135deg, #f97316, #fbbf24);
      box-shadow: 0 0 15px #fb923caa;
      transition: box-shadow 0.3s ease;
    }
    aside .logo-circle:hover {
      box-shadow: 0 0 30px #fb923cdd;
    }
    nav a {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.85rem 1.25rem;
      color: #cbd5e1;
      font-weight: 600;
      font-size: 1.05rem;
      border-radius: 12px;
      transition: background 0.25s ease, color 0.25s ease, box-shadow 0.25s ease;
      position: relative;
      overflow: hidden;
    }
    nav a::before {
      content: '';
      position: absolute;
      width: 0;
      height: 100%;
      left: 0;
      top: 0;
      background: #f97316;
      border-radius: 12px;
      z-index: 1;
      transition: width 0.3s ease;
      opacity: 0.2;
      pointer-events: none;
    }
    nav a:hover, nav a:focus {
      color: #fff;
      background: transparent;
      box-shadow: 0 0 12px #fb923c;
    }
    nav a:hover::before, nav a:focus::before {
      width: 100%;
    }
    nav a span:first-child {
      font-size: 1.6rem;
      z-index: 2;
    }
    nav a span:last-child {
      z-index: 2;
      white-space: nowrap;
    }

    /* Main container */
    main {
      padding: 3rem 3.5rem;
      flex-grow: 1;
      overflow-y: auto;
    }
    /* Title */
    header h1 {
      font-size: 3rem;
      font-weight: 800;
      background: linear-gradient(90deg, #f97316, #fbbf24);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 0.25rem;
      user-select: none;
      letter-spacing: 0.07em;
      text-shadow: 0 2px 4px #fb923caa;
    }
    header p {
      color: #94a3b8;
      font-size: 1.15rem;
      margin-bottom: 2.5rem;
    }
    /* Statistics cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
      gap: 2rem;
      margin-bottom: 3rem;
    }
    .card {
      position: relative;
      background: rgba(255,255,255,0.08);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 1rem;
      padding: 2rem 2.5rem;
      box-shadow: 0 8px 32px 0 rgba(255 111 97 / 0.45);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      cursor: default;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      user-select: none;
    }
    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 14px 40px 0 rgba(255 111 97 / 0.85);
    }
    .card .icon {
      font-size: 2.5rem;
      background: linear-gradient(135deg, #fb923c, #fbbf24);
      width: 56px;
      height: 56px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 0 16px #fb923cbc;
      margin-bottom: 1rem;
      color: white;
    }
    .card h2 {
      font-size: 1.05rem;
      font-weight: 600;
      color: #fcd34d;
      margin-bottom: 0.25rem;
      letter-spacing: 0.05em;
    }
    .card p.value {
      font-size: 2.25rem;
      font-weight: 900;
      color: white;
      letter-spacing: 0.05em;
      margin-bottom: 0.15rem;
    }
    .card p.subvalue {
      font-size: 0.875rem;
      font-weight: 600;
      color: #6ee7b7;
      user-select: text;
    }
    /* Recent activity */
    section.recent-activity {
      background: rgba(255,255,255,0.07);
      border-radius: 1.25rem;
      padding: 2rem 3rem;
      box-shadow: 0 12px 48px 0 rgba(255 111 97 / 0.5);
      max-width: 900px;
      margin: 0 auto;
      user-select: none;
    }
    section.recent-activity h2 {
      font-weight: 800;
      font-size: 2rem;
      margin-bottom: 1.5rem;
      color: #fbbf24;
      letter-spacing: 0.06em;
      text-align: center;
    }
    ul.activity-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }
    ul.activity-list li {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid rgba(255,255,255,0.15);
      padding: 1rem 0;
      transition: background 0.3s ease;
      cursor: default;
    }
    ul.activity-list li:last-child {
      border-bottom: none;
    }
    ul.activity-list li:hover {
      background: rgba(251, 115, 60, 0.15);
      box-shadow: inset 0 0 12px #fb923caa;
      border-radius: 1rem;
    }
    ul.activity-list li div p:first-child {
      font-weight: 700;
      font-size: 1.1rem;
      color: #fed7aa;
      margin-bottom: 0.25rem;
    }
    ul.activity-list li div p:last-child {
      font-size: 0.85rem;
      color: #e0e7ffcc;
    }
    ul.activity-list li span {
      color: #94a3b8;
      font-size: 0.875rem;
      min-width: 6.5rem;
      text-align: right;
    }
    /* Scrollbars */
    main::-webkit-scrollbar {
      width: 10px;
    }
    main::-webkit-scrollbar-track {
      background: #0f172a;
    }
    main::-webkit-scrollbar-thumb {
      background: #f97316aa;
      border-radius: 20px;
      border: 2px solid #0f172a;
    }
    
    /* Responsive tweaks */
    @media (max-width: 768px) {
      aside:hover {
        width: 16rem;
      }
      .card p.value {
        font-size: 1.75rem;
      }
      .card .icon {
        font-size: 2rem;
        width: 48px;
        height: 48px;
      }
      section.recent-activity {
        padding: 1.5rem 2rem;
      }
    }
  </style>
</head>
<body>
  <div class="flex min-h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-16 hover:w-64 flex flex-col p-4 space-y-12 text-gray-100 select-none shadow-lg">
      <div class="flex items-center space-x-4">
        <div class="logo-circle w-12 h-12 rounded-full flex items-center justify-center text-2xl font-extrabold text-white select-none cursor-default" aria-label="User  Initial">
          <?= strtoupper(substr($username, 0, 1)) ?>
        </div>
        <div class="hidden group-hover:flex flex-col select-text transition-all duration-300">
          <h3 class="font-extrabold text-lg text-yellow-400 truncate max-w-xs"><?= htmlspecialchars($username) ?></h3>
          <p class="text-sm text-yellow-300 lowercase tracking-wide"><?= ucfirst(str_replace('_',' ',$role)) ?></p>
        </div>
      </div>
   <nav class="flex flex-col flex-grow space-y-3 text-sm">
    <a href="dashboard.php" class="group" tabindex="0" aria-current="page">
        <span class="text-2xl">🏠</span>
        <span class="hidden md:inline-block font-semibold">Dashboard</span>
    </a>
    <a href="menu_management.php" class="group" tabindex="0">
        <span class="text-2xl">📁</span>
        <span class="hidden md:inline-block font-semibold">Menu Management</span>
    </a>
    <a href="user_management.php" class="group" tabindex="0">
        <span class="text-2xl">👥</span>
        <span class="hidden md:inline-block font-semibold">User  Management</span>
    </a>
    <?php if ($role === 'superior_admin'): ?>
        <a href="inventory_management.php" class="group" tabindex="0">
            <span class="text-2xl">📦</span>
            <span class="hidden md:inline-block font-semibold">Inventory</span>
        </a>
        <a href="reporting.php" class="group" tabindex="0">
            <span class="text-2xl">📊</span>
            <span class="hidden md:inline-block font-semibold">Reporting</span>
        </a>
    <?php endif; ?>
    <a href="order_management.php" class="group" tabindex="0">
        <span class="text-2xl">🧾</span>
        <span class="hidden md:inline-block font-semibold">Orders</span>
    </a>
    <a href="payment_management.php" class="group" tabindex="0">
        <span class="text-2xl">💳</span>
        <span class="hidden md:inline-block font-semibold">Payments</span>
    </a>
    <!-- New POS Section -->
    <a href="pos/POS.php" class="group" tabindex="0">
        <span class="text-2xl">🛒</span>
        <span class="hidden md:inline-block font-semibold">POS</span>
    </a>
</nav>

      <a href="logout.php" class="group mt-auto flex items-center p-2 rounded-lg hover:bg-yellow-600 hover:text-white cursor-pointer select-none" tabindex="0">
        <span class="text-2xl">🚪</span>
        <span class="hidden md:inline-block ml-2 font-semibold">Logout</span>
      </a>
    </aside>

    <!-- Main Content -->
    <main class="flex-grow overflow-y-auto p-10">
      <header class="mb-10">
        <h1 class="text-5xl font-extrabold tracking-wide text-gradient bg-gradient-to-r from-yellow-400 to-orange-500 select-none">
          Welcome, <?= htmlspecialchars($username) ?>!
        </h1>
        <p class="text-gray-400 mt-2 text-lg select-text">Here's your admin dashboard overview</p>
      </header>

      <section class="stats-grid">
        <article class="card" role="region" aria-label="Total Transactions">
          <div class="icon" aria-hidden="true">✅</div>
          <h2>Total Transactions</h2>
          <p class="value"><?= htmlspecialchars($total_transactions) ?></p>
        </article>

        <article class="card" role="region" aria-label="Revenue">
          <div class="icon" aria-hidden="true">💰</div>
          <h2>Revenue</h2>
          <p class="value">$<?= htmlspecialchars(number_format($total_revenue, 2)) ?></p>
        </article>

        <article class="card" role="region" aria-label="Active Students">
          <div class="icon" aria-hidden="true">🎓</div>
          <h2>Active Students</h2>
          <p class="value"><?= htmlspecialchars($active_students) ?></p>
        </article>

        <article class="card" role="region" aria-label="Menu Items">
          <div class="icon" aria-hidden="true">📦</div>
          <h2>Menu Items</h2>
          <p class="value"><?= htmlspecialchars($menu_items) ?></p>
        </article>
      </section>

      <section class="recent-activity">
        <h2>Recent Activity</h2>
        <ul class="activity-list" aria-live="polite" aria-relevant="additions">
          <?php foreach ($recent_activity as $activity): ?>
            <li>
              <div>
                <p><?= $activity['action'] ?></p>
                <p><?= $activity['description'] ?></p>
              </div>
              <span><?= date('F j, Y, g:i a', strtotime($activity['time'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    </main>
  </div>
</body>
</html>
