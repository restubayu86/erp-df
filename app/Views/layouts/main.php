<!DOCTYPE html>
<html lang="id" data-bs-theme="light" dir="ltr">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="base-url" content="<?= base_url('/') ?>">
  <meta name="csrf-header" content="<?= csrf_header() ?>">
  <meta name="csrf-token" content="<?= csrf_hash() ?>">
  <title><?= esc((string)$this->renderSection('title')) ?> | <?= esc(app_name()) ?></title>
  <link rel="icon" type="image/x-icon" href="<?= base_url('assets/img/favicons/favicon.ico') ?>">

  <script src="<?= base_url('assets/js/config.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/simplebar/simplebar.min.js') ?>"></script>

  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,500,600,700%7cPoppins:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('assets/vendors/simplebar/simplebar.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('assets/css/theme.min.css') ?>" id="style-default">
  <link rel="stylesheet" href="<?= base_url('assets/css/user.min.css') ?>" id="user-style-default">
  <?= $this->renderSection('styles') ?>
</head>

<body data-module="<?= esc((string) ($moduleName ?? "")) ?>">
  <main class="main" id="top">
    <div class="container-fluid" data-layout="container">

      <?= $this->include('partials/topnav') ?>

      <div class="content">
        <?= $this->include('partials/flash') ?>
        <?= $this->include('partials/page_header') ?>

        <?= $this->renderSection('content') ?>

        <?= $this->include('partials/footer') ?>
      </div>
    </div>
  </main>

  <script src="<?= base_url('assets/vendors/popper/popper.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/bootstrap/bootstrap.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/anchorjs/anchor.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/is/is.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/fontawesome/all.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/lodash/lodash.min.js') ?>"></script>
  <script src="<?= base_url('assets/vendors/list.js/list.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/theme.js') ?>"></script>
  <script src="<?= base_url('assets/js/erp.js') ?>"></script>
  <?= $this->renderSection('scripts') ?>
</body>

</html>