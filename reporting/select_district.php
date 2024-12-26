<?php
require_once '../config/config.php';
require_once '../src/controllers/DistrictListController.php';
session_start();

// Перевірка прав доступу та активної сесії
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Дозволити доступ лише користувачам з ролями admin (1) та supervisor (2)
$allowedRoles = [1, 2, 4];
if (!in_array($_SESSION['role_id'], $allowedRoles)) {
  echo "<p>У вас немає доступу до перегляду звітів по округах.</p>";
  exit();
}

// Перевірка наявності сесії в базі даних
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :user_id AND session_token = :session_token");
$stmt->execute([
  'user_id'       => $_SESSION['user_id'],
  'session_token' => $_SESSION['session_token']
]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

// Якщо сесія не знайдена або її термін закінчився, виконуємо вихід
if (!$session || (isset($session['expires_at']) && strtotime($session['expires_at']) < time())) {
  session_unset();
  session_destroy();
  header("Location: /login");
  exit();
}

// Ініціалізація контролера для отримання списку округів
$districtListController = new DistrictListController($pdo);
$districts              = $districtListController->getDistricts();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вибір округу для звіту</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-table.min.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-editable.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/jquery.min.js"></script>
  <script>
    function generateReport() {
      const districtId = document.getElementById("district_id").value;
      if (districtId) {
        window.location.href = `/district-report/${districtId}`;
      }
    }
  </script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="pt-5 pb-5">
  <div class="container">
    <h1 class="text-center">Вибір округу для генерації звіту</h1>

    <div class="form-group">
      <!-- Форма вибору округу -->
      <label for="district_id">Оберіть округ:</label>
      <select class="form-control" id="district_id" required>
        <option value="" disabled selected>Виберіть округ</option>
        <?php foreach ($districts as $district): ?>
          <option value="<?= htmlspecialchars($district['district_id']) ?>">
            <?= htmlspecialchars($district['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button class="btn btn-secondary" onclick="generateReport()">Згенерувати звіт</button>
  </div>
</section>

<?php include '../includes/footer.php'; ?>
</body>
</html>
