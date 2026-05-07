<?php
session_start();

if (!isset($_SESSION['blockchain_access']) || $_SESSION['blockchain_access'] !== true) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Доступ запрещён'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/blockchain.php';

$db = Database::getInstance()->getConnection();
$blockchain = new Blockchain($db);

$blocks = $blockchain->getChain(50);

foreach ($blocks as &$block) {
    $stmt = $db->prepare("
        SELECT 
            bt.id as id, 
            bt.request_id, 
            bt.transaction_hash,
            r.request_code,
            r.city_sender,
            r.name_sender,
            r.address_sender,
            r.phone_sender,
            r.inn_sender,
            r.city_recipient,
            r.name_recipient,
            r.address_recipient,
            r.phone_recipient,
            r.inn_recipient,
            r.cargo_name,
            r.cargo_quantity,
            r.cargo_weight,
            r.cargo_volume,
            r.cargo_cost,
            r.cargo_length,
            r.cargo_width,
            r.cargo_height,
            r.work_time_start,
            r.work_time_end,
            r.insurance,
            r.delivery_to_door,
            r.power_of_attorney,
            r.comment,
            r.created_at
        FROM blockchain_transactions bt
        JOIN requests r ON bt.request_id = r.id
        WHERE bt.block_id = ?
    ");
    $stmt->execute([$block['id']]);
    $block['requests'] = $stmt->fetchAll();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'hacked' => $_SESSION['hacked'] ?? false,
    'blocks' => $blocks
], JSON_UNESCAPED_UNICODE);
?>