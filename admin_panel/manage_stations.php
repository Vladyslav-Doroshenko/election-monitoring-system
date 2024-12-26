<?php
require_once '../config/config.php';
require_once '../src/controllers/StationController.php';
session_start();

// Перевірка прав доступу (лише адміністратори) та активної сесії
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
$stationController = new StationController($pdo);
$action            = $_GET['action'] ?? 'view';
$stationId         = $_GET['station_id'] ?? null;

// Обробка дій на основі параметра "action"
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stationController->createStation($_POST);

  header("Location: /manage-stations");
  exit();
} elseif ($action === 'edit' && $stationId && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stationController->updateStation($stationId, $_POST);

  header("Location: /manage-stations");
  exit();
} elseif ($action === 'delete' && $stationId) {
  $stationController->deleteStation($stationId);

  header("Location: /manage-stations");
  exit();
}

// Отримання списку дільниць для відображення
$stations = $stationController->getAllStations();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Управління виборчими дільницями</title>
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
    <h1 class="text-center">Управління виборчими дільницями</h1>

    <div class="row">
      <div class="w-100">
        <!-- Кнопка для додавання нової дільниці (відображається лише, якщо немає активної дії "create" або "edit") -->
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/manage-stations/create" class="btn btn-secondary">Додати нову дільницю</a>
          </div>
        <?php endif; ?>

        <!-- Форма додавання/редагування дільниці -->
        <?php if ($action === 'create' || ($action === 'edit' && $stationId)):
          $station = $action === 'edit' ? $stationController->getStationById($stationId) : null; ?>
          <div class="modal fade" id="stationModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="stationModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати дільницю</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post"
                        action="/manage-stations/<?= $action ?>/<?= htmlspecialchars($stationId) ?>">
                    <div class="m-0 form-group">
                      <label for="name">Назва дільниці</label>
                      <input class="form-control" type="text" id="name" name="name"
                             value="<?= htmlspecialchars($station['name'] ?? '') ?>"
                             required>
                    </div>

                    <div class="m-0 form-group">
                      <label for="address">Адреса</label>
                      <input class="form-control" type="text" id="address" name="address"
                             value="<?= htmlspecialchars($station['address'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="district_id">Округ</label>
                      <select class="form-control" id="district_id" name="district_id" required>
                        <?= $stationController->getDistrictsOptions($station['district_id'] ?? null) ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="total_voters">Загальна кількість виборців</label>
                      <input class="form-control" type="number" id="total_voters" name="total_voters"
                             value="<?= htmlspecialchars($station['total_voters'] ?? '') ?>" required>
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50"
                              type="submit"><?= $action === 'edit' ? 'Зберегти зміни' : 'Додати дільницю' ?></button>
                      <a href="/manage-stations" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#stationModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Список дільниць -->
          <h2 class="h3 text-center mb-3">Список виборчих дільниць</h2>

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
                   data-export-options='{ "fileName":"Дільниці", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th class="idd text-center" data-sortable="true" title="ID" data-filter-control="input"
                    data-visible="true" data-field="id">ID
                </th>
                <th data-sortable="true" class="text-center" title="Назва" data-filter-control="input"
                    data-visible="true"
                    data-field="name">Назва
                </th>
                <th data-sortable="true" class="text-center" title="Адреса" data-filter-control="input"
                    data-visible="true"
                    data-field="address">Адреса
                </th>
                <th data-sortable="true" class="text-center" title="Округ" data-filter-control="input"
                    data-visible="true"
                    data-field="district_name">Округ
                </th>
                <th data-sortable="true" class="text-center" title="Загальна кількість виборців"
                    data-filter-control="input"
                    data-visible="true"
                    data-field="total_voters">Загальна кількість виборців
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($stations as $station): ?>
                <tr id="tr-id-<?= htmlspecialchars($station['station_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($station['station_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($station['station_id']); ?>"
                      class="td-c-<?= htmlspecialchars($station['station_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($station['station_id']); ?>">
                    <?= htmlspecialchars($station['station_id']); ?>
                  </td>
                  <td title="<?= htmlspecialchars($station['name']); ?>">
                    <span data-type="text" data-title="Назва" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($station['station_id']); ?>" data-name="name">
                      <?= htmlspecialchars($station['name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($station['address']); ?>">
                    <span data-type="text" data-title="Адреса" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($station['station_id']); ?>" data-name="address">
                      <?= htmlspecialchars($station['address']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($station['district_name']); ?>">
                    <span data-type="text" data-title="Округ" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($station['station_id']); ?>" data-name="district_name">
                      <?= htmlspecialchars($station['district_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($station['total_voters']); ?>">
                    <span data-type="text" data-title="Загальна кількість виборців" data-mode="popup"
                          data-placement="top"
                          data-pk="<?= htmlspecialchars($station['station_id']); ?>" data-name="total_voters">
                      <?= htmlspecialchars($station['total_voters']); ?> </span>
                  </td>
                  <td title="Дії">
                    <a href="/manage-stations/edit/<?= $station['station_id'] ?>" class="ems-icon edit"
                       title="Редагувати">
                      <i class="far fa-edit"></i>
                    </a>
                    |
                    <a href="/manage-stations/delete/<?= $station['station_id'] ?>" class="ems-icon trash"
                       title="Видалити"
                       onclick="return confirm('Ви впевнені, що хочете видалити цю дільницю?')">
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
        pdf.save('Дільниці.pdf');
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
