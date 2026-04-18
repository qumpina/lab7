<?php
// login.php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = "Введите логин и пароль";
    } else {
        // Поиск пользователя в БД
        $stmt = $pdo->prepare("
            SELECT au.id, au.application_id, au.login, au.password_hash, 
                   a.full_name, a.phone, a.email, a.birth_date, a.gender, a.biography
            FROM application_users au
            JOIN application a ON au.application_id = a.id
            WHERE au.login = ?
        ");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['application_id'] = $user['application_id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_data'] = [
                'full_name' => $user['full_name'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'birth_date' => $user['birth_date'],
                'gender' => $user['gender'],
                'biography' => $user['biography']
            ];
            
            // Получаем языки пользователя
            $lang_stmt = $pdo->prepare("
                SELECT pl.name FROM application_languages al
                JOIN programming_languages pl ON al.language_id = pl.id
                WHERE al.application_id = ?
            ");
            $lang_stmt->execute([$user['application_id']]);
            $languages = $lang_stmt->fetchAll(PDO::FETCH_COLUMN);
            $_SESSION['user_data']['languages'] = $languages;
            
            header('Location: edit.php');
            exit;
        } else {
            $error = "Неверный логин или пароль";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - Лабораторная работа 5</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-background {
            width: 600px;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .login-container h2 {
            color: #333;
            margin-top: 0;
            background: white;
            border-radius: 15px;
            padding: 20px;
        }
        .back-link {
            height: 40px;
            font-size: 24px;
            display: block;
            text-align: center;
            margin-top: 20px;
            color: black;
            background: white;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход для редактирования</h2>
        
        <?php if ($error): ?>
            <div class="error-message" style="margin-bottom: 20px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php" class="form-background">
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Войти</button>
        </form>
        
        <a href="index.php" class="back-link">← Вернуться к форме</a>
    </div>
</body>
</html>