<?php
/**
 * File: add_docente.php
 * Descrizione: Form per l'aggiunta di un nuovo docente al sistema
 * 
 * Questo file permette di:
 * - Visualizzare un form per inserire i dati di un nuovo docente
 * - Validare i dati inseriti sia lato server che client
 * - Inserire il docente nel database usando transazioni per garantire consistenza
 * - Gestire eventuali errori e fornire feedback all'utente
 */

// Imposta il titolo della pagina
$pageTitle = 'Aggiungi Docente';

// Include il file di connessione al database
require_once 'db_connect.php';

// Inizializza array per raccogliere eventuali errori di validazione
$errors = [];

// Flag per indicare se l'inserimento è avvenuto con successo
$success = false;

// === GESTIONE FORM POST ===
// Verifica se è stata inviata una richiesta POST (invio del form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  // === RECUPERO E SANITIZZAZIONE DATI ===
  // Recupera i dati dal form e li pulisce da spazi extra
  // strtoupper() converte il CF in maiuscolo per standardizzazione
  $cf            = strtoupper(trim($_POST['cf'] ?? ''));
  $cognome       = trim($_POST['cognome'] ?? '');
  $cittaNascita  = trim($_POST['cittaNascita'] ?? '');
  $tipo          = trim($_POST['tipo'] ?? 'I'); // Default 'I' per Interno
  $telefono      = trim($_POST['telefono'] ?? '');

  // === VALIDAZIONE CODICE FISCALE ===
  if (!$cf) {
    $errors[] = "Il Codice Fiscale è obbligatorio.";
  } elseif (strlen($cf) !== 16) {
    // Il codice fiscale italiano deve essere sempre di 16 caratteri
    $errors[] = "Il Codice Fiscale deve essere di 16 caratteri.";
  } else {
    // === CONTROLLO UNICITÀ CODICE FISCALE ===
    // Verifica che non esista già un docente con questo CF
    $check = $mysqli->prepare("SELECT 1 FROM Docente WHERE cf = ?");
    $check->bind_param("s", $cf);
    $check->execute();
    $result = $check->get_result();
    
    // Se trova una riga, significa che il CF è già presente
    if ($result->num_rows > 0) {
      $errors[] = "Esiste già un docente con questo Codice Fiscale.";
    }
    $check->close();
  }

  // === VALIDAZIONE COGNOME ===
  if (!$cognome) {
    $errors[] = "Il Cognome è obbligatorio.";
  }

  // === VALIDAZIONE CITTÀ DI NASCITA ===
  if (!$cittaNascita) {
    $errors[] = "La Città di Nascita è obbligatoria.";
  }

  // === VALIDAZIONE TIPO DOCENTE ===
  // Il tipo deve essere 'I' (Interno) o 'C' (Consulente)
  if (!in_array($tipo, ['I', 'C'])) {
    $errors[] = "Il Tipo docente deve essere 'I' (Interno) o 'C' (Consulente).";
  }

  // === VALIDAZIONE TELEFONO (OPZIONALE) ===
  // Se è stato inserito un telefono, controlla che abbia un formato valido
  if ($telefono && !preg_match('/^\+?[0-9\s\-]+$/', $telefono)) {
    $errors[] = "Il formato del numero di telefono non è valido.";
  }

  // === INSERIMENTO NEL DATABASE ===
  // Procede solo se non ci sono errori di validazione
  if (empty($errors)) {
    try {
      // === INIZIO TRANSAZIONE ===
      // Usa una transazione per garantire che tutte le operazioni vengano completate
      // o nessuna di esse venga eseguita (atomicità)
      $mysqli->begin_transaction();
      
      // === INSERIMENTO DOCENTE ===
      // Prepara la query per inserire il docente nella tabella principale
      $stmt = $mysqli->prepare(
        "INSERT INTO Docente (cf, cognome, cittaNascita, tipo) VALUES (?, ?, ?, ?)"
      );
      $stmt->bind_param("ssss", $cf, $cognome, $cittaNascita, $tipo);
      
      // Esegue la query e controlla se è andata a buon fine
      if (!$stmt->execute()) {
        throw new Exception("Errore durante l'inserimento del docente: " . $mysqli->error);
      }
      $stmt->close();
      
      // === INSERIMENTO TELEFONO (SE PRESENTE) ===
      // Se è stato fornito un numero di telefono, lo inserisce nella tabella separata
      if ($telefono) {
        $stmt_tel = $mysqli->prepare(
          "INSERT INTO Telefono (numero, docente) VALUES (?, ?)"
        );
        $stmt_tel->bind_param("ss", $telefono, $cf);
        
        // Esegue la query del telefono
        if (!$stmt_tel->execute()) {
          throw new Exception("Errore durante l'inserimento del telefono: " . $mysqli->error);
        }
        $stmt_tel->close();
      }
      
      // === CONFERMA TRANSAZIONE ===
      // Se tutto è andato bene, conferma le modifiche al database
      $mysqli->commit();
      $success = true;
      
      // === REINDIRIZZAMENTO ===
      // Dopo 2 secondi reindirizza alla lista docenti
      header("refresh:2;url=docenti.php");
      
    } catch (Exception $e) {
      // === GESTIONE ERRORI ===
      // Se si verifica un errore, annulla tutte le modifiche
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
  <!-- === BREADCRUMB NAVIGATION === -->
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
      <li class="breadcrumb-item active" aria-current="page">Aggiungi</li>
    </ol>
  </nav>

  <!-- === CARD PRINCIPALE === -->
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h4 class="card-title mb-0">
        <i class="fa-solid fa-user-plus me-2"></i>Nuovo Docente
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
        
        <?php 
        // === VISUALIZZAZIONE ERRORI ===
        // Se ci sono errori di validazione, li mostra all'utente
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

        <!-- === FORM DI INSERIMENTO === -->
        <!-- novalidate disabilita la validazione HTML5 per usare quella personalizzata -->
        <form method="post" id="addDocenteForm" novalidate>
          
          <!-- === CAMPO CODICE FISCALE === -->
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
          
          <!-- === ROW CON COGNOME E CITTÀ === -->
          <div class="row">
            <!-- === CAMPO COGNOME === -->
            <div class="col-md-6 mb-4">
              <label for="cognome" class="form-label">Cognome</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" id="cognome" name="cognome" class="form-control" 
                      value="<?= htmlspecialchars($_POST['cognome'] ?? '') ?>"
                      placeholder="Es. Rossi" required>
              </div>
            </div>
            
            <!-- === CAMPO CITTÀ DI NASCITA === -->
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
          
          <!-- === ROW CON TIPO E TELEFONO === -->
          <div class="row">
            <!-- === CAMPO TIPO DOCENTE === -->
            <div class="col-md-6 mb-4">
              <label for="tipo" class="form-label">Tipo Docente</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-tag"></i></span>
                <select id="tipo" name="tipo" class="form-select" required>
                  <!-- Mantiene la selezione precedente in caso di errore -->
                  <option value="I" <?= ($_POST['tipo'] ?? 'I') === 'I' ? 'selected' : '' ?>>Interno</option>
                  <option value="C" <?= ($_POST['tipo'] ?? 'I') === 'C' ? 'selected' : '' ?>>Consulente</option>
                </select>
              </div>
              <div class="form-text">Interno: dipendente della scuola. Consulente: collaboratore esterno.</div>
            </div>
            
            <!-- === CAMPO TELEFONO (OPZIONALE) === -->
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
          
          <!-- === PULSANTI DI AZIONE === -->
          <div class="d-flex gap-2">
            <!-- Pulsante per salvare il docente -->
            <button type="submit" class="btn btn-success">
              <i class="fa-solid fa-save me-2"></i>Salva Docente
            </button>
            
            <!-- Pulsante per annullare e tornare alla lista -->
            <a href="docenti.php" class="btn btn-secondary">
              <i class="fa-solid fa-ban me-2"></i>Annulla
            </a>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <!-- === SEZIONE INFORMATIVA === -->
  <!-- Card con informazioni sulla struttura del database -->
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

<!-- === JAVASCRIPT PER VALIDAZIONE E UX === -->
<script>
// Attende che il DOM sia completamente caricato
document.addEventListener('DOMContentLoaded', function() {
  
  // === VALIDAZIONE E FORMATTAZIONE CODICE FISCALE ===
  const cfInput = document.getElementById('cf');
  if (cfInput) {
    // Aggiunge un listener per l'evento 'input' (quando l'utente digita)
    cfInput.addEventListener('input', function() {
      // Converte automaticamente in maiuscolo
      this.value = this.value.toUpperCase();
      
      // Rimuove tutti i caratteri che non sono lettere o numeri
      this.value = this.value.replace(/[^A-Z0-9]/g, '');
      
      // Limita la lunghezza a 16 caratteri
      if (this.value.length > 16) {
        this.value = this.value.substring(0, 16);
      }
    });
  }
  
  // === VALIDAZIONE FORM LATO CLIENT ===
  const form = document.getElementById('addDocenteForm');
  if (form) {
    // Aggiunge un listener per l'evento 'submit' del form
    form.addEventListener('submit', function(e) {
      // Recupera i valori dei campi obbligatori
      const cf = document.getElementById('cf').value;
      const cognome = document.getElementById('cognome').value;
      const cittaNascita = document.getElementById('cittaNascita').value;
      
      // === VALIDAZIONE LUNGHEZZA CODICE FISCALE ===
      if (cf.length !== 16) {
        e.preventDefault(); // Impedisce l'invio del form
        alert('Il codice fiscale deve essere di esattamente 16 caratteri');
        return false;
      }
      
      // === VALIDAZIONE COGNOME ===
      if (!cognome.trim()) {
        e.preventDefault();
        alert('Il cognome è obbligatorio');
        return false;
      }
      
      // === VALIDAZIONE CITTÀ DI NASCITA ===
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