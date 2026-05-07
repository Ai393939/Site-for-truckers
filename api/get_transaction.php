<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['blockchain_access']) || $_SESSION['blockchain_access'] !== true) {
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

require_once __DIR__ . '/db.php';
$db = Database::getInstance()->getConnection();

$transaction_id = $_GET['id'] ?? 0;

$stmt = $db->prepare("
    SELECT bt.*, r.* 
    FROM blockchain_transactions bt
    JOIN requests r ON bt.request_id = r.id
    WHERE bt.id = ?
");
$stmt->execute([$transaction_id]);
$transaction = $stmt->fetch();

if ($transaction) {
    echo json_encode([
        'success' => true,
        'transaction' => $transaction
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Транзакция не найдена для ID: ' . $transaction_id
    ], JSON_UNESCAPED_UNICODE);
}
?>