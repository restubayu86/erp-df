<?php

/** Dikirim dari controller lewat $this->page(): $pageTitle, $breadcrumbs, $actions (HTML) */
$pageTitle   = $pageTitle   ?? '';
$breadcrumbs = $breadcrumbs ?? [];
$actions     = $actions     ?? '';
?>
<?php if ($pageTitle): ?>
  <div class="row flex-between-center gy-2 mb-3">
    <div class="col-md">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb fs-10 mb-1">
          <li class="breadcrumb-item"><a href="<?= site_url('/') ?>">Home</a></li>
          <?php foreach ($breadcrumbs as $label => $url): ?>
            <?php if ($url): ?>
              <li class="breadcrumb-item"><a href="<?= site_url($url) ?>"><?= esc($label) ?></a></li>
            <?php else: ?>
              <li class="breadcrumb-item active" aria-current="page"><?= esc($label) ?></li>
            <?php endif ?>
          <?php endforeach ?>
        </ol>
      </nav>
      <h5 class="mb-0"><?= esc($pageTitle) ?></h5>
    </div>
    <?php if ($actions): ?><div class="col-auto"><?= $actions ?></div><?php endif ?>
  </div>
<?php endif ?>