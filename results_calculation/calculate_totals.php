<?php
require_once '../config/config.php';
require_once '../src/controllers/ResultController.php';
session_start();

// Перевірка прав доступу та активної сесії
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Дозволити доступ лише користувачам з ролями admin (1) та supervisor (2)
$allowedRoles = [1, 2, 4];
if (!in_array($_SESSION['role_id'], $allowedRoles)) {
  echo "<p>У вас немає доступу до перегляду та розрахунку загальних результатів.</p>";
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

// Ініціалізація контролера
$resultController = new ResultController($pdo);
$message          = '';

// Перевірка на наявність вже збережених результатів
$resultsCalculated = $resultController->checkIfResultsCalculated();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $resultController->calculateTotals();
    $message = "Результати успішно розраховані та збережені.";
  } catch (Exception $e) {
    $message = "Помилка: " . $e->getMessage();
  }
}

// Отримання результатів для відображення
$results = $resultController->getResults();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Підрахунок загальних результатів</title>
  <link rel="stylesheet" href="/assets/css/bootstrap.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-table.min.css">
  <link rel="stylesheet" href="/assets/css/bt/bootstrap-editable.css">
  <link rel="stylesheet" href="/assets/css/styles.css">
  <script src="/assets/js/fontawesome.js"></script>
  <script src="/assets/js/jquery.min.js"></script>
</head>
<body>
<?php include '../includes/header.php'; ?>

<section class="pt-5 pb-5">
  <div class="container">
    <h1 class="text-center">Підрахунок загальних результатів</h1>

    <div class="row">
      <div class="w-100">
        <?php if ($message): ?>
          <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif;


        if ($_SESSION['role_id'] === 1 || $_SESSION['role_id'] === 2): ?>
          <!-- Кнопка для розрахунку результатів -->
          <form method="post" action="/calculate-totals">
            <button class="btn btn-secondary" type="submit">
              Розрахувати результати
            </button>
          </form>
        <?php endif; ?>

        <div class="w-100">
          <!-- Таблиця результатів -->
          <h2 class="h3 text-center mb-3">Результати по округах</h2>

          <div id="table1" class="table-responsive">
            <table class="table table-striped table-condensed table-hover"
                   data-locale="uk-UA"
                   id="table111"
                   data-toggle="table111"
                   data-show-toggle="false"
                   data-toolbar="#toolbar1"
                   data-show-fullscreen="false"
                   data-filter-control="true"
                   data-filter-show-clear="false"
                   data-show-print="true"
                   data-show-copy-rows="false"
                   data-show-export="true"
                   data-click-to-select="false"
                   data-pagination="true"
                   data-page-list="[10, 25, 50, 100, 250, 500]"
                   data-maintain-selected="true"
                   data-maintain-meta-data="true"
                   data-show-refresh="false"
                   data-show-columns="true"
                   data-show-search-button="false"
                   data-show-search-clear-button="true"
                   data-unique-id="id"
                   data-minimum-count-columns="1"
                   data-detail-view="false"
                   data-mobile-responsive="true"
                   data-check-on-init="true"
                   data-export-types="['excel', 'doc', 'pdf']"
                   data-export-options='{ "fileName":"Результати по округах", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th data-sortable="true" class="text-center" title="Округ" data-filter-control="input"
                    data-visible="true"
                    data-field="district_name">Округ
                </th>
                <th data-sortable="true" class="text-center" title="Загальна кількість виборців"
                    data-filter-control="input"
                    data-visible="true"
                    data-field="total_voters">Загальна кількість виборців
                </th>
                <th data-sortable="true" class="text-center" title="Кількість голосів" data-filter-control="input"
                    data-visible="true"
                    data-field="total_votes">Кількість голосів
                </th>
                <th data-sortable="true" class="text-center" title="Явка виборців (%)" data-filter-control="input"
                    data-visible="true"
                    data-field="voter_turnout">Явка виборців (%)
                </th>
                <th data-sortable="true" class="text-center" title="Лідер" data-filter-control="input"
                    data-visible="true"
                    data-field="leading_candidate">Лідер
                </th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($results as $result): ?>
                <tr id="tr-id-<?= htmlspecialchars($result['summary_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($result['summary_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($result['summary_id']); ?>"
                      class="td-c-<?= htmlspecialchars($result['summary_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($result['district_name']); ?>">
                    <span data-type="text" data-title="Округ" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($result['summary_id']); ?>" data-name="district_name">
                      <?= htmlspecialchars($result['district_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($result['total_voters']); ?>">
                    <span data-type="text" data-title="Загальна кількість виборців" data-mode="popup"
                          data-placement="top"
                          data-pk="<?= htmlspecialchars($result['summary_id']); ?>" data-name="total_voters">
                      <?= htmlspecialchars($result['total_voters']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($result['total_votes']); ?>">
                    <span data-type="text" data-title="Кількість голосів" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($result['summary_id']); ?>" data-name="total_votes">
                      <?= htmlspecialchars($result['total_votes']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars(number_format($result['voter_turnout'], 2)); ?>%">
                    <span data-type="text" data-title="Явка виборців (%)" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($result['summary_id']); ?>" data-name="voter_turnout">
                      <?= htmlspecialchars(number_format($result['voter_turnout'], 2)); ?>%</span>
                  </td>
                  <td title="<?= htmlspecialchars($result['leading_candidate'] ?? 'Немає даних'); ?>">
                    <span data-type="text" data-title="Лідер" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($result['summary_id']); ?>" data-name="leading_candidate">
                      <?= htmlspecialchars($result['leading_candidate'] ?? 'Немає даних'); ?> </span>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include '../includes/footer.php'; ?>

<script src="/assets/js/bt/bootstrap-table.min.js"></script>
<script src="/assets/js/bt/jspdf.min.js"></script>
<script src="/assets/js/bt/bootstrap-table-print.min.js"></script>
<script src="/assets/js/bt/bootstrap-table-locale-all.min.js"></script>
<script src="/assets/js/bt/bootstrap-table-export.min.js"></script>
<script src="/assets/js/bt/tableExport.min.js"></script>
<script src="/assets/js/bt/bootstrap-table-mobile.js"></script>
<script src="/assets/js/bt/bootstrap-table-filter-control.min.js"></script>
<script src="/assets/js/bt/bootstrap-editable.min.js"></script>
<script src="/assets/js/bt/bootstrap-table-editable.min.js"></script>

<script type="text/javascript">
  $(document).ready(function () {
    $(document).on("click", "a[data-type='pdf']", function () {
      var pdf = new jsPDF('p', 'pt', 'a4');
      // $('table thead').css('background', '#000');
      pdf.addHTML($("#table111"), function () {
        pdf.save('Результати по округах.pdf');
        document.location.href = document.location.href;
      });
    });

    $(function () {
      var $table1 = $('#table111'), selections1 = [], ids = [];

      function getHeight() {
        return $(window).height() - 150;
      }

      $(window).resize(function () {
        $table1.bootstrapTable('resetView', {'height': getHeight()});
      });

      $('#show').click(function () {
        $table1.bootstrapTable('togglePagination');
        $table1.bootstrapTable('checkInvert');
        var ids = $.map($table1.bootstrapTable('getSelections'), function (row) {
          return row.id
        })
        $table1.bootstrapTable('remove', {
          field: 'id',
          values: ids
        })
        $table1.bootstrapTable('togglePagination');
      });

      $table1.bootstrapTable({
        height: getHeight(),
        silent: true,
        search: true,
        paginationLoop: true,
        sidePagination: 'client', // client or server
        totalRows: 1, // server side need to set
        pageNumber: 1,
        pageSize: 10,
        showPrint: true,
        paginationHAlign: 'right',
        paginationVAlign: 'both',
        icons: {print: 'fa-print', export: 'fa-file-export', columns: 'fa-list', clearSearch: 'fa-trash'}
      });
      setTimeout(function () {
        $table1.bootstrapTable('resetView', {'height': getHeight()});
      }, 1000);
    });
  });
</script>
</body>
</html>
