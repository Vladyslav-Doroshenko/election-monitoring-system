<header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="./../">Election Monitoring System</a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNavDropdown">
      <ul class="navbar-nav w-100 justify-content-end">
        <?php
        $current_page = $_SERVER['REQUEST_URI'];

        if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item <?= $current_page == '/dashboard' ? 'active' : '' ?>">
            <a class="nav-link" href="/dashboard">Дашборд</a>
          </li>

          <?php if ($_SESSION['role_id'] == 1): ?> <!-- Admin -->
            <li class="nav-item dropdown <?= $current_page == '/manage-users' || $current_page == '/manage-roles' || $current_page == '/manage-sessions' || $current_page == '/manage-regions' ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                Admin
              </a>

              <div class="dropdown-menu">
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-users' ? 'active' : '' ?>"
                   href="/manage-users">Користувачі</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-roles' ? 'active' : '' ?>"
                   href="/manage-roles">Ролі</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-sessions' ? 'active' : '' ?>"
                   href="/manage-sessions">Сесії користувачів</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-regions' ? 'active' : '' ?>"
                   href="/manage-regions">Регіони</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/start/auto-insert-data' ? 'active' : '' ?>"
                   href="start/auto-insert-data">Generate Data Test</a>
              </div>
            </li>
          <?php endif;

          if (in_array($_SESSION['role_id'], [1, 2])): ?> <!-- Admin, Supervisor -->
            <li class="nav-item dropdown <?= $current_page == '/manage-districts' || $current_page == '/manage-stations' ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                Виборчі
              </a>

              <div class="dropdown-menu">
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-districts' ? 'active' : '' ?>"
                   href="/manage-districts">Округи</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/manage-stations' ? 'active' : '' ?>"
                   href="/manage-stations">Дільниці</a>
              </div>
            </li>

            <li class="nav-item <?= $current_page == '/manage-candidates' ? 'active' : '' ?>">
              <a class="nav-link" href="/manage-candidates">Кандидати</a>
            </li>
          <?php endif;

          if (in_array($_SESSION['role_id'], [1, 3])): ?> <!-- Admin, Data Entry -->
            <li class="nav-item dropdown <?= $current_page == '/enter-votes' || $current_page == '/enter-ballots' ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                Ввести
              </a>

              <div class="dropdown-menu">
                <a class="dropdown-item btn-secondary <?= $current_page == '/enter-votes' ? 'active' : '' ?>"
                   href="/enter-votes">Голоси</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/enter-ballots' ? 'active' : '' ?>"
                   href="/enter-ballots">Бюлетені</a>
              </div>
            </li>
          <?php endif;

          if (in_array($_SESSION['role_id'], [1, 2, 4])): ?> <!-- Admin, Supervisor, Viewer -->
            <li class="nav-item dropdown <?= $current_page == '/calculate-totals' || $current_page == '/calculate-turnout' ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                Підрахунок
              </a>

              <div class="dropdown-menu">
                <a class="dropdown-item btn-secondary <?= $current_page == '/calculate-totals' ? 'active' : '' ?>"
                   href="/calculate-totals">По округах</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/calculate-turnout' ? 'active' : '' ?>"
                   href="/calculate-turnout">По дільницях</a>
              </div>
            </li>

            <li class="nav-item dropdown <?= $current_page == '/summary-report' || $current_page == '/select-district' ? 'active' : '' ?>">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-expanded="false">
                Звіт
              </a>

              <div class="dropdown-menu">
                <a class="dropdown-item btn-secondary <?= $current_page == '/summary-report' ? 'active' : '' ?>"
                   href="/summary-report">Підсумковий</a>
                <a class="dropdown-item btn-secondary <?= $current_page == '/select-district' ? 'active' : '' ?>"
                   href="/select-district">По округу</a>
              </div>
            </li>
          <?php endif; ?>

          <li class="nav-item <?= $current_page == '/logout' ? 'active' : '' ?>">
            <a class="nav-link" href="/logout">Вийти</a>
          </li>
        <?php else: ?>
          <li class="nav-item <?= $current_page == '/login' ? 'active' : '' ?>">
            <a class="nav-link" href="/login">Вхід</a>
          </li>

          <li class="nav-item <?= $current_page == '/register' ? 'active' : '' ?>">
            <a class="nav-link" href="/register">Реєстрація</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>
