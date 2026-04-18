<?php
// create_admin.php - Создание администратора (запустить один раз)
require_once 'config.php';

$login = 'admin';
$password = 'admin123';
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO admin_users (login, password_hash) VALUES (?, ?)");
    $stmt->execute([$login, $password_hash]);
    echo "Администратор создан!<br>";
    echo "Логин: admin<br>";
    echo "Пароль: admin123<br>";
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        echo "Администратор уже существует!<br>";
    } else {
        echo "Ошибка: " . $e->getMessage() . "<br>";
    }
}
?>