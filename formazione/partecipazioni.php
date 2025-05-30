<?php
$pageTitle = 'Gestione Partecipazioni';
require_once 'db_connect.php';
require_once 'utils.php';

// Messaggio di feedback per operazioni
$feedback = [
  'show' => false,
  'type' => '',
  'message' => '',
  'details' => []
];

// Gestione filtro partecipante specifico
$partecipante_filtrato = null;
$filtro_cf = $_GET['cf'] ?? null;
if ($filtro_cf) {
  $part_query = $mysqli->prepare("SELECT cf, nome, cognome FROM Partecipanti WHERE cf = ?");
  $part_query->bind_param("s", $filtro_cf);
  $part_query->execute();
  $result = $part_query->get_result();
  if ($result->num_rows > 0) {
    $partecipante_filtrato = $result->fetch_assoc();
  }
  $part_query->close();
}

// Gestione filtro edizione specifica
$edizione_filtrata = null;
$filtro_edizione = $_GET['edizione'] ?? null;
if ($filtro_edizione) {
  $ed_query = $mysqli->prepare("
    SELECT e.codice, c.titolo, CONCAT(d.nome, ' ', d.cognome) AS docente
    FROM Edizioni e
    JOIN Corsi c ON e.codice_corso = c.codice
    LEFT JOIN Docenti d ON e.cf_docente = d.cf
    WHERE e.codice = ?
  ");
  $ed_query->bind_param("i", $filtro_edizione);
  $ed_query->execute();
  $result = $ed_query->get_result();
  if ($result->num_rows > 0) {
    $edizione_filtrata = $result->fetch_assoc();
  }
  $ed_query->close();
}

// Gestione form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $cf_p = $_POST['cf_partecipante'] ?? '';
  $cod_ed = (int)($_POST['codice_edizione'] ?? 0);
  $voto = isset($_POST['voto']) && $_POST['voto'] !== '' ? (int)$_POST['voto'] : null;
  
  // Validazione base
  $errors = [];
  if (!$cf_p) {
    $errors[] = "Seleziona un partecipante";
  }
  if ($cod_ed <= 0) {
    $errors[] = "Seleziona un'edizione valida";
  }
  if ($voto !== null && ($voto < 0 || $voto > 30)) {
    $errors[] = "Il voto deve essere compreso tra 0 e 30";
  }

  if (empty($errors)) {
    // — INSERT —
    if (isset($_POST['add'])) {
      // Verifica se esiste già questa partecipazione
      $check = $mysqli->prepare("
        SELECT 1 
        FROM Partecipazioni 
        WHERE cf_partecipante = ? 
          AND codice_edizione = ?
      ");
      $check->bind_param("si", $cf_p, $cod_ed);
      $check->execute();
      $result = $check->get_result();
      $check->close();

      if ($result->num_rows > 0) {
        $feedback = [
          'show' => true,
          'type' => 'warning',
          'message' => 'Il partecipante è già iscritto a questa edizione',
          'details' => ['Usa "Aggiorna" per modificare il voto']
        ];
      } else {
        $stmt = $mysqli->prepare("
          INSERT INTO Partecipazioni (cf_partecipante, codice_edizione, voto) 
          VALUES (?, ?, ?)
        ");
        // cf = string, edizione = integer, voto = integer (o NULL)
        $stmt->bind_param("sii", $cf_p, $cod_ed, $voto);
        if ($stmt->execute()) {
          // redirect POST→GET per aggiornare la lista
          $qs = [];
          if ($filtro_cf) {
            $qs[] = 'cf=' . urlencode($filtro_cf);
          } elseif ($filtro_edizione) {
            $qs[] = 'edizione=' . urlencode($filtro_edizione);
          }
          header('Location: partecipazioni.php' . (count($qs) ? '?' . implode('&', $qs) : ''));
          exit;
        } else {
          $feedback = [
            'show' => true,
            'type' => 'danger',
            'message' => 'Errore durante l\'iscrizione',
            'details' => [$mysqli->error]
          ];
        }
        $stmt->close();
      }
    }

    // — UPDATE —
    if (isset($_POST['upd'])) {
      if ($voto === null) {
        $stmt = $mysqli->prepare("
          UPDATE Partecipazioni 
          SET voto = NULL 
          WHERE cf_partecipante = ? 
            AND codice_edizione = ?
        ");
        $stmt->bind_param("si", $cf_p, $cod_ed);
      } else {
        $stmt = $mysqli->prepare("
          UPDATE Partecipazioni 
          SET voto = ? 
          WHERE cf_partecipante = ? 
            AND codice_edizione = ?
        ");
        $stmt->bind_param("isi", $voto, $cf_p, $cod_ed);
      }
      if ($stmt->execute()) {
        // redirect POST→GET per aggiornare la lista
        $qs = [];
        if ($filtro_cf) {
          $qs[] = 'cf=' . urlencode($filtro_cf);
        } elseif ($filtro_edizione) {
          $qs[] = 'edizione=' . urlencode($filtro_edizione);
        }
        header('Location: partecipazioni.php' . (count($qs) ? '?' . implode('&', $qs) : ''));
        exit;
      } else {
        $feedback = [
          'show' => true,
          'type' => 'danger',
          'message' => 'Errore durante l\'aggiornamento',
          'details' => [$mysqli->error]
        ];
      }
      $stmt->close();
    }

    // — DELETE —
    if (isset($_POST['del'])) {
      $stmt = $mysqli->prepare("
        DELETE FROM Partecipazioni 
        WHERE cf_partecipante = ? 
          AND codice_edizione = ?
      ");
      $stmt->bind_param("si", $cf_p, $cod_ed);
      if ($stmt->execute()) {
        // redirect POST→GET per aggiornare la lista
        $qs = [];
        if ($filtro_cf) {
          $qs[] = 'cf=' . urlencode($filtro_cf);
        } elseif ($filtro_edizione) {
          $qs[] = 'edizione=' . urlencode($filtro_edizione);
        }
        header('Location: partecipazioni.php' . (count($qs) ? '?' . implode('&', $qs) : ''));
        exit;
      } else {
        $feedback = [
          'show' => true,
          'type' => 'danger',
          'message' => 'Errore durante la rimozione',
          'details' => [$mysqli->error]
        ];
      }
      $stmt->close();
    }
  } else {
    // feedback errori di validazione
    $feedback = [
      'show' => true,
      'type' => 'danger',
      'message' => 'Si sono verificati degli errori:',
      'details' => $errors
    ];
  }
}

// Recupera elenco partecipanti
$part_query = "
  SELECT cf, CONCAT(nome, ' ', cognome) AS nome_completo 
  FROM Partecipanti 
  ORDER BY cognome, nome
";
$parts = $mysqli->query($part_query);

// Recupera elenco edizioni con corsi e docenti
$ed_query = "
  SELECT e.codice, 
         CONCAT(c.titolo, ' (', e.codice, ')') AS titolo_con_codice,
         CONCAT(d.nome, ' ', d.cognome) AS docente
  FROM Edizioni e
  JOIN Corsi c ON e.codice_corso = c.codice
  LEFT JOIN Docenti d ON e.cf_docente = d.cf
  ORDER BY e.codice DESC
";
$eds = $mysqli->query($ed_query);

// Recupera partecipazioni esistenti
$pars_query = "
  SELECT p.cf_partecipante, 
         p.codice_edizione, 
         p.voto,
         CONCAT(pa.nome, ' ', pa.cognome) AS partecipante,
         c.titolo AS corso,
         e.codice_corso,
         CONCAT(d.nome, ' ', d.cognome) AS docente
  FROM Partecipazioni p
  JOIN Partecipanti pa ON p.cf_partecipante = pa.cf
  JOIN Edizioni e ON p.codice_edizione = e.codice
  JOIN Corsi c ON e.codice_corso = c.codice
  LEFT JOIN Docenti d ON e.cf_docente = d.cf
";
$where_clauses = [];
if ($partecipante_filtrato) {
  $where_clauses[] = "p.cf_partecipante = '" . $mysqli->real_escape_string($partecipante_filtrato['cf']) . "'";
}
if ($edizione_filtrata) {
  $where_clauses[] = "p.codice_edizione = " . (int)$edizione_filtrata['codice'];
}
if (!empty($where_clauses)) {
  $pars_query .= " WHERE " . implode(" AND ", $where_clauses);
}
$pars_query .= " ORDER BY partecipante, p.codice_edizione";
$pars = $mysqli->query($pars_query);

// Statistiche
$stat_query = "
  SELECT 
    COUNT(*) AS totale_partecipazioni,
    COUNT(DISTINCT cf_partecipante) AS partecipanti_iscritti,
    COUNT(DISTINCT codice_edizione) AS edizioni_con_partecipanti,
    COUNT(voto) AS partecipazioni_con_voto,
    ROUND(AVG(voto), 1) AS media_voti
  FROM Partecipazioni
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
  <?php if ($partecipante_filtrato || $edizione_filtrata): ?>
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="partecipazioni.php">Partecipazioni</a></li>
        <?php if ($partecipante_filtrato): ?>
          <li class="breadcrumb-item active" aria-current="page">
            <?= htmlspecialchars($partecipante_filtrato['nome'] . ' ' . $partecipante_filtrato['cognome']) ?>
          </li>
        <?php endif; ?>
        <?php if ($edizione_filtrata): ?>
          <li class="breadcrumb-item active" aria-current="page">
            Edizione <?= $edizione_filtrata['codice'] ?> - <?= htmlspecialchars($edizione_filtrata['titolo']) ?>
          </li>
        <?php endif; ?>
      </ol>
    </nav>
  <?php endif; ?>

  <h2 class="section-header">
    <?php if ($partecipante_filtrato): ?>
      Partecipazioni di <?= htmlspecialchars($partecipante_filtrato['nome'] . ' ' . $partecipante_filtrato['cognome']) ?>
    <?php elseif ($edizione_filtrata): ?>
      Partecipanti all'edizione <?= $edizione_filtrata['codice'] ?> - <?= htmlspecialchars($edizione_filtrata['titolo']) ?>
    <?php else: ?>
      Gestione Partecipazioni
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
      <strong><?= htmlspecialchars($feedback['message']) ?></strong>
      <?php if (!empty($feedback['details'])): ?>
        <ul class="mb-0 mt-2">
          <?php foreach ($feedback['details'] as $detail): ?>
            <li><?= htmlspecialchars($detail) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-8">
      <!-- Form per gestire le partecipazioni -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-user-check me-2"></i>Gestisci Partecipazioni
          </h4>
        </div>
        <div class="card-body p-4">
          <form method="post" action="partecipazioni.php" class="row g-3" id="partecipazioniForm">
            <div class="col-md-6">
              <label for="cf_partecipante" class="form-label">Partecipante</label>
              <select name="cf_partecipante" id="cf_partecipante" class="form-select" <?= $partecipante_filtrato ? 'disabled' : '' ?> required>
                <?php if (!$partecipante_filtrato): ?>
                  <option value="" selected disabled>Seleziona un partecipante</option>
                <?php endif; ?>
                <?php if ($parts && $parts->num_rows > 0): ?>
                  <?php while($p = $parts->fetch_assoc()): ?>
                    <option value="<?= $p['cf'] ?>" <?= ($partecipante_filtrato && $p['cf'] === $partecipante_filtrato['cf']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['nome_completo']) ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
              <?php if ($partecipante_filtrato): ?>
                <input type="hidden" name="cf_partecipante" value="<?= htmlspecialchars($partecipante_filtrato['cf']) ?>">
              <?php endif; ?>
            </div>
            
            <div class="col-md-6">
              <label for="codice_edizione" class="form-label">Edizione</label>
              <select name="codice_edizione" id="codice_edizione" class="form-select" <?= $edizione_filtrata ? 'disabled' : '' ?> required>
                <?php if (!$edizione_filtrata): ?>
                  <option value="" selected disabled>Seleziona un'edizione</option>
                <?php endif; ?>
                <?php if ($eds && $eds->num_rows > 0): ?>
                  <?php while($e = $eds->fetch_assoc()): ?>
                    <option value="<?= $e['codice'] ?>" <?= ($edizione_filtrata && $e['codice'] == $edizione_filtrata['codice']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($e['titolo_con_codice']) ?><?php if ($e['docente']): ?> — <?= htmlspecialchars($e['docente']) ?><?php endif; ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
              <?php if ($edizione_filtrata): ?>
                <input type="hidden" name="codice_edizione" value="<?= (int)$edizione_filtrata['codice'] ?>">
              <?php endif; ?>
            </div>
            
            <div class="col-md-4">
              <label for="voto" class="form-label">Voto</label>
              <div class="input-group">
                <input type="number" id="voto" name="voto" class="form-control" min="0" max="30" placeholder="es. 28">
                <span class="input-group-text">/30</span>
              </div>
            </div>
            
            <div class="col-12 mt-5 d-flex gap-2">
              <button type="submit" name="add" class="btn btn-success flex-grow-1">
                <i class="fa-solid fa-user-plus me-1"></i>Iscrivi
              </button>
              <button type="submit" name="upd" class="btn btn-warning flex-grow-1">
                <i class="fa-solid fa-pen me-1"></i>Aggiorna Voto
              </button>
              <button type="submit" name="del" class="btn btn-danger flex-grow-1" onclick="return confirm('Sei sicuro di voler rimuovere questa iscrizione?')">
                <i class="fa-solid fa-user-minus me-1"></i>Rimuovi
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Tabella partecipazioni -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fa-solid fa-list me-2"></i>Elenco Partecipazioni
            </h5>
            <?php if ($pars): ?>
              <span class="badge bg-primary rounded-pill">
                <?= $pars->num_rows ?> risultati
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body p-0">
          <?php if (!$pars || $pars->num_rows === 0): ?>
            <div class="p-4 text-center">
              <i class="fa-solid fa-info-circle text-muted fa-2x mb-3"></i>
              <p class="mb-0">
                <?php if ($partecipante_filtrato): ?>
                  Questo partecipante non è iscritto a nessuna edizione. Usa il form sopra per iscriverlo.
                <?php elseif ($edizione_filtrata): ?>
                  Non ci sono partecipanti iscritti a questa edizione. Usa il form sopra per iscrivere partecipanti.
                <?php else: ?>
                  Non ci sono partecipazioni registrate. Usa il form sopra per iscrivere un partecipante a un'edizione.
                <?php endif; ?>
              </p>
            </div>
          <?php else: ?>
            <!-- Filtro tabella -->
            <div class="p-3 border-bottom">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" placeholder="Filtra partecipazioni...">
              </div>
            </div>
            
            <div class="table-responsive text-center">
              <table class="table table-hover mb-0" id="partecipazioniTable">
                <thead>
                  <tr>
                    <?php if (!$partecipante_filtrato): ?><th>Partecipante</th><?php endif; ?>
                    <?php if (!$edizione_filtrata): ?><th>Edizione</th><th>Corso</th><?php endif; ?>
                    <th>Voto</th><th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while($r = $pars->fetch_assoc()): ?>
                  <tr>
                    <?php if (!$partecipante_filtrato): ?>
                      <td>
                        <a href="partecipazioni.php?cf=<?= urlencode($r['cf_partecipante']) ?>" class="text-decoration-none">
                          <?= htmlspecialchars($r['partecipante']) ?>
                        </a>
                      </td>
                    <?php endif; ?>
                    <?php if (!$edizione_filtrata): ?>
                      <td>
                        <a href="partecipazioni.php?edizione=<?= urlencode($r['codice_edizione']) ?>" class="text-decoration-none">
                          <span class="badge bg-secondary"><?= htmlspecialchars($r['codice_edizione']) ?></span>
                        </a>
                      </td>
                      <td><?= htmlspecialchars($r['corso']) ?></td>
                    <?php endif; ?>
                    <td>
                      <?php if ($r['voto'] !== null): ?>
                        <span class="badge <?= $r['voto'] >= 18 ? 'bg-success' : 'bg-danger' ?>">
                          <?= htmlspecialchars($r['voto']) ?>/30
                        </span>
                      <?php else: ?>
                        <span class="badge bg-secondary">Non disponibile</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="d-flex gap-2">
<!-- Pulsante di modifica -->
<button type="button" class="btn btn-sm btn-warning update-voto-btn action-btn edit-btn" 
        data-cf="<?= htmlspecialchars($r['cf_partecipante']) ?>"
        data-edizione="<?= $r['codice_edizione'] ?>"
        data-voto="<?= $r['voto'] !== null ? $r['voto'] : '' ?>"
        data-bs-toggle="modal" data-bs-target="#updateVotoModal" title="Modifica">
  <span class="unicode-icon">✏️</span>
</button>

<!-- Pulsante di eliminazione -->
<form method="post" action="partecipazioni.php" class="d-inline">
  <input type="hidden" name="cf_partecipante" value="<?= htmlspecialchars($r['cf_partecipante']) ?>">
  <input type="hidden" name="codice_edizione" value="<?= $r['codice_edizione'] ?>">
  <button type="submit" name="del" class="btn btn-sm btn-danger action-btn delete-btn" 
          onclick="return confirm('Sei sicuro di voler rimuovere questa iscrizione?')" title="Elimina">
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

    <div class="col-lg-4">
      <!-- Statistiche -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-chart-simple me-2"></i>Statistiche
          </h5>
        </div>
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-user-check fa-2x text-success"></i>
            </div>
            <div>
              <h6 class="mb-0">Totale Partecipazioni</h6>
              <h4 class="mb-0"><?= $statistiche['totale_partecipazioni'] ?></h4>
            </div>
          </div>
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-users fa-2x text-primary"></i>
            </div>
            <div>
              <h6 class="mb-0">Partecipanti Iscritti</h6>
              <h4 class="mb-0"><?= $statistiche['partecipanti_iscritti'] ?></h4>
            </div>
          </div>
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-calendar-days fa-2x text-warning"></i>
            </div>
            <div>
              <h6 class="mb-0">Edizioni con Partecipanti</h6>
              <h4 class="mb-0"><?= $statistiche['edizioni_con_partecipanti'] ?></h4>
            </div>
          </div>
          <div class="d-flex align-items-center">
            <div class="me-3">
              <i class="fa-solid fa-star fa-2x text-warning"></i>
            </div>
            <div>
              <h6 class="mb-0">Media Voti</h6>
              <h4 class="mb-0">
                <?php if ($statistiche['partecipazioni_con_voto'] > 0): ?>
                  <?= $statistiche['media_voti'] ?>/30
                  <small class="text-muted">(<?= $statistiche['partecipazioni_con_voto'] ?> valutazioni)</small>
                <?php else: ?>
                  <span class="text-muted">N/D</span>
                <?php endif; ?>
              </h4>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Informazioni -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-circle-info me-2"></i>Informazioni
          </h5>
        </div>
        <div class="card-body">
          <h6>Cosa sono le partecipazioni?</h6>
          <p>Le partecipazioni rappresentano le iscrizioni dei partecipanti alle edizioni dei corsi, con i relativi voti.</p>
          <h6>Come gestire le partecipazioni?</h6>
          <p>Usa il form in questa pagina per:</p>
          <ul>
            <li><strong>Iscrivere</strong> un partecipante a un'edizione</li>
            <li><strong>Aggiornare</strong> il voto di un partecipante</li>
            <li><strong>Rimuovere</strong> l'iscrizione di un partecipante</li>
          </ul>
          <div class="alert alert-info mt-3 mb-0">
            <i class="fa-solid fa-lightbulb me-2"></i>
            <strong>Suggerimento:</strong> Il voto può essere inserito in un secondo momento, dopo che il partecipante ha completato il corso.
          </div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
          <?php if ($partecipante_filtrato): ?>
            <a href="coming_soon.php?section=Partecipanti" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-users me-2"></i>Tutti i Partecipanti
            </a>
          <?php elseif ($edizione_filtrata): ?>
            <a href="coming_soon.php?section=Edizioni" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-calendar-days me-2"></i>Tutte le Edizioni
            </a>
          <?php else: ?>
            <a href="coming_soon.php?section=Partecipanti" class="btn btn-outline-primary flex-grow-1">
              <i class="fa-solid fa-users me-1"></i>Partecipanti
            </a>
            <a href="coming_soon.php?section=Edizioni" class="btn btn-outline-primary flex-grow-1">
              <i class="fa-solid fa-calendar-days me-1"></i>Edizioni
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal per modifica rapida voto -->
  <div class="modal fade" id="updateVotoModal" tabindex="-1" aria-labelledby="updateVotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title" id="updateVotoModalLabel">
            <i class="fa-solid fa-pen me-2"></i>Aggiorna Voto
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" action="partecipazioni.php">
          <div class="modal-body">
            <input type="hidden" name="cf_partecipante" id="modalCf">
            <input type="hidden" name="codice_edizione" id="modalEdizione">
            <div class="mb-3">
              <label for="modalVoto" class="form-label">Voto</label>
              <div class="input-group">
                <input type="number" id="modalVoto" name="voto" class="form-control" min="0" max="30">
                <span class="input-group-text">/30</span>
              </div>
              <div class="form-text">Per rimuovere il voto, lasciare vuoto il campo.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            <button type="submit" name="upd" class="btn btn-warning">
              <i class="fa-solid fa-save me-1"></i>Salva Voto
            </button>
          </div>
        </form>
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
      const term = this.value.toLowerCase();
      document.querySelectorAll('#partecipazioniTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }

  // Popola modal con dati esistenti
  document.querySelectorAll('.update-voto-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('modalCf').value = btn.dataset.cf;
      document.getElementById('modalEdizione').value = btn.dataset.edizione;
      document.getElementById('modalVoto').value = btn.dataset.voto;
    });
  });

  // Validazione client del form principale
  const form = document.getElementById('partecipazioniForm');
  if (form) {
    form.addEventListener('submit', e => {
      const p = document.getElementById('cf_partecipante');
      const eSel = document.getElementById('codice_edizione');
      if ((!p.disabled && p.value === '') || (!eSel.disabled && eSel.value === '')) {
        e.preventDefault();
        alert('Seleziona sia un partecipante che un\'edizione');
      }
    });
  }
});
</script>
</body>
</html>
