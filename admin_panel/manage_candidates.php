<?php
require_once '../config/config.php';
require_once '../src/controllers/CandidateController.php';
session_start();

// Перевірка авторизації, сесії та ролей
if (!isset($_SESSION['user_id']) || !isset($_SESSION['session_token']) || !in_array($_SESSION['role_id'], [1, 3])) {
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
$candidateController = new CandidateController($pdo);
$action              = $_GET['action'] ?? 'view';
$candidateId         = $_GET['candidate_id'] ?? null;

// Обробка дій на основі параметра "action"
try {
  if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 1)) {
    $candidateController->createCandidate($_POST);
    header('Location: /manage-candidates');
    exit();
  } elseif ($action === 'edit' && $candidateId && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['role_id'] == 3 || $_SESSION['role_id'] == 1)) {
    $candidateController->updateCandidate($candidateId, $_POST);
    header('Location: /manage-candidates');
    exit();
  } elseif ($action === 'delete' && $candidateId && $_SESSION['role_id'] == 1) {
    $candidateController->deleteCandidate($candidateId);
    header('Location: /manage-candidates');
    exit();
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}

// Отримання списку кандидатів для відображення
$candidates = $candidateController->getAllCandidates();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Управління кандидатами</title>
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
    <h1 class="text-center">Управління кандидатами</h1>

    <div class="row">
      <div class="w-100">
        <?php if (isset($error)): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Кнопка для додавання нового кандидата -->
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/manage-candidates/create" class="btn btn-secondary">Додати нового кандидата</a>
          </div>
        <?php endif; ?>

        <!-- Форма додавання/редагування кандидата -->
        <?php if ($action === 'create' || ($action === 'edit' && $candidateId)):
          $candidate = $action === 'edit' ? $candidateController->getCandidateById($candidateId) : null; ?>
          <div class="modal fade" id="candidateModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="candidateModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати кандидата</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post"
                        action="/manage-candidates/<?= $action ?>/<?= htmlspecialchars($candidateId) ?>"
                        enctype="multipart/form-data">
                    <div class="m-0 form-group">
                      <label for="name">Ім'я кандидата</label>
                      <input class="form-control" type="text" id="name" name="name"
                             value="<?= htmlspecialchars($candidate['name'] ?? '') ?>"
                             required>
                    </div>

                    <div class="m-0form-group">
                      <label for="full_name">Повне ім'я кандидата</label>
                      <input class="form-control" type="text" id="full_name" name="full_name"
                             value="<?= htmlspecialchars($candidate['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="m-0 form-group">
                      <label for="date_of_birth">Дата народження</label>
                      <input class="form-control" type="date" id="date_of_birth" name="date_of_birth"
                             value="<?= htmlspecialchars($candidate['date_of_birth'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="party">Партія</label>
                      <input class="form-control" type="text" id="party" name="party"
                             value="<?= htmlspecialchars($candidate['party'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="party_affiliation">Партійна приналежність</label>
                      <input class="form-control" type="text" id="party_affiliation" name="party_affiliation"
                             value="<?= htmlspecialchars($candidate['party_affiliation'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="district_id">Округ</label>
                      <select class="form-control" id="district_id" name="district_id" required>
                        <?= $candidateController->getDistrictsOptions($candidate['district_id'] ?? null) ?>
                      </select>
                    </div>

                    <div class="m-0 form-group">
                      <label for="bio">Біографія</label>
                      <textarea class="form-control" id="bio"
                                name="bio"><?= htmlspecialchars($candidate['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="m-0 form-group">
                      <label for="previous_experience">Попередній досвід</label>
                      <textarea class="form-control" id="previous_experience"
                                name="previous_experience"><?= htmlspecialchars($candidate['previous_experience'] ?? '') ?></textarea>
                    </div>

                    <div class="m-0 form-group">
                      <label for="education">Освіта</label>
                      <input class="form-control" type="text" id="education" name="education"
                             value="<?= htmlspecialchars($candidate['education'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="campaign_promises">Програма кандидата</label>
                      <textarea class="form-control" id="campaign_promises"
                                name="campaign_promises"><?= htmlspecialchars($candidate['campaign_promises'] ?? '') ?></textarea>
                    </div>

                    <div class="m-0 form-group">
                      <label for="contact_info">Контактна інформація</label>
                      <input class="form-control" type="text" id="contact_info" name="contact_info"
                             value="<?= htmlspecialchars($candidate['contact_info'] ?? '') ?>">
                    </div>

                    <div class="m-0 form-group">
                      <label for="social_media_links">Посилання на соціальні мережі</label>
                      <input class="form-control" type="text" id="social_media_links" name="social_media_links"
                             value="<?= htmlspecialchars($candidate['social_media_links'] ?? '') ?>">
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50"
                              type="submit"><?= $action === 'edit' ? 'Зберегти зміни' : 'Додати кандидата' ?></button>
                      <a href="/manage-candidates" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#candidateModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Список кандидатів -->
          <h2 class="h3 text-center mb-3">Список кандидатів</h2>

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
                   data-export-options='{ "fileName":"Кандидати", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th class="idd text-center" data-sortable="true" title="ID" data-filter-control="input"
                    data-visible="true" data-field="id">ID
                </th>
                <th data-sortable="true" class="text-center" title="Назва" data-filter-control="input"
                    data-visible="true"
                    data-field="name">Ім'я
                </th>
                <th data-sortable="true" class="text-center" title="Повне імʼя" data-filter-control="input"
                    data-visible="true"
                    data-field="full_name">Повне ім'я
                </th>
                <th data-sortable="true" class="text-center" title="Партія" data-filter-control="input"
                    data-visible="true"
                    data-field="party">Партія
                </th>
                <th data-sortable="true" class="text-center" title="Округ" data-filter-control="input"
                    data-visible="true"
                    data-field="district_name">Округ
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($candidates as $candidate): ?>
                <tr id="tr-id-<?= htmlspecialchars($candidate['candidate_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($candidate['candidate_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($candidate['candidate_id']); ?>"
                      class="td-c-<?= htmlspecialchars($candidate['candidate_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($candidate['candidate_id']); ?>">
                    <?= htmlspecialchars($candidate['candidate_id']); ?>
                  </td>
                  <td title="<?= htmlspecialchars($candidate['name']); ?>">
                    <span data-type="text" data-title="Назва" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($candidate['candidate_id']); ?>" data-name="name">
                      <?= htmlspecialchars($candidate['name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($candidate['full_name']); ?>">
                    <span data-type="text" data-title="Повне імʼя" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($candidate['candidate_id']); ?>" data-name="full_name">
                      <?= htmlspecialchars($candidate['full_name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($candidate['party']); ?>">
                    <span data-type="text" data-title="Партія" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($candidate['candidate_id']); ?>" data-name="party">
                      <?= htmlspecialchars($candidate['party']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($candidate['district_name']); ?>">
                    <span data-type="text" data-title="Округ" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($candidate['candidate_id']); ?>" data-name="district_name">
                      <?= htmlspecialchars($candidate['district_name']); ?> </span>
                  </td>
                  <td title="Дії">
                    <?php if ($_SESSION['role_id'] === 1 || $_SESSION['role_id'] === 3): ?>
                      <a href="/manage-candidates/edit/<?= $candidate['candidate_id'] ?>" class="ems-icon edit"
                         title="Редагувати">
                        <i class="far fa-edit"></i>
                      </a>
                    <?php endif; ?>
                    <?php if ($_SESSION['role_id'] === 1): ?>
                      | <a href="/manage-candidates/delete/<?= $candidate['candidate_id'] ?>"
                           onclick="return confirm('Ви впевнені, що хочете видалити цього кандидата?')"
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
        pdf.save('Кандидати.pdf');
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
