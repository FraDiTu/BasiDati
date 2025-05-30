<?php
/**
 * File: delete_docente.php
 * Descrizione: Pagina per l'eliminazione sicura di un docente dal sistema
 * 
 * Questo file gestisce l'eliminazione completa di un docente e di tutti i suoi dati correlati:
 * - Visualizza un form di conferma con i dettagli del docente
 * - Mostra le dipendenze esistenti (telefoni, abilitazioni, edizioni)
 * - Esegue l'eliminazione in cascata usando transazioni per garantire consistenza
 * - Gestisce appropriatamente le relazioni per evitare errori di integrità referenziale
 * 
 * Processo di eliminazione:
 * 1. Elimina tutti i telefoni del docente
 * 2. Elimina tutte le abilitazioni del docente
 * 3. Imposta NULL nelle edizioni dove è assegnato (invece di eliminare le edizioni)
 * 4. Elimina il record del docente
 * 
 * Tutto viene eseguito in una transazione per garantire atomicità.
 */

// Imposta il titolo della pagina
$pageTitle = 'Elimina Docente';

// Include il file di connessione al database
require_once 'db_connect.php';

// === RECUPERO PARAMETRO CF ===
// Ottiene il Codice Fiscale del docente da eliminare dalla query string
$cf = isset($_GET['cf']) ? trim($_GET['cf']) : '';

// Inizializza array per eventuali errori e flag di successo
$errors = [];
$success = false;
$docente = null;

// === VALIDAZIONE PARAMETRO CF ===
// Se non è stato specificato un CF, reindirizza alla lista docenti
if (empty($cf)) {
    header('Location: docenti.php');
    exit;
}

// === RECUPERO DATI DOCENTE ===
// Query per ottenere le informazioni base del docente da eliminare
$query_docente = $mysqli->prepare("SELECT cognome, cittaNascita, tipo FROM Docente WHERE cf = ?");
$query_docente->bind_param("s", $cf);
$query_docente->execute();
$result = $query_docente->get_result();

// Se il docente non esiste, reindirizza alla lista
if ($result->num_rows === 0) {
    header('Location: docenti.php');
    exit;
}

// Salva i dati del docente per la visualizzazione
$docente = $result->fetch_assoc();
$query_docente->close();

// === ANALISI DIPENDENZE ===
// Verifica quanti record dipendenti esistono per questo docente
// Questo è importante per informare l'utente su cosa verrà eliminato
$query_dipendenze = $mysqli->prepare("
    SELECT 
        (SELECT COUNT(*) FROM Abilitazione WHERE docente = ?) AS num_abilitazioni,
        (SELECT COUNT(*) FROM Edizione WHERE docente = ?) AS num_edizioni,
        (SELECT COUNT(*) FROM Telefono WHERE docente = ?) AS num_telefoni
");
$query_dipendenze->bind_param("sss", $cf, $cf, $cf);
$query_dipendenze->execute();
$query_dipendenze->bind_result($num_abilitazioni, $num_edizioni, $num_telefoni);
$query_dipendenze->fetch();
$query_dipendenze->close();

// Flag per determinare se esistono dipendenze
$has_dependencies = ($num_abilitazioni > 0 || $num_edizioni > 0 || $num_telefoni > 0);

// === GESTIONE ELIMINAZIONE ===
// Processa l'eliminazione solo se è stata confermata tramite POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_confirmed'])) {
    try {
        // === INIZIO TRANSAZIONE ===
        // Usa una transazione per garantire che tutte le operazioni vengano completate
        // o nessuna di esse venga eseguita (proprietà ACID - Atomicità)
        $mysqli->begin_transaction();
        
        // === STEP 1: ELIMINAZIONE TELEFONI ===
        // Elimina tutti i numeri di telefono associati al docente
        // Questo deve essere fatto prima di eliminare il docente per via delle foreign key
        $del_telefoni = $mysqli->prepare("DELETE FROM Telefono WHERE docente = ?");
        $del_telefoni->bind_param("s", $cf);
        $del_telefoni->execute();
        $del_telefoni->close();
        
        // === STEP 2: ELIMINAZIONE ABILITAZIONI ===
        // Elimina tutte le abilitazioni del docente ai corsi
        // Questo rimuove la capacità del docente di insegnare qualsiasi corso
        $del_abilitazioni = $mysqli->prepare("DELETE FROM Abilitazione WHERE docente = ?");
        $del_abilitazioni->bind_param("s", $cf);
        $del_abilitazioni->execute();
        $del_abilitazioni->close();
        
        // === STEP 3: GESTIONE EDIZIONI ===
        // IMPORTANTE: Non eliminiamo le edizioni, ma impostiamo il docente a NULL
        // Questo preserva la storia delle edizioni anche se il docente non c'è più
        $upd_edizioni = $mysqli->prepare("UPDATE Edizione SET docente = NULL WHERE docente = ?");
        $upd_edizioni->bind_param("s", $cf);
        $upd_edizioni->execute();
        $upd_edizioni->close();
        
        // === STEP 4: ELIMINAZIONE DOCENTE ===
        // Infine elimina il record principale del docente
        // Questo deve essere l'ultimo step per rispettare i vincoli di integrità referenziale
        $del_docente = $mysqli->prepare("DELETE FROM Docente WHERE cf = ?");
        $del_docente->bind_param("s", $cf);
        $del_docente->execute();
        
        // === VERIFICA SUCCESSO ===
        if ($del_docente->affected_rows > 0) {
            // Se almeno una riga è stata eliminata, l'operazione è riuscita
            $mysqli->commit();  // Conferma tutte le modifiche
            $success = true;
        } else {
            // Se nessuna riga è stata eliminata, qualcosa è andato storto
            $mysqli->rollback(); // Annulla tutte le modifiche
            $errors[] = "Nessun docente eliminato. Possibile problema con il database.";
        }
        $del_docente->close();
        
    } catch (Exception $e) {
        // === GESTIONE ERRORI ===
        // Se si verifica qualsiasi errore durante il processo, annulla tutto
        $mysqli->rollback();
        $errors[] = "Errore durante l'eliminazione: " . $e->getMessage();
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
      <li class="breadcrumb-item active" aria-current="page">Elimina</li>
    </ol>
  </nav>

  <!-- === CONTENUTO PRINCIPALE === -->
  <div class="row justify-content-center">
    <div class="col-lg-8">
      
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
              <h4 class="alert-heading">Docente eliminato con successo!</h4>
              <p>Il docente <?= htmlspecialchars($docente['cognome']) ?> (<?= htmlspecialchars($cf) ?>) è stato rimosso dal database.</p>
              <hr>
              <p class="mb-0">Verrai reindirizzato alla lista docenti tra pochi secondi. 
                Se non vuoi attendere, <a href="docenti.php" class="alert-link">clicca qui</a>.
              </p>
            </div>
          </div>
        </div>
        
        <!-- === SCRIPT DI REINDIRIZZAMENTO AUTOMATICO === -->
        <script>
          // Reindirizza automaticamente dopo 2 secondi per migliorare l'UX
          setTimeout(function() {
            window.location.href = 'docenti.php';
          }, 2000);
        </script>
      
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
                <h4 class="alert-heading">Operazione non riuscita</h4>
                <ul class="mb-0">
                  <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        <?php endif; ?>
        
        <!-- === CARD CONFERMA ELIMINAZIONE === -->
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white">
            <h4 class="card-title mb-0">
              <i class="fa-solid fa-trash me-2"></i>Conferma Eliminazione
            </h4>
          </div>
          <div class="card-body p-4">
            
            <!-- === ALERT DI AVVERTIMENTO === -->
            <div class="alert alert-warning">
              <div class="d-flex">
                <div class="me-3">
                  <i class="fa-solid fa-exclamation-triangle fa-2x"></i>
                </div>
                <div>
                  <h4 class="alert-heading">Attenzione!</h4>
                  <p>Stai per eliminare il seguente docente:</p>
                  <ul class="mb-2">
                    <li><strong>Cognome:</strong> <?= htmlspecialchars($docente['cognome']) ?></li>
                    <li><strong>Codice Fiscale:</strong> <?= htmlspecialchars($cf) ?></li>
                    <li><strong>Città di Nascita:</strong> <?= htmlspecialchars($docente['cittaNascita']) ?></li>
                    <li><strong>Tipo:</strong> <?= $docente['tipo'] === 'I' ? 'Interno' : 'Consulente' ?></li>
                  </ul>
                  <p class="mb-0"><strong>Questa operazione non può essere annullata.</strong></p>
                </div>
              </div>
            </div>
            
            <?php 
            // === VISUALIZZAZIONE DIPENDENZE ===
            // Se il docente ha dipendenze, avvisa l'utente su cosa verrà eliminato/modificato
            if ($has_dependencies): 
            ?>
              <div class="alert alert-danger">
                <p><strong>Il docente ha le seguenti dipendenze nel database:</strong></p>
                <ul class="mb-2">
                  <?php if ($num_telefoni > 0): ?>
                    <li><?= $num_telefoni ?> numeri di telefono</li>
                  <?php endif; ?>
                  <?php if ($num_abilitazioni > 0): ?>
                    <li><?= $num_abilitazioni ?> abilitazioni a corsi</li>
                  <?php endif; ?>
                  <?php if ($num_edizioni > 0): ?>
                    <li><?= $num_edizioni ?> edizioni insegnate</li>
                  <?php endif; ?>
                </ul>
                <p class="mb-0">Procedendo con l'eliminazione:</p>
                <ul class="mb-0">
                  <li>Verranno eliminati tutti i telefoni del docente</li>
                  <li>Verranno rimosse tutte le abilitazioni del docente</li>
                  <li>Il docente verrà rimosso dalle edizioni (impostato a NULL)</li>
                </ul>
              </div>
            <?php endif; ?>
            
            <!-- === FORM DI CONFERMA ELIMINAZIONE === -->
            <form method="post" action="">
              <!-- Campo hidden per confermare l'intenzione di eliminare -->
              <input type="hidden" name="delete_confirmed" value="1">
              
              <div class="d-flex gap-2">
                <!-- === PULSANTE ELIMINAZIONE === -->
                <!-- onclick con confirm() aggiunge un ulteriore livello di conferma JavaScript -->
                <button type="submit" class="btn btn-danger" onclick="return confirm('Sei davvero sicuro di voler eliminare questo docente? Questa operazione è irreversibile.')">
                  <i class="fa-solid fa-trash me-2"></i>Elimina Docente
                </button>
                
                <!-- === PULSANTE ANNULLA === -->
                <a href="docenti.php" class="btn btn-secondary">
                  <i class="fa-solid fa-ban me-2"></i>Annulla
                </a>
              </div>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>