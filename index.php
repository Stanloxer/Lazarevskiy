<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

$errors = [];

// Базовая защита от brute force: ограничение по IP и сессии
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка CSRF';
    }

    if ($_SESSION['login_attempts'] >= 5 && (time() - $_SESSION['last_attempt_time']) < 60) {
        $errors[] = 'Слишком много попыток входа. Попробуйте через 1 минуту.';
    }

    if (empty($errors)) {
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        
        if ($login === 'admin' && $password === 'bookworm') {
            session_regenerate_id(true);
            $_SESSION['user_id'] = 0; 
            $_SESSION['is_admin'] = true;
            $_SESSION['login_attempts'] = 0;

            header('Location: admin_panel.php');
            exit();
        }

        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
         
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = $user['is_admin'];
            $_SESSION['login_attempts'] = 0;

            // Перенаправление в зависимости от роли
            if ($user['is_admin']) {
                header('Location: admin_panel.php');
            } else {
                header('Location: cards.php');
            }
            exit();
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $errors[] = 'Неверный логин или пароль.';
        }
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <title>Вход - Буквоежка</title>
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>
    <h1>Вход в систему - Буквоежка</h1>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['success'])): ?>
    <div class="success"><?= e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>" />
        <label>Логин:<br>
            <input type="text" name="login" required />
        </label><br>
        <label>Пароль:<br>
            <input type="password" name="password" required />
        </label><br>
        <button type="submit">Войти</button>
    </form>

    <a href="register.php">Регистрация</a>
</body>
</html>
