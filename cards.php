<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Удаление карточки
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_card_id'])) {
    $card_id = (int)$_POST['delete_card_id'];
    
    $stmt = $pdo->prepare("UPDATE cards SET status = 'archived' WHERE id = ? AND user_id = ?");
    $stmt->execute([$card_id, $user_id]);
}

// Активные карточки текущего пользователя
$stmt_active = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? AND status IN ('pending','approved')");
$stmt_active->execute([$user_id]);
$cards_active = $stmt_active->fetchAll();

// Активные карточки других пользователей
$stmt_others = $pdo->prepare("
    SELECT c.*, u.login 
    FROM cards c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.user_id != ? AND c.status IN ('approved')
");
$stmt_others->execute([$user_id]);
$cards_others = $stmt_others->fetchAll();

// Архивные карточки текущего пользователя
$stmt_archived = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? AND status = 'archived'");
$stmt_archived->execute([$user_id]);
$cards_archived = $stmt_archived->fetchAll();

// Отклонённые карточки текущего пользователя
$stmt_rejected = $pdo->prepare("SELECT * FROM cards WHERE user_id = ? AND status = 'rejected'");
$stmt_rejected->execute([$user_id]);
$cards_rejected = $stmt_rejected->fetchAll();

?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8" />
    <title>Мои карточки - Буквоежка</title>
    <link rel="stylesheet" href="css/style.css" />
</head>

<body>
    <h1>Мои карточки</h1>
    <a href="add_card.php">Добавить карточку</a> |
    <a href="logout.php">Выйти</a>
    <?php if (!empty($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
    | <a href="admin_panel.php">Перейти в админку</a>
    <?php endif; ?>

    <h2>Активные карточки</h2>
    <?php if ($cards_active): ?>
    <ul>
        <?php foreach ($cards_active as $card): ?>
        <li>
            <strong><?= e($card['author']) ?></strong> — <?= e($card['title']) ?>
            (<?= e($card['type'] == 'share' ? 'Готов поделиться' : 'Хочу в библиотеку') ?>)
            [Статус: <?= e($card['status']) ?>]
            <form method="POST" style="display:inline" onsubmit="return confirm('Удалить эту карточку?');">
                <input type="hidden" name="delete_card_id" value="<?= $card['id'] ?>" />
                <button type="submit">Удалить</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>Активных карточек нет.</p>
    <?php endif; ?>

    <h2>Пользовательские</h2>
    <?php if ($cards_others): ?>
    <ul>
        <?php foreach ($cards_others as $card): ?>
        <li>
            <strong><?= e($card['author']) ?></strong> — <?= e($card['title']) ?>
            (<?= e($card['type'] == 'share' ? 'Готов поделиться' : 'Хочу в библиотеку') ?>)
            — <em>Пользователь: <?= e($card['login']) ?></em>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>Пользовательских карточек нет.</p>
    <?php endif; ?>

    <h2>Отклонённые карточки</h2>
    <?php if ($cards_rejected): ?>
    <ul>
        <?php foreach ($cards_rejected as $card): ?>
        <li>
            <strong><?= e($card['author']) ?></strong> — <?= e($card['title']) ?>
            (<?= e($card['type'] == 'share' ? 'Готов поделиться' : 'Хочу в библиотеку') ?>)
            <br>Причина отклонения: <?= e($card['rejection_reason']) ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Удалить эту карточку?');">
                <input type="hidden" name="delete_card_id" value="<?= $card['id'] ?>" />
                <button type="submit">Удалить</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>Отклонённых карточек нет.</p>
    <?php endif; ?>

    <h2>Архивные карточки</h2>
    <?php if ($cards_archived): ?>
    <ul>
        <?php foreach ($cards_archived as $card): ?>
        <li><strong><?= e($card['author']) ?></strong> — <?= e($card['title']) ?>
            (<?= e($card['type'] == 'share' ? 'Готов поделиться' : 'Хочу в библиотеку') ?>)</li>
        <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <p>Архивных карточек нет.</p>
    <?php endif; ?>

</body>

</html>