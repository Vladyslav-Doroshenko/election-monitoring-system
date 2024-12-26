<?php
// Підключення до бази даних
require_once '../config/config.php';
session_start();

if (isset($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit();
}

// Ініціалізація змінних
$error            = '';
$username         = '';
$email            = '';
$password         = '';
$confirm_password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username         = trim($_POST['username']);
  $email            = trim($_POST['email']);
  $password         = trim($_POST['password']);
  $confirm_password = trim($_POST['confirm_password']);

  if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
    $error = 'Будь ласка, заповніть усі поля';
  } elseif ($password !== $confirm_password) {
    $error = 'Паролі не співпадають';
  } else {
    try {
      $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
      $stmt->execute(['username' => $username, 'email' => $email]);

      if ($stmt->fetchColumn() > 0) {
        $error = 'Ім\'я користувача або email вже зайняті';
      } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (:username, :email, :password, :role_id)");
        $stmt->execute([
          'username' => $username,
          'email'    => $email,
          'password' => $hashedPassword,
          'role_id'  => 4
        ]);

        header('Location: /login');
        exit();
      }
    } catch (PDOException $e) {
      $error = 'Помилка реєстрації: ' . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Реєстрація</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-table.min.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-editable.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/jquery.min.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="auth-form auth-form_register p-5">
  <div class="container">
    <div class="row justify-content-center align-content-center h-100">
      <div class="d-flex flex-column auth-container w-50 border p-5 shadow-lg bg-white rounded">
        <h1 class="m-0 text-center">
          Реєстрація
        </h1>

        <form class="d-flex flex-column" method="post" action="/register">
          <div class="m-0 form-group">
            <label for="username">Ім'я користувача</label>
            <input class="form-control" type="text" id="username" name="username"
                   value="<?= htmlspecialchars($username); ?>" required>
          </div>

          <div class="m-0 form-group">
            <label for="email">Email</label>
            <input class="form-control" type="email" id="email" name="email" value="<?= htmlspecialchars($email); ?>"
                   required>
          </div>

          <div class="m-0 form-group">
            <div class="row">
              <div class="col">
                <label for="password">Пароль</label>
                <input class="form-control" type="password" id="password" name="password" required>
              </div>

              <div class="col">
                <label for="confirm_password">Підтвердження пароля</label>
                <input class="form-control" type="password" id="confirm_password" name="confirm_password" required>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-secondary w-100">Зареєструватися</button>
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
          Вже маєте акаунт? <a href="/login" class="text-secondary">Увійти</a>
        </p>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
</body>
</html>
