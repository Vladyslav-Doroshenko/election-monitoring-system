<?php
require_once '../config/config.php';
require_once '../src/controllers/RegionController.php';
session_start();

// Перевірка авторизації, активної сесії та прав доступу (тільки для адміністратора)
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
$regionController = new RegionController($pdo);
$action           = $_GET['action'] ?? 'view';
$regionId         = $_GET['region_id'] ?? null;

try {
  // Обробка дій на основі параметра "action"
  if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $regionController->createRegion($_POST);
    header('Location: /manage-regions');
    exit();
  } elseif ($action === 'edit' && $regionId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $regionController->updateRegion($regionId, $_POST);
    header('Location: /manage-regions');
    exit();
  } elseif ($action === 'delete' && $regionId) {
    $regionController->deleteRegion($regionId);
    header('Location: /manage-regions');
    exit();
  } elseif ($action === 'populate') {
    $regionController->populateRegionsFromAPI();
    header('Location: /manage-regions');
    exit();
  }
} catch (Exception $e) {
  $error = $e->getMessage();
}

// Отримання списку регіонів для відображення
$regions = $regionController->getAllRegions();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Управління регіонами</title>
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
    <h1 class="text-center">Управління регіонами</h1>

    <div class="row">
      <div class="w-100">
        <?php if (isset($error)): ?>
          <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Кнопки для додавання регіону та оновлення з API (лише для Admin) -->
        <?php if ($action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/manage-regions/create" class="btn btn-secondary">Додати новий регіон</a>
            <a href="/manage-regions/populate" class="btn btn-outline-secondary">Оновити з API</a>
          </div>
        <?php endif; ?>

        <!-- Форма додавання/редагування регіону -->
        <?php if ($action === 'create' || ($action === 'edit' && $regionId)):
          $region = $action === 'edit' ? $regionController->getRegionById($regionId) : null; ?>
          <div class="modal fade" id="regionModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="regionModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати регіон</h5>
                </div>
                <div class="modal-body p-4">
                  <form method="post" action="/manage-regions/<?= $action ?>/<?= htmlspecialchars($regionId); ?>">
                    <div class="m-0 form-group">
                      <label for="name">Назва регіону</label>
                      <input class="form-control" type="text" id="name" name="name"
                             value="<?= htmlspecialchars($region['name'] ?? ''); ?>"
                             required>
                    </div>

                    <div class="m-0 form-group">
                      <label for="description">Опис</label>
                      <textarea class="form-control" id="description" name="description"
                                required><?= htmlspecialchars($region['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button class="btn btn-success w-50"
                              type="submit"><?= $action === 'edit' ? 'Зберегти зміни' : 'Додати регіон'; ?></button>
                      <a href="/manage-regions" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#regionModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Список регіонів -->
          <h2 class="h3 text-center mb-3">Список регіонів</h2>

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
                   data-export-options='{ "fileName":"Регіони", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th class="idd text-center" data-sortable="true" title="ID" data-filter-control="input"
                    data-visible="true" data-field="id">ID
                </th>
                <th data-sortable="true" class="text-center" title="Назва регіону" data-filter-control="input"
                    data-visible="true"
                    data-field="name">Назва регіону
                </th>
                <th data-sortable="true" class="text-center" title="Опис" data-filter-control="input"
                    data-visible="true"
                    data-field="description">Опис
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($regions as $region): ?>
                <tr id="tr-id-<?= htmlspecialchars($region['region_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($region['region_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($region['region_id']); ?>"
                      class="td-c-<?= htmlspecialchars($region['region_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($region['region_id']); ?>">
                    <?= htmlspecialchars($region['region_id']); ?>
                  </td>
                  <td title="<?= htmlspecialchars($region['name']); ?>">
                    <span data-type="text" data-title="Назва регіону" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($region['region_id']); ?>" data-name="name">
                      <?= htmlspecialchars($region['name']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($region['description']); ?>">
                    <span data-type="text" data-title="Опис" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($region['region_id']); ?>" data-name="description">
                      <?= htmlspecialchars($region['description']); ?> </span>
                  </td>
                  <td title="Дії">
                    <?php if ($_SESSION['role_id'] == 1): ?>
                      <a href="/manage-regions/edit/<?= $region['region_id'] ?>" class="ems-icon edit"
                         title="Редагувати">
                        <i class="far fa-edit"></i>
                      </a>
                      |
                      <a href="/manage-regions/delete/<?= $region['region_id'] ?>" class="ems-icon trash"
                         title="Видалити"
                         onclick="return confirm('Ви впевнені, що хочете видалити цей регіон?')">
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
        pdf.save('Регіони.pdf');
        document.location.href = document.location.href;
      });
    });

    $(function () {
      var $table1 = $('#table111'), selections1 = [], ids = [];

      function getHeight() {
        return $(window).height() - 120;
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
