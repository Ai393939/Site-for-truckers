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

    $emailSubject = "Обратная связь с сайта: $type";

    $emailBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 15px; text-align: center; }
            .content { padding: 20px; border: 1px solid #ddd; margin-top: 20px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #2c3e50; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #777; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Новое сообщение с сайта</h1>
                <p>ID: $feedbackId</p>
            </div>
            
            <div class='content'>
                <div class='field'>
                    <span class='label'>Тип сообщения:</span>
                    <span>" . htmlspecialchars($type) . "</span>
                </div>
                
                <div class='field'>
                    <span class='label'>Имя отправителя:</span>
                    <span>" . htmlspecialchars($name) . "</span>
                </div>
                
                <div class='field'>
                    <span class='label'>Email:</span>
                    <span>" . htmlspecialchars($email) . "</span>
                </div>
                
                <div class='field'>
                    <span class='label'>Сообщение:</span>
                    <div style='margin-top: 10px; padding: 10px; background: #f9f9f9; border-left: 4px solid #3498db;'>
                        " . nl2br(htmlspecialchars($message)) . "
                    </div>
                </div>
                
                <div class='field'>
                    <span class='label'>Дата отправки:</span>
                    <span>" . date('d.m.Y H:i:s') . "</span>
                </div>
            </div>
            
            <div class='footer'>
                <p>Это сообщение было отправлено с формы обратной связи сайта ООО \&quot;Леку-Транс\&quot;</p>
                <p>Для ответа используйте email: " . htmlspecialchars($email) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";

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


    $adminEmail = 'ваш_почта@gmail.com';
    $siteEmail = 'info@beltransgroup.by';
    $siteName = 'ООО "Леку-Транс"';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $siteName . " <" . $siteEmail . ">\r\n";
    $headers .= "Reply-To: " . htmlspecialchars($name) . " <" . htmlspecialchars($email) . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $emailSent = mail($adminEmail, $emailSubject, $emailBody, $headers);

    if ($emailSent) {
        $clientSubject = "Спасибо за обращение в $siteName";
        $clientBody = "
        <h2>Уважаемый(ая) " . htmlspecialchars($name) . "!</h2>
        <p>Мы получили ваше сообщение типа <strong>" . htmlspecialchars($type) . "</strong>.</p>
        <p>Наш менеджер рассмотрит его и ответит вам в ближайшее время.</p>
        <p><strong>Номер вашей заявки:</strong> $feedbackId</p>
        <p>Если ваш вопрос срочный, звоните: <strong>+375 (29) 123-45-67</strong></p>
        <p>С уважением,<br>Команда $siteName</p>
        ";

        $clientHeaders = "MIME-Version: 1.0\r\n";
        $clientHeaders .= "Content-Type: text/html; charset=UTF-8\r\n";
        $clientHeaders .= "From: " . $siteName . " <" . $siteEmail . ">\r\n";

        mail($email, $clientSubject, $clientBody, $clientHeaders);
    }

    $response = [
        'success' => true,
        'message' => 'Сообщение успешно отправлено! Мы ответим вам в ближайшее время.',
        'id' => $feedbackId,
        'email_sent' => $emailSent
    ];

    if (!$emailSent) {
        $response['note'] = 'Сообщение сохранено, но возникла проблема с отправкой email. Мы свяжемся с вами по телефону.';
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при отправке сообщения: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>