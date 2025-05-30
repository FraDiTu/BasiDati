<?php
$pageTitle = 'Home — Scuola di Formazione';
require_once 'db_connect.php';

// Recupera statistiche per la dashboard con i nomi corretti delle tabelle
$stats = [
  'docenti' => 0,
  'corsi' => 0,
  'edizioni' => 0,
  'partecipanti' => 0
];

// Query per ottenere i conteggi
$queryDocenti = $mysqli->query("SELECT COUNT(*) as count FROM Docente");
if ($queryDocenti) {
  $stats['docenti'] = $queryDocenti->fetch_assoc()['count'];
}

$queryCorsi = $mysqli->query("SELECT COUNT(*) as count FROM Corso");
if ($queryCorsi) {
  $stats['corsi'] = $queryCorsi->fetch_assoc()['count'];
}

$queryEdizioni = $mysqli->query("SELECT COUNT(*) as count FROM Edizione");
if ($queryEdizioni) {
  $stats['edizioni'] = $queryEdizioni->fetch_assoc()['count'];
}

$queryPartecipanti = $mysqli->query("SELECT COUNT(*) as count FROM Partecipante");
if ($queryPartecipanti) {
  $stats['partecipanti'] = $queryPartecipanti->fetch_assoc()['count'];
}

// Query per gli ultimi docenti aggiunti
$ultimi_docenti = $mysqli->query("
  SELECT cf, cognome, cittaNascita, tipo
  FROM Docente 
  ORDER BY cf DESC 
  LIMIT 5
");

// Query per i corsi più popolari (con più edizioni)
$corsi_popolari = $mysqli->query("
  SELECT c.titolo, COUNT(e.codice) as num_edizioni
  FROM Corso c
  LEFT JOIN Edizione e ON c.codice = e.corso
  GROUP BY c.codice
  ORDER BY num_edizioni DESC
  LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
  <!-- Hero Section -->
  <div class="hero-section">
    <div class="row align-items-center">
      <div class="col-lg-8 mb-5 mb-lg-0">
        <h1 class="display-4 fw-bold mb-4">Scuola di Formazione</h1>
        <p class="lead mb-4">
          Un sistema completo per gestire docenti, corsi, edizioni e partecipanti in modo efficiente.
          Tutto in un'unica interfaccia semplice e intuitiva.
        </p>
        <div class="mt-5 d-flex flex-wrap gap-3">
          <a href="docenti.php" class="btn btn-outline-primary btn-lg">
            Gestisci Docenti
          </a>
          <a href="abilitazioni.php" class="btn btn-outline-primary btn-lg">
            Gestisci Abilitazioni
          </a>
          <a href="partecipazioni.php" class="btn btn-outline-primary btn-lg">
            Gestisci Partecipazioni
          </a>
          <a href="coming_soon.php?section=Corsi" class="btn btn-outline-primary btn-lg">
            Gestisci Corsi
          </a>          
        </div>
      </div>
      <div class="col-lg-4">
        <div class="feature-card">
          <div class="feature-card-content p-4 p-md-5 text-center">
            <h2 class="mb-4">Statistiche Sistema</h2>
            <div class="stats-grid">
              <div class="row g-3">
                <div class="col-6">
                  <div class="stat-box">
                    <div class="stat-value"><?= $stats['docenti'] ?></div>
                    <div class="stat-label">Docenti</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-box">
                    <div class="stat-value"><?= $stats['corsi'] ?></div>
                    <div class="stat-label">Corsi</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-box">
                    <div class="stat-value"><?= $stats['edizioni'] ?></div>
                    <div class="stat-label">Edizioni</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="stat-box">
                    <div class="stat-value"><?= $stats['partecipanti'] ?></div>
                    <div class="stat-label">Partecipanti</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Features Section -->
  <div class="features-section py-5 my-5">
    <h2 class="text-center mb-5">Funzionalità Principali</h2>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="feature-item text-center">
          <div class="feature-icon-container mb-3">
            <span class="feature-icon">👨‍🏫</span>
          </div>
          <h3>Gestione Docenti</h3>
          <p>Aggiungi, modifica ed elimina i docenti. Gestisci dati personali, telefoni e abilitazioni.</p>
          <a href="docenti.php" class="btn btn-outline-primary mt-3">Vai ai Docenti</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-item text-center">
          <div class="feature-icon-container mb-3">
            <span class="feature-icon">📚</span>
          </div>
          <h3>Gestione Corsi</h3>
          <p>Crea e gestisci corsi formativi. Organizza le edizioni e assegna i docenti abilitati.</p>
          <a href="coming_soon.php?section=Corsi" class="btn btn-outline-primary mt-3">Vai ai Corsi</a>
        </div>
      </div>
      <div class="col-md-4">
        <div class="feature-item text-center">
          <div class="feature-icon-container mb-3">
            <span class="feature-icon">👥</span>
          </div>
          <h3>Gestione Partecipanti</h3>
          <p>Iscrivi i partecipanti ai corsi e tieni traccia delle valutazioni con il sistema di voti.</p>
          <a href="coming_soon.php?section=Partecipanti" class="btn btn-outline-primary mt-3">Vai ai Partecipanti</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Activity Section -->
  <div class="activity-section py-5">
    <div class="row">
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="card-title">Ultimi Docenti Aggiunti</h5>
            <a href="add_docente.php" class="btn btn-sm btn-primary">
              Aggiungi Docente
            </a>
          </div>
          <div class="card-body">
            <?php if ($ultimi_docenti && $ultimi_docenti->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                  <thead>
                    <tr>
                      <th class="text-center">Cognome</th>
                      <th class="text-center">Città</th>
                      <th class="text-center">Tipo</th>
                      <th class="text-end text-center">Azioni</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($d = $ultimi_docenti->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($d['cognome']) ?></td>
                      <td><?= htmlspecialchars($d['cittaNascita']) ?></td>
                      <td class="text-center">
                        <span class="badge <?= $d['tipo'] === 'I' ? 'bg-info' : 'bg-warning' ?>">
                          <?= $d['tipo'] === 'I' ? 'Interno' : 'Consulente' ?>
                        </span>
                      </td>
                      <td class="text-end">
                        <a href="edit_docente.php?cf=<?= urlencode($d['cf']) ?>" class="btn btn-sm btn-outline-primary">
                          Modifica
                        </a>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">Nessun docente presente nel sistema.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
      
      <div class="col-lg-6 mb-4">
        <div class="card h-100">
          <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
            <h5 class="card-title">Corsi Più Popolari</h5>
            <a href="coming_soon.php?section=Corsi" class="btn btn-sm btn-success">
              Aggiungi Corso
            </a>
          </div>
          <div class="card-body">
            <?php if ($corsi_popolari && $corsi_popolari->num_rows > 0): ?>
              <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                  <thead>
                    <tr>
                      <th>Titolo Corso</th>
                      <th class="text-end">Edizioni</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php while ($c = $corsi_popolari->fetch_assoc()): ?>
                    <tr>
                      <td><?= htmlspecialchars($c['titolo']) ?></td>
                      <td class="text-end">
                        <span class="badge bg-primary rounded-pill"><?= $c['num_edizioni'] ?></span>
                      </td>
                    </tr>
                    <?php endwhile; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <p class="text-muted">Nessun corso presente nel sistema.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Info Section -->
  <div class="info-section py-4 mt-3 mb-5">
    <div class="card">
      <div class="card-body p-4">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <h4 class="mb-3">Sul Database Formazione del Professore</h4>
            <p>Il database <strong>Formazione</strong> è stato sviluppato seguendo esattamente le specifiche del professore per gestire in modo efficiente i dati relativi alla scuola di formazione:</p>
            <div class="row mt-3">
              <div class="col-md-6">
                <ul class="feature-list">
                  <li><strong>Docente:</strong> gestione anagrafica con tipo (Interno/Consulente)</li>
                  <li><strong>Telefono:</strong> numeri di telefono associati ai docenti</li>
                  <li><strong>Corso:</strong> catalogo completo dei corsi disponibili</li>
                  <li><strong>Edizione:</strong> programmazione delle edizioni dei corsi</li>
                </ul>
              </div>
              <div class="col-md-6">
                <ul class="feature-list">
                  <li><strong>Abilitazione:</strong> competenze dei docenti sui corsi</li>
                  <li><strong>Partecipazione:</strong> iscrizioni e voti degli studenti</li>
                  <li><strong>Lezione:</strong> programmazione dettagliata delle lezioni</li>
                  <li><strong>Datore, Dipendente, Professionista:</strong> gestione anagrafica partecipanti</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card info-card">
              <div class="card-body text-center p-4">
                <h5 class="card-title">Struttura Database</h5>
                <p class="card-text">Il sistema implementa fedelmente lo schema relazionale fornito dal professore con 12 tabelle interconnesse.</p>
                <div class="mt-3">
                  <a href="search.php" class="btn btn-primary w-100">
                    Ricerca nel Database
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<style>
/* Stili aggiuntivi solo per la homepage */
.hero-section {
  background: linear-gradient(135deg, rgba(78, 115, 223, 0.05) 0%, rgba(78, 115, 223, 0.1) 100%);
  border-radius: 1rem;
  padding: 3rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: -50px;
  right: -50px;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: linear-gradient(135deg, rgba(78, 115, 223, 0.1) 0%, rgba(78, 115, 223, 0.2) 100%);
  z-index: 0;
}

.hero-section h1 {
  color: var(--primary-color);
  position: relative;
  z-index: 1;
}

.feature-card {
  border-radius: 1rem;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  background: white;
  overflow: hidden;
  height: 100%;
  transform: translateY(0);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-10px);
  box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
}

.feature-card-content {
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.stats-container {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-top: 1.5rem;
}

.stat-box {
  background-color: var(--light-bg);
  padding: 1.25rem;
  border-radius: 0.75rem;
  transition: all 0.3s ease;
}

.stat-box:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--primary-color);
  line-height: 1;
  margin-bottom: 0.5rem;
}

.stat-label {
  color: var(--text-color);
  font-weight: 600;
}

.feature-item {
  height: 100%;
  padding: 2rem;
  border-radius: 1rem;
  background-color: white;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
}

.feature-item:hover {
  transform: translateY(-10px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
}

.feature-icon-container {
  width: 80px;
  height: 80px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background-color: var(--light-bg);
}

.feature-icon {
  font-size: 2.5rem;
}

.feature-list {
  list-style-type: none;
  padding-left: 0.5rem;
}

.feature-list li {
  margin-bottom: 0.75rem;
  position: relative;
  padding-left: 1.5rem;
}

.feature-list li::before {
  content: '✓';
  position: absolute;
  left: 0;
  color: var(--secondary-color);
  font-weight: bold;
}

.info-card {
  height: 100%;
  border: none;
  border-radius: 0.75rem;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
  background: linear-gradient(135deg, #f8f9fc 0%, #eaecf4 100%);
}

@media (max-width: 768px) {
  .hero-section {
    padding: 2rem;
  }
  
  .stats-container {
    gap: 1rem;
  }
  
  .stat-value {
    font-size: 2rem;
  }
  
  .features-section .row {
    margin-left: -0.5rem;
    margin-right: -0.5rem;
  }
  
  .features-section [class*="col-"] {
    padding-left: 0.5rem;
    padding-right: 0.5rem;
  }
  
  .feature-item {
    padding: 1.5rem;
  }
}

@media (max-width: 576px) {
  .hero-section {
    padding: 1.5rem;
    text-align: center;
  }
  
  .stats-container {
    grid-template-columns: 1fr;
  }
}

/* Dark theme adjustments */
[data-theme="dark"] .hero-section {
  background: linear-gradient(135deg, rgba(58, 77, 177, 0.1) 0%, rgba(58, 77, 177, 0.2) 100%);
}

[data-theme="dark"] .feature-card,
[data-theme="dark"] .feature-item,
[data-theme="dark"] .info-card {
  background-color: rgb(120, 180, 239);
}

[data-theme="dark"] .feature-icon-container {
  background-color: #2c3136;
}

[data-theme="dark"] .stat-box {
  background-color: rgb(214, 231, 250);
}

[data-theme="dark"] .info-card {
  background: linear-gradient(135deg, #rgb(80, 153, 237) 0%, #rgb(65, 118, 179) 100%);
}
</style>

</body>
</html>