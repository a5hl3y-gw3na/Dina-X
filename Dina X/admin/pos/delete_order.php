<?php
session_start();
require_once '../../config.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['general_admin', 'superior_admin'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['order_id'])) {
    header('Location: POS.php');
    exit;
}

$order_id = (int)$_POST['order_id'];

// Delete payments related to order
$stmt = $pdo->prepare("DELETE FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);

// Delete order_items related to order
$stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);

// Delete order itself
$stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
$stmt->execute([$order_id]);

header('Location: POS.php?deleted=1');
exit;
?>
