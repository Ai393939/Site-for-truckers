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
    
    $type = $_POST['type'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($type) || empty($name) || empty($email) || empty($message)) {
        throw new Exception('Заполните все обязательные поля');
    }
    
    $feedbackId = 'MSG-' . date('YmdHis') . '-' . rand(100, 999);
    
    $logContent = "Новое сообщение #$feedbackId\n";
    $logContent .= "Тип: " . htmlspecialchars($type) . "\n";
    $logContent .= "Имя: " . htmlspecialchars($name) . "\n";
    $logContent .= "Email: " . htmlspecialchars($email) . "\n";
    $logContent .= "Сообщение: " . htmlspecialchars($message) . "\n";
    $logContent .= "Дата: " . date('d.m.Y H:i:s') . "\n";
    $logContent .= str_repeat('-', 50) . "\n\n";
    
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/feedback.log';
    file_put_contents($logFile, $logContent, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Сообщение успешно отправлено! Мы ответим вам в ближайшее время.',
        'id' => $feedbackId,
        'note' => 'Сообщение сохранено в логах. В будущем будет добавлена отправка на email.'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при отправке сообщения: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>