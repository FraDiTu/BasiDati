<?php
$pageTitle = 'Elimina Docente';
require_once 'db_connect.php';

// Ottieni il CF dalla richiesta GET
$cf = isset($_GET['cf']) ? trim($_GET['cf']) : '';

// Inizializza variabili
$errors = [];
$success = false;
$docente = null;

// Verifica se è stato specificato un CF
if (empty($cf)) {
    header('Location: docenti.php');
    exit;
}

// Recupera i dati del docente
$query_docente = $mysqli->prepare("SELECT cognome, cittaNascita, tipo FROM Docente WHERE cf = ?");
$query_docente->bind_param("s", $cf);
$query_docente->execute();
$result = $query_docente->get_result();

if ($result->num_rows === 0) {
    header('Location: docenti.php');
    exit;
}

$docente = $result->fetch_assoc();
$query_docente->close();

// Verifica se ci sono relazioni dipendenti
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

$has_dependencies = ($num_abilitazioni > 0 || $num_edizioni > 0 || $num_telefoni > 0);

// Gestione dell'eliminazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_confirmed'])) {
    try {
        // Inizia transazione
        $mysqli->begin_transaction();
        
        // 1. Elimina i telefoni del docente
        $del_telefoni = $mysqli->prepare("DELETE FROM Telefono WHERE docente = ?");
        $del_telefoni->bind_param("s", $cf);
        $del_telefoni->execute();
        $del_telefoni->close();
        
        // 2. Elimina le abilitazioni del docente
        $del_abilitazioni = $mysqli->prepare("DELETE FROM Abilitazione WHERE docente = ?");
        $del_abilitazioni->bind_param("s", $cf);
        $del_abilitazioni->execute();
        $del_abilitazioni->close();
        
        // 3. Imposta NULL nelle edizioni dove è assegnato questo docente
        $upd_edizioni = $mysqli->prepare("UPDATE Edizione SET docente = NULL WHERE docente = ?");
        $upd_edizioni->bind_param("s", $cf);
        $upd_edizioni->execute();
        $upd_edizioni->close();
        
        // 4. Infine elimina il docente
        $del_docente = $mysqli->prepare("DELETE FROM Docente WHERE cf = ?");
        $del_docente->bind_param("s", $cf);
        $del_docente->execute();
        
        if ($del_docente->affected_rows > 0) {
            // Conferma le modifiche
            $mysqli->commit();
            $success = true;
        } else {
            // Nessuna riga influenzata
            $mysqli->rollback();
            $errors[] = "Nessun docente eliminato. Possibile problema con il database.";
        }
        $del_docente->close();
        
    } catch (Exception $e) {
        // Annulla tutte le modifiche in caso di errore
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
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="index.php">Home</a></li>
      <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
      <li class="breadcrumb-item active" aria-current="page">Elimina</li>
    </ol>
  </nav>

  <div class="row justify-content-center">
    <div class="col-lg-8">
      <?php if ($success): ?>
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
        <script>
          // Reindirizzamento tramite JavaScript dopo 2 secondi
          setTimeout(function() {
            window.location.href = 'docenti.php';
          }, 2000);
        </script>
      <?php else: ?>
        <?php if ($errors): ?>
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
        
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white">
            <h4 class="card-title mb-0">
              <i class="fa-solid fa-trash me-2"></i>Conferma Eliminazione
            </h4>
          </div>
          <div class="card-body p-4">
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
            
            <?php if ($has_dependencies): ?>
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
            
            <!-- Form di eliminazione -->
            <form method="post" action="">
              <input type="hidden" name="delete_confirmed" value="1">
              
              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Sei davvero sicuro di voler eliminare questo docente? Questa operazione è irreversibile.')">
                  <i class="fa-solid fa-trash me-2"></i>Elimina Docente
                </button>
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