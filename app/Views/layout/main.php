<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title ?? 'System Management Product') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Global Layout CSS -->
    <link rel="stylesheet" href="<?= base_url('assets/css/layout.css') ?>">

    <!-- CSS khusus halaman -->
    <?= $this->renderSection('css') ?>
</head>
<body>

<!-- HEADER -->
<?= $this->include('layout/header') ?>

<!-- SIDEBAR -->
<?= $this->include('layout/sidebar') ?>

<!-- MAIN CONTENT -->
<main class="content">
    <!-- ALERT MODAL -->
<div class="modal fade" id="alertModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" id="alertModalContent">

      <div class="modal-header">
        <h5 class="modal-title" id="alertModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="alertModalBody">
        
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Tutup
        </button>
      </div>

    </div>
  </div>
</div>

    <?= $this->renderSection('content') ?>
</main>

<!-- GLOBAL JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function logout() {
    if (confirm('Yakin ingin logout?')) {
        alert('Logout berhasil');
        window.location.href = "<?= site_url('logout') ?>";
    }
}   
</script>

<!-- JS khusus halaman -->
<?= $this->renderSection('js') ?>

</body>
</html>
