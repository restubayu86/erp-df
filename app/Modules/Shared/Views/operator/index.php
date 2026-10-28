<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Tablet Operator<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$user  = auth()->user();
$btn   = [
  'running' => ['approved' => ['Mulai', 'success'], 'paused' => ['Lanjutkan', 'success']],
  'paused'  => ['running' => ['Pause', 'warning']],
  'done'    => ['running' => ['Selesai', 'primary'], 'paused' => ['Selesai', 'primary']],
];
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <?php foreach ($shifts as $code => $s): ?>
    <a class="btn btn-sm <?= $code === $shift ? 'btn-primary' : 'btn-falcon-default' ?>"
      href="<?= site_url($moduleName . '/operator?shift=' . $code . '&date=' . $date) ?>"><?= esc((string)$s['label']) ?></a>
  <?php endforeach ?>
  <span class="text-body-tertiary fs-9 ms-2">Tanggal produksi: <?= esc((string)$date) ?></span>
</div>

<?php if (! $byMachine): ?>
  <div class="alert alert-info">Belum ada schedule yang disetujui untuk shift ini.</div>
<?php endif ?>

<div class="row g-3">
  <?php foreach ($byMachine as $machine => $items): ?>
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header">
          <h4 class="mb-0"><?= esc((string)$machine) ?></h4>
        </div>
        <div class="list-group list-group-flush">
          <?php foreach ($items as $r): ?>
            <?php
            $l       = $labels[$r['status']] ?? [$r['status'], 'secondary'];
            $allowed = $workflow->allowedFor($r['status'], $user);
            ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-bold fs-8">#<?= (int) $r['priority'] ?> · <?= esc((string)$r['order_no']) ?></div>
                  <div class="fs-9 text-body-tertiary">
                    <?= esc((string)$r['process']) ?> · <?= esc((string)$r['customer'] ?? '-') ?> ·
                    <?= esc((string)substr($r['start_at'], 11, 5)) ?>–<?= esc((string)substr($r['end_at'], 11, 5)) ?>
                  </div>
                </div>
                <span class="badge rounded-pill badge-subtle-<?= esc((string)$l[1]) ?>"><?= esc((string)$l[0]) ?></span>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach (['running', 'paused', 'done'] as $to): ?>
                  <?php if (in_array($to, $allowed, true) && isset($btn[$to][$r['status']])): ?>
                    <?php [$label, $cls] = $btn[$to][$r['status']]; ?>
                    <button class="btn btn-lg btn-<?= $cls ?> btn-trans" data-id="<?= (int) $r['id'] ?>" data-to="<?= $to ?>"><?= $label ?></button>
                  <?php endif ?>
                <?php endforeach ?>
                <?php if (in_array($r['status'], ['running', 'paused'], true)): ?>
                  <!-- Tahap berikutnya: tautkan ke form checksheet & laporan produksi per schedule -->
                  <a class="btn btn-lg btn-falcon-default disabled" aria-disabled="true">Checksheet</a>
                  <a class="btn btn-lg btn-falcon-default disabled" aria-disabled="true">Laporan</a>
                <?php endif ?>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('.btn-trans');
    if (!b) return;
    b.disabled = true;
    if (await erp.transition(b.dataset.id, b.dataset.to)) location.reload();
    else b.disabled = false;
  });
  setTimeout(() => location.reload(), 60000); // sinkron bila ada perubahan dari ACC / scheduler
</script>
<?= $this->endSection() ?>