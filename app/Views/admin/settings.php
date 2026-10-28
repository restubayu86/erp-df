<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Setting Schedule<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card" style="max-width:640px">
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/settings') ?>">
      <?= csrf_field() ?>
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" role="switch" id="auto_approve" name="auto_approve" value="1" <?= $auto ? 'checked' : '' ?>>
        <label class="form-check-label fw-bold" for="auto_approve">Auto ACC schedule</label>
      </div>
      <p class="fs-9 text-body-tertiary">
        Bila aktif, schedule yang diajukan scheduler langsung berstatus <em>Disetujui</em> dan tampil di TV
        tanpa menunggu Kepala Bagian. Tetap tercatat di riwayat sebagai "Auto ACC".
      </p>
      <button class="btn btn-primary" type="submit">Simpan</button>
    </form>
  </div>
</div>
<?= $this->endSection() ?>
