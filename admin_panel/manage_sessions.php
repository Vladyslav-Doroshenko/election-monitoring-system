<?php
require_once '../config/config.php';
require_once '../src/controllers/SessionController.php';
session_start();

// Перевірка прав доступу (тільки адміністратори) та активної сесії
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1 || !isset($_SESSION['session_token'])) {
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

// Ініціалізація контролера
$sessionController = new SessionController($pdo);
$action            = $_GET['action'] ?? 'view';
$sessionId         = $_GET['session_id'] ?? null;

// Видалення однієї сесії
if ($action === 'delete' && $sessionId) {
  $sessionController->deleteSession($sessionId);
  header('Location: /manage-sessions');
  exit();
}

// Завершення всіх сесій, крім поточної
if ($action === 'delete_all') {
  $sessionController->deleteAllSessionsExceptCurrent($_SESSION['session_token']);
  header('Location: /manage-sessions');
  exit();
}

// Отримання списку сесій для відображення
$sessions = $sessionController->getAllSessions();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Управління сесіями</title>
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
    <h1 class="text-center">Управління сесіями</h1>

    <div class="row">
      <div class="w-100">
        <!-- Кнопка для завершення всіх сесій -->
        <div class="end-all-sessions">
          <a href="/manage-sessions/delete-all" class="btn btn-secondary"
             onclick="return confirm('Ви впевнені, що хочете завершити всі сесії?')">Завершити всі сесії</a>
        </div>

        <!-- Список активних сесій -->
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
                 data-export-options='{ "fileName":"Сесії користувачів", "worksheetName":"list1" }'>
            <thead>
            <tr>
              <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
              <th data-sortable="true" class="text-center" title="Користувач" data-filter-control="input"
                  data-visible="true"
                  data-field="username">Користувач
              </th>
              <th data-sortable="true" class="text-center" title="Токен сесії" data-filter-control="input"
                  data-visible="true"
                  data-field="session_token">Токен сесії
              </th>
              <th data-sortable="true" class="text-center" title="Час створення" data-filter-control="input"
                  data-visible="true"
                  data-field="created_at">Час створення
              </th>
              <th data-sortable="true" class="text-center" title="Токен сесії" data-filter-control="input"
                  data-visible="true"
                  data-field="expires_at">Час закінчення
              </th>
              <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($sessions as $session): ?>
              <tr id="tr-id-<?= htmlspecialchars($session['session_id']); ?>"
                  class="tr-c-<?= htmlspecialchars($session['session_id']); ?>">
                <td id="td-id-<?= htmlspecialchars($session['session_id']); ?>"
                    class="td-c-<?= htmlspecialchars($session['session_id']); ?>"></td>
                <td title="<?= htmlspecialchars($session['username']); ?>">
                    <span data-type="text" data-title="Користувач" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($session['session_id']); ?>" data-name="username">
                      <?= htmlspecialchars($session['username']); ?> </span>
                </td>
                <td title="<?= htmlspecialchars($session['session_token']); ?>">
                    <span data-type="text" data-title="Токен сесії" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($session['session_id']); ?>" data-name="name">
                      <?= htmlspecialchars($session['session_token']); ?> </span>
                </td>
                <td title="<?= htmlspecialchars($session['created_at']); ?>">
                    <span data-type="text" data-title="Час створення" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($session['session_id']); ?>" data-name="name">
                      <?= htmlspecialchars($session['created_at']); ?> </span>
                </td>
                <td title="<?= htmlspecialchars($session['expires_at']); ?>">
                    <span data-type="text" data-title="Час закінчення" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($session['session_id']); ?>" data-name="name">
                      <?= htmlspecialchars($session['expires_at']); ?> </span>
                </td>
                <td title="Дії">
                  <a href="/manage-sessions/delete/<?= $session['session_id'] ?>" class="ems-icon trash"
                     title="Завершити сесію"
                     onclick="return confirm('Ви впевнені, що хочете видалити цю сесію?')">
                    <i class="far fa-trash-alt"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
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
        pdf.save('Сесії користувачів.pdf');
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
