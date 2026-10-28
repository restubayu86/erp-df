<?php

/**
 * Navbar Falcon (top navigation)
 * Menu: config/Menu.php | Notifikasi: Libraries/NavNotifications.php
 */
$user    = auth()->user();
$name    = user_display_name($user);
$ini     = user_initials($name);
$roles   = user_role_titles($user);
$modules = array_map(static fn($c) => config('Menu')->modules[$c]['label'], user_modules($user));
$notifs  = nav_notifications();
$nTotal  = array_sum(array_column($notifs, 'count'));
?>
<nav class="navbar navbar-light navbar-glass navbar-top navbar-expand-lg">
  <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button" data-bs-toggle="collapse"
    data-bs-target="#navbarTopCollapse" aria-controls="navbarTopCollapse" aria-expanded="false" aria-label="Toggle Navigation">
    <span class="navbar-toggle-icon"><span class="toggle-line"></span></span>
  </button>
  <a class="navbar-brand me-1 me-sm-3" href="<?= site_url('/') ?>">
    <span class="font-sans-serif text-primary fw-bold">ERPDF</span>
  </a>

  <div class="collapse navbar-collapse scrollbar" id="navbarTopCollapse">
    <ul class="navbar-nav" data-top-nav-dropdowns="data-top-nav-dropdowns">
      <?php foreach (menu_items() as $m): ?>
        <?php if (!empty($m['children'])): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= $m['active'] ? 'active' : '' ?>" href="#" role="button"
              data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><?= esc($m['label']) ?></a>
            <div class="dropdown-menu dropdown-caret dropdown-menu-card border-0 mt-0">
              <div class="bg-white dark__bg-1000 rounded-3 py-2">
                <?php foreach ($m['children'] as $c): ?>
                  <?php if ($c['ready']): ?>
                    <a class="dropdown-item link-600 fw-medium <?= $c['active'] ? 'active' : '' ?>" href="<?= site_url($c['url']) ?>">
                      <?= esc((string) $c['label']) ?>
                    </a>
                  <?php else: ?>
                    <a class="dropdown-item link-600 fw-medium disabled" href="#!" tabindex="-1" aria-disabled="true">
                      <?= esc((string) $c['label']) ?><span class="badge rounded-pill ms-2 badge-subtle-secondary">Segera</span>
                    </a>
                  <?php endif ?>
                <?php endforeach ?>
              </div>
            </div>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link <?= $m['active'] ? 'active' : '' ?> <?= $m['ready'] ? '' : 'disabled' ?>" href="<?= site_url($m['url']) ?>">
              <?= esc($m['label']) ?>
            </a>
          </li>
        <?php endif ?>
      <?php endforeach ?>
    </ul>
  </div>

  <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
    <!-- Tema -->
    <li class="nav-item ps-2 pe-0">
      <div class="dropdown theme-control-dropdown">
        <a class="nav-link d-flex align-items-center dropdown-toggle fa-icon-wait fs-9 pe-1 py-0" href="#" role="button"
          id="themeSwitchDropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <span class="fas fa-sun fs-7" data-fa-transform="shrink-2" data-theme-dropdown-toggle-icon="light"></span>
          <span class="fas fa-moon fs-7" data-fa-transform="shrink-3" data-theme-dropdown-toggle-icon="dark"></span>
          <span class="fas fa-adjust fs-7" data-fa-transform="shrink-2" data-theme-dropdown-toggle-icon="auto"></span>
        </a>
        <div class="dropdown-menu dropdown-menu-end dropdown-caret border py-0 mt-3" aria-labelledby="themeSwitchDropdown">
          <div class="bg-white dark__bg-1000 rounded-2 py-2">
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="light" data-theme-control="theme">
              <span class="fas fa-sun"></span>Terang<span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="dark" data-theme-control="theme">
              <span class="fas fa-moon"></span>Gelap<span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
            <button class="dropdown-item d-flex align-items-center gap-2" type="button" value="auto" data-theme-control="theme">
              <span class="fas fa-adjust"></span>Otomatis<span class="fas fa-check dropdown-check-icon ms-auto text-600"></span>
            </button>
          </div>
        </div>
      </div>
    </li>

    <!-- Notifikasi -->
    <li class="nav-item dropdown">
      <a class="nav-link <?= $nTotal ? 'notification-indicator notification-indicator-primary' : '' ?> px-0 fa-icon-wait"
        id="navbarDropdownNotification" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
        data-hide-on-body-scroll="data-hide-on-body-scroll">
        <span class="fas fa-bell" data-fa-transform="shrink-6" style="font-size: 33px;"></span>
      </a>
      <div class="dropdown-menu dropdown-caret dropdown-menu-end dropdown-menu-card dropdown-menu-notification dropdown-caret-bg"
        aria-labelledby="navbarDropdownNotification">
        <div class="card card-notification shadow-none">
          <div class="card-header">
            <div class="row justify-content-between align-items-center">
              <div class="col-auto">
                <h6 class="card-header-title mb-0">Notifikasi</h6>
              </div>
              <?php if ($nTotal): ?>
                <div class="col-auto ps-0 ps-sm-3">
                  <a class="card-link fw-normal" href="<?= site_url('schedule') ?>"><?= (int) $nTotal ?> baru</a>
                </div>
              <?php endif ?>
            </div>
          </div>
          <div class="scrollbar-overlay" style="max-height:19rem">
            <div class="list-group list-group-flush fw-normal fs-10">
              <?php if (!$notifs): ?>
                <div class="list-group-item text-center text-body-tertiary py-4">
                  <span class="fas fa-check-circle fs-5"></span>
                  <p class="fs-10 mb-0 mt-2">Tidak ada yang perlu ditindaklanjuti.</p>
                </div>
              <?php endif ?>
              <?php foreach ($notifs as $n): ?>
                <div class="list-group-item">
                  <a class="notification notification-flush notification-unread" href="<?= esc((string) $n['url']) ?>">
                    <div class="notification-avatar">
                      <div class="avatar avatar-2xl me-3">
                        <div class="avatar-name rounded-circle bg-<?= esc((string) $n['tone']) ?>-subtle text-<?= esc((string) $n['tone']) ?>">
                          <span class="fas fa-<?= esc((string) $n['icon']) ?>"></span>
                        </div>
                      </div>
                    </div>
                    <div class="notification-body">
                      <p class="mb-1">
                        <strong><?= esc((string) $n['title']) ?></strong>
                        <span class="badge rounded-pill ms-2 badge-subtle-<?= esc((string) $n['tone']) ?>"><?= (int) $n['count'] ?></span>
                      </p>
                      <?php if ($n['time']): ?>
                        <span class="notification-time"><span class="far fa-clock me-2"></span><?= esc((string) $n['time']) ?></span>
                      <?php endif ?>
                    </div>
                  </a>
                </div>
              <?php endforeach ?>
            </div>
          </div>
          <div class="card-footer text-center border-top">
            <a class="card-link d-block" href="<?= site_url('schedule') ?>">Lihat semua</a>
          </div>
        </div>
      </div>
    </li>

    <!-- Profil -->
    <li class="nav-item dropdown">
      <a class="nav-link pe-0 ps-2" id="navbarDropdownUser" href="#" role="button" data-bs-toggle="dropdown"
        aria-haspopup="true" aria-expanded="false">
        <div class="avatar avatar-xl">
          <div class="avatar-name rounded-circle bg-primary-subtle text-primary"><span><?= esc($ini) ?></span></div>
        </div>
      </a>
      <div class="dropdown-menu dropdown-caret dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
        <div class="bg-white dark__bg-1000 rounded-2 py-2">
          <div class="text-center px-3 pb-2">
            <h6 class="mb-0"><?= esc($name) ?></h6>
            <?php if ($user?->email): ?><p class="fs-10 text-600 mb-2"><?= esc($user->email) ?></p><?php endif ?>
            <?php foreach ($roles as $r): ?>
              <span class="badge rounded-pill badge-subtle-primary me-1"><?= esc((string) $r) ?></span>
            <?php endforeach ?>
            <p class="fs-10 text-600 mt-2 mb-0">
              Bagian: <?= $modules ? esc(implode(', ', $modules)) : '<span class="text-warning">belum diatur</span>' ?>
            </p>
          </div>
          <div class="dropdown-divider"></div>
          <?php if ($user?->can('settings.manage')): ?>
            <a class="dropdown-item" href="<?= site_url('admin/settings') ?>">
              <span class="fas fa-cog me-2"></span>Setting schedule
            </a>
          <?php endif ?>
          <a class="dropdown-item" href="<?= site_url('logout') ?>">
            <span class="fas fa-sign-out-alt me-2"></span>Keluar
          </a>
        </div>
      </div>
    </li>
  </ul>
</nav>