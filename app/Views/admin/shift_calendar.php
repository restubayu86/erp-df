<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Pola & Kalender Shift<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header"><h6 class="mb-0">Pola shift</h6></div>
      <div class="card-body fs-10">
        <?php foreach ($patterns as $p): ?>
          <div class="mb-3">
            <strong><?= esc($p['name']) ?></strong>
            <?php if ((int) $p['id'] === (int) $defaultId): ?><span class="badge badge-subtle-primary">default</span><?php endif ?>
            <div class="text-600"><?= esc((string) $p['description']) ?></div>
            <?php if ($p['items']): ?>
              <?php foreach ($p['items'] as $i): ?>
                <span class="badge badge-subtle-info me-1"><?= esc($i['name']) ?> <?= esc(substr($i['start_time'], 0, 5)) ?>–<?= esc(substr($i['end_time'], 0, 5)) ?><?= $i['crosses_midnight'] ? ' (+1)' : '' ?></span>
              <?php endforeach ?>
            <?php else: ?><span class="badge badge-subtle-secondary">tidak ada shift</span><?php endif ?>
          </div>
        <?php endforeach ?>
        <p class="text-600 mb-0">Mengubah isi pola (jam per shift) belum ada di layar ini; saat ini lewat database/seeder.</p>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h6 class="mb-0">Tetapkan pola untuk tanggal</h6></div>
      <div class="card-body">
        <form method="post" action="<?= site_url('master/shift-calendar') ?>" class="row g-2">
          <?= csrf_field() ?>
          <div class="col-6"><label class="form-label fs-10" for="date_from">Dari</label><input type="date" class="form-control form-control-sm" id="date_from" name="date_from" required value="<?= esc(old('date_from', $today)) ?>"></div>
          <div class="col-6"><label class="form-label fs-10" for="date_to">Sampai (opsional)</label><input type="date" class="form-control form-control-sm" id="date_to" name="date_to" value="<?= esc(old('date_to')) ?>"></div>
          <div class="col-12">
            <label class="form-label fs-10" for="pattern_id">Pola</label>
            <select class="form-select form-select-sm" id="pattern_id" name="pattern_id" required>
              <?php foreach ($patterns as $p): ?><option value="<?= (int) $p['id'] ?>"><?= esc($p['name']) ?></option><?php endforeach ?>
            </select>
          </div>
          <div class="col-12"><label class="form-label fs-10" for="reason">Alasan / keputusan *</label><input class="form-control form-control-sm" id="reason" name="reason" maxlength="255" required placeholder="mis. Arahan manajemen, bahan baku terbatas" value="<?= esc(old('reason')) ?>"></div>
          <div class="col-12"><button class="btn btn-primary btn-sm" type="submit">Tetapkan</button></div>
          <div class="col-12 fs-10 text-600">Berlaku langsung untuk form schedule, tablet, dan TV. Jadwal yang sudah dibuat tidak diubah otomatis.</div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Hari ini (<?= esc($today) ?>)</h6>
        <div class="fs-10 text-600"><?= $todayInfo['pattern'] ? esc($todayInfo['pattern']['name']) . ($todayInfo['pattern']['reason'] ? ' – ' . esc($todayInfo['pattern']['reason']) : '') : 'Belum ada pola' ?></div>
      </div>
      <div class="card-body border-bottom fs-10">
        <?php foreach ($todayInfo['shifts'] as $s): ?>
          <span class="badge badge-subtle-success me-1"><?= esc($s['name']) ?> <?= esc($s['start']) ?>–<?= esc($s['end']) ?></span>
        <?php endforeach ?>
        <?php if (! $todayInfo['shifts']): ?><span class="badge badge-subtle-secondary">tidak ada shift aktif</span><?php endif ?>
      </div>
      <div class="table-responsive scrollbar">
        <table class="table table-sm fs-10 mb-0">
          <thead class="bg-body-tertiary"><tr><th>Tanggal</th><th>Pola</th><th>Alasan</th><th>Ditetapkan oleh</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($calendar as $c): ?>
              <tr>
                <td><?= esc($c['work_date']) ?></td><td><?= esc($c['name']) ?></td><td><?= esc($c['reason']) ?></td>
                <td><?= esc((string) ($c['username'] ?? '–')) ?><div class="text-600"><?= esc(substr((string) $c['updated_at'], 0, 16)) ?></div></td>
                <td>
                  <form method="post" action="<?= site_url('master/shift-calendar/delete') ?>" onsubmit="return confirm('Hapus penetapan? Tanggal ini kembali ke pola default.')">
                    <?= csrf_field() ?><input type="hidden" name="work_date" value="<?= esc($c['work_date']) ?>">
                    <button class="btn btn-link text-danger p-0" title="Hapus"><span class="fas fa-trash"></span></button>
                  </form>
                </td>
              </tr>
            <?php endforeach ?>
            <?php if (! $calendar): ?><tr><td colspan="5" class="text-center text-600 py-3">Belum ada penetapan khusus. Semua tanggal memakai pola default.</td></tr><?php endif ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?= $this->endSection() ?>
