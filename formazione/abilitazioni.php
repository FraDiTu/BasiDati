<?php
$pageTitle = 'Gestione Abilitazioni';
require_once 'db_connect.php';

// Gestione filtro docente specifico
$docente_filtrato = null;
$filtro_cf = $_GET['cf'] ?? null;
if ($filtro_cf) {
  $doc_query = $mysqli->prepare("SELECT cf, nome, cognome FROM Docenti WHERE cf = ?");
  $doc_query->bind_param("s", $filtro_cf);
  $doc_query->execute();
  $result = $doc_query->get_result();
  if ($result->num_rows > 0) {
    $docente_filtrato = $result->fetch_assoc();
  }
  $doc_query->close();
}

// Messaggio di feedback per operazioni
$feedback = [
  'show' => false,
  'type' => '',
  'message' => ''
];

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cf_doc = $_POST['cf_docente'] ?? '';
  $cod_corso = $_POST['codice_corso'] ?? '';
  
  // Validazione base
  if (!$cf_doc || !$cod_corso) {
    $feedback = [
      'show' => true,
      'type' => 'danger',
      'message' => 'Seleziona sia il docente che il corso'
    ];
  } else {
    // Operazione di aggiunta
    if (isset($_POST['add'])) {
      $stmt = $mysqli->prepare(
        "INSERT IGNORE INTO Abilitazioni (cf_docente, codice_corso) VALUES (?, ?)"
      );
      $stmt->bind_param("ss", $cf_doc, $cod_corso);
      if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          $feedback = [
            'show' => true,
            'type' => 'success',
            'message' => 'Abilitazione aggiunta con successo'
          ];
        } else {
          $feedback = [
            'show' => true,
            'type' => 'warning',
            'message' => 'Questa abilitazione esiste già'
          ];
        }
      } else {
        $feedback = [
          'show' => true,
          'type' => 'danger',
          'message' => 'Errore nell\'aggiunta dell\'abilitazione: ' . $mysqli->error
        ];
      }
      $stmt->close();
    }
    
    // Operazione di rimozione
    if (isset($_POST['del'])) {
      $stmt = $mysqli->prepare(
        "DELETE FROM Abilitazioni WHERE cf_docente=? AND codice_corso=?"
      );
      $stmt->bind_param("ss", $cf_doc, $cod_corso);
      if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
          $feedback = [
            'show' => true,
            'type' => 'success',
            'message' => 'Abilitazione rimossa con successo'
          ];
        } else {
          $feedback = [
            'show' => true,
            'type' => 'warning',
            'message' => 'Questa abilitazione non esiste'
          ];
        }
      } else {
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

// Recupera elenco docenti
$doc_query = "SELECT cf, CONCAT(nome, ' ', cognome) AS nome_completo FROM Docenti ORDER BY cognome, nome";
if ($docente_filtrato) {
  $doc = $mysqli->query($doc_query);
} else {
  $doc = $mysqli->query($doc_query);
}

// Recupera elenco corsi
$cors = $mysqli->query("SELECT codice, titolo FROM Corsi ORDER BY titolo");

// Recupera abilitazioni esistenti con nomi docenti e titoli corsi
$abil_query = "
  SELECT a.cf_docente, a.codice_corso, CONCAT(d.nome, ' ', d.cognome) AS docente, c.titolo
  FROM Abilitazioni a
  JOIN Docenti d ON a.cf_docente = d.cf
  JOIN Corsi c ON a.codice_corso = c.codice
";

// Se c'è un filtro su un docente specifico
if ($docente_filtrato) {
  $abil_query .= " WHERE a.cf_docente = '" . $mysqli->real_escape_string($docente_filtrato['cf']) . "'";
}

$abil_query .= " ORDER BY docente, titolo";
$abil = $mysqli->query($abil_query);

// Conta le abilitazioni per statistica
$stat_query = "
  SELECT 
    COUNT(*) AS totale_abilitazioni,
    COUNT(DISTINCT cf_docente) AS docenti_con_abilitazioni,
    COUNT(DISTINCT codice_corso) AS corsi_con_docenti
  FROM Abilitazioni
";
$stat = $mysqli->query($stat_query);
$statistiche = $stat->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-4">
  <?php if ($docente_filtrato): ?>
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="docenti.php">Docenti</a></li>
        <li class="breadcrumb-item"><a href="edit_docente.php?cf=<?= urlencode($docente_filtrato['cf']) ?>"><?= htmlspecialchars($docente_filtrato['nome'] . ' ' . $docente_filtrato['cognome']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page">Abilitazioni</li>
      </ol>
    </nav>
  <?php endif; ?>

  <h2 class="section-header">
    <?php if ($docente_filtrato): ?>
      Abilitazioni di <?= htmlspecialchars($docente_filtrato['nome'] . ' ' . $docente_filtrato['cognome']) ?>
    <?php else: ?>
      Gestione Abilitazioni
    <?php endif; ?>
  </h2>
  
  <?php if ($feedback['show']): ?>
    <div class="alert alert-<?= $feedback['type'] ?> alert-dismissible fade show" role="alert">
      <?php if ($feedback['type'] === 'success'): ?>
        <i class="fa-solid fa-check-circle me-2"></i>
      <?php elseif ($feedback['type'] === 'danger'): ?>
        <i class="fa-solid fa-circle-exclamation me-2"></i>
      <?php else: ?>
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
      <?php endif; ?>
      <?= htmlspecialchars($feedback['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <!-- Form per aggiungere/rimuovere abilitazioni -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-certificate me-2"></i>Assegna/Rimuovi Abilitazioni
          </h4>
        </div>
        <div class="card-body p-4">
          <form method="post" class="row g-3" id="abilitazioniForm">
            <div class="col-md-5">
              <label for="cf_docente" class="form-label">Docente</label>
              <select name="cf_docente" id="cf_docente" class="form-select" <?= $docente_filtrato ? 'disabled' : '' ?> required>
                <?php if (!$docente_filtrato): ?>
                  <option value="" selected disabled>Seleziona un docente</option>
                <?php endif; ?>
                
                <?php while($d = $doc->fetch_assoc()): ?>
                  <option value="<?= $d['cf'] ?>" <?= ($docente_filtrato && $d['cf'] === $docente_filtrato['cf']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['nome_completo']) ?>
                  </option>
                <?php endwhile; ?>
              </select>
              
              <?php if ($docente_filtrato): ?>
                <input type="hidden" name="cf_docente" value="<?= htmlspecialchars($docente_filtrato['cf']) ?>">
              <?php endif; ?>
            </div>
            
            <div class="col-md-5">
              <label for="codice_corso" class="form-label">Corso</label>
              <select name="codice_corso" id="codice_corso" class="form-select" required>
                <option value="" selected disabled>Seleziona un corso</option>
                <?php while($c = $cors->fetch_assoc()): ?>
                  <option value="<?= $c['codice'] ?>"><?= htmlspecialchars($c['titolo']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            
            <div class="col-md-2 d-flex gap-2 flex-column justify-content-end">
              <button type="submit" name="add" class="btn btn-success">
                <i class="fa-solid fa-plus me-1"></i>Abilita
              </button>
              <button type="submit" name="del" class="btn btn-danger">
                <i class="fa-solid fa-minus me-0"></i>Rimuovi
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Tabella abilitazioni -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fa-solid fa-list me-2"></i>Elenco Abilitazioni
            </h5>
            <span class="badge bg-primary rounded-pill">
              <?= $abil->num_rows ?> risultati
            </span>
          </div>
        </div>
        
        <div class="card-body p-0">
          <?php if ($abil->num_rows === 0): ?>
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
            <!-- Filtro tabella -->
            <div class="p-3 border-bottom ">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" placeholder="Filtra abilitazioni...">
              </div>
            </div>
            
            <div class="table-responsive text-center ">
              <table class="table table-hover mb-0" id="abilitazioniTable">
                <thead>
                  <tr>
                    <?php if (!$docente_filtrato): ?>
                      <th>Docente</th>
                    <?php endif; ?>
                    <th>Corso</th>
                    <th>Codice Corso</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($r = $abil->fetch_assoc()): ?>
                  <tr>
                    <?php if (!$docente_filtrato): ?>
                      <td>
                        <a href="edit_docente.php?cf=<?= urlencode($r['cf_docente']) ?>" class="text-decoration-none">
                          <?= htmlspecialchars($r['docente']) ?>
                        </a>
                      </td>
                    <?php endif; ?>
                    <td><?= htmlspecialchars($r['titolo']) ?></td>
                    <td>
                      <span class="badge bg-secondary "><?= htmlspecialchars($r['codice_corso']) ?></span>
                    </td>
                    <td class="text-center">
  <form method="post" class="d-inline" onsubmit="return confirm('Sei sicuro di voler rimuovere questa abilitazione?')">
    <input type="hidden" name="cf_docente" value="<?= htmlspecialchars($r['cf_docente']) ?>">
    <input type="hidden" name="codice_corso" value="<?= htmlspecialchars($r['codice_corso']) ?>">
                        <button type="submit" name="del" class="action-btn delete-btn" data-bs-toggle="tooltip" title="Elimina">🗑️</button>                          <i class="fa-solid fa-trash"></i>
      <i class="fa-solid fa-trash"></i>
    </button>
  </form>
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
    
    <div class="col-lg-4">
      <!-- Statistiche e informazioni -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-chart-simple me-2"></i>Statistiche
          </h5>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-certificate fa-2x text-success"></i>
            </div>
            <div>
              <h6 class="mb-0">Totale Abilitazioni</h6>
              <h4 class="mb-0"><?= $statistiche['totale_abilitazioni'] ?></h4>
            </div>
          </div>
          
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-chalkboard-user fa-2x text-primary"></i>
            </div>
            <div>
              <h6 class="mb-0">Docenti con Abilitazioni</h6>
              <h4 class="mb-0"><?= $statistiche['docenti_con_abilitazioni'] ?></h4>
            </div>
          </div>
          
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
      
      <!-- Informazioni e aiuto -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-circle-info me-2"></i>Informazioni
          </h5>
        </div>
        <div class="card-body">
          <h6>Cosa sono le abilitazioni?</h6>
          <p>Le abilitazioni indicano quali corsi un docente è autorizzato a insegnare.</p>
          
          <h6>Come funzionano?</h6>
          <p>Prima di poter assegnare un docente a un'edizione di un corso, è necessario che il docente sia abilitato a insegnare quel corso specifico.</p>
          
          <h6>Come gestire le abilitazioni?</h6>
          <p>Usa il form in questa pagina per aggiungere o rimuovere abilitazioni. Per aggiungere un'abilitazione, seleziona un docente e un corso, quindi clicca su "Abilita". Per rimuovere un'abilitazione, seleziona la stessa combinazione e clicca su "Rimuovi".</p>
          
          <div class="alert alert-warning mt-3 mb-0">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <strong>Attenzione:</strong> Rimuovere un'abilitazione potrebbe impedire al docente di essere assegnato a future edizioni del corso.
          </div>
        </div>
        
        <?php if (!$docente_filtrato): ?>
        <div class="card-footer bg-transparent">
          <a href="docenti.php" class="btn btn-outline-primary w-100">
            <i class="fa-solid fa-users me-2"></i>Gestisci Docenti
          </a>
        </div>
        <?php else: ?>
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

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Filtro tabella
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = document.querySelectorAll('#abilitazioniTable tbody tr');
      
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }
  
  // Validazione form
  const form = document.getElementById('abilitazioniForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      const docente = document.getElementById('cf_docente');
      const corso = document.getElementById('codice_corso');
      
      if ((docente && docente.value === '') || corso.value === '') {
        e.preventDefault();
        alert('Seleziona sia un docente che un corso');
      }
    });
  }
});
</script>
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

</body>

</html>