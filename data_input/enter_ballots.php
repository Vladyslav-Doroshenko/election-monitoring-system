<?php
require_once '../config/config.php';
require_once '../src/controllers/BallotController.php';
session_start();

// Перевірка прав доступу та активної сесії
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1 || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Ініціалізація контролера
$ballotController = new BallotController($pdo);
$message          = '';
$action           = $_GET['action'] ?? '';

// Ініціалізація змінних для редагування
$ballot = null;

// Перевірка на редагування даних бюлетеня
if ($action === 'edit' && isset($_GET['ballot_id'])) {
  $ballotId = (int)$_GET['ballot_id'];
  $ballot   = $ballotController->getBallotById($ballotId);
  if (!$ballot) {
    $message = "Запис про бюлетень не знайдено.";
  }
}

// Обробка форми для додавання або редагування бюлетенів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stationId       = (int)$_POST['station_id'];
  $ballotsReceived = (int)$_POST['ballots_received'];
  $ballotsIssued   = (int)$_POST['ballots_issued'];
  $ballotsSpoiled  = (int)$_POST['ballots_spoiled'];
  $ballotsUnused   = (int)$_POST['ballots_unused'];

  if ($stationId && $ballotsReceived >= 0 && $ballotsIssued >= 0 && $ballotsSpoiled >= 0 && $ballotsUnused >= 0) {
    try {
      if ($action === 'edit' && isset($ballotId)) {
        $ballotController->updateBallot($ballotId, $ballotsReceived, $ballotsIssued, $ballotsSpoiled, $ballotsUnused);
        $message = "Дані про бюлетені успішно оновлено.";
      } else {
        $ballotController->addBallot($stationId, $ballotsReceived, $ballotsIssued, $ballotsSpoiled, $ballotsUnused);
        $message = "Дані про бюлетені успішно додано.";
      }
      header("Location: /enter-ballots");
      exit();
    } catch (Exception $e) {
      $message = "Помилка: " . $e->getMessage();
    }
  } else {
    $message = "Будь ласка, заповніть усі поля коректно.";
  }
}

// Отримання списку дільниць та бюлетенів
$stations = $ballotController->getStations();
$ballots  = $ballotController->getAllBallots();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Введення даних про бюлетені</title>
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
    <h1 class="text-center">Введення даних про бюлетені</h1>

    <div class="row">
      <div class="w-100">
        <?php if ($message): ?>
          <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- Кнопка для додавання бюлетені -->
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/enter-ballots/create" class="btn btn-secondary">Додати бюлетені</a>
          </div>
        <?php endif; ?>

        <!-- Форма для додавання або редагування даних про бюлетені -->
        <?php if ($action === 'edit' && $ballot): ?>
          <div class="modal fade" id="editBallotModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="editBallotModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Редагування даних про бюлетені</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post" action="/enter-ballots/edit/<?= htmlspecialchars($ballotId) ?>">
                    <div class="m-0 form-group">
                      <label for="station_id">Виборча дільниця</label>
                      <select class="form-control" id="station_id" name="station_id" required>
                        <?php foreach ($stations as $station): ?>
                          <option
                              value="<?= $station['station_id'] ?>" <?= $station['station_id'] == $ballot['station_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($station['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_received">Отримані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_received" name="ballots_received" required
                             min="0"
                             value="<?= htmlspecialchars($ballot['ballots_received']) ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_issued">Видані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_issued" name="ballots_issued" required
                             min="0"
                             value="<?= htmlspecialchars($ballot['ballots_issued']) ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_spoiled">Зіпсовані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_spoiled" name="ballots_spoiled" required
                             min="0"
                             value="<?= htmlspecialchars($ballot['ballots_spoiled']) ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_unused">Невикористані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_unused" name="ballots_unused" required
                             min="0"
                             value="<?= htmlspecialchars($ballot['ballots_unused']) ?>">
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50" type="submit">Зберегти зміни</button>
                      <a href="/enter-ballots" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#editBallotModal').modal('show');
            });
          </script>
        <?php elseif ($action === 'create'): ?>
          <div class="modal fade" id="addBallotModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="addBallotModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати дані про бюлетені</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post" action="/enter-ballots">
                    <div class="m-0 form-group">
                      <label for="station_id">Виборча дільниця</label>
                      <select class="form-control" id="station_id" name="station_id" required>
                        <option value="">Виберіть дільницю</option>
                        <?php foreach ($stations as $station): ?>
                          <option
                              value="<?= $station['station_id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_received">Отримані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_received" name="ballots_received" required
                             min="0">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_issued">Видані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_issued" name="ballots_issued" required
                             min="0">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_spoiled">Зіпсовані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_spoiled" name="ballots_spoiled" required
                             min="0">
                    </div>

                    <div class="m-0 form-group">
                      <label for="ballots_unused">Невикористані бюлетені</label>
                      <input class="form-control" type="number" id="ballots_unused" name="ballots_unused" required
                             min="0">
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50" type="submit">Додати бюлетені</button>
                      <a href="/enter-ballots" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#addBallotModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Таблиця даних про бюлетені з кнопками для редагування та видалення -->
          <h2 class="h3 text-center mb-3">Таблиця даних про бюлетені</h2>

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
                   data-export-options='{ "fileName":"Бюлетені", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th data-sortable="true" class="text-center" title="Виборча дільниця" data-filter-control="input"
                    data-visible="true"
                    data-field="station_name">Виборча дільниця
                </th>
                <th data-sortable="true" class="text-center" title="Отримані бюлетені" data-filter-control="input"
                    data-visible="true"
                    data-field="ballots_received">Отримані бюлетені
                </th>
                <th data-sortable="true" class="text-center" title="Видані бюлетені" data-filter-control="input"
                    data-visible="true"
                    data-field="ballots_issued">Видані бюлетені
                </th>
                <th data-sortable="true" class="text-center" title="Зіпсовані бюлетені" data-filter-control="input"
                    data-visible="true"
                    data-field="ballots_spoiled">Зіпсовані бюлетені
                </th>
                <th data-sortable="true" class="text-center" title="Невикористані бюлетені" data-filter-control="input"
                    data-visible="true"
                    data-field="ballots_unused">Невикористані бюлетені
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($ballots as $ballot): ?>
                <tr id="tr-id-<?= htmlspecialchars($ballot['ballot_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($ballot['ballot_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($ballot['ballot_id']); ?>"
                      class="td-c-<?= htmlspecialchars($ballot['ballot_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($ballot['station_name']); ?>">
                    <span data-type="text" data-title="Виборча дільниця" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($ballot['ballot_id']); ?>" data-name="station_name">
                      <?= htmlspecialchars($ballot['station_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($ballot['ballots_received']); ?>">
                    <span data-type="text" data-title="Отримані бюлетені" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($ballot['ballot_id']); ?>" data-name="ballots_received">
                      <?= htmlspecialchars($ballot['ballots_received']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($ballot['ballots_issued']); ?>">
                    <span data-type="text" data-title="Видані бюлетені" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($ballot['ballot_id']); ?>" data-name="ballots_issued">
                      <?= htmlspecialchars($ballot['ballots_issued']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($ballot['ballots_spoiled']); ?>">
                    <span data-type="text" data-title="Зіпсовані бюлетені" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($ballot['ballot_id']); ?>" data-name="ballots_spoiled">
                      <?= htmlspecialchars($ballot['ballots_spoiled']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($ballot['ballots_unused']); ?>">
                    <span data-type="text" data-title="Невикористані бюлетені" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($ballot['ballot_id']); ?>" data-name="ballots_unused">
                      <?= htmlspecialchars($ballot['ballots_unused']); ?> </span>
                  </td>
                  <td title="Дії">
                    <a href="/enter-ballots/edit/<?= $ballot['ballot_id'] ?>" class="ems-icon edit"
                       title="Редагувати">
                      <i class="far fa-edit"></i>
                    </a>
                    |
                    <a href="/enter-ballots/delete/<?= $ballot['ballot_id'] ?>"
                       onclick="return confirm('Ви впевнені, що хочете видалити цей запис про бюлетені?')"
                       class="ems-icon trash"
                       title="Видалити">
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
        pdf.save('Бюлетені.pdf');
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

