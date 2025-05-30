<?php
/**
 * File: abilitazioni.php
 * Descrizione: Gestione delle abilitazioni dei docenti ai corsi
 * 
 * Questo file permette di:
 * - Visualizzare tutte le abilitazioni o quelle di un docente specifico
 * - Aggiungere nuove abilitazioni (associare un docente a un corso)
 * - Rimuovere abilitazioni esistenti
 * - Visualizzare statistiche sulle abilitazioni
 */

// Imposta il titolo della pagina per il tag <title>
$pageTitle = 'Gestione Abilitazioni';

// Include il file di connessione al database
require_once 'db_connect.php';

// === GESTIONE FILTRO DOCENTE SPECIFICO ===
// Inizializza la variabile per il docente filtrato
$docente_filtrato = null;

// Recupera il parametro 'cf' dalla query string per filtrare per un docente specifico
$filtro_cf = $_GET['cf'] ?? null;

// Se è stato specificato un codice fiscale per il filtro
if ($filtro_cf) {
  // Prepara una query sicura per recuperare i dati del docente
  $doc_query = $mysqli->prepare("SELECT cf, cognome FROM Docente WHERE cf = ?");
  $doc_query->bind_param("s", $filtro_cf); // 's' indica che il parametro è una stringa
  $doc_query->execute();
  $result = $doc_query->get_result();
  
  // Se il docente esiste, salva i suoi dati
  if ($result->num_rows > 0) {
    $docente_filtrato = $result->fetch_assoc();
  }
  
  // Chiude lo statement preparato per liberare risorse
  $doc_query->close();
}

// === SISTEMA DI FEEDBACK PER LE OPERAZIONI ===
// Array associativo per gestire i messaggi di feedback all'utente
$feedback = [
  'show' => false,    // Se mostrare o meno il messaggio
  'type' => '',       // Tipo di messaggio (success, danger, warning, info)
  'message' => ''     // Testo del messaggio
];

// === GESTIONE FORM POST ===
// Verifica se è stata inviata una richiesta POST (invio del form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Recupera i dati dal form usando null coalescing operator (??) per evitare errori
  $cf_doc = $_POST['cf_docente'] ?? '';
  $cod_corso = $_POST['codice_corso'] ?? '';
  
  // === VALIDAZIONE DATI ===
  // Controlla che entrambi i campi siano stati compilati
  if (!$cf_doc || !$cod_corso) {
    $feedback = [
      'show' => true,
      'type' => 'danger',
      'message' => 'Seleziona sia il docente che il corso'
    ];
  } else {
    // === OPERAZIONE DI AGGIUNTA ABILITAZIONE ===
    // Controlla se è stato cliccato il pulsante "Aggiungi"
    if (isset($_POST['add'])) {
      // Prepara query INSERT con IGNORE per evitare duplicati
      $stmt = $mysqli->prepare(
        "INSERT IGNORE INTO Abilitazione (corso, docente) VALUES (?, ?)"
      );
      $stmt->bind_param("ss", $cod_corso, $cf_doc); // Due stringhe: codice corso e CF docente
      
      // Esegue la query e gestisce il risultato
      if ($stmt->execute()) {
        // Controlla se sono state effettivamente inserite righe
        if ($stmt->affected_rows > 0) {
          $feedback = [
            'show' => true,
            'type' => 'success',
            'message' => 'Abilitazione aggiunta con successo'
          ];
        } else {
          // Se affected_rows è 0, significa che l'abilitazione esisteva già
          $feedback = [
            'show' => true,
            'type' => 'warning',
            'message' => 'Questa abilitazione esiste già'
          ];
        }
      } else {
        // Errore durante l'esecuzione della query
        $feedback = [
          'show' => true,
          'type' => 'danger',
          'message' => 'Errore nell\'aggiunta dell\'abilitazione: ' . $mysqli->error
        ];
      }
      $stmt->close();
    }
    
    // === OPERAZIONE DI RIMOZIONE ABILITAZIONE ===
    // Controlla se è stato cliccato il pulsante "Rimuovi"
    if (isset($_POST['del'])) {
      // Prepara query DELETE per rimuovere l'abilitazione specifica
      $stmt = $mysqli->prepare(
        "DELETE FROM Abilitazione WHERE docente=? AND corso=?"
      );
      $stmt->bind_param("ss", $cf_doc, $cod_corso);
      
      // Esegue la query e gestisce il risultato
      if ($stmt->execute()) {
        // Controlla se sono state effettivamente eliminate righe
        if ($stmt->affected_rows > 0) {
          $feedback = [
            'show' => true,
            'type' => 'success',
            'message' => 'Abilitazione rimossa con successo'
          ];
        } else {
          // Se affected_rows è 0, significa che l'abilitazione non esisteva
          $feedback = [
            'show' => true,
            'type' => 'warning',
            'message' => 'Questa abilitazione non esiste'
          ];
        }
      } else {
        // Errore durante l'esecuzione della query
        $feedback = [
          'show' => true,
          'type' => 'danger',
          'message' => 'Errore nella rimozione dell\'abilitazione: ' . $mysqli->error
        ];
      }
      $stmt->close();
    }
  }
}

// === RECUPERO DATI PER I DROPDOWN ===
// Query per recuperare tutti i docenti ordinati per cognome
$doc_query = "SELECT cf, cognome FROM Docente ORDER BY cognome";
$doc = $mysqli->query($doc_query);

// Query per recuperare tutti i corsi ordinati per titolo
$cors = $mysqli->query("SELECT codice, titolo FROM Corso ORDER BY titolo");

// === RECUPERO ABILITAZIONI ESISTENTI ===
// Query complessa con JOIN per recuperare abilitazioni con nomi e titoli
$abil_query = "
  SELECT a.docente, a.corso, d.cognome, c.titolo
  FROM Abilitazione a
  JOIN Docente d ON a.docente = d.cf     -- Unisce con la tabella Docente per ottenere il cognome
  JOIN Corso c ON a.corso = c.codice     -- Unisce con la tabella Corso per ottenere il titolo
";

// Se c'è un filtro attivo per un docente specifico, aggiunge la clausola WHERE
if ($docente_filtrato) {
  // Usa real_escape_string per prevenire SQL injection
  $abil_query .= " WHERE a.docente = '" . $mysqli->real_escape_string($docente_filtrato['cf']) . "'";
}

// Aggiunge l'ordinamento finale
$abil_query .= " ORDER BY d.cognome, c.titolo";

// Esegue la query delle abilitazioni
$abil = $mysqli->query($abil_query);

// === STATISTICHE GENERALI ===
// Query per calcolare statistiche aggregate sulle abilitazioni
$stat_query = "
  SELECT 
    COUNT(*) AS totale_abilitazioni,                    -- Conta tutte le abilitazioni
    COUNT(DISTINCT docente) AS docenti_con_abilitazioni,-- Conta i docenti unici con almeno un'abilitazione
    COUNT(DISTINCT corso) AS corsi_con_docenti          -- Conta i corsi unici con almeno un docente abilitato
  FROM Abilitazione
";

$stat = $mysqli->query($stat_query);
$statistiche = $stat->fetch_assoc(); // Recupera la riga dei risultati come array associativo
?>
<!DOCTYPE html>
<html lang="it">
<?php 
// Include l'header HTML comune con meta tags e CSS
include 'head.php'; 
?>
<body>
<?php 
// Include la barra di navigazione comune
include 'navbar.php'; 
?>

<div class="container py-4">
  <?php 
  // === BREADCRUMB NAVIGATION ===
  // Se stiamo visualizzando le abilitazioni di un docente specifico, mostra il breadcrumb
  if ($docente_filtrato): 
  ?>
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
        <li class="breadcrumb-item"><a href="edit_docente.php?cf=<?= urlencode($docente_filtrato['cf']) ?>"><?= htmlspecialchars($docente_filtrato['cognome']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page">Abilitazioni</li>
      </ol>
    </nav>
  <?php endif; ?>

  <!-- === TITOLO PRINCIPALE === -->
  <h2 class="section-header">
    <?php if ($docente_filtrato): ?>
      <!-- Titolo personalizzato se si stanno visualizzando le abilitazioni di un docente specifico -->
      Abilitazioni di <?= htmlspecialchars($docente_filtrato['cognome']) ?>
    <?php else: ?>
      <!-- Titolo generico per la gestione di tutte le abilitazioni -->
      Gestione Abilitazioni
    <?php endif; ?>
  </h2>
  
  <?php 
  // === VISUALIZZAZIONE FEEDBACK ===
  // Se c'è un messaggio di feedback da mostrare
  if ($feedback['show']): 
  ?>
    <div class="alert alert-<?= $feedback['type'] ?> alert-dismissible fade show" role="alert">
      <?php 
      // Icona diversa a seconda del tipo di messaggio
      if ($feedback['type'] === 'success'): ?>
        <i class="fa-solid fa-check-circle me-2"></i>
      <?php elseif ($feedback['type'] === 'danger'): ?>
        <i class="fa-solid fa-circle-exclamation me-2"></i>
      <?php else: ?>
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
      <?php endif; ?>
      
      <!-- Messaggio del feedback con escape HTML per sicurezza -->
      <?= htmlspecialchars($feedback['message']) ?>
      
      <!-- Pulsante per chiudere l'alert -->
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- === COLONNA PRINCIPALE (Form + Tabella) === -->
    <div class="col-lg-8">
      
      <!-- === FORM PER GESTIRE LE ABILITAZIONI === -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-certificate me-2"></i>Assegna/Rimuovi Abilitazioni
          </h4>
        </div>
        <div class="card-body p-4">
          <!-- Form che invia dati in POST alla stessa pagina -->
          <form method="post" class="row g-3" id="abilitazioniForm">
            
            <!-- === DROPDOWN SELEZIONE DOCENTE === -->
            <div class="col-md-5">
              <label for="cf_docente" class="form-label">Docente</label>
              <!-- Se stiamo filtrando per un docente, il dropdown è disabilitato -->
              <select name="cf_docente" id="cf_docente" class="form-select" <?= $docente_filtrato ? 'disabled' : '' ?> required>
                <?php if (!$docente_filtrato): ?>
                  <!-- Opzione placeholder se non c'è filtro attivo -->
                  <option value="" selected disabled>Seleziona un docente</option>
                <?php endif; ?>
                
                <?php 
                // Cicla attraverso tutti i docenti per popolare il dropdown
                while($d = $doc->fetch_assoc()): 
                ?>
                  <option value="<?= $d['cf'] ?>" <?= ($docente_filtrato && $d['cf'] === $docente_filtrato['cf']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['cognome']) ?> (<?= htmlspecialchars($d['cf']) ?>)
                  </option>
                <?php endwhile; ?>
              </select>
              
              <?php 
              // Se c'è un filtro attivo, include un campo hidden per mantenere il valore
              if ($docente_filtrato): 
              ?>
                <input type="hidden" name="cf_docente" value="<?= htmlspecialchars($docente_filtrato['cf']) ?>">
              <?php endif; ?>
            </div>
            
            <!-- === DROPDOWN SELEZIONE CORSO === -->
            <div class="col-md-5">
              <label for="codice_corso" class="form-label">Corso</label>
              <select name="codice_corso" id="codice_corso" class="form-select" required>
                <option value="" selected disabled>Seleziona un corso</option>
                <?php 
                // Cicla attraverso tutti i corsi per popolare il dropdown
                while($c = $cors->fetch_assoc()): 
                ?>
                  <option value="<?= $c['codice'] ?>"><?= htmlspecialchars($c['titolo']) ?> (<?= htmlspecialchars($c['codice']) ?>)</option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <!-- === PULSANTI DI AZIONE === -->
            <div class="col-md-2 d-flex gap-2 flex-column justify-content-end">
              <!-- Pulsante per aggiungere un'abilitazione -->
              <button type="submit" name="add" class="btn btn-success">
                <i class="fa-solid fa-plus me-1"></i>Abilita
              </button>
              
              <!-- Pulsante per rimuovere un'abilitazione -->
              <button type="submit" name="del" class="btn btn-danger">
                <i class="fa-solid fa-minus me-0"></i>Rimuovi
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- === TABELLA DELLE ABILITAZIONI ESISTENTI === -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fa-solid fa-list me-2"></i>Elenco Abilitazioni
            </h5>
            <!-- Badge con il numero di risultati -->
            <span class="badge bg-primary rounded-pill">
              <?= $abil->num_rows ?> risultati
            </span>
          </div>
        </div>
        
        <div class="card-body p-0">
          <?php 
          // === GESTIONE CASO NESSUNA ABILITAZIONE ===
          if ($abil->num_rows === 0): 
          ?>
            <div class="p-4 text-center">
              <i class="fa-solid fa-info-circle text-muted fa-2x mb-3"></i>
              <p class="mb-0">
                <?php if ($docente_filtrato): ?>
                  Questo docente non ha ancora abilitazioni. Utilizza il form sopra per abilitarlo a insegnare corsi.
                <?php else: ?>
                  Non sono presenti abilitazioni. Utilizza il form sopra per abilitare un docente a insegnare un corso.
                <?php endif; ?>
              </p>
            </div>
          <?php else: ?>
            
            <!-- === FILTRO DI RICERCA NELLA TABELLA === -->
            <div class="p-3 border-bottom ">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" placeholder="Filtra abilitazioni...">
              </div>
            </div>
            
            <!-- === TABELLA RESPONSIVE === -->
            <div class="table-responsive text-center ">
              <table class="table table-hover mb-0" id="abilitazioniTable">
                <thead>
                  <tr>
                    <?php 
                    // Mostra la colonna "Docente" solo se non stiamo filtrando per un docente specifico
                    if (!$docente_filtrato): 
                    ?>
                      <th>Docente</th>
                    <?php endif; ?>
                    <th>Corso</th>
                    <th>Codice Corso</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  // === CICLO DELLE ABILITAZIONI ===
                  // Itera attraverso ogni abilitazione recuperata dal database
                  while($r = $abil->fetch_assoc()): 
                  ?>
                  <tr>
                    <?php 
                    // Colonna docente (solo se non stiamo filtrando per docente)
                    if (!$docente_filtrato): 
                    ?>
                      <td>
                        <!-- Link al profilo del docente -->
                        <a href="edit_docente.php?cf=<?= urlencode($r['docente']) ?>" class="text-decoration-none">
                          <?= htmlspecialchars($r['cognome']) ?>
                        </a>
                      </td>
                    <?php endif; ?>
                    
                    <!-- Colonna titolo del corso -->
                    <td><?= htmlspecialchars($r['titolo']) ?></td>
                    
                    <!-- Colonna codice del corso (come badge) -->
                    <td>
                      <span class="badge bg-secondary "><?= htmlspecialchars($r['corso']) ?></span>
                    </td>
                    
                    <!-- === COLONNA AZIONI === -->
                    <td class="text-center">
                      <div class="d-flex justify-content-center">
                        <!-- Form per eliminare l'abilitazione -->
                        <form method="post" class="d-inline" onsubmit="return confirm('Sei sicuro di voler rimuovere questa abilitazione?')">
                          <!-- Campi hidden per identificare l'abilitazione da eliminare -->
                          <input type="hidden" name="cf_docente" value="<?= htmlspecialchars($r['docente']) ?>">
                          <input type="hidden" name="codice_corso" value="<?= htmlspecialchars($r['corso']) ?>">
                          
                          <!-- Pulsante di eliminazione con conferma JavaScript -->
                          <button type="submit" name="del" class="action-btn delete-btn" data-bs-toggle="tooltip" title="Elimina">
                            <span class="unicode-icon">🗑️</span>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
    
    <!-- === SIDEBAR CON STATISTICHE E INFORMAZIONI === -->
    <div class="col-lg-4">
      
      <!-- === CARD STATISTICHE === -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-chart-simple me-2"></i>Statistiche
          </h5>
        </div>
        <div class="card-body">
          
          <!-- Statistica: Totale Abilitazioni -->
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-certificate fa-2x text-success"></i>
            </div>
            <div>
              <h6 class="mb-0">Totale Abilitazioni</h6>
              <h4 class="mb-0"><?= $statistiche['totale_abilitazioni'] ?></h4>
            </div>
          </div>
          
          <!-- Statistica: Docenti con Abilitazioni -->
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-chalkboard-user fa-2x text-primary"></i>
            </div>
            <div>
              <h6 class="mb-0">Docenti con Abilitazioni</h6>
              <h4 class="mb-0"><?= $statistiche['docenti_con_abilitazioni'] ?></h4>
            </div>
          </div>
          
          <!-- Statistica: Corsi con Docenti Abilitati -->
          <div class="d-flex align-items-center">
            <div class="me-3">
              <i class="fa-solid fa-book-open fa-2x text-warning"></i>
            </div>
            <div>
              <h6 class="mb-0">Corsi con Docenti Abilitati</h6>
              <h4 class="mb-0"><?= $statistiche['corsi_con_docenti'] ?></h4>
            </div>
          </div>
        </div>
      </div>
      
      <!-- === CARD INFORMAZIONI E AIUTO === -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-circle-info me-2"></i>Informazioni
          </h5>
        </div>
        <div class="card-body">
          <!-- Sezioni informative con spiegazioni per l'utente -->
          <h6>Cosa sono le abilitazioni?</h6>
          <p>Le abilitazioni indicano quali corsi un docente è autorizzato a insegnare.</p>
          
          <h6>Come funzionano?</h6>
          <p>Prima di poter assegnare un docente a un'edizione di un corso, è necessario che il docente sia abilitato a insegnare quel corso specifico.</p>
          
          <h6>Come gestire le abilitazioni?</h6>
          <p>Usa il form in questa pagina per aggiungere o rimuovere abilitazioni. Per aggiungere un'abilitazione, seleziona un docente e un corso, quindi clicca su "Abilita". Per rimuovere un'abilitazione, seleziona la stessa combinazione e clicca su "Rimuovi".</p>
          
          <!-- Alert di avvertimento -->
          <div class="alert alert-warning mt-3 mb-0">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <strong>Attenzione:</strong> Rimuovere un'abilitazione potrebbe impedire al docente di essere assegnato a future edizioni del corso.
          </div>
        </div>
        
        <!-- === FOOTER DELLA CARD CON LINK DI NAVIGAZIONE === -->
        <?php if (!$docente_filtrato): ?>
        <!-- Se non stiamo filtrando, mostra link alla gestione docenti -->
        <div class="card-footer bg-transparent">
          <a href="docenti.php" class="btn btn-outline-primary w-100">
            <i class="fa-solid fa-users me-2"></i>Gestisci Docenti
          </a>
        </div>
        <?php else: ?>
        <!-- Se stiamo filtrando, mostra link per tornare al docente -->
        <div class="card-footer bg-transparent">
          <a href="edit_docente.php?cf=<?= urlencode($docente_filtrato['cf']) ?>" class="btn btn-outline-primary w-100">
            <i class="fa-solid fa-arrow-left me-2"></i>Torna al Docente
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php 
// Include il footer comune con scripts
include 'footer.php'; 
?>

<!-- === JAVASCRIPT PER INTERATTIVITÀ === -->
<script>
// Attende che il DOM sia completamente caricato prima di eseguire il codice
document.addEventListener('DOMContentLoaded', function() {
  
  // === FUNZIONALITÀ DI FILTRO TABELLA ===
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    // Aggiunge un listener per l'evento 'input' (quando l'utente digita)
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase(); // Converte il termine di ricerca in minuscolo
      const rows = document.querySelectorAll('#abilitazioniTable tbody tr'); // Seleziona tutte le righe della tabella
      
      // Cicla attraverso ogni riga della tabella
      rows.forEach(row => {
        const text = row.textContent.toLowerCase(); // Ottiene tutto il testo della riga in minuscolo
        // Mostra o nasconde la riga a seconda che contenga il termine di ricerca
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }
  
  // === VALIDAZIONE FORM LATO CLIENT ===
  const form = document.getElementById('abilitazioniForm');
  if (form) {
    // Aggiunge un listener per l'evento 'submit' del form
    form.addEventListener('submit', function(e) {
      const docente = document.getElementById('cf_docente');
      const corso = document.getElementById('codice_corso');
      
      // Controlla che entrambi i campi siano stati selezionati
      if ((docente && docente.value === '') || corso.value === '') {
        e.preventDefault(); // Impedisce l'invio del form
        alert('Seleziona sia un docente che un corso'); // Mostra un alert di errore
      }
    });
  }
});
</script>

<!-- === CSS PERSONALIZZATO === -->
<style>
  /* === STILI PER LE CARD STATISTICHE === */
  .stat-card {
    transition: all 0.3s ease; /* Transizione fluida per le animazioni */
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Ombra leggera */
  }
  
  /* Effetto hover per le card statistiche */
  .stat-card:hover {
    transform: translateY(-5px); /* Sposta la card verso l'alto */
    box-shadow: 0 8px 24px rgba(0,0,0,0.1); /* Ombra più pronunciata */
  }
  
  /* Colori di sfondo per le diverse statistiche */
  .bg-light-blue {
    background-color: #e6f2ff;
  }
  
  .bg-light-green {
    background-color: #e6fff2;
  }
  
  .bg-light-yellow {
    background-color: #fffbe6;
  }
  
  /* === STILI PER I BADGE CONTATORI === */
  .count-badge {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
  }
  
  /* === STILI PER I PULSANTI AZIONE === */
  .action-buttons {
    display: flex;
    justify-content: center;
    gap: 10px; /* Spazio tra i pulsanti */
  }

  .action-btn {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s; /* Transizione per animazioni */
    border: none;
  }

  /* Effetto hover per i pulsanti azione */
  .action-btn:hover {
    transform: translateY(-3px); /* Sposta il pulsante verso l'alto */
    box-shadow: 0 4px 8px rgba(0,0,0,0.2); /* Aggiunge ombra */
    color: white;
  }

  /* Dimensione delle icone Unicode */
  .unicode-icon {
    font-size: 1rem;
    line-height: 1;
  }

  /* Colori specifici per i diversi tipi di pulsanti */
  .edit-btn {
    background-color: var(--primary-color);
  }

  .edit-btn:hover {
    background-color: #3551db;
  }

  .delete-btn {
    background-color: var(--danger-color);
  }

  .delete-btn:hover {
    background-color: #d92b39;
  }

  .details-btn {
    background-color: var(--info-color);
  }

  .details-btn:hover {
    background-color: #33bfe9;
  }

  /* === RESPONSIVE DESIGN PER SCHERMI PICCOLI === */
  @media (max-width: 576px) {
    .action-buttons {
      gap: 6px; /* Riduce lo spazio tra i pulsanti */
    }
    
    .action-btn {
      width: 36px; /* Riduce la dimensione dei pulsanti */
      height: 36px;
    }
    
    .unicode-icon {
      font-size: 1.1rem; /* Aumenta leggermente l'icona per compensare */