<?php
// config.php
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
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Функция для генерации логина
function generateLogin($full_name) {
    $clean_name = preg_replace('/[^a-zA-Z]/', '', $full_name);
    if (strlen($clean_name) < 4) {
        $clean_name = 'user';
    }
    $login = substr($clean_name, 0, 4);
    $login .= rand(100, 999);
    return strtolower($login);
}

// Функция для генерации пароля
function generatePassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

// Функция для получения всех языков
function getAllLanguages($pdo) {
    $stmt = $pdo->query("SELECT id, name FROM programming_languages ORDER BY name");
    return $stmt->fetchAll();
}

// Функция для получения языков пользователя
function getUserLanguages($pdo, $application_id) {
    $stmt = $pdo->prepare("
        SELECT pl.name FROM application_languages al
        JOIN programming_languages pl ON al.language_id = pl.id
        WHERE al.application_id = ?
    ");
    $stmt->execute([$application_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Функция для получения всех заявок
function getAllApplications($pdo) {
    $stmt = $pdo->query("
        SELECT a.* FROM application a 
        ORDER BY a.created_at DESC
    ");
    return $stmt->fetchAll();
}

// Функция для статистики по языкам
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

// Функция для проверки администратора
function checkAdminAuth($pdo) {
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        return false;
    }
    
    $login = $_SERVER['PHP_AUTH_USER'];
    $password = $_SERVER['PHP_AUTH_PW'];
    
    $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        return true;
    }
    return false;
}

// Функция для HTTP-аутентификации
function authenticateAdmin($pdo) {
    if (!checkAdminAuth($pdo)) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="Admin Panel - Lab6"');
        echo '<!DOCTYPE html>
        <html>
        <head><title>401 Требуется авторизация</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #f5f5f5; }
            .error { background: #fee; color: #c33; padding: 20px; border-radius: 8px; display: inline-block; }
        </style>
        </head>
        <body>
            <div class="error">
                <h1>401 Требуется авторизация</h1>
                <p>Доступ разрешен только администраторам</p>
                <p>Используйте логин и пароль администратора</p>
            </div>
        </body>
        </html>';
        exit();
    }
}
?>