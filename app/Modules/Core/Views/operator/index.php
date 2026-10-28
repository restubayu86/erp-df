<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Tablet Operator<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$user = auth()->user();
$btn  = [   // tujuan => [label pada status asal => [teks, warna]]
  'running' => ['approved' => ['Mulai', 'success'], 'paused' => ['Lanjutkan', 'success']],
  'paused'  => ['running' => ['Jeda', 'warning']],
  'done'    => ['running' => ['Selesai', 'primary']],
];
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <?php foreach ($shifts as $s): ?>
    <a class="btn btn-sm <?= $s['shift_id'] === $shiftId ? 'btn-primary' : 'btn-falcon-default' ?>"
       href="<?= site_url($moduleName . '/operator?shift=' . $s['shift_id'] . '&date=' . $date) ?>"><?= esc($s['name']) ?> <span class="fs-11">(<?= esc($s['start']) ?>–<?= esc($s['end']) ?>)</span></a>
  <?php endforeach ?>
  <?php if (! $shifts): ?><span class="badge badge-subtle-warning">Tidak ada shift aktif pada <?= esc($date) ?></span><?php endif ?>
  <span class="text-body-tertiary fs-9 ms-2">Tanggal produksi: <?= esc($date) ?></span>
</div>

<?php if (! $byMachine): ?>
  <div class="alert alert-info">Belum ada schedule yang disetujui untuk shift ini.</div>
<?php endif ?>

<div class="row g-3">
  <?php foreach ($byMachine as $machine => $items): ?>
    <div class="col-12 col-xl-6">
      <div class="card h-100">
        <div class="card-header"><h4 class="mb-0"><?= esc($machine) ?></h4></div>
        <div class="list-group list-group-flush">
          <?php foreach ($items as $r): ?>
            <?php $l = $labels[$r['status']] ?? [$r['status'], 'secondary']; $allowed = $workflow->allowedFor($r['status'], $user); ?>
            <div class="list-group-item">
              <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                  <div class="fw-bold fs-8">#<?= (int) $r['sequence'] ?> · <?= esc((string) $r['order_first']) ?><?= $r['item_count'] > 1 ? ' +' . ($r['item_count'] - 1) : '' ?></div>
                  <div class="fs-9 text-body-tertiary">
                    <?= esc((string) ($r['process_name'] ?? '–')) ?> · <?= esc((string) ($r['customer_first'] ?? '-')) ?> ·
                    <?= esc(substr($r['start_at'], 11, 5)) ?>–<?= esc(substr($r['end_at'], 11, 5)) ?>
                    <?php if ($r['qty_primary'] !== null): ?> · <?= esc(number_format((float) $r['qty_primary'], 1, ',', '.')) ?> <?= esc($qtyUom) ?><?php endif ?>
                  </div>
                  <div class="fs-10 text-600"><?= esc($r['schedule_no']) ?> · <?= esc($r['shift_name']) ?> <?= esc($r['work_date']) ?></div>
                </div>
                <div class="text-end">
                  <span class="badge rounded-pill badge-subtle-<?= esc($l[1]) ?>"><?= esc($l[0]) ?></span>
                  <?php if (isset($overshift[(int) $r['id']])): ?><span class="badge bg-danger">Overshift</span><?php endif ?>
                </div>
              </div>
              <div class="d-flex flex-wrap gap-2 mt-2">
                <?php foreach (['running', 'paused', 'done'] as $to): ?>
                  <?php if (in_array($to, $allowed, true) && isset($btn[$to][$r['status']])): [$label, $cls] = $btn[$to][$r['status']]; ?>
                    <button class="btn btn-lg btn-<?= $cls ?> btn-trans" data-id="<?= (int) $r['id'] ?>" data-to="<?= $to ?>"><?= $label ?></button>
                  <?php endif ?>
                <?php endforeach ?>
                <?php if (in_array($r['status'], ['running', 'paused'], true)): ?>
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
  setTimeout(() => location.reload(), 60000);   // sinkron bila ada perubahan dari approval
</script>
<?= $this->endSection() ?>
