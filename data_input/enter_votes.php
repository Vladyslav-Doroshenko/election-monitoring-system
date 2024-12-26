<?php
require_once '../config/config.php';
require_once '../src/controllers/VoteController.php';
session_start();

// Перевірка прав доступу та активної сесії
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Перевірка прав доступу
$roleId       = $_SESSION['role_id'];
$allowedRoles = [1, 2]; // Доступ мають адміністратор (1) і supervisor (2)

// Перевірка ролі та активної сесії
if (!in_array($roleId, $allowedRoles) || !isset($_SESSION['session_token'])) {
  header('Location: /login');
  exit();
}

// Ініціалізація контролера
$voteController = new VoteController($pdo);
$message        = '';
$action         = $_GET['action'] ?? '';

// Обробка форми для додавання або редагування голосів
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $stationId     = (int)$_POST['station_id'];
  $candidateId   = (int)$_POST['candidate_id'];
  $votesReceived = (int)$_POST['votes_received'];

  if ($stationId && $candidateId && $votesReceived >= 0) {
    try {
      if ($action === 'edit' && isset($_GET['vote_id'])) {
        // Дозволити редагування тільки адміністраторам
        if ($roleId == 1) {
          $voteId = (int)$_GET['vote_id'];
          $voteController->updateVote($voteId, $stationId, $candidateId, $votesReceived);
          $message = "Голос успішно оновлено.";
        } else {
          $message = "У вас немає прав для редагування голосів.";
        }
      } else {
        // Додавання нового голосу доступне адміністраторам і supervisor
        $voteController->addVote($stationId, $candidateId, $votesReceived);
        $message = "Голос успішно додано.";
      }
      header("Location: /enter-votes");
      exit();
    } catch (Exception $e) {
      $message = "Помилка: " . $e->getMessage();
    }
  } else {
    $message = "Будь ласка, заповніть усі поля.";
  }
}

// Обробка видалення голосу
if ($action === 'delete' && isset($_GET['vote_id'])) {
  // Видалення доступне тільки адміністраторам
  if ($roleId == 1) {
    $voteId = (int)$_GET['vote_id'];
    try {
      $voteController->deleteVote($voteId);
      $message = "Голос успішно видалено.";
      header("Location: /enter-votes");
      exit();
    } catch (Exception $e) {
      $message = "Помилка: " . $e->getMessage();
    }
  } else {
    $message = "У вас немає прав для видалення голосів.";
  }
}

// Отримання списку дільниць, кандидатів та голосів
$stations   = $voteController->getStations();
$candidates = $voteController->getCandidates();
$votes      = $voteController->getAllVotes();
$voteToEdit = null;

// Якщо редагування, отримуємо дані для заповнення форми
if ($action === 'edit' && isset($_GET['vote_id'])) {
  $voteId     = (int)$_GET['vote_id'];
  $voteToEdit = $voteController->getVoteById($voteId);

  if ($voteToEdit) {
    $stationId     = $voteToEdit['station_id'];
    $candidateId   = $voteToEdit['candidate_id'];
    $votesReceived = $voteToEdit['votes_received'];
  }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Введення даних про голоси</title>
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
    <h1 class="text-center">Введення даних про голоси</h1>

    <div class="row">
      <div class="w-100">
        <?php if ($message): ?>
          <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <!-- Кнопка для додавання нового голосу -->
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/enter-votes/create" class="btn btn-secondary">Додати нового голосу</a>
          </div>
        <?php endif; ?>

        <!-- Форма для додавання або редагування голосів -->
        <?php if ($action === 'edit' && isset($_GET['vote_id']) && $roleId == 1): ?>
          <div class="modal fade" id="editVoteModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="editVoteModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Редагування голосу</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post" action="/enter-votes/edit/<?= htmlspecialchars($voteId); ?>">
                    <div class="m-0 form-group">
                      <label for="station_id">Виборча дільниця</label>
                      <select class="form-control" id="station_id" name="station_id" required>
                        <?php foreach ($stations as $station): ?>
                          <option
                              value="<?= $station['station_id'] ?>" <?= $voteToEdit && $voteToEdit['station_id'] == $station['station_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($station['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="candidate_id">Кандидат</label>
                      <select class="form-control" id="candidate_id" name="candidate_id" required>
                        <?php foreach ($candidates as $candidate): ?>
                          <option
                              value="<?= $candidate['candidate_id'] ?>" <?= $voteToEdit && $voteToEdit['candidate_id'] == $candidate['candidate_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($candidate['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="votes_received">Кількість голосів</label>
                      <input class="form-control" type="number" id="votes_received" name="votes_received" required
                             min="0"
                             value="<?= htmlspecialchars($voteToEdit['votes_received'] ?? '') ?>">
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50" type="submit">Зберегти зміни</button>
                      <a href="/enter-votes" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#editVoteModal').modal('show');
            });
          </script>
        <?php elseif ($action === 'create'): ?>
          <div class="modal fade" id="addVoteModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="addVoteModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати голос</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post" action="/enter-votes">
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
                      <label for="candidate_id">Кандидат</label>
                      <select class="form-control" id="candidate_id" name="candidate_id" required>
                        <option value="">Виберіть кандидата</option>
                        <?php foreach ($candidates as $candidate): ?>
                          <option
                              value="<?= $candidate['candidate_id'] ?>"><?= htmlspecialchars($candidate['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="votes_received">Кількість голосів</label>
                      <input class="form-control" type="number" id="votes_received" name="votes_received" required
                             min="0">
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50" type="submit">Додати голос</button>
                      <a href="/enter-votes" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#addVoteModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Таблиця голосів з кнопками для редагування та видалення -->
          <h2 class="h3 text-center mb-3">Таблиця голосів</h2>

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
                   data-export-options='{ "fileName":"Голоси", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th data-sortable="true" class="text-center" title="Дільниця" data-filter-control="input"
                    data-visible="true"
                    data-field="station_name">Дільниця
                </th>
                <th data-sortable="true" class="text-center" title="Кандидат" data-filter-control="input"
                    data-visible="true"
                    data-field="candidate_name">Кандидат
                </th>
                <th data-sortable="true" class="text-center" title="Кількість голосів" data-filter-control="input"
                    data-visible="true"
                    data-field="votes_received">Кількість голосів
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($votes as $vote): ?>
                <tr id="tr-id-<?= htmlspecialchars($vote['vote_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($vote['vote_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($vote['vote_id']); ?>"
                      class="td-c-<?= htmlspecialchars($vote['vote_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($vote['station_name']); ?>">
                    <span data-type="text" data-title="Дільниця" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($vote['vote_id']); ?>" data-name="station_name">
                      <?= htmlspecialchars($vote['station_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($vote['candidate_name']); ?>">
                    <span data-type="text" data-title="Кандидат" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($vote['vote_id']); ?>" data-name="candidate_name">
                      <?= htmlspecialchars($vote['candidate_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($vote['votes_received']); ?>">
                    <span data-type="text" data-title="Кількість голосів" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($vote['vote_id']); ?>" data-name="votes_received">
                      <?= htmlspecialchars($vote['votes_received']); ?> </span>
                  </td>
                  <td title="Дії">
                    <?php if ($roleId == 1): ?>
                      <a href="/enter-votes/edit/<?= $vote['vote_id'] ?>"
                         class="ems-icon edit"
                         title="Редагувати">
                        <i class="far fa-edit"></i>
                      </a>
                      |
                      <a href="/enter-votes/delete/<?= $vote['vote_id'] ?>"
                         onclick="return confirm('Ви впевнені, що хочете видалити цей запис про голоси?')"
                         class="ems-icon trash"
                         title="Видалити">
                        <i class="far fa-trash-alt"></i>
                      </a>
                    <?php endif; ?>
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
        pdf.save('Голоси.pdf');
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

