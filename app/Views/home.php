<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="card">
  <div class="card-body text-center py-5">
    <span class="fas fa-user-lock fs-4 text-warning mb-3"></span>
    <h5 class="mb-1">Akun belum memiliki bagian kerja</h5>
    <p class="fs-10 text-600 mb-0">Hubungi admin untuk mengatur akses modul (Dyeing / Finishing) pada akun Anda.</p>
  </div>
</div>
<?= $this->endSection() ?>
