<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim($path, '/');

if (strpos($path, 'api/') === 0) {
    $apiFile = substr($path, 4);
    if ($apiFile === 'feedback.php') {
        include 'api/feedback.php';
        exit;
    }
}

if (empty($path)) {
    include 'MainPage.html';
    exit;
}

switch ($path) {
    case 'submit-request':
        include 'SubmitRequest.html';
        break;
    case 'feedback':
        include 'Feedback.html';
        break;
    case 'search':
        if (isset($_GET['query'])) {
            $search_query = htmlspecialchars($_GET['query']);
            echo "<h1>Результаты поиска: $search_query</h1>";
        }
        break;
    default:
        http_response_code(404);
        echo "<h1>Страница не найдена</h1>";
        echo "<p>Запрошенный адрес: /$path</p>";
        break;
}
?>