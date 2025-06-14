<?php
/**
 * File: edit_docente.php
 * Descrizione: Pagina per la modifica dei dati di un docente esistente
 * 
 * Questo file fornisce una vista completa e editabile di un docente, includendo:
 * - Form per modificare i dati anagrafici base (cognome, città, tipo)
 * - Visualizzazione read-only delle informazioni correlate (telefoni, abilitazioni, edizioni)
 * - Dashboard riassuntiva con statistiche del docente
 * - Link rapidi alle funzioni correlate (gestione abilitazioni, eliminazione)
 * 
 * La pagina è strutturata in due colonne:
 * - Colonna principale: Form di modifica
 * - Sidebar: Informazioni dettagliate e azioni correlate
 * 
 * Nota: Il Codice Fiscale NON è modificabile per preservare l'integrità referenziale
 */

// Imposta il titolo della pagina
$pageTitle = 'Modifica Docente';

// Include i file necessari
require_once 'db_connect.php';
require_once 'utils.php';

// === RECUPERO E VALIDAZIONE PARAMETRI ===
// Ottiene il Codice Fiscale dalla query string
$cf = isset($_GET['cf']) ? $_GET['cf'] : '';
$errors = [];
$success = false;

// Se non è stato fornito un CF, reindirizza alla lista docenti
if (!$cf) {
  header('Location: docenti.php');
  exit;
}

// === RECUPERO DATI DOCENTE ===
// Query per ottenere i dati anagrafici base del docente
$stmt = $mysqli->prepare(
  "SELECT cognome, cittaNascita, tipo FROM Docente WHERE cf = ?"
);
$stmt->bind_param("s", $cf);
$stmt->execute();
$stmt->bind_result($cognome, $cittaNascita, $tipo);

// Se il docente non esiste, reindirizza alla lista
if (!$stmt->fetch()) {
  $stmt->close();
  header('Location: docenti.php');
  exit;
}
$stmt->close();

// === RECUPERO TELEFONI ASSOCIATI ===
// Query per ottenere tutti i numeri di telefono del docente
$telefoni = [];
$stmt_telefoni = $mysqli->prepare(
  "SELECT numero FROM Telefono WHERE docente = ?"
);
$stmt_telefoni->bind_param("s", $cf);
$stmt_telefoni->execute();
$result_telefoni = $stmt_telefoni->get_result();

// Costruisce un array con tutti i telefoni
while ($row = $result_telefoni->fetch_assoc()) {
  $telefoni[] = $row['numero'];
}
$stmt_telefoni->close();

// === RECUPERO ABILITAZIONI ===
// Query con JOIN per ottenere le abilitazioni del docente con i titoli dei corsi
$abilitazioni = [];
$stmt_abilitazioni = $mysqli->prepare(
  "SELECT a.corso, c.titolo 
   FROM Abilitazione a 
   JOIN Corso c ON a.corso = c.codice    -- JOIN per ottenere il titolo del corso
   WHERE a.docente = ?"
);
$stmt_abilitazioni->bind_param("s", $cf);
$stmt_abilitazioni->execute();
$result_abilitazioni = $stmt_abilitazioni->get_result();

// Costruisce un array con tutte le abilitazioni
while ($row = $result_abilitazioni->fetch_assoc()) {
  $abilitazioni[] = $row;
}
$stmt_abilitazioni->close();

// === RECUPERO EDIZIONI INSEGNATE ===
// Query complessa per ottenere le edizioni che il docente insegna/ha insegnato
$edizioni = [];
$stmt_edizioni = $mysqli->prepare(
  "SELECT e.codice, c.titolo, e.dataIn, e.dataFine
   FROM Edizione e 
   JOIN Corso c ON e.corso = c.codice     -- JOIN per ottenere il titolo del corso
   WHERE e.docente = ?"
);
$stmt_edizioni->bind_param("s", $cf);
$stmt_edizioni->execute();
$result_edizioni = $stmt_edizioni->get_result();

// Costruisce un array con tutte le edizioni
while ($row = $result_edizioni->fetch_assoc()) {
  $edizioni[] = $row;
}
$stmt_edizioni->close();

// === GESTIONE FORM DI MODIFICA ===
// Processa i dati solo se è stata inviata una richiesta POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  // === RECUPERO E SANITIZZAZIONE DATI ===
  $nuovo_cognome = trim($_POST['cognome'] ?? '');
  $nuova_cittaNascita = trim($_POST['cittaNascita'] ?? '');
  $nuovo_tipo = trim($_POST['tipo'] ?? 'I');

  // === VALIDAZIONE DATI ===
  if (!$nuovo_cognome) {
    $errors[] = "Il Cognome è obbligatorio.";
  }

  if (!$nuova_cittaNascita) {
    $errors[] = "La Città di Nascita è obbligatoria.";
  }

  // Validazione che il tipo sia uno dei valori consentiti
  if (!in_array($nuovo_tipo, ['I', 'C'])) {
    $errors[] = "Il Tipo docente deve essere 'I' (Interno) o 'C' (Consulente).";
  }

  // === AGGIORNAMENTO DATABASE ===
  // Procede solo se non ci sono errori di validazione
  if (empty($errors)) {
    // Prepara la query di UPDATE
    $upd = $mysqli->prepare(
      "UPDATE Docente SET cognome=?, cittaNascita=?, tipo=? WHERE cf=?"
    );
    $upd->bind_param("ssss", $nuovo_cognome, $nuova_cittaNascita, $nuovo_tipo, $cf);
    
    $result_update = $upd->execute();
    
    if ($result_update) {
      $success = true;
      
      // === AGGIORNAMENTO VARIABILI LOCALI ===
      // Aggiorna le variabili per riflettere i nuovi valori nella vista
      $cognome = $nuovo_cognome;
      $cittaNascita = $nuova_cittaNascita;
      $tipo = $nuovo_tipo;
      
      // === REINDIRIZZAMENTO AUTOMATICO ===
      // Dopo 2 secondi reindirizza alla lista docenti
      header("refresh:2;url=docenti.php");
    } else {
      // Errore durante l'aggiornamento
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
  <!-- === BREADCRUMB NAVIGATION === -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
      <li class="breadcrumb-item active" aria-current="page">Modifica</li>
    </ol>
  </nav>

  <!-- === LAYOUT A DUE COLONNE === -->
  <div class="row">
    
    <!-- === COLONNA PRINCIPALE - FORM DI MODIFICA === -->
    <div class="col-lg-8">
      
      <!-- === CARD FORM MODIFICA === -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-user-edit me-2"></i>Modifica Docente
          </h4>
        </div>
        <div class="card-body p-4">
          
          <?php 
          // === VISUALIZZAZIONE MESSAGGIO DI SUCCESSO ===
          if ($success): 
          ?>
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
            
            <?php 
            // === VISUALIZZAZIONE ERRORI ===
            if ($errors): 
            ?>
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

            <!-- === FORM DI MODIFICA === -->
            <form method="post" id="editDocenteForm" novalidate>
              
              <!-- === CAMPO CODICE FISCALE (READ-ONLY) === -->
              <div class="mb-4">
                <label class="form-label">Codice Fiscale</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-id-card"></i></span>
                  <!-- Campo readonly per mostrare il CF ma impedire la modifica -->
                  <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($cf) ?>" readonly>
                </div>
                <div class="form-text">Il Codice Fiscale non può essere modificato.</div>
              </div>
              
              <!-- === ROW CON COGNOME E CITTÀ === -->
              <div class="row">
                
                <!-- === CAMPO COGNOME === -->
                <div class="col-md-6 mb-4">
                  <label for="cognome" class="form-label">Cognome</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                    <!-- Usa il valore attuale del database come default -->
                    <input type="text" id="cognome" name="cognome" class="form-control" 
                          value="<?= htmlspecialchars($cognome) ?>"
                          placeholder="Es. Rossi" required>
                  </div>
                </div>
                
                <!-- === CAMPO CITTÀ DI NASCITA === -->
                <div class="col-md-6 mb-4">
                  <label for="cittaNascita" class="form-label">Città di Nascita</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-map-marker-alt"></i></span>
                    <!-- Usa il valore attuale del database come default -->
                    <input type="text" id="cittaNascita" name="cittaNascita" class="form-control" 
                          value="<?= htmlspecialchars($cittaNascita) ?>"
                          placeholder="Es. Torino" required>
                  </div>
                </div>
              </div>
              
              <!-- === CAMPO TIPO DOCENTE === -->
              <div class="mb-4">
                <label for="tipo" class="form-label">Tipo Docente</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                  <select id="tipo" name="tipo" class="form-select" required>
                    <!-- Mantiene la selezione attuale del database -->
                    <option value="I" <?= $tipo === 'I' ? 'selected' : '' ?>>Interno</option>
                    <option value="C" <?= $tipo === 'C' ? 'selected' : '' ?>>Consulente</option>
                  </select>
                </div>
                <div class="form-text">Interno: dipendente della scuola. Consulente: collaboratore esterno.</div>
              </div>
              
              <!-- === PULSANTI DI AZIONE === -->
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
    
    <!-- === SIDEBAR - INFORMAZIONI DETTAGLIATE === -->
    <div class="col-lg-4">
      
      <!-- === CARD INFORMAZIONI DOCENTE === -->
      <div class="card mb-4">
        <div class="card-header bg-light">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-info-circle me-2"></i>Dettagli Docente
          </h5>
        </div>
        <div class="card-body">
          <!-- === DEFINITION LIST CON DATI DOCENTE === -->
          <!-- dl/dt/dd fornisce una struttura semantica per coppie etichetta-valore -->
          <dl class="row mb-0">
            <dt class="col-sm-4">Cognome:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($cognome) ?></dd>
            
            <dt class="col-sm-4">CF:</dt>
            <dd class="col-sm-8"><code><?= htmlspecialchars($cf) ?></code></dd>
            
            <dt class="col-sm-4">Città:</dt>
            <dd class="col-sm-8"><?= htmlspecialchars($cittaNascita) ?></dd>
            
            <dt class="col-sm-4">Tipo:</dt>
            <dd class="col-sm-8">
              <!-- Badge colorato diversamente per tipo docente -->
              <span class="badge <?= $tipo === 'I' ? 'bg-info' : 'bg-warning' ?>">
                <?= $tipo === 'I' ? 'Interno' : 'Consulente' ?>
              </span>
            </dd>
            
            <dt class="col-sm-4">Telefoni:</dt>
            <dd class="col-sm-8">
              <?php if (!empty($telefoni)): ?>
                <?php foreach ($telefoni as $tel): ?>
                  <!-- Link tel: per chiamate dirette su dispositivi mobili -->
                  <a href="tel:<?= htmlspecialchars($tel) ?>" class="text-decoration-none">
                    <i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($tel) ?>
                  </a><br>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="text-muted fst-italic">Non disponibile</span>
              <?php endif; ?>
            </dd>
            
            <dt class="col-sm-4">Abilitazioni:</dt>
            <dd class="col-sm-8">
              <!-- Badge con conteggio abilitazioni -->
              <span class="badge bg-success"><?= count($abilitazioni) ?></span>
            </dd>
            
            <dt class="col-sm-4">Edizioni:</dt>
            <dd class="col-sm-8">
              <!-- Badge con conteggio edizioni -->
              <span class="badge bg-primary"><?= count($edizioni) ?></span>
            </dd>
          </dl>
        </div>
        
        <!-- === FOOTER CON AZIONI RAPIDE === -->
        <div class="card-footer bg-transparent">
          <div class="d-flex justify-content-between">
            <!-- Link per gestire le abilitazioni di questo docente -->
            <a href="abilitazioni.php?cf=<?= urlencode($cf) ?>" class="btn btn-sm btn-outline-success">
              <i class="fa-solid fa-certificate me-1"></i>Gestisci Abilitazioni
            </a>
            <!-- Link per eliminare il docente -->
            <a href="delete_docente.php?cf=<?= urlencode($cf) ?>" class="btn btn-sm btn-outline-danger">
              <i class="fa-solid fa-trash me-1"></i>Elimina
            </a>
          </div>
        </div>
      </div>
      
      <!-- === CARD TELEFONI (SE PRESENTI) === -->
      <?php if (!empty($telefoni)): ?>
      <div class="card mb-4">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-phone me-2"></i>Telefoni (<?= count($telefoni) ?>)
          </h5>
        </div>
        <!-- === LISTA TELEFONI === -->
        <ul class="list-group list-group-flush">
          <?php foreach ($telefoni as $telefono): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <!-- Link cliccabile per chiamare -->
              <a href="tel:<?= htmlspecialchars($telefono) ?>" class="text-decoration-none">
                <i class="fa-solid fa-phone me-2 text-success"></i><?= htmlspecialchars($telefono) ?>
              </a>
              <!-- Pulsante per eliminare (link a sezione non ancora implementata) -->
              <a href="coming_soon.php?section=Telefoni" class="btn btn-sm btn-outline-danger">
                <i class="fa-solid fa-trash"></i>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <!-- === FOOTER PER AGGIUNGERE TELEFONO === -->
        <div class="card-footer bg-transparent">
          <a href="coming_soon.php?section=Telefoni" class="btn btn-outline-info w-100">
            <i class="fa-solid fa-plus me-2"></i>Aggiungi Telefono
          </a>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- === CARD ABILITAZIONI (SE PRESENTI) === -->
      <?php if (!empty($abilitazioni)): ?>
      <div class="card mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-certificate me-2"></i>Corsi Abilitati (<?= count($abilitazioni) ?>)
          </h5>
        </div>
        <!-- === LISTA ABILITAZIONI === -->
        <ul class="list-group list-group-flush">
          <?php foreach ($abilitazioni as $abilitazione): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <!-- Titolo del corso -->
              <?= htmlspecialchars($abilitazione['titolo']) ?>
              <!-- Badge con codice corso -->
              <span class="badge bg-light text-dark"><?= htmlspecialchars($abilitazione['corso']) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <!-- === FOOTER PER GESTIRE ABILITAZIONI === -->
        <div class="card-footer bg-transparent">
          <a href="abilitazioni.php?cf=<?= urlencode($cf) ?>" class="btn btn-outline-success w-100">
            <i class="fa-solid fa-edit me-2"></i>Gestisci Abilitazioni
          </a>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- === CARD EDIZIONI (SE PRESENTI) === -->
      <?php if (!empty($edizioni)): ?>
      <div class="card">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-calendar-days me-2"></i>Edizioni Insegnate (<?= count($edizioni) ?>)
          </h5>
        </div>
        <!-- === LISTA EDIZIONI === -->
        <ul class="list-group list-group-flush">
          <?php foreach ($edizioni as $edizione): ?>
            <li class="list-group-item">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <!-- Titolo del corso dell'edizione -->
                  <h6 class="mb-1"><?= htmlspecialchars($edizione['titolo']) ?></h6>
                  <!-- Date di inizio e fine formattate -->
                  <small class="text-muted">
                    <?= format_date($edizione['dataIn']) ?> - <?= format_date($edizione['dataFine']) ?>
                  </small>
                </div>
                <!-- Badge con codice edizione che funge da link -->
                <a href="partecipazioni.php?edizione=<?= urlencode($edizione['codice']) ?>" class="badge bg-primary">
                  <?= htmlspecialchars($edizione['codice']) ?>
                </a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <!-- === FOOTER PER VEDERE TUTTE LE EDIZIONI === -->
        <div class="card-footer bg-transparent">
          <a href="coming_soon.php?section=Edizioni" class="btn btn-outline-primary w-100">
            <i class="fa-solid fa-list me-2"></i>Tutte le Edizioni
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<!-- === JAVASCRIPT PER VALIDAZIONE E UX === -->
<script>
// Attende che il DOM sia completamente caricato
document.addEventListener('DOMContentLoaded', function() {
  
  // === VALIDAZIONE FORM LATO CLIENT ===
  const form = document.getElementById('editDocenteForm');
  if (form) {
    // Aggiunge un listener per l'evento submit del form
    form.addEventListener('submit', function(e) {
      // Ottiene i valori dei campi obbligatori
      const cognome = document.getElementById('cognome').value.trim();
      const cittaNascita = document.getElementById('cittaNascita').value.trim();
      
      // === VALIDAZIONE COGNOME ===
      if (!cognome) {
        e.preventDefault(); // Impedisce l'invio del form
        alert('Il cognome è obbligatorio');
        return false;
      }
      
      // === VALIDAZIONE CITTÀ DI NASCITA ===
      if (!cittaNascita) {
        e.preventDefault(); // Impedisce l'invio del form
        alert('La città di nascita è obbligatoria');
        return false;
      }
    });
  }
});
</script>
</body>
</html>