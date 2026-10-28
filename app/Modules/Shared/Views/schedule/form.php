<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Schedule<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$v   = fn(string $k, $d = '') => old($k, $row[$k] ?? $d);
$dt  = fn(string $k) => ($x = old($k, $row[$k] ?? '')) ? date('Y-m-d\TH:i', strtotime($x)) : '';
$url = $row ? site_url($moduleName . '/schedule/' . $row['id']) : site_url($moduleName . '/schedule');
$err = session('errors') ?? [];
?>
<div class="card" style="max-width:820px">
  <div class="card-body">
    <?php if ($err): ?>
      <div class="alert alert-danger fs-9"><?= esc((string)implode(' ', $err)) ?></div>
    <?php endif ?>

    <form method="post" action="<?= $url ?>" class="row g-3">
      <?= csrf_field() ?>

      <div class="col-md-4">
        <label class="form-label" for="work_date">Tanggal produksi</label>
        <input type="date" class="form-control" id="work_date" name="work_date" required
          value="<?= esc((string)$v('work_date', config('Shifts')->productionDate())) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="shift">Shift</label>
        <select class="form-select" id="shift" name="shift" required>
          <?php foreach ($shifts as $code => $s): ?>
            <option value="<?= esc((string)$code) ?>" <?= $v('shift', 'pagi') === $code ? 'selected' : '' ?>>
              <?= esc((string)$s['label']) ?> (<?= esc((string)$s['start']) ?>–<?= esc((string)$s['end']) ?>)
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="machine_id">Mesin</label>
        <select class="form-select" id="machine_id" name="machine_id" required>
          <option value="">Pilih mesin</option>
          <?php foreach ($machines as $m): ?>
            <option value="<?= (int) $m['id'] ?>" <?= (int) $v('machine_id') === (int) $m['id'] ? 'selected' : '' ?>><?= esc((string)$m['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label" for="order_no">No. Order</label>
        <input class="form-control" id="order_no" name="order_no" maxlength="50" required value="<?= esc((string)$v('order_no')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="customer">Customer</label>
        <input class="form-control" id="customer" name="customer" maxlength="100" value="<?= esc((string)$v('customer')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="process">Proses</label>
        <input class="form-control" id="process" name="process" maxlength="50" required placeholder="Dyeing / Stenter / ..." value="<?= esc((string)$v('process')) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="category">Kategori</label>
        <select class="form-select" id="category" name="category" required>
          <?php foreach (['Produksi', 'Perbaikan', 'Percobaan'] as $cat): ?>
            <option value="<?= $cat ?>" <?= $v('category', 'Produksi') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="design">Desain</label>
        <input class="form-control" id="design" name="design" maxlength="100" value="<?= esc((string)$v('design')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="color">Warna</label>
        <input class="form-control" id="color" name="color" maxlength="50" value="<?= esc((string)$v('color')) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="mp_no">No. MP</label>
        <input class="form-control" id="mp_no" name="mp_no" maxlength="50" value="<?= esc((string)$v('mp_no')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="wash_machine">Cuci Mesin</label>
        <input class="form-control" id="wash_machine" name="wash_machine" maxlength="50" value="<?= esc((string)$v('wash_machine')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="progress">Progress (%)</label>
        <input type="number" class="form-control" id="progress" name="progress" min="0" max="100" value="<?= esc((string)$v('progress', 0)) ?>">
      </div>

      <div class="col-md-4">
        <label class="form-label" for="dye_lot_no">Dye Lot Number (Kode Produksi)</label>
        <input class="form-control" id="dye_lot_no" name="dye_lot_no" maxlength="50" value="<?= esc((string)$v('dye_lot_no')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="dop_no">DOP</label>
        <input class="form-control" id="dop_no" name="dop_no" maxlength="50" value="<?= esc((string)$v('dop_no')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="note">Catatan</label>
        <input class="form-control" id="note" name="note" value="<?= esc((string)$v('note')) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label" for="start_at">Rencana mulai</label>
        <input type="datetime-local" class="form-control" id="start_at" name="start_at" required value="<?= esc((string)$dt('start_at')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="end_at">Rencana selesai</label>
        <input type="datetime-local" class="form-control" id="end_at" name="end_at" required value="<?= esc((string)$dt('end_at')) ?>">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Simpan</button>
        <a class="btn btn-falcon-default" href="<?= site_url($moduleName . '/schedule') ?>">Batal</a>
      </div>
    </form>
  </div>
</div>
<?= $this->endSection() ?>