<?php
require_once '../config/config.php';
require_once '../src/controllers/ResultController.php';
require_once '../src/controllers/TurnoutController.php';

session_start();

// Перевірка авторизації та активної сесії
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Перевірка наявності сесії в базі даних
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE user_id = :user_id AND session_token = :session_token");
$stmt->execute([
  'user_id'       => $_SESSION['user_id'],
  'session_token' => $_SESSION['session_token']
]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session || (isset($session['expires_at']) && strtotime($session['expires_at']) < time())) {
  session_unset();
  session_destroy();
  header("Location: /login");
  exit();
}

// Отримання загальної статистики
try {
  $totalDistricts  = $pdo->query("SELECT COUNT(*) FROM districts")->fetchColumn();
  $totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();
  $totalStations   = $pdo->query("SELECT COUNT(*) FROM polling_stations")->fetchColumn();
  $totalUsers      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
} catch (PDOException $e) {
  die("Помилка отримання даних: " . $e->getMessage());
}

// Отримання даних для діаграми результатів по кандидатах
try {
  $stmt       = $pdo->query("
      SELECT candidates.candidate_id, candidates.name AS candidate_name, COALESCE(SUM(votes.votes_received), 0) AS total_votes
      FROM candidates
      LEFT JOIN votes ON candidates.candidate_id = votes.candidate_id
      GROUP BY candidates.candidate_id
      ORDER BY total_votes DESC
  ");
  $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Помилка отримання результатів по кандидатах: " . $e->getMessage());
}

// Додаємо дефолтне значення, якщо немає кандидатів
if (empty($candidates)) {
  $candidates = [
    ['candidate_name' => 'Немає даних', 'total_votes' => 1]
  ];
}

// Отримання даних результатів по округам
$resultController = new ResultController($pdo);
$districtResults  = $resultController->getResults();

// Отримання даних результатів по дільницям
$turnoutController = new TurnoutController($pdo);
$turnoutResults    = $turnoutController->getTurnout();

?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Дашборд</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="pt-5 pb-5">
  <div class="container">
    <div class="row">
      <div class="dashboard-wrapper d-flex flex-wrap justify-content-between">
        <div class="dashboard-general-statistics">
          <h2 class="h3 text-center mb-3">Загальна статистика</h2>

          <canvas id="generalStatsChart" width="100" height="100"></canvas>
        </div>

        <div class="dashboard-results">
          <h2 class="h3 text-center mb-3">Результати по кандидатах</h2>

          <div class="d-flex align-items-center">
            <?php if ($candidates[0]['candidate_name'] !== 'Немає даних') : ?>
              <div id="candidateList">
                <h3 class="h5 mb-2">Деталі по кандидатах</h3>

                <ol>
                  <?php foreach ($candidates as $index => $candidate): ?>
                    <li
                        onmouseover="highlightSegment(<?= $index ?>)"
                        onmouseout="resetChart()"
                        onclick="location.href='/manage-candidates/edit/<?= htmlspecialchars($candidate['candidate_id']) ?>'"
                        style="cursor: pointer;">
                      <?= htmlspecialchars($candidate['candidate_name']) ?>
                      (<?= htmlspecialchars($candidate['total_votes']) ?> голосів)
                    </li>
                  <?php endforeach; ?>
                </ol>
              </div>
            <?php endif; ?>

            <canvas id="resultsChart" width="100" height="100"></canvas>
          </div>
        </div>

        <!-- Діаграми для округів та дільниць -->
        <div class="dashboard-districts">
          <h2 class="h3 text-center mb-3">Результати по округах</h2>

          <canvas id="districtsResultsChart" width="100" height="100"></canvas>
        </div>

        <div class="dashboard-stations">
          <h2 class="h3 text-center mb-3">Результати по дільницях</h2>

          <canvas id="stationsChart" width="100" height="100"></canvas>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  const generalStatsData = {
    labels: ['Виборчі округи', 'Кандидати', 'Виборчі дільниці', 'Користувачі'],
    datasets: [{
      label: 'Загальна статистика',
      data: [
        <?= $totalDistricts ?>,
        <?= $totalCandidates ?>,
        <?= $totalStations ?>,
        <?= $totalUsers ?? 0 ?>
      ],
      backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0'],
    }]
  };

  const generalStatsChart = new Chart(
    document.getElementById('generalStatsChart').getContext('2d'), {
      type: 'doughnut',
      data: generalStatsData,
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    }
  );

  const candidateData = <?= json_encode($candidates) ?>;
  const resultsChart = new Chart(
    document.getElementById('resultsChart').getContext('2d'), {
      type: 'pie',
      data: {
        labels: candidateData.map(c => c.candidate_name),
        datasets: [{
          data: candidateData.map(c => c.total_votes),
          backgroundColor: ['#36a2eb', '#ff6384', '#ffce56', '#4bc0c0', '#9966ff', '#ff9f40'],
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        },
        onClick: (event, elements) => {
          if (elements.length > 0) {
            const index = elements[0].index;
            const candidateId = candidateData[index].candidate_id;

            if (candidateId !== undefined) {
              window.location.href = `/manage-candidates/edit/${candidateId}`;
            }
          }
        }
      }
    }
  );

  // Діаграма округів
  const districtResultsData = <?= json_encode($districtResults) ?>;
  const districtsResultsChart = new Chart(
    document.getElementById('districtsResultsChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: districtResultsData.map(d => `${d.district_name}`),
        datasets: [{
          label: 'Явка виборців',
          data: districtResultsData.map(d => parseFloat(d.voter_turnout).toFixed(2)),
          backgroundColor: '#4bc0c0',
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {display: false}
        },
        onClick: (event, elements) => {
          if (elements.length > 0) {
            window.location.href = '/calculate-totals';
          }
        }
      }
    }
  );

  // Діаграма дільниць
  const stationData = <?= json_encode($turnoutResults) ?>;
  const stationsChart = new Chart(
    document.getElementById('stationsChart').getContext('2d'), {
      type: 'bar',
      data: {
        labels: stationData.map(s => s.station_name),
        datasets: [{
          label: 'Явка виборців',
          data: stationData.map(s => parseFloat(s.voter_turnout).toFixed(2)),
          backgroundColor: '#ffce56',
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {display: false}
        },
        onClick: (event, elements) => {
          if (elements.length > 0) {
            window.location.href = '/calculate-turnout';
          }
        }
      }
    }
  );

  // Підсвічування сегмента діаграми для кандидатів
  function highlightSegment(index) {
    resultsChart.setActiveElements([{datasetIndex: 0, index: index}]);
    resultsChart.tooltip.setActiveElements([{datasetIndex: 0, index: index}]);
    resultsChart.update();
  }

  function resetChart() {
    resultsChart.setActiveElements([]);
    resultsChart.tooltip.setActiveElements([]);
    resultsChart.update();
  }
</script>

<?php include '../includes/footer.php'; ?>
</body>
</html>
