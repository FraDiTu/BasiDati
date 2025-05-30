<?php
/**
 * File: search.php
 * Descrizione: Funzionalità di ricerca globale nel database
 * 
 * Questo file implementa un sistema di ricerca completo che permette di:
 * - Cercare simultaneamente in tutte le entità principali del database
 * - Visualizzare risultati organizzati per categoria (docenti, corsi, edizioni, partecipanti)
 * - Fornire links diretti alle pagine di dettaglio per ogni risultato
 * - Gestire query vuote e risultati zero con appropriate UX
 * - Ottimizzare le query per performance con LIMIT appropriati
 * 
 * La ricerca utilizza LIKE con wildcard per matching parziale e cerca
 * nei campi più rilevanti di ogni entità (nomi, codici, titoli, etc.).
 */

// Imposta il titolo della pagina
$pageTitle = 'Ricerca';

// Include il file di connessione al database
require_once 'db_connect.php';

// === RECUPERO E SANITIZZAZIONE TERMINE DI RICERCA ===
// Ottiene il termine di ricerca dalla query string 'q' (standard web)
$search_term = $_GET['q'] ?? '';
$search_term = trim($search_term); // Rimuove spazi iniziali/finali

// === INIZIALIZZAZIONE ARRAY RISULTATI ===
// Organizza i risultati per categoria per una presentazione strutturata
$results = [
  'docenti' => [],        // Risultati nella tabella Docente
  'corsi' => [],          // Risultati nella tabella Corso
  'edizioni' => [],       // Risultati nelle edizioni (con info corso/docente)
  'partecipanti' => []    // Risultati nella tabella Partecipante
];

// === ESECUZIONE RICERCHE (SOLO SE C'È UN TERMINE) ===
// Evita query inutili se l'utente non ha inserito nulla
if ($search_term) {
  
  // === RICERCA NEI DOCENTI ===
  // Cerca nei campi più rilevanti: cognome, CF, città di nascita
  $query_docenti = "
    SELECT cf, cognome, cittaNascita, tipo
    FROM Docente
    WHERE cognome LIKE ? OR cf LIKE ? OR cittaNascita LIKE ?
    ORDER BY cognome
    LIMIT 20
  ";
  
  $stmt_docenti = $mysqli->prepare($query_docenti);
  $search_param = "%$search_term%"; // Aggiunge wildcard per matching parziale
  $stmt_docenti->bind_param("sss", $search_param, $search_param, $search_param);
  $stmt_docenti->execute();
  $result_docenti = $stmt_docenti->get_result();
  
  // Raccoglie tutti i risultati docenti
  while ($row = $result_docenti->fetch_assoc()) {
    $results['docenti'][] = $row;
  }
  $stmt_docenti->close();
  
  // === RICERCA NEI CORSI ===
  // Cerca nel titolo e codice del corso
  $query_corsi = "
    SELECT codice, titolo
    FROM Corso
    WHERE titolo LIKE ? OR codice LIKE ?
    ORDER BY titolo
    LIMIT 20
  ";
  
  $stmt_corsi = $mysqli->prepare($query_corsi);
  $stmt_corsi->bind_param("ss", $search_param, $search_param);
  $stmt_corsi->execute();
  $result_corsi = $stmt_corsi->get_result();
  
  // Raccoglie tutti i risultati corsi
  while ($row = $result_corsi->fetch_assoc()) {
    $results['corsi'][] = $row;
  }
  $stmt_corsi->close();
  
  // === RICERCA NELLE EDIZIONI ===
  // Query complessa con JOIN per includere informazioni corso e docente
  $query_edizioni = "
    SELECT e.codice, c.titolo, d.cognome AS docente_cognome
    FROM Edizione e
    JOIN Corso c ON e.corso = c.codice           -- JOIN per titolo corso
    LEFT JOIN Docente d ON e.docente = d.cf      -- LEFT JOIN perché docente può essere NULL
    WHERE c.titolo LIKE ? OR e.codice LIKE ? OR d.cognome LIKE ?
    ORDER BY e.codice DESC                       -- Edizioni più recenti prima
    LIMIT 20
  ";
  
  $stmt_edizioni = $mysqli->prepare($query_edizioni);
  $stmt_edizioni->bind_param("sss", $search_param, $search_param, $search_param);
  $stmt_edizioni->execute();
  $result_edizioni = $stmt_edizioni->get_result();
  
  // Raccoglie tutti i risultati edizioni
  while ($row = $result_edizioni->fetch_assoc()) {
    $results['edizioni'][] = $row;
  }
  $stmt_edizioni->close();
  
  // === RICERCA NEI PARTECIPANTI ===
  // Cerca nei campi anagrafici principali
  $query_partecipanti = "
    SELECT cf, cognome, eta, sesso
    FROM Partecipante
    WHERE cognome LIKE ? OR cf LIKE ? OR cittaNascita LIKE ?
    ORDER BY cognome
    LIMIT 20
  ";
  
  $stmt_partecipanti = $mysqli->prepare($query_partecipanti);
  $stmt_partecipanti->bind_param("sss", $search_param, $search_param, $search_param);
  $stmt_partecipanti->execute();
  $result_partecipanti = $stmt_partecipanti->get_result();
  
  // Raccoglie tutti i risultati partecipanti
  while ($row = $result_partecipanti->fetch_assoc()) {
    $results['partecipanti'][] = $row;
  }
  $stmt_partecipanti->close();
}

// === CALCOLO RISULTATI TOTALI ===
// Somma tutti i risultati per statistiche di ricerca
$total_results = count($results['docenti']) + count($results['corsi']) + 
                 count($results['edizioni']) + count($results['partecipanti']);
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <h2 class="section-header mb-4">Ricerca</h2>
  
  <!-- === FORM DI RICERCA === -->
  <div class="card shadow-sm mb-4">
    <div class="card-body p-4">
      <!-- Form GET per permettere bookmarking/sharing dei risultati -->
      <form action="search.php" method="get" class="mb-0">
        <div class="input-group input-group-lg">
          <!-- Campo di ricerca con valore mantenuto -->
          <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($search_term) ?>" 
                placeholder="Cerca docenti, corsi, edizioni, partecipanti..." aria-label="Cerca" required>
          <button class="btn btn-primary" type="submit">
            <i class="fa-solid fa-search me-2"></i>Cerca
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <?php 
  // === GESTIONE NESSUN RISULTATO ===
  if ($search_term && $total_results === 0): 
  ?>
    <div class="alert alert-info">
      <div class="d-flex">
        <div class="me-3">
          <i class="fa-solid fa-info-circle fa-2x"></i>
        </div>
        <div>
          <h4 class="alert-heading">Nessun risultato</h4>
          <p class="mb-0">
            La ricerca di <strong>"<?= htmlspecialchars($search_term) ?>"</strong> non ha prodotto risultati.
            Prova con termini diversi o meno specifici.
          </p>
        </div>
      </div>
    </div>
    
  <?php 
  // === VISUALIZZAZIONE RISULTATI ===
  elseif ($search_term): 
  ?>
    <!-- === HEADER RISULTATI === -->
    <div class="alert alert-success mb-4">
      <div class="d-flex align-items-center">
        <div class="me-3">
          <i class="fa-solid fa-check-circle fa-2x"></i>
        </div>
        <div>
          <h4 class="alert-heading">Risultati della ricerca</h4>
          <p class="mb-0">
            La ricerca di <strong>"<?= htmlspecialchars($search_term) ?>"</strong> ha prodotto
            <strong><?= $total_results ?></strong> risultati.
          </p>
        </div>
      </div>
    </div>
    
    <!-- === GRIGLIA RISULTATI === -->
    <div class="row">
      
      <!-- === SEZIONE DOCENTI === -->
      <?php if (!empty($results['docenti'])): ?>
      <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fa-solid fa-chalkboard-user me-2"></i>Docenti
              </h5>
              <!-- Badge con numero risultati -->
              <span class="badge bg-light text-dark rounded-pill">
                <?= count($results['docenti']) ?> risultati
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            
            <!-- === LISTA RISULTATI DOCENTI === -->
            <div class="list-group list-group-flush">
              <?php foreach ($results['docenti'] as $docente): ?>
                <!-- Link cliccabile per andare al dettaglio docente -->
                <a href="edit_docente.php?cf=<?= urlencode($docente['cf']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($docente['cognome']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($docente['cf']) ?></small>
                  </div>
                  <p class="mb-1 text-muted">
                    <i class="fa-solid fa-map-marker-alt me-1"></i><?= htmlspecialchars($docente['cittaNascita']) ?>
                    <!-- Badge colorato per tipo docente -->
                    <span class="badge <?= $docente['tipo'] === 'I' ? 'bg-info' : 'bg-warning' ?> ms-2">
                      <?= $docente['tipo'] === 'I' ? 'Interno' : 'Consulente' ?>
                    </span>
                  </p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- === FOOTER CON LINK A VISTA COMPLETA === -->
          <div class="card-footer bg-transparent">
            <a href="docenti.php" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i docenti
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- === SEZIONE PARTECIPANTI === -->
      <?php if (!empty($results['partecipanti'])): ?>
      <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fa-solid fa-users me-2"></i>Partecipanti
              </h5>
              <span class="badge bg-light text-dark rounded-pill">
                <?= count($results['partecipanti']) ?> risultati
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            
            <!-- === LISTA RISULTATI PARTECIPANTI === -->
            <div class="list-group list-group-flush">
              <?php foreach ($results['partecipanti'] as $partecipante): ?>
                <!-- Link alle partecipazioni di questo partecipante -->
                <a href="partecipazioni.php?cf=<?= urlencode($partecipante['cf']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($partecipante['cognome']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($partecipante['cf']) ?></small>
                  </div>
                  <p class="mb-1 text-muted">
                    <i class="fa-solid fa-user-check me-1"></i>Età: <?= htmlspecialchars($partecipante['eta']) ?> - <?= $partecipante['sesso'] === 'M' ? 'Maschio' : 'Femmina' ?>
                  </p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- === FOOTER CON LINK A VISTA COMPLETA === -->
          <div class="card-footer bg-transparent">
            <a href="coming_soon.php?section=Partecipanti" class="btn btn-outline-success w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i partecipanti
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- === SEZIONE CORSI === -->
      <?php if (!empty($results['corsi'])): ?>
      <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fa-solid fa-book me-2"></i>Corsi
              </h5>
              <span class="badge bg-light text-dark rounded-pill">
                <?= count($results['corsi']) ?> risultati
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            
            <!-- === LISTA RISULTATI CORSI === -->
            <div class="list-group list-group-flush">
              <?php foreach ($results['corsi'] as $corso): ?>
                <!-- Link al dettaglio corso (da implementare) -->
                <a href="coming_soon.php?section=Corsi&id=<?= urlencode($corso['codice']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($corso['titolo']) ?></h6>
                    <small class="badge bg-secondary"><?= htmlspecialchars($corso['codice']) ?></small>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- === FOOTER CON LINK A VISTA COMPLETA === -->
          <div class="card-footer bg-transparent">
            <a href="coming_soon.php?section=Corsi" class="btn btn-outline-info w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i corsi
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- === SEZIONE EDIZIONI === -->
      <?php if (!empty($results['edizioni'])): ?>
      <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fa-solid fa-calendar-days me-2"></i>Edizioni
              </h5>
              <span class="badge bg-light text-dark rounded-pill">
                <?= count($results['edizioni']) ?> risultati
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            
            <!-- === LISTA RISULTATI EDIZIONI === -->
            <div class="list-group list-group-flush">
              <?php foreach ($results['edizioni'] as $edizione): ?>
                <!-- Link ai partecipanti di questa edizione -->
                <a href="partecipazioni.php?edizione=<?= urlencode($edizione['codice']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($edizione['titolo']) ?></h6>
                    <small class="badge bg-warning text-dark"><?= htmlspecialchars($edizione['codice']) ?></small>
                  </div>
                  
                  <!-- === INFORMAZIONI DOCENTE === -->
                  <?php if ($edizione['docente_cognome']): ?>
                    <p class="mb-1 text-muted">
                      <i class="fa-solid fa-chalkboard-user me-1"></i><?= htmlspecialchars($edizione['docente_cognome']) ?>
                    </p>
                  <?php else: ?>
                    <p class="mb-1 text-muted">
                      <i class="fa-solid fa-user-slash me-1"></i>Nessun docente assegnato
                    </p>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          
          <!-- === FOOTER CON LINK A VISTA COMPLETA === -->
          <div class="card-footer bg-transparent">
            <a href="coming_soon.php?section=Edizioni" class="btn btn-outline-warning w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutte le edizioni
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
    
  <?php else: ?>
    
    <!-- === STATO INIZIALE (NESSUNA RICERCA) === -->
    <div class="card shadow-sm border-0 bg-light">
      <div class="card-body p-5 text-center">
        <i class="fa-solid fa-search fa-5x mb-3 text-muted"></i>
        <h3>Inserisci un termine di ricerca</h3>
        <p class="lead text-muted">
          Puoi cercare docenti, corsi, edizioni e partecipanti.
        </p>
        <p class="text-muted">
          Esempi di ricerca: cognomi di docenti, titoli di corsi, codici fiscali...
        </p>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>