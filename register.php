<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка CSRF';
    }

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!validate_login($login)) {
        $errors[] = 'Логин должен содержать минимум 6 символов кириллицей.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть минимум 6 символов.';
    }
    if (!validate_fullname($fullname)) {
        $errors[] = 'ФИО должно содержать только кириллицу и пробелы.';
    }
    if (!validate_phone($phone)) {
        $errors[] = 'Телефон должен быть в формате +7(XXX)-XXX-XX-XX.';
    }
    if (!validate_email($email)) {
        $errors[] = 'Некорректный формат электронной почты.';
    }

    // Проверка уникальности логина
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->execute([$login]);
        if ($stmt->fetch()) {
            $errors[] = 'Такой логин уже существует.';
        }
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (login, password, fullname, phone, email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$login, $hash, $fullname, $phone, $email]);
        $_SESSION['success'] = 'Регистрация прошла успешно. Войдите в систему.';
        header('Location: index.php');
        exit();
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <title>Регистрация - Буквоежка</title>
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <h1>Регистрация</h1>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= e($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>" />
        <label>Логин (кириллица, минимум 6 символов):<br>
            <input type="text" name="login" value="<?= e($_POST['login'] ?? '') ?>" required pattern="^[а-яё]{6,}$"
                title="Минимум 6 кириллических символов" />
        </label><br>
        <label>Пароль (мин. 6 символов):<br>
            <input type="password" name="password" required minlength="6" />
        </label><br>
        <label>ФИО:<br>
            <input type="text" name="fullname" value="<?= e($_POST['fullname'] ?? '') ?>" required pattern="^[а-яё\s]+$"
                title="Только кириллица и пробелы" />
        </label><br>
        <label>Телефон (+7(XXX)-XXX-XX-XX):<br>
            <input type="tel" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" required
                pattern="^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$" title="+7(XXX)-XXX-XX-XX" />
        </label><br>
        <label>Email:<br>
            <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required />
        </label><br>
        <button type="submit">Зарегистрироваться</button>
    </form>
    <a href="index.php">Войти</a>
</body>

</html>