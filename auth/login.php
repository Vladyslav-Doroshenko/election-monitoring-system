<?php
// Підключення до бази даних
require_once '../config/config.php';
session_start();

// Перевірка, чи користувач вже авторизований
if (isset($_SESSION['user_id'])) {
  header('Location: /dashboard');
  exit();
}

// Ініціалізація змінної для помилок
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);

  if (empty($username) || empty($password)) {
    $error = 'Будь ласка, заповніть усі поля';
  } else {
    try {
      // Запит на отримання даних користувача з таблиці `users`
      $stmt = $pdo->prepare("SELECT user_id, username, password, role_id FROM users WHERE username = :username");
      $stmt->execute(['username' => $username]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);

      // Перевірка пароля
      if ($user && password_verify($password, $user['password'])) {
        // Ініціалізація сесії користувача
        $_SESSION['user_id']  = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id']  = $user['role_id'];

        // Генерація унікального токена для сесії
        $session_token             = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $session_token;

        // Встановлення часу закінчення сесії (наприклад, 1 година)
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Додавання запису про сесію в базу даних
        $stmt = $pdo->prepare("INSERT INTO sessions (user_id, session_token, created_at, expires_at) VALUES (:user_id, :session_token, NOW(), :expires_at)");
        $stmt->execute([
          'user_id'       => $_SESSION['user_id'],
          'session_token' => $session_token,
          'expires_at'    => $expires_at
        ]);

        // Перенаправлення на дашборд
        header('Location: /dashboard');
        exit();
      } else {
        $error = 'Невірний логін або пароль';
      }
    } catch (PDOException $e) {
      $error = 'Помилка входу: ' . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вхід до системи</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-table.min.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-editable.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/jquery.min.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="auth-form p-5">
  <div class="container">
    <div class="row justify-content-center align-content-center h-100">
      <div class="d-flex flex-column auth-container w-50 border p-5 shadow-lg bg-white rounded">
        <h1 class="m-0 text-center">
          Вхід
        </h1>

        <form class="d-flex flex-column" method="post" action="/login">
          <div class="m-0 form-group">
            <label for="username">Ім'я користувача</label>
            <input class="form-control" type="text" id="username" name="username" required>
          </div>

          <div class="m-0 form-group">
            <label for="password">Пароль</label>
            <input class="form-control" type="password" id="password" name="password" required>
          </div>

          <button type="submit" class="btn btn-secondary w-100">Увійти</button>
        </form>

        <?php if ($error): ?>
          <div class="m-0 d-flex justify-content-between alert alert-warning alert-danger fade show" role="alert">
            <?= htmlspecialchars($error); ?>

            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>

        <p class="m-0 text-center">
          Немає акаунта? <a href="/register" class="text-secondary">Реєстрація</a>
        </p>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
</body>
</html>
