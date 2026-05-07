<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['blockchain_access']) || $_SESSION['blockchain_access'] !== true) {
    echo json_encode(['error' => 'Доступ запрещён']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/blockchain.php';

$db = Database::getInstance()->getConnection();
$blockchain = new Blockchain($db);

$input = json_decode(file_get_contents('php://input'), true);
$transaction_id = $input['id'] ?? 0;
$new_data = $input['data'] ?? [];

$stmt = $db->prepare("
    SELECT bt.*, b.block_hash as old_block_hash, b.block_index 
    FROM blockchain_transactions bt
    JOIN blockchain_blocks b ON bt.block_id = b.id
    WHERE bt.id = ?
");
$stmt->execute([$transaction_id]);
$old_tx = $stmt->fetch();

if (!$old_tx) {
    echo json_encode(['error' => 'Транзакция не найдена']);
    exit;
}

$request_id = $old_tx['request_id'];
$old_tx_hash = $old_tx['transaction_hash'];
$old_block_hash = $old_tx['old_block_hash'];
$block_id = $old_tx['block_id'];

$stmt = $db->prepare("
    UPDATE requests SET 
        city_sender = :city_sender,
        name_sender = :name_sender,
        address_sender = :address_sender,
        phone_sender = :phone_sender,
        city_recipient = :city_recipient,
        name_recipient = :name_recipient,
        cargo_name = :cargo_name,
        cargo_quantity = :cargo_quantity,
        cargo_weight = :cargo_weight,
        cargo_volume = :cargo_volume,
        cargo_cost = :cargo_cost,
        comment = :comment
    WHERE id = :request_id
");

$stmt->execute([
    ':city_sender' => $new_data['city_sender'] ?? '',
    ':name_sender' => $new_data['name_sender'] ?? '',
    ':address_sender' => $new_data['address_sender'] ?? '',
    ':phone_sender' => $new_data['phone_sender'] ?? '',
    ':city_recipient' => $new_data['city_recipient'] ?? '',
    ':name_recipient' => $new_data['name_recipient'] ?? '',
    ':cargo_name' => $new_data['cargo_name'] ?? '',
    ':cargo_quantity' => $new_data['cargo_quantity'] ?? 0,
    ':cargo_weight' => $new_data['cargo_weight'] ?? 0,
    ':cargo_volume' => $new_data['cargo_volume'] ?? 0,
    ':cargo_cost' => $new_data['cargo_cost'] ?? 0,
    ':comment' => $new_data['comment'] ?? '',
    ':request_id' => $request_id
]);

$newDataJson = json_encode($new_data, JSON_UNESCAPED_UNICODE);
$new_tx_hash = hash('sha256', $newDataJson . time() . rand());

$stmt = $db->prepare("UPDATE blockchain_transactions SET transaction_hash = ?, data = ? WHERE id = ?");
$stmt->execute([$new_tx_hash, $newDataJson, $transaction_id]);

$stmt = $db->prepare("
    SELECT transaction_hash FROM blockchain_transactions 
    WHERE block_id = ? 
    ORDER BY id ASC
");
$stmt->execute([$block_id]);
$tx_hashes = $stmt->fetchAll();

$stmt = $db->prepare("SELECT * FROM blockchain_blocks WHERE id = ?");
$stmt->execute([$block_id]);
$block = $stmt->fetch();

$merkleRoot = hash('sha256', implode('', array_column($tx_hashes, 'transaction_hash')));
$dataForHash = $block['block_index'] . $block['previous_hash'] . $block['timestamp'] . $block['nonce'] . $merkleRoot;
$new_block_hash = hash('sha256', $dataForHash);

$stmt = $db->prepare("UPDATE blockchain_blocks SET merkle_root = ?, block_hash = ? WHERE id = ?");
$stmt->execute([$merkleRoot, $new_block_hash, $block_id]);

echo json_encode([
    'success' => true,
    'message' => 'Данные изменены! Хэш блока пересчитан.',
    'old_tx_hash' => $old_tx_hash,
    'new_tx_hash' => $new_tx_hash,
    'old_block_hash' => $old_block_hash,
    'new_block_hash' => $new_block_hash
], JSON_UNESCAPED_UNICODE);
?>