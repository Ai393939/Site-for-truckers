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
    
    $emailBody = "НОВАЯ ЗАЯВКА НА ПЕРЕВОЗКУ\n";
    $emailBody .= "Дата: " . date('d.m.Y H:i:s') . "\n\n";
    
    $emailBody .= "=== ОТПРАВИТЕЛЬ ===\n";
    $emailBody .= "Город: " . htmlspecialchars($data['citySender']) . "\n";
    $emailBody .= "Компания: " . htmlspecialchars($data['nameSender']) . "\n";
    $emailBody .= "Адрес: " . htmlspecialchars($data['adressSender']) . "\n";
    $emailBody .= "Телефон: " . htmlspecialchars($data['numberSender']) . "\n";
    $emailBody .= "ИНН/УНП: " . htmlspecialchars($data['innSender']) . "\n";
    
    if (!empty($data['timeStartSender']) || !empty($data['timeEndSender'])) {
        $emailBody .= "Время работы: " . htmlspecialchars($data['timeStartSender']) . " - " . htmlspecialchars($data['timeEndSender']) . "\n";
    }
    
    if (!empty($data['cityRecipient'])) {
        $emailBody .= "\n=== ПОЛУЧАТЕЛЬ ===\n";
        $emailBody .= "Город: " . htmlspecialchars($data['cityRecipient']) . "\n";
        $emailBody .= "Компания: " . htmlspecialchars($data['nameRecipient']) . "\n";
        $emailBody .= "Адрес: " . htmlspecialchars($data['adressRecipient']) . "\n";
        $emailBody .= "Телефон: " . htmlspecialchars($data['numberRecipient']) . "\n";
        $emailBody .= "ИНН/УНП: " . htmlspecialchars($data['innRecipient']) . "\n";
    }
    
    if (!empty($data['nameFreight']) || !empty($data['countFreight'])) {
        $emailBody .= "\n=== ХАРАКТЕРИСТИКИ ГРУЗА ===\n";
        $emailBody .= "Наименование: " . htmlspecialchars($data['nameFreight']) . "\n";
        $emailBody .= "Количество: " . htmlspecialchars($data['countFreight']) . " шт.\n";
        $emailBody .= "Вес: " . htmlspecialchars($data['weightFreight']) . " кг\n";
        $emailBody .= "Объем: " . htmlspecialchars($data['volumeFreight']) . " м³\n";
        $emailBody .= "Стоимость: " . htmlspecialchars($data['costFreight']) . " руб.\n";
        
        if (!empty($data['lengthFreight']) || !empty($data['widthFreight']) || !empty($data['heightFreight'])) {
            $emailBody .= "Габариты: " . htmlspecialchars($data['lengthFreight']) . "×" . 
                         htmlspecialchars($data['widthFreight']) . "×" . 
                         htmlspecialchars($data['heightFreight']) . " см\n";
        }
    }
    
    if (!empty($data['insurance']) || !empty($data['dop1']) || !empty($data['dop2'])) {
        $emailBody .= "\n=== ДОПОЛНИТЕЛЬНО ===\n";
        $emailBody .= "Страхование: " . (isset($data['insurance']) ? "Да" : "Нет") . "\n";
        $emailBody .= "Доставка до получателя: " . (isset($data['dop1']) ? "Да" : "Нет") . "\n";
        $emailBody .= "Доверенность: " . (isset($data['dop2']) ? "Да" : "Нет") . "\n";
    }
    
    if (!empty($data['comment'])) {
        $emailBody .= "\n=== КОММЕНТАРИЙ ===\n";
        $emailBody .= htmlspecialchars($data['comment']) . "\n";
    }
    
    $requestId = 'REQ-' . date('Ymd') . '-' . rand(1000, 9999);
    
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/requests.log';
    $logEntry = date('Y-m-d H:i:s') . " | ID: $requestId\n" . $emailBody . "\n" . str_repeat('=', 80) . "\n\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    $response = [
        'success' => true,
        'message' => 'Заявка успешно отправлена! Наш менеджер свяжется с вами в ближайшее время.',
        'request_id' => $requestId,
        'note' => 'Заявка сохранена в логах. В будущем будет добавлена отправка на email.'
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при отправке заявки: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>