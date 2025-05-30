<?php
$pageTitle = 'Gestione Docenti';
require_once 'db_connect.php';
require_once 'utils.php';

// Query per ottenere tutti i docenti
$result = $mysqli->query("
  SELECT d.cf, d.nome, d.cognome, d.telefono,
         COUNT(DISTINCT a.codice_corso) AS num_abilitazioni,
         COUNT(DISTINCT e.codice) AS num_edizioni
  FROM Docenti d
  LEFT JOIN Abilitazioni a ON d.cf = a.cf_docente
  LEFT JOIN Edizioni e ON d.cf = e.cf_docente
  GROUP BY d.cf
  ORDER BY d.cognome, d.nome
");

// Prepara l'array per i risultati
$docenti = [];
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $docenti[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="section-header mb-0">Gestione Docenti</h2>
    <a href="add_docente.php" class="btn btn-primary">
      <i class="fa-solid fa-plus me-2"></i>Nuovo Docente
    </a>
  </div>
  
  <?php if (empty($docenti)): ?>
    <div class="alert alert-info">
      <i class="fa-solid fa-info-circle me-2"></i>
      Nessun docente presente nel database. Aggiungi il primo docente utilizzando il pulsante "Nuovo Docente".
    </div>
  <?php else: ?>
    <!-- Filtri di ricerca -->
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          <div class="col-md-8">
            <div class="input-group">
              <span class="input-group-text">
                <i class="fa-solid fa-search"></i>
              </span>
              <input type="text" id="searchInput" class="form-control" placeholder="Filtra docenti...">
            </div>
          </div>
          <div class="col-md-4 text-md-end">
            <div class="btn-group filter-group" role="group">
              <button type="button" class="btn btn-outline-primary active" id="viewAll">
                <i class="fa-solid fa-list me-1"></i>Tutti
              </button>
              <button type="button" class="btn btn-outline-primary" id="viewActive">
                <i class="fa-solid fa-check-circle me-1"></i>Attivi
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Tabella docenti -->
    <div class="table-wrapper text-center">
      <table class="table table-hover" id="docentiTable">
        <thead>
          <tr>
            <th>CF</th>
            <th>Nome</th>
            <th>Cognome</th>
            <th>Telefono</th>
            <th>Corsi Abilitati</th>
            <th>Edizioni</th>
            <th>Azioni</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($docenti as $d): ?>
          <tr data-abilitazioni="<?= $d['num_abilitazioni'] ?>">
            <td>
              <span class="badge bg-secondary"><?= htmlspecialchars($d['cf']) ?></span>
            </td>
            <td><?= htmlspecialchars($d['nome']) ?></td>
            <td><?= htmlspecialchars($d['cognome']) ?></td>
            <td>
              <?php if ($d['telefono']): ?>
                <a href="tel:<?= htmlspecialchars($d['telefono']) ?>" class="text-decoration-none">
                  <i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($d['telefono']) ?>
                </a>
              <?php else: ?>
                <span class="text-muted fst-italic">Non disponibile</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <span class="badge <?= $d['num_abilitazioni'] > 0 ? 'bg-success' : 'bg-secondary' ?> rounded-pill count-badge">
                <?= $d['num_abilitazioni'] ?>
              </span>
            </td>
            <td class="text-center">
              <span class="badge <?= $d['num_edizioni'] > 0 ? 'bg-primary' : 'bg-secondary' ?> rounded-pill count-badge">
                <?= $d['num_edizioni'] ?>
              </span>
            </td>
            <td class="text-center">
              <div class="action-buttons">
                <a href="edit_docente.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn edit-btn" title="Modifica">
                  <span class="unicode-icon">✏️</span>
                </a>
                <a href="delete_docente.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn delete-btn" title="Elimina">
                  <span class="unicode-icon">🗑️</span>
                </a>
                <?php if ($d['num_abilitazioni'] > 0): ?>
                <a href="abilitazioni.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn details-btn" title="Dettagli">
                  <span class="unicode-icon">🔍</span>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    
    <!-- Statistiche docenti - Versione migliorata -->
    <div class="card mt-4 shadow-sm border-0">
      <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-chart-simple text-primary me-2"></i>Statistiche Docenti
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-4">
            <div class="stat-card bg-light-blue rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Totale Docenti</h6>
              <h2 class="display-4 fw-bold mb-0"><?= count($docenti) ?></h2>
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="stat-card bg-light-green rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Docenti con Abilitazioni</h6>
              <?php
              $docentiConAbilitazioni = array_filter($docenti, function($d) {
                return $d['num_abilitazioni'] > 0;
              });
              $percentualeAbilitati = count($docenti) > 0 ? (count($docentiConAbilitazioni) / count($docenti) * 100) : 0;
              ?>
              <h2 class="display-4 fw-bold mb-0"><?= count($docentiConAbilitazioni) ?></h2>
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $percentualeAbilitati ?>%"></div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="stat-card bg-light-yellow rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Docenti con Edizioni</h6>
              <?php
              $docentiConEdizioni = array_filter($docenti, function($d) {
                return $d['num_edizioni'] > 0;
              });
              $percentualeConEdizioni = count($docenti) > 0 ? (count($docentiConEdizioni) / count($docenti) * 100) : 0;
              ?>
              <h2 class="display-4 fw-bold mb-0"><?= count($docentiConEdizioni) ?></h2>
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $percentualeConEdizioni ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<style>
  /* Stili per le statistiche migliorate */
  .stat-card {
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  }
  
  .stat-icon-container {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
  }
  
  .bg-light-blue {
    background-color: #e6f2ff;
  }
  
  .bg-light-green {
    background-color: #e6fff2;
  }
  
  .bg-light-yellow {
    background-color: #fffbe6;
  }
  
  .count-badge {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
  }
  
  /* Stili per i pulsanti azione con icone Unicode */
  .action-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
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
    transition: all 0.3s;
    border: none;
  }

  .action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    color: white;
  }

  .unicode-icon {
    font-size: 1 rem;
    line-height: 1;
  }

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

  /* Per schermi molto piccoli */
  @media (max-width: 576px) {
    .action-buttons {
      gap: 6px;
    }
    
    .action-btn {
      width: 36px;
      height: 36px;
    }
    
    .unicode-icon {
      font-size: 1.1rem;
    }
  }
</style>

<script>
// Script specifico per questa pagina
document.addEventListener('DOMContentLoaded', function() {
  // Inizializza i tooltip
  const tooltips = document.querySelectorAll('[title]');
  tooltips.forEach(tooltip => {
    new bootstrap.Tooltip(tooltip);
  });

  // Filtro di ricerca
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('#docentiTable tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }
  
  // Filtro per visualizzare solo docenti attivi (con abilitazioni)
  const viewAllBtn = document.getElementById('viewAll');
  const viewActiveBtn = document.getElementById('viewActive');
  
  if (viewAllBtn && viewActiveBtn) {
    viewAllBtn.addEventListener('click', function() {
      this.classList.add('active');
      viewActiveBtn.classList.remove('active');
      
      const rows = document.querySelectorAll('#docentiTable tbody tr');
      rows.forEach(row => {
        row.style.display = '';
      });
    });
    
    viewActiveBtn.addEventListener('click', function() {
      this.classList.add('active');
      viewAllBtn.classList.remove('active');
      
      const rows = document.querySelectorAll('#docentiTable tbody tr');
      rows.forEach(row => {
        const abilitazioni = parseInt(row.getAttribute('data-abilitazioni'), 10);
        row.style.display = abilitazioni > 0 ? '' : 'none';
      });
    });
  }
});
</script>
</body>
</html>