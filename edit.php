<?php
// edit.php
session_start();
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$application_id = $_SESSION['application_id'];
$user_data = $_SESSION['user_data'];

// Функция валидации
function validateData($data) {
    $errors = [];
    
    $full_name = trim($data['full_name'] ?? '');
    if (empty($full_name)) {
        $errors['full_name'] = "Поле ФИО обязательно";
    } elseif (!preg_match('/^[а-яА-ЯёЁa-zA-Z\s-]{2,150}$/u', $full_name)) {
        $errors['full_name'] = "ФИО должно содержать только буквы, пробелы и дефисы";
    }
    
    $phone = trim($data['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = "Поле Телефон обязательно";
    } elseif (!preg_match('/^[0-9+\-\s]{10,20}$/', $phone)) {
        $errors['phone'] = "Телефон должен содержать только цифры, +, - и пробелы";
    }
    
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = "Поле Email обязательно";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors['email'] = "Введите корректный email";
    }
    
    $birth_date = $data['birth_date'] ?? '';
    if (empty($birth_date)) {
        $errors['birth_date'] = "Поле Дата рождения обязательно";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
        $errors['birth_date'] = "Неверный формат даты";
    }
    
    $gender = $data['gender'] ?? '';
    if (!in_array($gender, ['male', 'female', 'other'])) {
        $errors['gender'] = "Выберите пол";
    }
    
    $languages = $data['languages'] ?? [];
    if (empty($languages)) {
        $errors['languages'] = "Выберите хотя бы один язык";
    }
    
    return $errors;
}

// Обработка POST запроса (сохранение изменений)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateData($_POST);
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Обновление данных в application
            $stmt = $pdo->prepare("
                UPDATE application 
                SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['full_name'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['birth_date'],
                $_POST['gender'],
                $_POST['biography'] ?? '',
                $application_id
            ]);
            
            // Удаление старых языков
            $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$application_id]);
            
            // Вставка новых языков
            $lang_stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($_POST['languages'] as $lang_name) {
                $lang_id_stmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
                $lang_id_stmt->execute([$lang_name]);
                $lang_id = $lang_id_stmt->fetchColumn();
                if ($lang_id) {
                    $lang_stmt->execute([$application_id, $lang_id]);
                }
            }
            
            $pdo->commit();
            
            // Обновляем данные в сессии
            $_SESSION['user_data'] = [
                'full_name' => $_POST['full_name'],
                'phone' => $_POST['phone'],
                'email' => $_POST['email'],
                'birth_date' => $_POST['birth_date'],
                'gender' => $_POST['gender'],
                'biography' => $_POST['biography'] ?? '',
                'languages' => $_POST['languages']
            ];
            
            $success = "Данные успешно обновлены!";
            $user_data = $_SESSION['user_data'];
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Ошибка при сохранении: " . $e->getMessage();
        }
    }
}

// Получаем текущие данные пользователя
$languages_list = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 
                   'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование анкеты - Лабораторная работа 5</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="header">
        <h2>Редактирование анкеты</h2>
        <div class="user-info">
            Вы вошли как: <strong><?php echo htmlspecialchars($_SESSION['user_login']); ?></strong>
            <a href="logout.php" class="btn-link">Выйти</a>
            <a href="index.php" class="btn-link">На главную</a>
        </div>
    </div>
    
    <?php if (isset($success)): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="edit.php">
        <div class="form-group">
            <label for="fio" class="required">ФИО:</label>
            <input type="text" id="fio" name="full_name" 
                   value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="phone" class="required">Телефон:</label>
            <input type="tel" id="phone" name="phone" 
                   value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="email" class="required">E-mail:</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label for="birthdate" class="required">Дата рождения:</label>
            <input type="date" id="birthdate" name="birth_date" 
                   value="<?php echo htmlspecialchars($user_data['birth_date'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label class="required">Пол:</label>
            <div class="radio-group">
                <div>
                    <input type="radio" name="gender" value="male" 
                           <?php echo ($user_data['gender'] ?? '') === 'male' ? 'checked' : ''; ?>>
                    <label>Мужской</label>
                </div>
                <div>
                    <input type="radio" name="gender" value="female" 
                           <?php echo ($user_data['gender'] ?? '') === 'female' ? 'checked' : ''; ?>>
                    <label>Женский</label>
                </div>
                <div>
                    <input type="radio" name="gender" value="other" 
                           <?php echo ($user_data['gender'] ?? '') === 'other' ? 'checked' : ''; ?>>
                    <label>Другой</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="language" class="required">Любимые языки программирования:</label>
            <select id="language" name="languages[]" multiple size="6" required>
                <?php
                $selected_langs = $user_data['languages'] ?? [];
                foreach ($languages_list as $lang):
                ?>
                    <option value="<?php echo $lang; ?>" 
                        <?php echo in_array($lang, $selected_langs) ? 'selected' : ''; ?>>
                        <?php echo $lang; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="bio">Биография:</label>
            <textarea id="bio" name="biography" rows="5"><?php echo htmlspecialchars($user_data['biography'] ?? ''); ?></textarea>
        </div>

        <button type="submit">Сохранить изменения</button>
    </form>
</body>
</html>