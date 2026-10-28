<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($row['schedule_no']) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$l   = $labels[$row['status']] ?? [$row['status'], 'secondary'];
$mod = $moduleName;
$can = static fn (string $p) => $user->can($p);
$act = [
  'submitted' => ['Ajukan', 'paper-plane', 'primary'], 'approved' => ['Approve', 'check', 'success'],
  'revision'  => ['Minta Revisi', 'undo', 'warning'], 'rejected' => ['Reject', 'times', 'danger'],
  'cancelled' => ['Batalkan', 'ban', 'secondary'],
];
$fmt = static fn (?string $d) => $d ? date('d M Y H:i', strtotime($d)) : '–';
$actionNames = ['create' => 'Dibuat', 'copy' => 'Disalin', 'update' => 'Diubah', 'submit' => 'Diajukan', 'resubmit' => 'Diajukan ulang', 'approve' => 'Disetujui',
  'revise' => 'Minta revisi', 'reject' => 'Ditolak', 'start' => 'Mulai', 'pause' => 'Jeda', 'finish' => 'Selesai', 'cancel' => 'Dibatalkan', 'delete' => 'Dihapus'];

$isProduksi = $row['category'] === 'produksi';
$dyeLotNo = $row['schedule_no'];
if ($isProduksi) {
    $date = date('ymd', strtotime($row['work_date']));
    $mc = preg_replace('/[^0-9]/', '', $row['machine_code']);
    $mc = str_pad($mc, 2, '0', STR_PAD_LEFT);
    $seq = str_pad($row['sequence'], 2, '0', STR_PAD_LEFT);
    $dyeLotNo = "JD{$date}{$mc}{$seq}";
}
$cats = [
    'produksi' => ['Produksi', 'primary'],
    'perbaikan' => ['Perbaikan', 'warning'],
    'litbang' => ['Litbang', 'info'],
    'lain-lain' => ['Lain-lain', 'secondary']
];
$cat = $cats[$row['category']] ?? [$row['category'], 'secondary'];
?>
<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div>
        <h5 class="mb-1"><?= esc($dyeLotNo) ?>
          <span class="badge rounded-pill badge-subtle-<?= esc($cat[1]) ?> ms-2"><?= esc($cat[0]) ?></span>
          <span class="badge rounded-pill badge-subtle-<?= esc($l[1]) ?> ms-1"><?= esc($l[0]) ?></span>
          <?php if ($row['revision_no'] > 0): ?><span class="badge badge-subtle-info">Rev. <?= (int) $row['revision_no'] ?></span><?php endif ?>
        </h5>
        <div class="fs-10 text-600">ID: <?= esc($row['schedule_no']) ?> · Dibuat oleh <?= esc((string) ($row['creator_name'] ?? '–')) ?> · <?= esc($fmt($row['created_at'])) ?></div>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-start">
        <?php if ($can('schedule.edit') && in_array($row['status'], config('ScheduleFlow')->editable, true)): ?>
          <a class="btn btn-falcon-default btn-sm" href="<?= site_url("{$mod}/schedule/{$row['id']}/edit") ?>"><span class="fas fa-edit me-1"></span>Ubah</a>
        <?php endif ?>
        <?php if ($can('schedule.create')): ?>
          <a class="btn btn-falcon-default btn-sm" href="<?= site_url("{$mod}/schedule/create?copy_from={$row['id']}") ?>"><span class="fas fa-copy me-1"></span>Salin sebagai baru</a>
        <?php endif ?>
        <?php foreach ($allowed as $to): if (! isset($act[$to])) continue; [$label, $icon, $tone] = $act[$to]; $ep = $to === 'cancelled' ? 'transition' : $to; ?>
          <button class="btn btn-falcon-default btn-sm btn-flow" data-ep="<?= $ep ?>" data-to="<?= $to ?>" data-note="<?= in_array($to, ['revision', 'rejected'], true) ? 1 : 0 ?>"><span class="fas fa-<?= $icon ?> me-1 text-<?= $tone ?>"></span><?= $label ?></button>
        <?php endforeach ?>
      </div>
    </div>
    <hr>
    <div class="row g-3 fs-10">
      <div class="col-6 col-md-3"><div class="text-600">Mesin</div><strong><?= esc($row['machine_code'] . ' – ' . $row['machine_name']) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Proses</div><strong><?= esc((string) ($row['process_name'] ?? '–')) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Tanggal / Shift</div><strong><?= esc($row['work_date']) ?> · <?= esc($row['shift_name']) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Urutan / Prioritas</div><strong>#<?= (int) $row['sequence'] ?> · <?= esc(ucfirst($row['priority'])) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Rencana</div><strong><?= esc($fmt($row['start_at'])) ?> → <?= esc($fmt($row['end_at'])) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Aktual</div><strong><?= esc($fmt($row['actual_start'])) ?> → <?= esc($fmt($row['actual_end'])) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Operator</div><strong><?= esc((string) ($row['operator_name'] ?? '–')) ?></strong></div>
      <div class="col-6 col-md-3"><div class="text-600">Catatan</div><strong><?= esc((string) ($row['notes'] ?? '–')) ?></strong></div>
      <?php if ($row['review_note']): ?><div class="col-12"><div class="alert alert-warning mb-0 py-2">Catatan Kepala Bagian: <?= esc($row['review_note']) ?></div></div><?php endif ?>
    </div>
  </div>
</div>

<div class="card mb-3">
  <div class="card-header"><h6 class="mb-0">Item / Order</h6></div>
  <div class="table-responsive scrollbar">
    <table class="table table-sm fs-10 mb-0">
      <thead class="bg-body-tertiary"><tr><th>#</th><th>No. Order</th><th>Customer</th><th>Desain</th><th>Warna</th><th>Lot</th><th>No. MP</th>
        <?php foreach ($defs as $d): ?><th class="text-end"><?= esc($d['name']) ?><?= $d['uom'] ? ' (' . esc($d['uom']) . ')' : '' ?></th><?php endforeach ?></tr></thead>
      <tbody>
        <?php foreach ($row['items'] as $it): ?>
          <tr><td><?= (int) $it['line_no'] ?></td><td><?= esc($it['order_no']) ?></td><td><?= esc((string) $it['customer_name']) ?></td>
            <td><?= esc((string) ($it['design_name'] ?: $it['article'])) ?></td><td><?= esc((string) $it['color_name']) ?></td><td><?= esc((string) $it['lot_no']) ?></td>
            <td><?= esc((string) $it['mp_no']) ?></td>
            <?php foreach ($defs as $d): $k = $it['params'][$d['code']] ?? []; ?>
              <td class="text-end"><?= esc((string) ($k['plan'] ?? '–')) ?><?php if (isset($k['actual'])): ?> <span class="text-success">(aktual <?= esc((string) $k['actual']) ?>)</span><?php endif ?></td>
            <?php endforeach ?></tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><h6 class="mb-0">Riwayat</h6></div>
  <div class="card-body">
    <?php foreach ($logs as $g): ?>
      <div class="border-bottom pb-2 mb-2 fs-10">
        <strong><?= esc($actionNames[$g['action']] ?? (string) $g['action']) ?></strong>
        <?php if ($g['to_status'] && $g['from_status'] !== $g['to_status']): ?><span class="text-600">(<?= esc((string) $g['from_status']) ?> → <?= esc($g['to_status']) ?>)</span><?php endif ?>
        · <?= esc((string) ($g['username'] ?? 'Sistem')) ?> · <span class="text-600"><?= esc($fmt($g['created_at'])) ?></span>
        <?php if ($g['note']): ?><div><?= esc($g['note']) ?></div><?php endif ?>
        <?php if ($g['changes']): $c = json_decode($g['changes'], true) ?: []; ?>
          <ul class="mb-0 text-600">
            <?php foreach ($c as $field => $vals): ?>
              <li><?= esc($field) ?>:
                <?php if ($field === 'items'): ?>item/parameter berubah
                <?php else: ?><?= esc((string) json_encode($vals['old'] ?? null, JSON_UNESCAPED_UNICODE)) ?> → <?= esc((string) json_encode($vals['new'] ?? null, JSON_UNESCAPED_UNICODE)) ?><?php endif ?>
              </li>
            <?php endforeach ?>
          </ul>
        <?php endif ?>
      </div>
    <?php endforeach ?>
  </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('click', async (e) => {
  const b = e.target.closest('.btn-flow');
  if (!b) return;
  const body = { to: b.dataset.to };
  if (b.dataset.note === '1') { body.note = (prompt(b.dataset.to === 'revision' ? 'Catatan revisi untuk Scheduler:' : 'Alasan penolakan:') || '').trim(); if (!body.note) return; }
  if (b.dataset.ep === 'transition' && !confirm('Batalkan schedule ini?')) return;
  b.disabled = true;
  try { await erp.post(<?= json_encode($moduleName . '/schedule/' . $row['id'] . '/') ?> + b.dataset.ep, body); location.reload(); }
  catch (err) { alert(err.message); b.disabled = false; }
});
</script>
<?= $this->endSection() ?>
