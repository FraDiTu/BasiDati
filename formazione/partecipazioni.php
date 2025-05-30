<?php
/**
 * File: partecipazioni.php
 * Descrizione: Gestione delle iscrizioni dei partecipanti alle edizioni dei corsi
 * 
 * Questo file permette di:
 * - Visualizzare tutte le partecipazioni o filtrarle per partecipante/edizione
 * - Iscrivere partecipanti a edizioni di corsi
 * - Aggiornare i voti dei partecipanti
 * - Rimuovere iscrizioni
 * - Visualizzare statistiche sulle partecipazioni
 * 
 * La pagina supporta tre modalità di visualizzazione:
 * 1. Tutte le partecipazioni (vista generale)
 * 2. Partecipazioni di un singolo partecipante (filtro per CF)
 * 3. Partecipanti di una singola edizione (filtro per codice edizione)
 */

// Imposta il titolo della pagina
$pageTitle = 'Gestione Partecipazioni';

// Include i file necessari
require_once 'db_connect.php';
require_once 'utils.php';

// === SISTEMA DI FEEDBACK PER LE OPERAZIONI ===
// Array strutturato per gestire messaggi di feedback all'utente
$feedback = [
  'show' => false,      // Flag per mostrare/nascondere il messaggio
  'type' => '',         // Tipo di messaggio (success, danger, warning, info)
  'message' => '',      // Messaggio principale
  'details' => []       // Array di dettagli aggiuntivi (per errori multipli)
];

// === GESTIONE FILTRO PARTECIPANTE SPECIFICO ===
$partecipante_filtrato = null;
$filtro_cf = $_GET['cf'] ?? null; // Recupera il parametro CF dalla query string

// Se è stato specificato un CF per filtrare
if ($filtro_cf) {
  // Query sicura per recuperare i dati del partecipante
  $part_query = $mysqli->prepare("SELECT cf, cognome FROM Partecipante WHERE cf = ?");
  $part_query->bind_param("s", $filtro_cf);
  $part_query->execute();
  $result = $part_query->get_result();
  
  // Se il partecipante esiste, salva i suoi dati
  if ($result->num_rows > 0) {
    $partecipante_filtrato = $result->fetch_assoc();
  }
  $part_query->close();
}

// === GESTIONE FILTRO EDIZIONE SPECIFICA ===
$edizione_filtrata = null;
$filtro_edizione = $_GET['edizione'] ?? null; // Recupera il parametro edizione dalla query string

// Se è stato specificato un codice edizione per filtrare
if ($filtro_edizione) {
  // Query per recuperare informazioni complete dell'edizione
  $ed_query = $mysqli->prepare("
    SELECT e.codice, c.titolo, d.cognome AS docente_cognome
    FROM Edizione e
    JOIN Corso c ON e.corso = c.codice           -- Unisce per ottenere il titolo del corso
    LEFT JOIN Docente d ON e.docente = d.cf      -- LEFT JOIN perché il docente potrebbe essere NULL
    WHERE e.codice = ?
  ");
  $ed_query->bind_param("s", $filtro_edizione);
  $ed_query->execute();
  $result = $ed_query->get_result();
  
  // Se l'edizione esiste, salva i suoi dati
  if ($result->num_rows > 0) {
    $edizione_filtrata = $result->fetch_assoc();
  }
  $ed_query->close();
}

// === GESTIONE FORM POST ===
// Verifica se è stata inviata una richiesta POST (invio del form)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  
  // === RECUPERO DATI DAL FORM ===
  $cf_p = $_POST['cf_partecipante'] ?? '';     // CF del partecipante
  $cod_ed = $_POST['codice_edizione'] ?? '';   // Codice dell'edizione
  
  // Gestione del voto: se il campo è vuoto, imposta NULL, altrimenti converte a intero
  $voto = isset($_POST['voto']) && $_POST['voto'] !== '' ? (int)$_POST['voto'] : null;
  
  // === VALIDAZIONE DATI ===
  $errors = [];
  
  if (!$cf_p) {
    $errors[] = "Seleziona un partecipante";
  }
  if (!$cod_ed) {
    $errors[] = "Seleziona un'edizione valida";
  }
  // Validazione range voto (0-30 è il sistema di valutazione universitario italiano)
  if ($voto !== null && ($voto < 0 || $voto > 30)) {
    $errors[] = "Il voto deve essere compreso tra 0 e 30";
  }

  // Procede solo se non ci sono errori di validazione
  if (empty($errors)) {
    
    // === OPERAZIONE DI INSERIMENTO (ISCRIZIONE) ===
    if (isset($_POST['add'])) {
      // Verifica se esiste già questa partecipazione per evitare duplicati
      $check = $mysqli->prepare("
        SELECT 1 
        FROM Partecipazione 
        WHERE partecipante = ? 
          AND edizione = ?
      ");
      $check->bind_param("ss", $cf_p, $cod_ed);
      $check->execute();
      $result = $check->get_result();
      $check->close();

      if ($result->num_rows > 0) {
        // La partecipazione esiste già
        $feedback = [
          'show' => true,
          'type' => 'warning',
          'message' => 'Il partecipante è già iscritto a questa edizione',
          'details' => ['Usa "Aggiorna" per modificare il voto']
        ];
      } else {
        // Inserisce la nuova partecipazione
        $stmt = $mysqli->prepare("
          INSERT INTO Partecipazione (partecipante, edizione, votazione) 
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param("ssi", $cf_p, $cod_ed, $voto);
        
        if ($stmt->execute()) {
          // === REDIRECT POST→GET ===
          // Reindirizza per evitare re-invio del form e aggiornare la lista
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

    // === OPERAZIONE DI AGGIORNAMENTO VOTO ===
    if (isset($_POST['upd'])) {
      $stmt = $mysqli->prepare("
        UPDATE Partecipazione 
        SET votazione = ? 
        WHERE partecipante = ? 
          AND edizione = ?
      ");
      $stmt->bind_param("iss", $voto, $cf_p, $cod_ed);
      
      if ($stmt->execute()) {
        // === REDIRECT POST→GET ===
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

    // === OPERAZIONE DI ELIMINAZIONE ===
    if (isset($_POST['del'])) {
      $stmt = $mysqli->prepare("
        DELETE FROM Partecipazione 
        WHERE partecipante = ? 
          AND edizione = ?
      ");
      $stmt->bind_param("ss", $cf_p, $cod_ed);
      
      if ($stmt->execute()) {
        // === REDIRECT POST→GET ===
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
    // === FEEDBACK ERRORI DI VALIDAZIONE ===
    $feedback = [
      'show' => true,
      'type' => 'danger',
      'message' => 'Si sono verificati degli errori:',
      'details' => $errors
    ];
  }
}

// === RECUPERO DATI PER I DROPDOWN ===

// Recupera tutti i partecipanti per il dropdown di selezione
$part_query = "
  SELECT cf, cognome 
  FROM Partecipante 
  ORDER BY cognome
";
$parts = $mysqli->query($part_query);

// Recupera tutte le edizioni con informazioni sui corsi e docenti
$ed_query = "
  SELECT e.codice, 
         CONCAT(c.titolo, ' (', e.codice, ')') AS titolo_con_codice, -- Combina titolo e codice per chiarezza
         d.cognome AS docente
  FROM Edizione e
  JOIN Corso c ON e.corso = c.codice           -- Unisce per ottenere il titolo del corso
  LEFT JOIN Docente d ON e.docente = d.cf      -- LEFT JOIN perché il docente potrebbe essere NULL
  ORDER BY e.codice DESC                       -- Ordina per codice edizione decrescente (più recenti prima)
";
$eds = $mysqli->query($ed_query);

// === RECUPERO PARTECIPAZIONI ESISTENTI ===
// Query complessa per ottenere tutte le informazioni necessarie delle partecipazioni
$pars_query = "
  SELECT p.partecipante, 
         p.edizione, 
         p.votazione,
         pa.cognome AS partecipante_cognome,     -- Cognome del partecipante
         c.titolo AS corso,                      -- Titolo del corso
         e.corso AS codice_corso,                -- Codice del corso
         d.cognome AS docente                    -- Cognome del docente (può essere NULL)
  FROM Partecipazione p
  JOIN Partecipante pa ON p.partecipante = pa.cf    -- Unisce per ottenere dati partecipante
  JOIN Edizione e ON p.edizione = e.codice          -- Unisce per ottenere dati edizione
  JOIN Corso c ON e.corso = c.codice                -- Unisce per ottenere dati corso
  LEFT JOIN Docente d ON e.docente = d.cf           -- LEFT JOIN per docente (può essere NULL)
";

// === APPLICAZIONE FILTRI ===
$where_clauses = [];

// Se stiamo filtrando per un partecipante specifico
if ($partecipante_filtrato) {
  $where_clauses[] = "p.partecipante = '" . $mysqli->real_escape_string($partecipante_filtrato['cf']) . "'";
}

// Se stiamo filtrando per un'edizione specifica
if ($edizione_filtrata) {
  $where_clauses[] = "p.edizione = '" . $mysqli->real_escape_string($edizione_filtrata['codice']) . "'";
}

// Aggiunge la clausola WHERE se ci sono filtri attivi
if (!empty($where_clauses)) {
  $pars_query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Aggiunge l'ordinamento finale
$pars_query .= " ORDER BY pa.cognome, p.edizione";

// Esegue la query delle partecipazioni
$pars = $mysqli->query($pars_query);

// === CALCOLO STATISTICHE ===
// Query per statistiche aggregate sulle partecipazioni
$stat_query = "
  SELECT 
    COUNT(*) AS totale_partecipazioni,                    -- Totale iscrizioni
    COUNT(DISTINCT partecipante) AS partecipanti_iscritti, -- Partecipanti unici iscritti
    COUNT(DISTINCT edizione) AS edizioni_con_partecipanti, -- Edizioni che hanno partecipanti
    COUNT(votazione) AS partecipazioni_con_voto,          -- Iscrizioni che hanno un voto
    ROUND(AVG(votazione), 1) AS media_voti                -- Media dei voti (arrotondata a 1 decimale)
  FROM Partecipazione
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
  
  <?php 
  // === BREADCRUMB NAVIGATION ===
  // Mostra il breadcrumb solo se stiamo filtrando per partecipante o edizione
  if ($partecipante_filtrato || $edizione_filtrata): 
  ?>
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="partecipazioni.php">Partecipazioni</a></li>
        
        <?php if ($partecipante_filtrato): ?>
          <!-- Breadcrumb per filtro partecipante -->
          <li class="breadcrumb-item active" aria-current="page">
            <?= htmlspecialchars($partecipante_filtrato['cognome']) ?>
          </li>
        <?php endif; ?>
        
        <?php if ($edizione_filtrata): ?>
          <!-- Breadcrumb per filtro edizione -->
          <li class="breadcrumb-item active" aria-current="page">
            Edizione <?= $edizione_filtrata['codice'] ?> - <?= htmlspecialchars($edizione_filtrata['titolo']) ?>
          </li>
        <?php endif; ?>
      </ol>
    </nav>
  <?php endif; ?>

  <!-- === TITOLO DINAMICO === -->
  <h2 class="section-header">
    <?php if ($partecipante_filtrato): ?>
      <!-- Titolo per vista filtrata per partecipante -->
      Partecipazioni di <?= htmlspecialchars($partecipante_filtrato['cognome']) ?>
    <?php elseif ($edizione_filtrata): ?>
      <!-- Titolo per vista filtrata per edizione -->
      Partecipanti all'edizione <?= $edizione_filtrata['codice'] ?> - <?= htmlspecialchars($edizione_filtrata['titolo']) ?>
    <?php else: ?>
      <!-- Titolo per vista generale -->
      Gestione Partecipazioni
    <?php endif; ?>
  </h2>
  
  <?php 
  // === VISUALIZZAZIONE FEEDBACK ===
  // Mostra messaggi di feedback per le operazioni eseguite
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
      
      <strong><?= htmlspecialchars($feedback['message']) ?></strong>
      
      <?php 
      // Se ci sono dettagli aggiuntivi (come lista di errori), li mostra
      if (!empty($feedback['details'])): 
      ?>
        <ul class="mb-0 mt-2">
          <?php foreach ($feedback['details'] as $detail): ?>
            <li><?= htmlspecialchars($detail) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      
      <!-- Pulsante per chiudere l'alert -->
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="row">
    
    <!-- === COLONNA PRINCIPALE (Form + Tabella) === -->
    <div class="col-lg-8">
      
      <!-- === FORM PER GESTIRE LE PARTECIPAZIONI === -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
          <h4 class="card-title mb-0">
            <i class="fa-solid fa-user-check me-2"></i>Gestisci Partecipazioni
          </h4>
        </div>
        <div class="card-body p-4">
          <!-- Form che invia in POST alla stessa pagina -->
          <form method="post" action="partecipazioni.php" class="row g-3" id="partecipazioniForm">
            
            <!-- === DROPDOWN SELEZIONE PARTECIPANTE === -->
            <div class="col-md-6">
              <label for="cf_partecipante" class="form-label">Partecipante</label>
              <!-- Disabilita il dropdown se stiamo filtrando per un partecipante specifico -->
              <select name="cf_partecipante" id="cf_partecipante" class="form-select" <?= $partecipante_filtrato ? 'disabled' : '' ?> required>
                <?php if (!$partecipante_filtrato): ?>
                  <option value="" selected disabled>Seleziona un partecipante</option>
                <?php endif; ?>
                
                <?php 
                // Popola il dropdown con tutti i partecipanti
                if ($parts && $parts->num_rows > 0): 
                  while($p = $parts->fetch_assoc()): 
                ?>
                    <option value="<?= $p['cf'] ?>" <?= ($partecipante_filtrato && $p['cf'] === $partecipante_filtrato['cf']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['cognome']) ?> (<?= htmlspecialchars($p['cf']) ?>)
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
              
              <?php 
              // Se stiamo filtrando, include un campo hidden per mantenere il valore
              if ($partecipante_filtrato): 
              ?>
                <input type="hidden" name="cf_partecipante" value="<?= htmlspecialchars($partecipante_filtrato['cf']) ?>">
              <?php endif; ?>
            </div>
            
            <!-- === DROPDOWN SELEZIONE EDIZIONE === -->
            <div class="col-md-6">
              <label for="codice_edizione" class="form-label">Edizione</label>
              <!-- Disabilita il dropdown se stiamo filtrando per un'edizione specifica -->
              <select name="codice_edizione" id="codice_edizione" class="form-select" <?= $edizione_filtrata ? 'disabled' : '' ?> required>
                <?php if (!$edizione_filtrata): ?>
                  <option value="" selected disabled>Seleziona un'edizione</option>
                <?php endif; ?>
                
                <?php 
                // Popola il dropdown con tutte le edizioni
                if ($eds && $eds->num_rows > 0): 
                  while($e = $eds->fetch_assoc()): 
                ?>
                    <option value="<?= $e['codice'] ?>" <?= ($edizione_filtrata && $e['codice'] == $edizione_filtrata['codice']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($e['titolo_con_codice']) ?><?php if ($e['docente']): ?> — <?= htmlspecialchars($e['docente']) ?><?php endif; ?>
                    </option>
                  <?php endwhile; ?>
                <?php endif; ?>
              </select>
              
              <?php 
              // Se stiamo filtrando, include un campo hidden per mantenere il valore
              if ($edizione_filtrata): 
              ?>
                <input type="hidden" name="codice_edizione" value="<?= htmlspecialchars($edizione_filtrata['codice']) ?>">
              <?php endif; ?>
            </div>
            
            <!-- === CAMPO VOTO === -->
            <div class="col-md-4">
              <label for="voto" class="form-label">Voto</label>
              <div class="input-group">
                <!-- Campo numerico con range 0-30 (sistema universitario italiano) -->
                <input type="number" id="voto" name="voto" class="form-control" min="0" max="30" placeholder="es. 28">
                <span class="input-group-text">/30</span>
              </div>
            </div>
            
            <!-- === PULSANTI DI AZIONE === -->
            <div class="col-12 mt-5 d-flex gap-2">
              <!-- Pulsante per iscrivere un partecipante -->
              <button type="submit" name="add" class="btn btn-success flex-grow-1">
                <i class="fa-solid fa-user-plus me-1"></i>Iscrivi
              </button>
              
              <!-- Pulsante per aggiornare un voto esistente -->
              <button type="submit" name="upd" class="btn btn-warning flex-grow-1">
                <i class="fa-solid fa-pen me-1"></i>Aggiorna Voto
              </button>
              
              <!-- Pulsante per rimuovere un'iscrizione con conferma JavaScript -->
              <button type="submit" name="del" class="btn btn-danger flex-grow-1" onclick="return confirm('Sei sicuro di voler rimuovere questa iscrizione?')">
                <i class="fa-solid fa-user-minus me-1"></i>Rimuovi
              </button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- === TABELLA PARTECIPAZIONI === -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
              <i class="fa-solid fa-list me-2"></i>Elenco Partecipazioni
            </h5>
            <!-- Badge con il numero di risultati -->
            <?php if ($pars): ?>
              <span class="badge bg-primary rounded-pill">
                <?= $pars->num_rows ?> risultati
              </span>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body p-0">
          <?php 
          // === GESTIONE CASO NESSUNA PARTECIPAZIONE ===
          if (!$pars || $pars->num_rows === 0): 
          ?>
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
            
            <!-- === FILTRO DI RICERCA NELLA TABELLA === -->
            <div class="p-3 border-bottom">
              <div class="input-group">
                <span class="input-group-text">
                  <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" id="searchInput" class="form-control" placeholder="Filtra partecipazioni...">
              </div>
            </div>
            
            <!-- === TABELLA RESPONSIVE === -->
            <div class="table-responsive text-center">
              <table class="table table-hover mb-0" id="partecipazioniTable">
                <thead>
                  <tr>
                    <!-- Mostra colonne diverse a seconda del filtro attivo -->
                    <?php if (!$partecipante_filtrato): ?><th>Partecipante</th><?php endif; ?>
                    <?php if (!$edizione_filtrata): ?><th>Edizione</th><th>Corso</th><?php endif; ?>
                    <th>Voto</th>
                    <th>Azioni</th>
                  </tr>
                </thead>
                <tbody>
                  <?php 
                  // === CICLO DELLE PARTECIPAZIONI ===
                  while($r = $pars->fetch_assoc()): 
                  ?>
                  <tr>
                    
                    <?php 
                    // Colonna partecipante (solo se non stiamo filtrando per partecipante)
                    if (!$partecipante_filtrato): 
                    ?>
                      <td>
                        <!-- Link per filtrare le partecipazioni di questo partecipante -->
                        <a href="partecipazioni.php?cf=<?= urlencode($r['partecipante']) ?>" class="text-decoration-none">
                          <?= htmlspecialchars($r['partecipante_cognome']) ?>
                        </a>
                      </td>
                    <?php endif; ?>
                    
                    <?php 
                    // Colonne edizione e corso (solo se non stiamo filtrando per edizione)
                    if (!$edizione_filtrata): 
                    ?>
                      <td>
                        <!-- Link per filtrare i partecipanti a questa edizione -->
                        <a href="partecipazioni.php?edizione=<?= urlencode($r['edizione']) ?>" class="text-decoration-none">
                          <span class="badge bg-secondary"><?= htmlspecialchars($r['edizione']) ?></span>
                        </a>
                      </td>
                      <td><?= htmlspecialchars($r['corso']) ?></td>
                    <?php endif; ?>
                    
                    <!-- === COLONNA VOTO === -->
                    <td>
                      <?php if ($r['votazione'] !== null): ?>
                        <!-- Badge colorato: verde se >= 18 (sufficiente), rosso se < 18 -->
                        <span class="badge <?= $r['votazione'] >= 18 ? 'bg-success' : 'bg-danger' ?>">
                          <?= htmlspecialchars($r['votazione']) ?>/30
                        </span>
                      <?php else: ?>
                        <!-- Badge grigio se non c'è voto -->
                        <span class="badge bg-secondary">Non disponibile</span>
                      <?php endif; ?>
                    </td>
                    
                    <!-- === COLONNA AZIONI === -->
                    <td>
                      <div class="d-flex gap-2 justify-content-center">
                        
                        <!-- === PULSANTE MODIFICA VOTO === -->
                        <!-- Pulsante che apre un modal per modificare rapidamente il voto -->
                        <button type="button" class="action-btn edit-btn update-voto-btn" 
                                data-cf="<?= htmlspecialchars($r['partecipante']) ?>"
                                data-edizione="<?= $r['edizione'] ?>"
                                data-voto="<?= $r['votazione'] !== null ? $r['votazione'] : '' ?>"
                                data-bs-toggle="modal" data-bs-target="#updateVotoModal" title="Modifica">
                          <span class="unicode-icon">✏️</span>
                        </button>

                        <!-- === PULSANTE ELIMINAZIONE === -->
                        <!-- Form per eliminare la partecipazione con conferma -->
                        <form method="post" action="partecipazioni.php" class="d-inline">
                          <input type="hidden" name="cf_partecipante" value="<?= htmlspecialchars($r['partecipante']) ?>">
                          <input type="hidden" name="codice_edizione" value="<?= $r['edizione'] ?>">
                          <button type="submit" name="del" class="action-btn delete-btn" 
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
          
          <!-- Statistica: Totale Partecipazioni -->
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-user-check fa-2x text-success"></i>
            </div>
            <div>
              <h6 class="mb-0">Totale Partecipazioni</h6>
              <h4 class="mb-0"><?= $statistiche['totale_partecipazioni'] ?></h4>
            </div>
          </div>
          
          <!-- Statistica: Partecipanti Iscritti -->
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-users fa-2x text-primary"></i>
            </div>
            <div>
              <h6 class="mb-0">Partecipanti Iscritti</h6>
              <h4 class="mb-0"><?= $statistiche['partecipanti_iscritti'] ?></h4>
            </div>
          </div>
          
          <!-- Statistica: Edizioni con Partecipanti -->
          <div class="d-flex align-items-center mb-3">
            <div class="me-3">
              <i class="fa-solid fa-calendar-days fa-2x text-warning"></i>
            </div>
            <div>
              <h6 class="mb-0">Edizioni con Partecipanti</h6>
              <h4 class="mb-0"><?= $statistiche['edizioni_con_partecipanti'] ?></h4>
            </div>
          </div>
          
          <!-- Statistica: Media Voti -->
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
      
      <!-- === CARD INFORMAZIONI === -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">
            <i class="fa-solid fa-circle-info me-2"></i>Informazioni
          </h5>
        </div>
        <div class="card-body">
          <!-- Sezioni informative per guidare l'utente -->
          <h6>Cosa sono le partecipazioni?</h6>
          <p>Le partecipazioni rappresentano le iscrizioni dei partecipanti alle edizioni dei corsi, con i relativi voti.</p>
          
          <h6>Come gestire le partecipazioni?</h6>
          <p>Usa il form in questa pagina per:</p>
          <ul>
            <li><strong>Iscrivere</strong> un partecipante a un'edizione</li>
            <li><strong>Aggiornare</strong> il voto di un partecipante</li>
            <li><strong>Rimuovere</strong> l'iscrizione di un partecipante</li>
          </ul>
          
          <!-- Alert informativo -->
          <div class="alert alert-info mt-3 mb-0">
            <i class="fa-solid fa-lightbulb me-2"></i>
            <strong>Suggerimento:</strong> Il voto può essere inserito in un secondo momento, dopo che il partecipante ha completato il corso.
          </div>
        </div>
        
        <!-- === FOOTER CON LINK DI NAVIGAZIONE === -->
        <div class="card-footer bg-transparent d-flex gap-2">
          <?php if ($partecipante_filtrato): ?>
            <!-- Se stiamo filtrando per partecipante, mostra link a tutti i partecipanti -->
            <a href="coming_soon.php?section=Partecipanti" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-users me-2"></i>Tutti i Partecipanti
            </a>
          <?php elseif ($edizione_filtrata): ?>
            <!-- Se stiamo filtrando per edizione, mostra link a tutte le edizioni -->
            <a href="coming_soon.php?section=Edizioni" class="btn btn-outline-primary w-100">
              <i class="fa-solid fa-calendar-days me-2"></i>Tutte le Edizioni
            </a>
          <?php else: ?>
            <!-- Vista generale: mostra entrambi i link -->
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

  <!-- === MODAL PER MODIFICA RAPIDA VOTO === -->
  <!-- Modal Bootstrap per permettere modifica rapida del voto senza ricaricare la pagina -->
  <div class="modal fade" id="updateVotoModal" tabindex="-1" aria-labelledby="updateVotoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header bg-warning text-white">
          <h5 class="modal-title" id="updateVotoModalLabel">
            <i class="fa-solid fa-pen me-2"></i>Aggiorna Voto
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <!-- Form all'interno del modal -->
        <form method="post" action="partecipazioni.php">
          <div class="modal-body">
            <!-- Campi hidden per identificare la partecipazione da modificare -->
            <input type="hidden" name="cf_partecipante" id="modalCf">
            <input type="hidden" name="codice_edizione" id="modalEdizione">
            
            <!-- Campo per il nuovo voto -->
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

<!-- === JAVASCRIPT PER INTERATTIVITÀ === -->
<script>
// Attende che il DOM sia completamente caricato
document.addEventListener('DOMContentLoaded', function() {
  
  // === FUNZIONALITÀ DI FILTRO TABELLA ===
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    // Aggiunge un listener per la ricerca in tempo reale
    searchInput.addEventListener('input', function() {
      const term = this.value.toLowerCase(); // Converte il termine in minuscolo
      // Seleziona tutte le righe della tabella partecipazioni
      document.querySelectorAll('#partecipazioniTable tbody tr').forEach(row => {
        // Mostra/nasconde la riga a seconda che contenga il termine di ricerca
        row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
      });
    });
  }

  // === POPOLAMENTO MODAL CON DATI ESISTENTI ===
  // Quando si clicca su un pulsante "modifica voto", popola il modal con i dati attuali
  document.querySelectorAll('.update-voto-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      // Recupera i dati dal pulsante tramite data attributes
      document.getElementById('modalCf').value = btn.dataset.cf;
      document.getElementById('modalEdizione').value = btn.dataset.edizione;
      document.getElementById('modalVoto').value = btn.dataset.voto;
    });
  });

  // === VALIDAZIONE CLIENT DEL FORM PRINCIPALE ===
  const form = document.getElementById('partecipazioniForm');
  if (form) {
    // Aggiunge validazione prima dell'invio del form
    form.addEventListener('submit', e => {
      const p = document.getElementById('cf_partecipante');
      const eSel = document.getElementById('codice_edizione');
      
      // Controlla che entrambi i campi obbligatori siano selezionati
      // (solo se non sono disabilitati per filtri attivi)
      if ((!p.disabled && p.value === '') || (!eSel.disabled && eSel.value === '')) {
        e.preventDefault(); // Impedisce l'invio del form
        alert('Seleziona sia un partecipante che un\'edizione');
      }
    });
  }
});
</script>

<!-- === CSS PERSONALIZZATO === -->
<style>
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
    transform: translateY(-3px); /* Solleva il pulsante */
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
    background-color: var(--primary-color); /* Blu per modifica */
  }

  .edit-btn:hover {
    background-color: #3551db; /* Blu più scuro al hover */
  }

  .delete-btn {
    background-color: var(--danger-color); /* Rosso per eliminazione */
  }

  .delete-btn:hover {
    background-color: #d92b39; /* Rosso più scuro al hover */
  }

  .details-btn {
    background-color: var(--info-color); /* Celeste per dettagli */
  }

  .details-btn:hover {
    background-color: #33bfe9; /* Celeste più scuro al hover */
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
      font-size: 1.1rem; /* Aumenta leggermente l'icona */
    }
  }
</style>

</body>
</html>
