<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/csrf.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: index.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Ошибка CSRF';
    } else {
        $card_id = (int)($_POST['card_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['rejection_reason'] ?? '');

        // Проверка карточки
        $stmt = $pdo->prepare("SELECT * FROM cards WHERE id = ?");
        $stmt->execute([$card_id]);
        $card = $stmt->fetch();

        if (!$card) {
            $errors[] = 'Карточка не найдена.';
        } else {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE cards SET status = 'approved', rejection_reason = NULL WHERE id = ?");
                $stmt->execute([$card_id]);
                $success = 'Карточка одобрена.';
            } elseif ($action === 'reject') {
                if ($reason === '') {
                    $errors[] = 'Укажите причину отклонения.';
                } else {
                    $stmt = $pdo->prepare("UPDATE cards SET status = 'rejected', rejection_reason = ? WHERE id = ?");
                    $stmt->execute([$reason, $card_id]);
                    $success = 'Карточка отклонена.';
                }
            } else {
                $errors[] = 'Неверное действие.';
            }
        }
    }
}

$csrf_token = generate_csrf_token();

$stmt = $pdo->query("SELECT c.*, u.login FROM cards c JOIN users u ON c.user_id = u.id WHERE c.status = 'pending' ORDER BY c.created_at DESC");
$pending_cards = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <title>Панель администратора - Буквоежка</title>
    <link rel="stylesheet" href="css/style.css" />
    <style>
        textarea {
            width: 100%;
            height: 50px;
        }
    </style>
</head>

<body>
    <h1>Панель администратора</h1>
    <a href="cards.php">Выйти из админки</a>

    <?php if ($success): ?>
    <div class="success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="errors">
        <ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <?php if ($pending_cards): ?>
    <?php foreach ($pending_cards as $card): ?>
    <div class="card" style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <p><strong>Пользователь:</strong> <?= e($card['login']) ?></p>
        <p><strong>Автор:</strong> <?= e($card['author']) ?></p>
        <p><strong>Название:</strong> <?= e($card['title']) ?></p>
        <p><strong>Тип:</strong> <?= e($card['type'] == 'share' ? 'Готов поделиться' : 'Хочу в библиотеку') ?></p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>" />
            <input type="hidden" name="card_id" value="<?= $card['id'] ?>" />
            <button name="action" value="approve" type="submit">Одобрить</button>
            <br><br>
            <label>Причина отклонения:<br>
                <textarea name="rejection_reason"></textarea>
            </label><br>
            <button name="action" value="reject" type="submit">Отклонить</button>
        </form>
    </div>
    <?php endforeach; ?>
    <?php else: ?>
    <p>Нет новых карточек для рассмотрения.</p>
    <?php endif; ?>

</body>

</html>