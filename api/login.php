<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /LoginBlockchain');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

require_once __DIR__ . '/db.php';
$db = Database::getInstance()->getConnection();

$query = "SELECT * FROM admins WHERE username = '$username' AND password = '$password'";
$result = $db->query($query);

if ($result && $result->rowCount() > 0) {
    $_SESSION['blockchain_access'] = true;
    
    $_SESSION['hacked'] = false;
    
    if (strpos($username, "'") !== false || 
        strpos($username, "OR") !== false || 
        strpos($username, "--") !== false) {
        $_SESSION['hacked'] = true;
    }
    
    header('Location: /blocks');
    exit;
}
?>