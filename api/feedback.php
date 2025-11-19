<?php
require_once __DIR__ . '/../vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('No data received');
    }
    
    $required = ['type', 'name', 'email', 'message'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $client = new MongoDB\Client('mongodb://mongodb:27017');
    $collection = $client->leku_trans->feedback;
    
    $data['createdAt'] = new MongoDB\BSON\UTCDateTime();
    
    $result = $collection->insertOne($data);
    
    echo json_encode([
        'success' => true,
        'message' => 'Сообщение сохранено в MongoDB!',
        'id' => (string)$result->getInsertedId()
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>