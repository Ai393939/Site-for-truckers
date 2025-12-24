<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

mb_internal_encoding('UTF-8');

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

if (strpos($path, 'api/') === 0) {
    $api_file = substr($path, 4);
    $api_path = __DIR__ . '/api/' . $api_file;
    
    if (file_exists($api_path)) {
        require $api_path;
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot)$/', $path)) {
    return false;
}

$routes = [
    '' => 'MainPage.html',
    'index' => 'MainPage.html',
    'main' => 'MainPage.html',
    'submit-request' => 'SubmitRequest.html',
    'feedback' => 'Feedback.html'
];

if (isset($routes[$path])) {
    $file = __DIR__ . '/' . $routes[$path];
    if (file_exists($file)) {
        include $file;
    } else {
        show404($path);
    }
} else {
    $html_file = __DIR__ . '/' . $path . '.html';
    if (file_exists($html_file)) {
        include $html_file;
    } else {
        show404($path);
    }
}

function show404($path) {
    http_response_code(404);
    echo '<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>404 - Страница не найдена</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <header>
            <div class="first-row">
                <div class="logo">
                    <h1><a href="/">ООО "Леку-Транс"</a></h1>
                </div>
            </div>
        </header>
        <main>
            <div style="text-align: center; padding: 100px;">
                <h1>404 - Страница не найдена</h1>
                <p>Запрошенный адрес: /' . htmlspecialchars($path) . '</p>
                <p><a href="/" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Вернуться на главную</a></p>
            </div>
        </main>
        <footer>
            <a href="tel:+79991234567">+7 (999) 123-45-67</a>
            <p>ООО "Леку-Транс"</p>
        </footer>
    </body>
    </html>';
}
?>