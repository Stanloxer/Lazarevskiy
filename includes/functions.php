<?php

// Очистка вывода для защиты от XSS
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Проверка email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Проверка формата телефона
function validate_phone($phone) {
    return preg_match('/^\+7\(\d{3}\)-\d{3}-\d{2}-\d{2}$/', $phone);
}

// Проверка логина
function validate_login($login) {
    return preg_match('/^[а-яё]{6,}$/iu', $login);
}

// Проверка ФИО
function validate_fullname($fullname) {
    return preg_match('/^[а-яё\s]+$/iu', $fullname);
}
