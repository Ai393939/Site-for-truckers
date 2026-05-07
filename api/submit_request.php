<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Метод должен быть POST');
    }
    
    $data = $_POST;
    
    $required = ['citySender', 'nameSender', 'adressSender', 'numberSender', 'innSender'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Заполните обязательное поле: " . $field);
        }
    }
    
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/blockchain.php';
    
    $db = Database::getInstance()->getConnection();
    
    $requestCode = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $stmt = $db->prepare("
        INSERT INTO requests (
            request_code, city_sender, name_sender, address_sender, 
            phone_sender, inn_sender, work_time_start, work_time_end,
            city_recipient, name_recipient, address_recipient, 
            phone_recipient, inn_recipient,
            cargo_quantity, cargo_name, cargo_cost, 
            cargo_weight, cargo_volume, cargo_length, 
            cargo_width, cargo_height,
            insurance, delivery_to_door, power_of_attorney,
            comment, created_at
        ) VALUES (
            :request_code, :city_sender, :name_sender, :address_sender,
            :phone_sender, :inn_sender, :work_time_start, :work_time_end,
            :city_recipient, :name_recipient, :address_recipient,
            :phone_recipient, :inn_recipient,
            :cargo_quantity, :cargo_name, :cargo_cost,
            :cargo_weight, :cargo_volume, :cargo_length,
            :cargo_width, :cargo_height,
            :insurance, :delivery_to_door, :power_of_attorney,
            :comment, NOW()
        )
    ");
    
    $stmt->execute([
        ':request_code' => $requestCode,
        ':city_sender' => $data['citySender'],
        ':name_sender' => $data['nameSender'],
        ':address_sender' => $data['adressSender'],
        ':phone_sender' => $data['numberSender'],
        ':inn_sender' => $data['innSender'],
        ':work_time_start' => $data['timeStartSender'] ?? null,
        ':work_time_end' => $data['timeEndSender'] ?? null,
        ':city_recipient' => $data['cityRecipient'] ?? null,
        ':name_recipient' => $data['nameRecipient'] ?? null,
        ':address_recipient' => $data['adressRecipient'] ?? null,
        ':phone_recipient' => $data['numberRecipient'] ?? null,
        ':inn_recipient' => $data['innRecipient'] ?? null,
        ':cargo_quantity' => $data['countFreight'] ?? null,
        ':cargo_name' => $data['nameFreight'] ?? null,
        ':cargo_cost' => $data['costFreight'] ?? null,
        ':cargo_weight' => $data['weightFreight'] ?? null,
        ':cargo_volume' => $data['volumeFreight'] ?? null,
        ':cargo_length' => $data['lengthFreight'] ?? null,
        ':cargo_width' => $data['widthFreight'] ?? null,
        ':cargo_height' => $data['heightFreight'] ?? null,
        ':insurance' => isset($data['insurance']) ? 1 : 0,
        ':delivery_to_door' => isset($data['dop1']) ? 1 : 0,
        ':power_of_attorney' => isset($data['dop2']) ? 1 : 0,
        ':comment' => $data['comment'] ?? null
    ]);
    
    $requestId = $db->lastInsertId();
    
    $blockchain = new Blockchain($db);
    
    $transactionData = [
        'request_code' => $requestCode,
        'city_sender' => $data['citySender'],
        'name_sender' => $data['nameSender'],
        'cargo_weight' => $data['weightFreight'] ?? 0,
        'cargo_cost' => $data['costFreight'] ?? 0,
        'comment' => $data['comment'] ?? ''
    ];
    
    $blockchain->addTransaction($requestId, $transactionData);
    
    $blockCreated = false;
    $blockResult = null;
    
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM blockchain_transactions WHERE block_id IS NULL");
    $pendingCount = $stmt->fetch();
    
    if ($pendingCount['cnt'] >= 2) {
        $blockResult = $blockchain->createBlock();
        if ($blockResult['success']) {
            $blockCreated = true;
        }
    }
    
    $response = [
        'success' => true,
        'message' => 'Заявка успешно отправлена!',
        'request_code' => $requestCode,
        'request_id' => $requestId
    ];
    
    if ($blockCreated) {
        $response['block_created'] = true;
        $response['block_index'] = $blockResult['block_index'];
        $response['transactions_in_block'] = $blockResult['transactions'];
    } else {
        $response['block_created'] = false;
        $response['message'] = 'Заявка отправлена. Блок будет создан после накопления 2 заявок.';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>