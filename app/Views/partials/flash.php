<?php foreach (['message' => ['success', 'check-circle'], 'error' => ['danger', 'times-circle']] as $key => [$type, $icon]): ?>
  <?php if (session()->getFlashdata($key)): ?>
    <div class="alert alert-<?= $type ?> border-2 d-flex align-items-center mb-3" role="alert">
      <div class="bg-<?= $type ?> me-3 icon-item"><span class="fas fa-<?= $icon ?> text-white fs-6"></span></div>
      <p class="mb-0 flex-1"><?= esc((string)session()->getFlashdata($key)) ?></p>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endif ?>
<?php endforeach ?>