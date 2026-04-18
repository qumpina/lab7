<?php
// config.php - с защитой от уязвимостей

// Отключение display errors
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Скрываем версию PHP
header('X-Powered-By: unknown');

// Сессия
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Функция для безопасного вывода (XSS защита)
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// CSRF защита
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

// Конфигурация БД
define('DB_HOST', 'localhost');
define('DB_USER', 'u82092');
define('DB_PASS', '1557612');
define('DB_NAME', 'u82092');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    die("Ошибка подключения к базе данных. Пожалуйста, попробуйте позже.");
}

// Функции работы с БД (используют подготовленные запросы)
function getAllApplications($pdo) {
    $stmt = $pdo->query("SELECT a.* FROM application a ORDER BY a.created_at DESC");
    return $stmt->fetchAll();
}

function getLanguageStats($pdo) {
    $stmt = $pdo->query("
        SELECT pl.name, COUNT(al.language_id) as count
        FROM programming_languages pl
        LEFT JOIN application_languages al ON pl.id = al.language_id
        GROUP BY pl.id
        ORDER BY count DESC, pl.name
    ");
    return $stmt->fetchAll();
}

function getUserLanguages($pdo, $application_id) {
    $stmt = $pdo->prepare("
        SELECT pl.name FROM application_languages al
        JOIN programming_languages pl ON al.language_id = pl.id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$application_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Генерация логина и пароля
function generateLogin($full_name) {
    $clean_name = preg_replace('/[^a-zA-Z]/', '', $full_name);
    if (strlen($clean_name) < 4) {
        $clean_name = 'user';
    }
    $login = substr($clean_name, 0, 4);
    $login .= rand(100, 999);
    return strtolower($login);
}

function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}
?>