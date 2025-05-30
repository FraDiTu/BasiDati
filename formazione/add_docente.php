<?php
$pageTitle = 'Aggiungi Docente';
require_once 'db_connect.php';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cf            = strtoupper(trim($_POST['cf'] ?? ''));
  $cognome       = trim($_POST['cognome'] ?? '');
  $cittaNascita  = trim($_POST['cittaNascita'] ?? '');
  $tipo          = trim($_POST['tipo'] ?? 'I');
  $telefono      = trim($_POST['telefono'] ?? '');

  // Validazione
  if (!$cf) {
    $errors[] = "Il Codice Fiscale è obbligatorio.";
  } elseif (strlen($cf) !== 16) {
    $errors[] = "Il Codice Fiscale deve essere di 16 caratteri.";
  } else {
    // Verifica se esiste già un docente con questo CF
    $check = $mysqli->prepare("SELECT 1 FROM Docente WHERE cf = ?");
    $check->bind_param("s", $cf);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
      $errors[] = "Esiste già un docente con questo Codice Fiscale.";
    }
    $check->close();
  }

  if (!$cognome) {
    $errors[] = "Il Cognome è obbligatorio.";
  }

  if (!$cittaNascita) {
    $errors[] = "La Città di Nascita è obbligatoria.";
  }

  if (!in_array($tipo, ['I', 'C'])) {
    $errors[] = "Il Tipo docente deve essere 'I' (Interno) o 'C' (Consulente).";
  }

  // Validazione opzionale del telefono
  if ($telefono && !preg_match('/^\+?[0-9\s\-]+$/', $telefono)) {
    $errors[] = "Il formato del numero di telefono non è valido.";
  }

  // Se non ci sono errori, procedi con l'inserimento
  if (empty($errors)) {
    try {
      // Inizia la transazione
      $mysqli->begin_transaction();
      
      // Inserisci il docente
      $stmt = $mysqli->prepare(
        "INSERT INTO Docente (cf, cognome, cittaNascita, tipo) VALUES (?, ?, ?, ?)"
      );
      $stmt->bind_param("ssss", $cf, $cognome, $cittaNascita, $tipo);
      
      if (!$stmt->execute()) {
        throw new Exception("Errore durante l'inserimento del docente: " . $mysqli->error);
      }
      $stmt->close();
      
      // Se c'è un telefono, inseriscilo nella tabella Telefono
      if ($telefono) {
        $stmt_tel = $mysqli->prepare(
          "INSERT INTO Telefono (numero, docente) VALUES (?, ?)"
        );
        $stmt_tel->bind_param("ss", $telefono, $cf);
        
        if (!$stmt_tel->execute()) {
          throw new Exception("Errore durante l'inserimento del telefono: " . $mysqli->error);
        }
        $stmt_tel->close();
      }
      
      // Conferma la transazione
      $mysqli->commit();
      $success = true;
      
      // Reindirizza dopo 2 secondi
      header("refresh:2;url=docenti.php");
      
    } catch (Exception $e) {
      // Annulla la transazione in caso di errore
      $mysqli->rollback();
      $errors[] = $e->getMessage();
    }
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
      <li class="breadcrumb-item active" aria-current="page">Aggiungi</li>
    </ol>
  </nav>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h4 class="card-title mb-0">
        <i class="fa-solid fa-user-plus me-2"></i>Nuovo Docente
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
              <h4 class="alert-heading">Docente aggiunto con successo!</h4>
              <p>Il docente è stato correttamente inserito nel database.</p>
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

        <form method="post" id="addDocenteForm" novalidate>
          <div class="mb-4">
            <label for="cf" class="form-label">Codice Fiscale</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
              <input type="text" id="cf" name="cf" class="form-control" 
                    value="<?= htmlspecialchars($_POST['cf'] ?? '') ?>"
                    placeholder="Es. RSSMRA80A01H501Z" 
                    maxlength="16"
                    pattern="[A-Za-z0-9]{16}"
                    required>
            </div>
            <div class="form-text">Il codice fiscale deve essere di 16 caratteri alfanumerici.</div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-4">
              <label for="cognome" class="form-label">Cognome</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" id="cognome" name="cognome" class="form-control" 
                      value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>"
                      placeholder="Es. Rossi" required>
              </div>
            </div>
            
            <div class="col-md-6 mb-4">
              <label for="cittaNascita" class="form-label">Città di Nascita</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-map-marker-alt"></i></span>
                <input type="text" id="cittaNascita" name="cittaNascita" class="form-control" 
                      value="<?= htmlspecialchars($_POST['cittaNascita'] ?? '') ?>"
                      placeholder="Es. Torino" required>
              </div>
            </div>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-4">
              <label for="tipo" class="form-label">Tipo Docente</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                <select id="tipo" name="tipo" class="form-select" required>
                  <option value="I" <?= ($_POST['tipo'] ?? 'I') === 'I' ? 'selected' : '' ?>>Interno</option>
                  <option value="C" <?= ($_POST['tipo'] ?? 'I') === 'C' ? 'selected' : '' ?>>Consulente</option>
                </select>
              </div>
              <div class="form-text">Interno: dipendente della scuola. Consulente: collaboratore esterno.</div>
            </div>
            
            <div class="col-md-6 mb-4">
              <label for="telefono" class="form-label">Telefono</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                <input type="tel" id="telefono" name="telefono" class="form-control" 
                      value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                      placeholder="Es. +39 123 456 7890">
              </div>
              <div class="form-text">Opzionale. Inserisci un recapito telefonico del docente.</div>
            </div>
          </div>
          
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-save me-2"></i>Salva Docente
            </button>
            <a href="docenti.php" class="btn btn-secondary">
              <i class="fa-solid fa-ban me-2"></i>Annulla
            </a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sezione informativa -->
  <div class="card bg-light mt-4">
    <div class="card-body">
      <h5><i class="fa-solid fa-info-circle me-2 text-primary"></i>Note Informative</h5>
      <p class="mb-2">
        <strong>Struttura del Database Docenti:</strong>
      </p>
      <ul class="mb-2">
        <li><strong>Docente:</strong> Contiene i dati anagrafici principali (CF, cognome, città di nascita, tipo)</li>
        <li><strong>Telefono:</strong> I numeri di telefono sono memorizzati in una tabella separata</li>
        <li><strong>Abilitazione:</strong> Le competenze di insegnamento sono gestite tramite abilitazioni ai corsi</li>
      </ul>
      <p class="mb-0">
        Dopo aver inserito un docente, sarà possibile assegnargli delle abilitazioni ai corsi 
        tramite la sezione <a href="abilitazioni.php">Abilitazioni</a>. Il docente potrà quindi essere assegnato come 
        responsabile delle edizioni dei corsi per cui è abilitato.
      </p>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Validazione client-side per il codice fiscale
  const cfInput = document.getElementById('cf');
  if (cfInput) {
    cfInput.addEventListener('input', function() {
      this.value = this.value.toUpperCase();
      
      // Rimuovi caratteri non alfanumerici
      this.value = this.value.replace(/[^A-Z0-9]/g, '');
      
      // Limita a 16 caratteri
      if (this.value.length > 16) {
        this.value = this.value.substring(0, 16);
      }
    });
  }
  
  // Validazione form
  const form = document.getElementById('addDocenteForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      const cf = document.getElementById('cf').value;
      const cognome = document.getElementById('cognome').value;
      const cittaNascita = document.getElementById('cittaNascita').value;
      
      if (cf.length !== 16) {
        e.preventDefault();
        alert('Il codice fiscale deve essere di esattamente 16 caratteri');
        return false;
      }
      
      if (!cognome.trim()) {
        e.preventDefault();
        alert('Il cognome è obbligatorio');
        return false;
      }
      
      if (!cittaNascita.trim()) {
        e.preventDefault();
        alert('La città di nascita è obbligatoria');
        return false;
      }
    });
  }
});
</script>
</body>
</html>