<?php
require_once '../config/config.php';
require_once '../src/controllers/UserController.php';
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

// Якщо сесія не знайдена або її термін закінчився, виконуємо вихід
if (!$session || (isset($session['expires_at']) && strtotime($session['expires_at']) < time())) {
  session_unset();
  session_destroy();
  header("Location: /login");
  exit();
}

// Ініціалізація контролера
$userController = new UserController($pdo);
$action         = $_GET['action'] ?? 'view';
$userId         = $_GET['user_id'] ?? null;

// Обробка різних дій на основі параметра "action"
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $userController->createUser($_POST);

  header("Location: /manage-users");
  exit();
} elseif ($action === 'edit' && $userId && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $userController->updateUser($userId, $_POST);

  header("Location: /manage-users");
  exit();
} elseif ($action === 'delete' && $userId) {
  $userController->deleteUser($userId);

  header("Location: /manage-users");
  exit();
}

// Отримання списку користувачів для відображення
$users = $userController->getAllUsers();
?>

<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Управління користувачами</title>
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
    <h1 class="text-center">Управління користувачами</h1>

    <div class="row">
      <div class="w-100">

        <!-- Кнопка для додавання нового користувача (відображається лише для адміністратора) -->
        <?php if ($_SESSION['role_id'] == 1 && $action !== 'create' && $action !== 'edit'): ?>
          <div class="add-button">
            <a href="/manage-users/create" class="btn btn-secondary">Додати нового користувача</a>
          </div>
        <?php endif; ?>

        <!-- Форма додавання/редагування користувача -->
        <?php if ($action === 'create' || ($action === 'edit' && $userId)):
          $user = $action === 'edit' ? $userController->getUserById($userId) : null; ?>
          <div class="modal fade" id="userModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
               role="dialog" aria-labelledby="userModalLabel"
               aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header justify-content-center">
                  <h5 class="modal-title">Додати користувача</h5>
                </div>
                <div class="modal-body p-4">
                  <form class="modal-form" method="post"
                        action="/manage-users/<?= $action ?>/<?= htmlspecialchars($userId) ?>">
                    <div class="m-0 form-group">
                      <label for="username">Ім'я користувача</label>
                      <input class="form-control" type="text" id="username" name="username"
                             value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                             required>
                    </div>

                    <div class="m-0 form-group">
                      <label for="email">Email</label>
                      <input class="form-control" type="email" id="email" name="email"
                             value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                             required>
                    </div>

                    <div class="m-0 form-group">
                      <label for="password">Пароль</label>
                      <input class="form-control" type="password" id="password"
                             name="password" <?= $action === 'create' ? 'required' : '' ?>
                             aria-describedby="passwordHelp">

                      <?php if ($action === 'edit'): ?>
                        <small id="passwordHelp" class="form-text text-muted">Залиште поле порожнім, якщо не бажаєте
                          змінювати пароль</small>
                      <?php endif; ?>
                    </div>

                    <div class="m-0 form-group">
                      <label for="role_id">Роль</label>
                      <select class="form-control" id="role_id" name="role_id" required>
                        <?= $userController->getRolesOptions($user['role_id'] ?? null) ?>
                      </select>
                    </div>

                    <div class="d-flex mt-4 w-100 modal-btn">
                      <button type="submit" class="btn btn-success w-50">
                        <?= $action === 'edit' ? 'Зберегти зміни' : 'Додати користувача' ?></button>
                      <a href="/manage-users" class="btn btn-danger w-50">Відміна</a>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>

          <script>
            document.addEventListener("DOMContentLoaded", function () {
              $('#userModal').modal('show');
            });
          </script>
        <?php endif; ?>

        <div class="w-100">
          <!-- Список користувачів -->
          <h2 class="h3 text-center mb-3">Список користувачів</h2>

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
                   data-export-options='{ "fileName":"Користувачі", "worksheetName":"list1" }'>
              <thead>
              <tr>
                <th data-field="state" data-print-ignore="true" data-checkbox="true" tabindex="0"></th>
                <th class="idd text-center" data-sortable="true" title="ID" data-filter-control="input"
                    data-visible="true" data-field="id">ID
                </th>
                <th data-sortable="true" class="text-center" title="Ім'я користувача" data-filter-control="input"
                    data-visible="true"
                    data-field="name">Ім'я користувача
                </th>
                <th data-sortable="true" class="text-center" title="Email" data-filter-control="input"
                    data-visible="true" data-field="email">Email
                </th>
                <th data-sortable="true" class="text-center" title="Роль" data-filter-control="input"
                    data-visible="true" data-field="role">Роль
                </th>
                <th class="text-center" title="Дії" data-print-ignore="true">Дії</th>
              </tr>
              </thead>
              <tbody>
              <?php foreach ($users as $user): ?>
                <tr id="tr-id-<?= htmlspecialchars($user['user_id']); ?>"
                    class="tr-c-<?= htmlspecialchars($user['user_id']); ?>">
                  <td id="td-id-<?= htmlspecialchars($user['user_id']); ?>"
                      class="td-c-<?= htmlspecialchars($user['user_id']); ?>"></td>
                  <td title="<?= htmlspecialchars($user['user_id']); ?>">
                    <?= htmlspecialchars($user['user_id']); ?>
                  </td>
                  <td title="<?= htmlspecialchars($user['username']); ?>">
                    <span data-type="text" data-title="Ім'я користувача" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($user['user_id']); ?>" data-name="name">
                      <?= htmlspecialchars($user['username']); ?> </span>
                  </td>
                  <td title="<?= htmlspecialchars($user['email']); ?>">
                    <span data-type="text" data-title="Email" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($user['user_id']); ?>" data-name="email">
                      <?= htmlspecialchars($user['email']); ?></span>
                  </td>
                  <td title="<?= htmlspecialchars($user['role_name']); ?>">
                    <span data-type="text" data-title="Роль" data-mode="popup" data-placement="top"
                          data-pk="<?= htmlspecialchars($user['user_id']); ?>" data-name="role">
                      <?= htmlspecialchars($user['role_name']); ?></span>
                  </td>
                  <td title="Дії">
                    <?php if ($_SESSION['role_id'] == 1): ?>
                      <a href="/manage-users/edit/<?= $user['user_id'] ?>" class="ems-icon edit" title="Редагувати">
                        <i class="far fa-edit"></i>
                      </a>
                      |
                      <a href="/manage-users/delete/<?= $user['user_id'] ?>" class="ems-icon trash" title="Видалити"
                         onclick="return confirm('Ви впевнені, що хочете видалити цього користувача?')"><i
                            class="far fa-trash-alt"></i></a>
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
        pdf.save('Користувачі.pdf');
        document.location.href = document.location.href;
      });
    });

    $(function () {
      var $table1 = $('#table111'), selections1 = [], ids = [];

      function getHeight() {
        return $(window).height() - 200;
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
