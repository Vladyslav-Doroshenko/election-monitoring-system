<?php
require_once '../config/config.php';
require_once '../src/controllers/DistrictReportController.php';
session_start();

// Перевірка прав доступу та активної сесії
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Дозволити доступ лише користувачам з ролями admin (1) та supervisor (2)
$allowedRoles = [1, 2];
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

// Отримання ID округу з параметрів запиту
$districtId = isset($_GET['district_id']) ? (int)$_GET['district_id'] : null;

if (!$districtId) {
  echo "<p>Помилка: округ не вказано. Будь ласка, поверніться на попередню сторінку та виберіть округ.</p>";
  exit();
}

// Ініціалізація контролера
$districtReportController = new DistrictReportController($pdo);

// Отримання звіту по округу
$report = $districtReportController->getDistrictReport($districtId);

if (!$report['district_info']) {
  echo "<p>Помилка: округ не знайдено. Перевірте, чи правильно вказано ідентифікатор округу.</p>";
  exit();
}

$districtInfo = $report['district_info'];
$candidates   = $report['candidates'];
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Звіт по округу</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-table.min.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-editable.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.5.0-beta4/html2canvas.min.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="pt-5 pb-5">
  <div class="container">
    <!-- Кнопка для повернення на сторінку вибору округу -->
    <div class="add-button">
      <a href="/select-district" class="btn btn-secondary">← Повернутися до вибору округу</a>

      <?php if ($_SESSION['role_id'] == 1 || $_SESSION['role_id'] == 2): ?>
        <button onclick="downloadPDF()" class="btn btn-outline-secondary">Завантажити звіт у PDF</button>
      <?php endif; ?>
    </div>

    <div class="pt-4" id="reportContent">
      <h1 class="text-center">Звіт по округу: <?= htmlspecialchars($districtInfo['district_name']) ?></h1>

      <h2 class="h3 text-center mb-3">Основна інформація</h2>

      <table class="table table-striped table-condensed">
        <tr>
          <th>Загальна кількість виборців</th>
          <td><?= htmlspecialchars($districtInfo['total_voters']) ?></td>
        </tr>
        <tr>
          <th>Видані бюлетені</th>
          <td><?= htmlspecialchars($districtInfo['total_ballots_issued']) ?></td>
        </tr>
        <tr>
          <th>Кількість голосів</th>
          <td><?= htmlspecialchars($districtInfo['total_votes']) ?></td>
        </tr>
        <tr>
          <th>Явка (%)</th>
          <td><?= htmlspecialchars(number_format($districtInfo['voter_turnout'], 2)) ?>%</td>
        </tr>
      </table>

      <h2 class="h3 text-center mb-3">Кандидати</h2>

      <table class="table table-striped table-condensed">
        <thead>
        <tr>
          <th>Кандидат</th>
          <th>Кількість голосів</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($candidates)): ?>
          <tr>
            <td colspan="2">Немає даних про кандидатів у цьому окрузі.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($candidates as $candidate): ?>
            <tr>
              <td><?= htmlspecialchars($candidate['candidate_name']) ?></td>
              <td><?= htmlspecialchars($candidate['total_votes']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script>
  function downloadPDF() {
    const {jsPDF} = window.jspdf;
    const pdf = new jsPDF();

    // Використання html2canvas для створення зображення з HTML-контенту
    html2canvas(document.querySelector("#reportContent")).then(canvas => {
      const imgData = canvas.toDataURL("image/png");
      const imgWidth = 190;
      const pageHeight = 295;
      const imgHeight = (canvas.height * imgWidth) / canvas.width;
      let heightLeft = imgHeight;
      let position = 0;

      pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
      heightLeft -= pageHeight;

      while (heightLeft >= 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'PNG', 10, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
      }

      pdf.save("Звіт по округу <?= $districtInfo['district_name']; ?>.pdf");
    });
  }
</script>
</body>
</html>
