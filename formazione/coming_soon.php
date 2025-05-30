<?php
$pageTitle = 'Sezione in sviluppo';
require_once 'db_connect.php';

// Ottiene il nome della sezione dalla query string
$section_name = $_GET['section'] ?? 'Questa sezione';
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8 text-center">
      <div class="card shadow border-0">
        <div class="card-body p-5">
          <div class="mb-4">
            <i class="fa-solid fa-gear fa-spin fa-4x text-primary"></i>
          </div>
          <h1 class="display-5 fw-bold mb-4"><?= htmlspecialchars($section_name) ?> è in fase di sviluppo</h1>
          <p class="lead mb-4">
            Stiamo lavorando per implementare questa funzionalità nel sistema.
            Torneremo online il prima possibile con nuove caratteristiche e miglioramenti.
          </p>
          <div class="alert alert-info mb-4">
            <i class="fa-solid fa-info-circle me-2"></i>
            Le funzionalità esistenti del sistema rimangono pienamente operative.
          </div>
          <a href="index.php" class="btn btn-primary btn-lg px-4">
            <i class="fa-solid fa-home me-2"></i>Torna alla Home
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>