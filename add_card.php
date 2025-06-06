<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка CSRF';
    }

    $author = trim($_POST['author'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $type = $_POST['type'] ?? '';
    $publisher = trim($_POST['publisher'] ?? '');
    $year = $_POST['year'] ?? null;
    $binding = trim($_POST['binding'] ?? '');
    $condition_book = trim($_POST['condition_book'] ?? '');

    if ($author === '') {
        $errors[] = 'Автор обязателен.';
    }
    if ($title === '') {
        $errors[] = 'Название книги обязательно.';
    }
    if (!in_array($type, ['share', 'want'])) {
        $errors[] = 'Неверный тип карточки.';
    }

    if ($year !== null && $year !== '' && (!preg_match('/^\d{4}$/', $year) || $year < 1000 || $year > (int)date('Y'))) {
        $errors[] = 'Неверный год издания.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO cards (user_id, author, title, type, publisher, year, binding, condition_book) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $author, $title, $type, $publisher, $year ?: null, $binding, $condition_book]);
        $_SESSION['success'] = 'Карточка отправлена на рассмотрение.';
        header('Location: cards.php');
        exit();
    }
}

$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <title>Добавить карточку - Буквоежка</title>
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <h1>Добавить карточку</h1>
    <a href="cards.php">Назад</a>

    <?php if (!empty($errors)): ?>
    <div class="errors">
        <ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>" />
        <label>Автор книги:<br><input type="text" name="author" value="<?= e($_POST['author'] ?? '') ?>"
                required /></label><br>
        <label>Название книги:<br><input type="text" name="title" value="<?= e($_POST['title'] ?? '') ?>"
                required /></label><br>
        <label>Тип карточки:<br>
            <input type="radio" id="share" name="type" value="share"
                <?= (($_POST['type'] ?? '') === 'share') ? 'checked' : '' ?> required />
            <label for="share">Готов поделиться</label>
            <input type="radio" id="want" name="type" value="want"
                <?= (($_POST['type'] ?? '') === 'want') ? 'checked' : '' ?> />
            <label for="want">Хочу в библиотеку</label>
        </label><br>
        <label>Издательство:<br><input type="text" name="publisher"
                value="<?= e($_POST['publisher'] ?? '') ?>" /></label><br>
        <label>Год издания:<br><input type="number" name="year" min="1000" max="<?= date('Y') ?>"
                value="<?= e($_POST['year'] ?? '') ?>" /></label><br>
        <label>Переплёт:<br><input type="text" name="binding" value="<?= e($_POST['binding'] ?? '') ?>" /></label><br>
        <label>Состояние книги:<br><input type="text" name="condition_book"
                value="<?= e($_POST['condition_book'] ?? '') ?>" /></label><br>
        <button type="submit">Отправить на рассмотрение</button>
    </form>
</body>

</html>