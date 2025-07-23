<?php
session_start();
require_once '../config.php';

// Check if user is logged in and is client
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Total count for pagination
$total_stmt = $pdo->prepare('SELECT COUNT(*) FROM payments WHERE user_id = ?');
$total_stmt->execute([$user_id]);
$total_payments = $total_stmt->fetchColumn();
$total_pages = ceil($total_payments / $limit);
// Fetch payments
$payments_stmt = $pdo->prepare('
    SELECT p.id, p.amount, p.payment_method, p.status, p.payment_date, o.id AS order_id, o.total_amount
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    WHERE o.user_id = ?
    ORDER BY p.payment_date DESC
    LIMIT ?, ?
');
$payments_stmt->bindValue(1, $user_id, PDO::PARAM_INT);
$payments_stmt->bindValue(2, $offset, PDO::PARAM_INT);
$payments_stmt->bindValue(3, $limit, PDO::PARAM_INT);
$payments_stmt->execute();
$payments = $payments_stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Payment History - DinaX</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <style>
        body {
            background-color: white; /* Set background to white */
            position: relative; /* Ensure particles are positioned correctly */
            overflow: hidden; /* Prevent scrollbars */
        }

        #particles-js {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            z-index: -1; /* Ensure particles are behind other content */
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans min-h-screen flex items-center justify-center p-4">

<div id="particles-js"></div>

<div class="w-full max-w-5xl bg-white rounded-lg shadow-lg p-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-semibold text-red-500">Payment History</h1>
        <nav class="space-x-4">
            <a href="dashboard.php" class="text-red-500 hover:underline">Dashboard</a>
            <a href="logout.php" class="text-red-500 hover:underline">Logout</a>
        </nav>
    </div>

    <?php if (count($payments) === 0): ?>
        <p class="text-center text-gray-600 mt-8">You have no payment history yet.</p>
    <?php else: ?>
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 rounded-lg divide-y divide-gray-200">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-2 border-b border-gray-300 text-left">Amount</th>
                        <th class="px-4 py-2 border-b border-gray-300 text-left">Payment Method</th>
                        <th class="px-4 py-2 border-b border-gray-300 text-left">Status</th>
                        <th class="px-4 py-2 border-b border-gray-300 text-left">Payment Date</th>
                        <th class="px-4 py-2 border-b border-gray-300 text-left">Receipt</th>
                    </tr>
                </thead>
               <tbody class="divide-y divide-gray-200">
    <?php foreach ($payments as $payment): ?>
        <tr>
            <td class="px-4 py-2">$<?= htmlspecialchars(number_format($payment['amount'], 2)) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($payment['payment_method']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars($payment['status']) ?></td>
            <td class="px-4 py-2"><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($payment['payment_date']))) ?></td>
            <td class="px-4 py-2">
                <a href="download_receipt.php?payment_id=<?= $payment['id'] ?>" target="_blank" class="text-green-600 hover:underline">Download PDF</a>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center space-x-2 mt-6 flex-wrap">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="px-3 py-2 rounded-lg <?= $i === $page ? 'bg-red-500 text-white' : 'bg-red-400 hover:bg-red-500 text-white' ?> transition duration-200">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    particlesJS("particles-js", {
        "particles": {
            "number": {
                "value": 80,
                "density": {
                    "enable": true,
                    "value_area": 800
                }
            },
            "color": {
                "value": "#ff7e5f" // Change particle color to match your theme
            },
            "shape": {
                "type": "circle",
                "stroke": {
                    "width": 0,
                    "color": "#000000"
                },
                "polygon": {
                    "nb_sides": 5
                },
                "image": {
                    "src": "img/github.svg",
                    "width": 100,
                    "height": 100
                }
            },
            "opacity": {
                "value": 0.5,
                "random": false,
                "anim": {
                    "enable": false,
                    "speed": 1,
                    "opacity_min": 0.1,
                    "sync": false
                }
            },
            "size": {
                "value": 3,
                "random": true,
                "anim": {
                    "enable": false,
                    "speed": 40,
                    "size_min": 0.1,
                    "sync": false
                }
            },
            "line_linked": {
                "enable": true,
                "distance": 150,
                "color": "#ff7e5f", // Change line color to match your theme
                "opacity": 0.4,
                "width": 1
            },
            "move": {
                "enable": true,
                "speed": 6,
                "direction": "none",
                "random": false,
                "straight": false,
                "out_mode": "out",
                "bounce": false,
                "attract": {
                    "enable": false,
                    "rotateX": 600,
                    "rotateY": 1200
                }
            }
        },
        "interactivity": {
            "detect_on": "canvas",
            "events": {
                "onhover": {
                    "enable": true,
                    "mode": "repulse"
                },
                "onclick": {
                    "enable": true,
                    "mode": "push"
                },
                "resize": true
            },
            "modes": {
                "grab": {
                    "distance": 400,
                    "line_linked": {
                        "opacity": 1
                    }
                },
                "bubble": {
                    "distance": 400,
                    "size": 40,
                    "duration": 2,
                    "opacity": 8,
                    "speed": 3
                },
                "repulse": {
                    "distance": 200,
                    "duration": 0.4
                },
                "push": {
                    "particles_nb": 4
                },
                "remove": {
                    "particles_nb": 2
                }
            }
        },
        "retina_detect": true
    });
</script>

</body>
</html>
