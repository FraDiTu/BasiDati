<?php
$pageTitle = 'Ricerca';
require_once 'db_connect.php';

// Recupera il termine di ricerca
$search_term = $_GET['q'] ?? '';
$search_term = trim($search_term);

// Prepara i risultati
$results = [
  'docenti' => [],
  'corsi' => [],
  'edizioni' => [],
  'partecipanti' => []
];

// Esegui la ricerca solo se c'è un termine
if ($search_term) {
  // Ricerca nei docenti
  $query_docenti = "
    SELECT cf, nome, cognome, telefono
    FROM Docenti
    WHERE nome LIKE ? OR cognome LIKE ? OR cf LIKE ? OR telefono LIKE ?
    ORDER BY cognome, nome
    LIMIT 20
  ";
  $stmt_docenti = $mysqli->prepare($query_docenti);
  $search_param = "%$search_term%";
  $stmt_docenti->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
  $stmt_docenti->execute();
  $result_docenti = $stmt_docenti->get_result();
  while ($row = $result_docenti->fetch_assoc()) {
    $results['docenti'][] = $row;
  }
  $stmt_docenti->close();
  
  // Ricerca nei corsi
  $query_corsi = "
    SELECT codice, titolo
    FROM Corsi
    WHERE titolo LIKE ? OR codice LIKE ?
    ORDER BY titolo
    LIMIT 20
  ";
  $stmt_corsi = $mysqli->prepare($query_corsi);
  $stmt_corsi->bind_param("ss", $search_param, $search_param);
  $stmt_corsi->execute();
  $result_corsi = $stmt_corsi->get_result();
  while ($row = $result_corsi->fetch_assoc()) {
    $results['corsi'][] = $row;
  }
  $stmt_corsi->close();
  
  // Ricerca nelle edizioni
  $query_edizioni = "
    SELECT e.codice, c.titolo, CONCAT(d.nome, ' ', d.cognome) AS docente
    FROM Edizioni e
    JOIN Corsi c ON e.codice_corso = c.codice
    LEFT JOIN Docenti d ON e.cf_docente = d.cf
    WHERE c.titolo LIKE ? OR e.codice LIKE ? OR CONCAT(d.nome, ' ', d.cognome) LIKE ?
    ORDER BY e.codice DESC
    LIMIT 20
  ";
  $stmt_edizioni = $mysqli->prepare($query_edizioni);
  $stmt_edizioni->bind_param("sss", $search_param, $search_param, $search_param);
  $stmt_edizioni->execute();
  $result_edizioni = $stmt_edizioni->get_result();
  while ($row = $result_edizioni->fetch_assoc()) {
    $results['edizioni'][] = $row;
  }
  $stmt_edizioni->close();
  
  // Ricerca nei partecipanti
  $query_partecipanti = "
    SELECT cf, nome, cognome
    FROM Partecipanti
    WHERE nome LIKE ? OR cognome LIKE ? OR cf LIKE ?
    ORDER BY cognome, nome
    LIMIT 20
  ";
  $stmt_partecipanti = $mysqli->prepare($query_partecipanti);
  $stmt_partecipanti->bind_param("sss", $search_param, $search_param, $search_param);
  $stmt_partecipanti->execute();
  $result_partecipanti = $stmt_partecipanti->get_result();
  while ($row = $result_partecipanti->fetch_assoc()) {
    $results['partecipanti'][] = $row;
  }
  $stmt_partecipanti->close();
}

// Conta i risultati totali
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
  
  <!-- Form di ricerca -->
  <div class="card shadow-sm mb-4">
    <div class="card-body p-4">
      <form action="search.php" method="get" class="mb-0">
        <div class="input-group input-group-lg">
          <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($search_term) ?>" 
                placeholder="Cerca docenti, corsi, edizioni, partecipanti..." aria-label="Cerca" required>
          <button class="btn btn-primary" type="submit">
            <i class="fa-solid fa-search me-2"></i>Cerca
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <?php if ($search_term && $total_results === 0): ?>
    <!-- Nessun risultato -->
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
  <?php elseif ($search_term): ?>
    <!-- Risultati della ricerca -->
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
    
    <div class="row">
      <!-- Docenti -->
      <?php if (!empty($results['docenti'])): ?>
      <div class="col-lg-6 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fa-solid fa-chalkboard-user me-2"></i>Docenti
              </h5>
              <span class="badge bg-light text-dark rounded-pill">
                <?= count($results['docenti']) ?> risultati
              </span>
            </div>
          </div>
          <div class="card-body p-0">
            <div class="list-group list-group-flush">
              <?php foreach ($results['docenti'] as $docente): ?>
                <a href="edit_docente.php?cf=<?= urlencode($docente['cf']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($docente['nome'] . ' ' . $docente['cognome']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($docente['cf']) ?></small>
                  </div>
                  <p class="mb-1 text-muted">
                    <?php if ($docente['telefono']): ?>
                      <i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($docente['telefono']) ?>
                    <?php else: ?>
                      <i class="fa-solid fa-phone-slash me-1"></i>Nessun telefono
                    <?php endif; ?>
                  </p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card-footer bg-transparent">
            <a href="docenti.php" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i docenti
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Partecipanti -->
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
            <div class="list-group list-group-flush">
              <?php foreach ($results['partecipanti'] as $partecipante): ?>
                <a href="partecipazioni.php?cf=<?= urlencode($partecipante['cf']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($partecipante['nome'] . ' ' . $partecipante['cognome']) ?></h6>
                    <small class="text-muted"><?= htmlspecialchars($partecipante['cf']) ?></small>
                  </div>
                  <p class="mb-1 text-muted">
                    <i class="fa-solid fa-user-check me-1"></i>Partecipante
                  </p>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card-footer bg-transparent">
            <a href="coming-soon.php" class="btn btn-outline-success w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i partecipanti
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Corsi -->
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
            <div class="list-group list-group-flush">
              <?php foreach ($results['corsi'] as $corso): ?>
                <a href="coming-soon.php?id=<?= urlencode($corso['codice']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($corso['titolo']) ?></h6>
                    <small class="badge bg-secondary"><?= htmlspecialchars($corso['codice']) ?></small>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="card-footer bg-transparent">
            <a href="coming-soon.php" class="btn btn-outline-info w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutti i corsi
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
      
      <!-- Edizioni -->
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
            <div class="list-group list-group-flush">
              <?php foreach ($results['edizioni'] as $edizione): ?>
                <a href="partecipazioni.php?edizione=<?= urlencode($edizione['codice']) ?>" class="list-group-item list-group-item-action">
                  <div class="d-flex w-100 justify-content-between">
                    <h6 class="mb-1"><?= htmlspecialchars($edizione['titolo']) ?></h6>
                    <small class="badge bg-warning text-dark"><?= htmlspecialchars($edizione['codice']) ?></small>
                  </div>
                  <?php if ($edizione['docente']): ?>
                    <p class="mb-1 text-muted">
                      <i class="fa-solid fa-chalkboard-user me-1"></i><?= htmlspecialchars($edizione['docente']) ?>
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
          <div class="card-footer bg-transparent">
            <a href="coming-soon.php" class="btn btn-outline-warning w-100">
              <i class="fa-solid fa-list me-1"></i>Visualizza tutte le edizioni
            </a>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <!-- Nessuna ricerca effettuata -->
    <div class="card shadow-sm border-0 bg-light">
      <div class="card-body p-5 text-center">
        <i class="fa-solid fa-search fa-5x mb-3 text-muted"></i>
        <h3>Inserisci un termine di ricerca</h3>
        <p class="lead text-muted">
          Puoi cercare docenti, corsi, edizioni e partecipanti.
        </p>
        <p class="text-muted">
          Esempi di ricerca: nomi di docenti, titoli di corsi, codici fiscali, numeri di telefono...
        </p>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>