<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $host = 'mysql';
    $dbname = 'leku_trans';
    $username = 'user';
    $password = 'password';
    $port = 3306;
    
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка подключения к базе данных: ' . $e->getMessage()
    ]);
    exit;
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
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        city_sender VARCHAR(100) NOT NULL,
        name_sender VARCHAR(200) NOT NULL,
        address_sender VARCHAR(300) NOT NULL,
        phone_sender VARCHAR(20) NOT NULL,
        inn_sender VARCHAR(12) NOT NULL,
        time_start_sender TIME,
        time_end_sender TIME,
        city_recipient VARCHAR(100),
        name_recipient VARCHAR(200),
        address_recipient VARCHAR(300),
        phone_recipient VARCHAR(20),
        inn_recipient VARCHAR(12),
        freight_count INT,
        freight_name VARCHAR(200),
        freight_cost DECIMAL(10,2),
        freight_weight DECIMAL(10,2),
        freight_volume DECIMAL(10,2),
        freight_length DECIMAL(10,2),
        freight_width DECIMAL(10,2),
        freight_height DECIMAL(10,2),
        insurance ENUM('yes', 'no') DEFAULT 'no',
        delivery_needed ENUM('yes', 'no') DEFAULT 'no',
        power_of_attorney ENUM('yes', 'no') DEFAULT 'no',
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createTableSQL);
    
    $sql = "INSERT INTO requests (
        city_sender, name_sender, address_sender, phone_sender, inn_sender,
        time_start_sender, time_end_sender,
        city_recipient, name_recipient, address_recipient, phone_recipient, inn_recipient,
        freight_count, freight_name, freight_cost, freight_weight, freight_volume,
        freight_length, freight_width, freight_height,
        insurance, delivery_needed, power_of_attorney, comment
    ) VALUES (
        :citySender, :nameSender, :adressSender, :numberSender, :innSender,
        :timeStartSender, :timeEndSender,
        :cityRecipient, :nameRecipient, :adressRecipient, :numberRecipient, :innRecipient,
        :countFreight, :nameFreight, :costFreight, :weightFreight, :volumeFreight,
        :lengthFreight, :widthFreight, :heightFreight,
        :insurance, :dop1, :dop2, :comment
    )";
    
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindValue(':citySender', $data['citySender'] ?? '');
    $stmt->bindValue(':nameSender', $data['nameSender'] ?? '');
    $stmt->bindValue(':adressSender', $data['adressSender'] ?? '');
    $stmt->bindValue(':numberSender', $data['numberSender'] ?? '');
    $stmt->bindValue(':innSender', $data['innSender'] ?? '');
    $stmt->bindValue(':timeStartSender', $data['timeStartSender'] ?? null);
    $stmt->bindValue(':timeEndSender', $data['timeEndSender'] ?? null);
    $stmt->bindValue(':cityRecipient', $data['cityRecipient'] ?? '');
    $stmt->bindValue(':nameRecipient', $data['nameRecipient'] ?? '');
    $stmt->bindValue(':adressRecipient', $data['adressRecipient'] ?? '');
    $stmt->bindValue(':numberRecipient', $data['numberRecipient'] ?? '');
    $stmt->bindValue(':innRecipient', $data['innRecipient'] ?? '');
    $stmt->bindValue(':countFreight', $data['countFreight'] ?? null, PDO::PARAM_INT);
    $stmt->bindValue(':nameFreight', $data['nameFreight'] ?? '');
    $stmt->bindValue(':costFreight', $data['costFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':weightFreight', $data['weightFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':volumeFreight', $data['volumeFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':lengthFreight', $data['lengthFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':widthFreight', $data['widthFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':heightFreight', $data['heightFreight'] ?? null, PDO::PARAM_STR);
    $stmt->bindValue(':insurance', isset($data['insurance']) && $data['insurance'] === 'Да' ? 'yes' : 'no');
    $stmt->bindValue(':dop1', isset($data['dop1']) && $data['dop1'] === 'Да' ? 'yes' : 'no');
    $stmt->bindValue(':dop2', isset($data['dop2']) && $data['dop2'] === 'Да' ? 'yes' : 'no');
    $stmt->bindValue(':comment', $data['comment'] ?? '');
    
    $stmt->execute();
    $requestId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно сохранена!',
        'request_id' => $requestId,
        'data' => [
            'id' => $requestId,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка при сохранении заявки: ' . $e->getMessage()
    ]);
}
?>