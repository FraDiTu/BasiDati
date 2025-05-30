<?php
$pageTitle = 'Modifica Docente';
require_once 'db_connect.php';
require_once 'utils.php';

$cf = isset($_GET['cf']) ? $_GET['cf'] : '';
$errors = [];
$success = false;

if (!$cf) {
  header('Location: docenti.php');
  exit;
}

// Recupera i dati del docente
$stmt = $mysqli->prepare(
  "SELECT nome, cognome, telefono FROM Docenti WHERE cf = ?"
);
$stmt->bind_param("s", $cf);
$stmt->execute();
$stmt->bind_result($nome, $cognome, $telefono);
if (!$stmt->fetch()) {
  $stmt->close();
  header('Location: docenti.php');
  exit;
}
$stmt->close();

// Recupera le abilitazioni del docente
$abilitazioni = [];
$stmt_abilitazioni = $mysqli->prepare(
  "SELECT a.codice_corso, c.titolo 
   FROM Abilitazioni a 
   JOIN Corsi c ON a.codice_corso = c.codice
   WHERE a.cf_docente = ?"
);
$stmt_abilitazioni->bind_param("s", $cf);
$stmt_abilitazioni->execute();
$result_abilitazioni = $stmt_abilitazioni->get_result();
while ($row = $result_abilitazioni->fetch_assoc()) {
  $abilitazioni[] = $row;
}
$stmt_abilitazioni->close();

// Recupera le edizioni insegnate dal docente
$edizioni = [];
$stmt_edizioni = $mysqli->prepare(
  "SELECT e.codice, c.titolo 
   FROM Edizioni e 
   JOIN Corsi c ON e.codice_corso = c.codice
   WHERE e.cf_docente = ?"
);
$stmt_edizioni->bind_param("s", $cf);
$stmt_edizioni->execute();
$result_edizioni = $stmt_edizioni->get_result();
while ($row = $result_edizioni->fetch_assoc()) {
  $edizioni[] = $row;
}
$stmt_edizioni->close();

// Gestione form di modifica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $cognome = trim($_POST['cognome'] ?? '');
  $telefono = trim($_POST['telefono'] ?? '');

  // Validazione
  if (!$nome) {
    $errors[] = "Il Nome è obbligatorio.";
  }

  if (!$cognome) {
    $errors[] = "Il Cognome è obbligatorio.";
  }

  // Validazione opzionale del telefono
  if ($telefono && !preg_match('/^\+?[0-9\s]+$/', $telefono)) {
    $errors[] = "Il formato del numero di telefono non è valido.";
  }

  // Se non ci sono errori, procedi con l'aggiornamento
  if (empty($errors)) {
    $upd = $mysqli->prepare(
      "UPDATE Docenti SET nome=?, cognome=?, telefono=? WHERE cf=?"
    );
    $upd->bind_param("ssss", $nome, $cognome, $telefono, $cf);
    
    $result_update = $upd->execute();
    
    if ($result_update) {
      $affected = $upd->affected_rows;
      
      if ($affected > 0) {
        $success = true;
        // Reindirizza dopo 2 secondi
        header("refresh:2;url=docenti.php");
      } else {
        // Nessuna riga modificata potrebbe significare che i dati erano già uguali
        $success = true;
        header("refresh:2;url=docenti.php");
      }
    } else {
      $errors[] = "Errore durante l'aggiornamento nel database: " . $mysqli->error;
    }
    $upd->close();
  }
}
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
      <li class="breadcrumb-item active" aria-current="page">Modifica</li>
    </ol>
  </nav>

  <!-- Scheda Docente -->
  <div class="row">
    <div class="col-lg-8">
      <!-- Form di modifica -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-user-edit me-2"></i>Modifica Docente
          </h4>
        </div>
        <div class="card-body p-4">
          <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
              <div class="d-flex">
                <div class="me-3">
                  <i class="fa-solid fa-check-circle fa-2x"></i>
                </div>
                <div>
                  <h4 class="alert-heading">Docente aggiornato con successo!</h4>
                  <p>I dati del docente sono stati correttamente aggiornati.</p>
                  <hr>
                  <p class="mb-0">Verrai reindirizzato alla lista docenti tra pochi secondi. 
                    Se non vuoi attendere, <a href="docenti.php" class="alert-link">clicca qui</a>.
                  </p>
                </div>
              </div>
            </div>
          <?php else: ?>
            <?php if ($errors): ?>
              <div class="alert alert-danger mb-4">
                <div class="d-flex">
                  <div class="me-3">
                    <i class="fa-solid fa-circle-exclamation fa-2x"></i>
                  </div>
                  <div>
                    <h4 class="alert-heading">Attenzione!</h4>
                    <p>Si sono verificati i seguenti errori:</p>
                    <ul class="mb-0">
                      <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>
            <?php endif; ?>

            <form method="post" id="editDocenteForm" novalidate>
              <div class="mb-4">
                <label class="form-label">Codice Fiscale</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                  <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($cf) ?>" readonly>
                </div>
                <div class="form-text">Il Codice Fiscale non può essere modificato.</div>
              </div>
              
              <div class="row">
                <div class="col-md-6 mb-4">
                  <label for="nome" class="form-label">Nome</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" id="nome" name="nome" class="form-control" 
                          value="<?= htmlspecialchars($nome) ?>"
                          placeholder="Es. Mario" required>
                  </div>
                </div>
                
                <div class="col-md-6 mb-4">
                  <label for="cognome" class="form-label">Cognome</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <input type="text" id="cognome" name="cognome" class="form-control" 
                          value="<?= htmlspecialchars($cognome) ?>"
                          placeholder="Es. Rossi" required>
                  </div>
                </div>
              </div>
              
              <div class="mb-4">
                <label for="telefono" class="form-label">Telefono</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                  <input type="tel" id="telefono" name="telefono" class="form-control" 
                        value="<?= htmlspecialchars($telefono) ?>"
                        placeholder="Es. +39 123 456 7890">
                </div>
                <div class="form-text">Opzionale. Inserisci un recapito telefonico del docente.</div>
              </div>
              
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-save me-2"></i>Aggiorna Dati
                </button>
                <a href="docenti.php" class="btn btn-secondary">
                  <i class="fa-solid fa-ban me-2"></i>Annulla
                </a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <div class="col-lg-4">
      <!-- Scheda informativa -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-info-circle me-2"></i>Dettagli Docente
          </h5>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Nome:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($nome) ?></dd>
            
            <dt class="col-sm-4">Cognome:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($cognome) ?></dd>
            
            <dt class="col-sm-4">CF:</dt>
            <dd class="col-sm-8"><code><?= htmlspecialchars($cf) ?></code></dd>
            
            <dt class="col-sm-4">Telefono:</dt>
            <dd class="col-sm-8">
              <?php if ($telefono): ?>
                <a href="tel:<?= htmlspecialchars($telefono) ?>" class="text-decoration-none">
                  <i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($telefono) ?>
                </a>
              <?php else: ?>
                <span class="text-muted fst-italic">Non disponibile</span>
              <?php endif; ?>
            </dd>
            
            <dt class="col-sm-4">Abilitazioni:</dt>
            <dd class="col-sm-8">
              <span class="badge bg-success"><?= count($abilitazioni) ?></span>
            </dd>
            
            <dt class="col-sm-4">Edizioni:</dt>
            <dd class="col-sm-8">
              <span class="badge bg-primary"><?= count($edizioni) ?></span>
            </dd>
          </dl>
        </div>
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-between">
            <a href="abilitazioni.php?cf=<?= urlencode($cf) ?>" class="btn btn-sm btn-outline-success">
              <i class="fa-solid fa-certificate me-1"></i>Gestisci Abilitazioni
            </a>
            <a href="delete_docente.php?cf=<?= urlencode($cf) ?>" class="btn btn-sm btn-outline-danger">
              <i class="fa-solid fa-trash me-1"></i>Elimina
            </a>
          </div>
        </div>
      </div>
      
      <!-- Abilitazioni -->
      <?php if (!empty($abilitazioni)): ?>
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-certificate me-2"></i>Corsi Abilitati (<?= count($abilitazioni) ?>)
          </h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($abilitazioni as $abilitazione): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?= htmlspecialchars($abilitazione['titolo']) ?>
              <span class="badge bg-light text-dark"><?= htmlspecialchars($abilitazione['codice_corso']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      
      <!-- Edizioni -->
      <?php if (!empty($edizioni)): ?>
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-calendar-days me-2"></i>Edizioni Insegnate (<?= count($edizioni) ?>)
          </h5>
        </div>
        <ul class="list-group list-group-flush">
          <?php foreach ($edizioni as $edizione): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?= htmlspecialchars($edizione['titolo']) ?>
              <a href="coming_soon.php?section=Edizioni" class="badge bg-primary">
                <?= htmlspecialchars($edizione['codice']) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>