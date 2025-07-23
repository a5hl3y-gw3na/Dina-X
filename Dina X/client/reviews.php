<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['menu_item_id'], $data['rating']) || !is_int($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    $menu_item_id = $data['menu_item_id'];
    $rating = $data['rating'];
    $review_text = $data['review_text'] ?? '';

    try {
        $stmt = $pdo->prepare('INSERT INTO reviews (user_id, menu_item_id, rating, review_text) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user_id, $menu_item_id, $rating, $review_text]);
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to submit review: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $menu_item_id = $_GET['menu_item_id'] ?? null;
    if (!$menu_item_id) {
        echo json_encode(['success' => false, 'message' => 'menu_item_id is required']);
        exit;
    }
    try {
        $stmt = $pdo->prepare('SELECT r.id, r.rating, r.review_text, r.created_at, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.menu_item_id = ? ORDER BY r.created_at DESC');
        $stmt->execute([$menu_item_id]);
        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'reviews' => $reviews]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch reviews: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
