<?php
// Налаштування бази даних
define('DB_HOST', 'localhost');       // Адреса сервера бази даних
define('DB_NAME', 'election_monitoring');     // Назва бази даних
define('DB_USER', 'root');        // Ім'я користувача бази даних
define('DB_PASS', '');        // Пароль користувача бази даних

// Параметри підключення до бази даних
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Встановлює режим обробки помилок
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Встановлює режим вибірки за замовчуванням
  PDO::ATTR_EMULATE_PREPARES   => false,            // Відключає емуляцію підготовлених запитів для підвищення безпеки
];

try {
  // Створення об'єкта PDO для підключення до бази даних
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
  // Виведення повідомлення про помилку підключення
  die("Помилка підключення до бази даних: " . $e->getMessage());
}

// Інші загальні налаштування
define('BASE_URL', 'https://localhost:8890/election-monitoring-system/'); // Базовий URL проекту

// Автоматичне завантаження класів
spl_autoload_register(function ($class) {
  $path = __DIR__ . '/../src/' . str_replace('\\', '/', $class) . '.php';
  if (file_exists($path)) {
    require_once $path;
  }
});

