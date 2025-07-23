<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'client') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['payment_id'])) {
    die('Payment ID is required.');
}

$payment_id = intval($_GET['payment_id']);
$user_id = $_SESSION['user_id'];

// Fetch payment details
$stmt = $pdo->prepare('SELECT p.id, p.order_id, p.amount, p.payment_method, p.status, p.payment_date, o.total_amount FROM payments p JOIN orders o ON p.order_id = o.id WHERE p.id = ? AND p.user_id = ?');
$stmt->execute([$payment_id, $user_id]);
$payment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payment) {
    die('Payment not found or access denied.');
}

// Generate PDF receipt using basic headers
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="receipt_' . $payment_id . '.pdf"');

// Simple PDF content with improved formatting
$pdf_content = "
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >>
endobj
4 0 obj
<< /Length 5 0 R >>
stream
BT
/F1 24 Tf
100 700 Td
(Dina X - Payment Receipt) Tj
ET
BT
/F1 16 Tf
100 650 Td
(----------------------------------------) Tj
ET
BT
/F1 12 Tf
100 620 Td
(Payment ID: {$payment['id']}) Tj
ET
BT
/F1 12 Tf
100 600 Td
(Order ID: {$payment['order_id']}) Tj
ET
BT
/F1 12 Tf
100 580 Td
(Amount: \${$payment['amount']}) Tj
ET
BT
/F1 12 Tf
100 560 Td
(Payment Method: {$payment['payment_method']}) Tj
ET
BT
/F1 12 Tf
100 540 Td
(Status: {$payment['status']}) Tj
ET
BT
/F1 12 Tf
100 520 Td
(Payment Date: " . date('Y-m-d H:i:s', strtotime($payment['payment_date'])) . ") Tj
ET
BT
/F1 16 Tf
100 500 Td
(----------------------------------------) Tj
ET
BT
/F1 12 Tf
100 480 Td
(Thank you for your payment!) Tj
ET
BT
/F1 12 Tf
100 460 Td
(If you have any questions, please contact us.) Tj
ET
endstream
endobj
5 0 obj
<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>
endobj
xref
0 6
0000000000 65535 f 
0000000010 00000 n 
0000000060 00000 n 
0000000117 00000 n 
0000000210 00000 n 
0000000410 00000 n 
trailer
<< /Size 6 /Root 1 0 R >>
startxref
510
%%EOF
";

echo $pdf_content;
exit;
?>
