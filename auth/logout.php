<?php
session_start();
require_once '../config/config.php';

// Видаляємо сесію з бази даних
if (isset($_SESSION['session_token'])) {
  $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_token = :session_token");
  $stmt->execute(['session_token' => $_SESSION['session_token']]);
}

// Очистка сесії
session_unset();
session_destroy();

// Перенаправлення на сторінку входу
header("Location: /login");
exit();
