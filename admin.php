<?php
// admin.php - Панель администратора с HTTP-авторизацией
require_once 'config.php';

// HTTP-авторизация (простая версия, как в test_auth.php)
if (empty($_SERVER['PHP_AUTH_USER']) || 
    empty($_SERVER['PHP_AUTH_PW']) || 
    $_SERVER['PHP_AUTH_USER'] != 'admin' || 
    $_SERVER['PHP_AUTH_PW'] != 'admin123') {
    
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
            <p>Логин: <strong>admin</strong></p>
            <p>Пароль: <strong>admin123</strong></p>
        </div>
    </body>
    </html>';
    exit();
}

// Если дошли сюда - авторизация успешна
// echo "Авторизация успешна!<br>"; // можно раскомментировать для проверки

// Обработка действий
$message = '';
$error = '';

// Удаление записи
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM application WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Запись #$id успешно удалена";
    } catch (PDOException $e) {
        $error = "Ошибка при удалении: " . $e->getMessage();
    }
}

// Получение всех заявок
$applications = getAllApplications($pdo);
$total_count = count($applications);
$language_stats = getLanguageStats($pdo);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Лабораторная 6</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .header h1 {
            color: #333;
            font-size: 1.8em;
        }
        
        .header .admin-info {
            color: #666;
        }
        
        .header .admin-info strong {
            color: #667eea;
        }
        
        .stats-container {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .stats-container h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card .lang-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
        }
        
        .stat-card .lang-count {
            font-size: 28px;
            font-weight: bold;
        }
        
        .total-card {
            background: #2c3e50;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e1e1e1;
        }
        
        th {
            background: #f8f9fa;
            color: #333;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
            background: #f5f5f5;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 13px;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-edit:hover {
            background: #e0a800;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c82333;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s;
        }
        
        .back-link:hover {
            background: #5a6268;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e9ecef;
            border-radius: 12px;
            font-size: 12px;
            margin: 2px;
        }
        
        .empty-row td {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            th, td {
                padding: 8px 10px;
                font-size: 12px;
            }
            .stats-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👑 Панель администратора</h1>
            <div class="admin-info">
                Вы вошли как: <strong><?php echo htmlspecialchars($_SERVER['PHP_AUTH_USER']); ?></strong>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-container">
            <h2>📊 Статистика по языкам программирования</h2>
            <div class="stats-grid">
                <?php foreach ($language_stats as $stat): ?>
                    <div class="stat-card">
                        <div class="lang-name"><?php echo htmlspecialchars($stat['name']); ?></div>
                        <div class="lang-count"><?php echo $stat['count']; ?></div>
                        <div style="font-size: 12px; opacity: 0.8;">пользователей</div>
                    </div>
                <?php endforeach; ?>
                <div class="stat-card total-card">
                    <div class="lang-name">📋 Всего</div>
                    <div class="lang-count"><?php echo $total_count; ?></div>
                    <div style="font-size: 12px; opacity: 0.8;">заявок</div>
                </div>
            </div>
        </div>
        
        <!-- Таблица с данными -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Языки программирования</th>
                        <th>Дата создания</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr class="empty-row">
                            <td colspan="9">Нет данных для отображения</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <?php $user_langs = getUserLanguages($pdo, $app['id']); ?>
                            <tr>
                                <td><?php echo $app['id']; ?></td>
                                <td><?php echo htmlspecialchars($app['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['phone']); ?></td>
                                <td><?php echo htmlspecialchars($app['email']); ?></td>
                                <td><?php echo date('d.m.Y', strtotime($app['birth_date'])); ?></td>
                                <td>
                                    <?php 
                                    $genders = ['male' => 'Мужской', 'female' => 'Женский', 'other' => 'Другой'];
                                    echo $genders[$app['gender']] ?? $app['gender'];
                                    ?>
                                </td>
                                <td>
                                    <?php foreach ($user_langs as $lang): ?>
                                        <span class="badge"><?php echo htmlspecialchars($lang); ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($app['created_at'])); ?></td>
                                <td class="actions">
                                    <a href="admin_edit.php?id=<?php echo $app['id']; ?>" class="btn btn-edit">✏️ Редактировать</a>
                                    <a href="?delete=<?php echo $app['id']; ?>" class="btn btn-delete" onclick="return confirm('Удалить запись #<?php echo $app['id']; ?>?')">🗑️ Удалить</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <a href="index.php" class="back-link">← Вернуться на главную</a>
    </div>
</body>
</html>