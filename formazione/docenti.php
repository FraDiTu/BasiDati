<?php
/**
 * File: docenti.php
 * Descrizione: Pagina principale per la gestione dei docenti
 * 
 * Questo file permette di:
 * - Visualizzare una lista completa di tutti i docenti
 * - Vedere informazioni aggregate per ogni docente (abilitazioni, edizioni, telefoni)
 * - Filtrare e cercare tra i docenti
 * - Accedere alle azioni di modifica ed eliminazione
 * - Visualizzare statistiche aggregate sui docenti
 */

// Imposta il titolo della pagina
$pageTitle = 'Gestione Docenti';

// Include i file necessari
require_once 'db_connect.php';
require_once 'utils.php';

// === QUERY PRINCIPALE PER RECUPERARE TUTTI I DOCENTI ===
// Query complessa che usa LEFT JOIN per ottenere informazioni aggregate
// senza perdere docenti che non hanno abilitazioni, edizioni o telefoni
$result = $mysqli->query("
  SELECT d.cf, d.cognome, d.cittaNascita, d.tipo,
         COUNT(DISTINCT a.corso) AS num_abilitazioni,           -- Conta i corsi per cui il docente è abilitato
         COUNT(DISTINCT e.codice) AS num_edizioni,              -- Conta le edizioni che il docente insegna
         GROUP_CONCAT(DISTINCT t.numero SEPARATOR ', ') AS telefoni -- Concatena tutti i telefoni del docente
  FROM Docente d
  LEFT JOIN Abilitazione a ON d.cf = a.docente                 -- LEFT JOIN per mantenere docenti senza abilitazioni
  LEFT JOIN Edizione e ON d.cf = e.docente                     -- LEFT JOIN per mantenere docenti senza edizioni
  LEFT JOIN Telefono t ON d.cf = t.docente                     -- LEFT JOIN per mantenere docenti senza telefoni
  GROUP BY d.cf                                                 -- Raggruppa per docente per le funzioni aggregate
  ORDER BY d.cognome                                            -- Ordina alfabeticamente per cognome
");

// === PREPARAZIONE ARRAY RISULTATI ===
// Converte il risultato della query in un array PHP per facilità di manipolazione
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
  
  <!-- === HEADER DELLA PAGINA === -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="section-header mb-0">Gestione Docenti</h2>
    <!-- Pulsante per aggiungere un nuovo docente -->
    <a href="add_docente.php" class="btn btn-primary">
      <i class="fa-solid fa-plus me-2"></i>Nuovo Docente
    </a>
  </div>
  
  <?php 
  // === GESTIONE CASO LISTA VUOTA ===
  // Se non ci sono docenti nel database, mostra un messaggio informativo
  if (empty($docenti)): 
  ?>
    <div class="alert alert-info">
      <i class="fa-solid fa-info-circle me-2"></i>
      Nessun docente presente nel database. Aggiungi il primo docente utilizzando il pulsante "Nuovo Docente".
    </div>
  <?php else: ?>
    
    <!-- === BARRA FILTRI E RICERCA === -->
    <div class="card mb-4 border-0 shadow-sm">
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          
          <!-- === CAMPO DI RICERCA === -->
          <div class="col-md-8">
            <div class="input-group">
              <span class="input-group-text">
                <i class="fa-solid fa-search"></i>
              </span>
              <!-- Campo di input per la ricerca in tempo reale -->
              <input type="text" id="searchInput" class="form-control" placeholder="Filtra docenti...">
            </div>
          </div>
          
          <!-- === PULSANTI FILTRO === -->
          <div class="col-md-4 text-md-end">
            <div class="btn-group filter-group" role="group">
              <!-- Pulsante per visualizzare tutti i docenti -->
              <button type="button" class="btn btn-outline-primary active" id="viewAll">
                <i class="fa-solid fa-list me-1"></i>Tutti
              </button>
              <!-- Pulsante per visualizzare solo docenti con abilitazioni -->
              <button type="button" class="btn btn-outline-primary" id="viewActive">
                <i class="fa-solid fa-check-circle me-1"></i>Attivi
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- === TABELLA PRINCIPALE DEI DOCENTI === -->
    <div class="table-wrapper text-center">
      <table class="table table-hover" id="docentiTable">
        <thead>
          <tr>
            <th>CF</th>                    <!-- Codice Fiscale -->
            <th>Cognome</th>               <!-- Cognome del docente -->
            <th>Città Nascita</th>         <!-- Città di nascita -->
            <th>Tipo</th>                  <!-- Interno o Consulente -->
            <th>Telefoni</th>              <!-- Numeri di telefono -->
            <th>Corsi Abilitati</th>       <!-- Numero di abilitazioni -->
            <th>Edizioni</th>              <!-- Numero di edizioni insegnate -->
            <th>Azioni</th>                <!-- Pulsanti per azioni -->
          </tr>
        </thead>
        <tbody>
          <?php 
          // === CICLO PER OGNI DOCENTE ===
          // Itera attraverso l'array dei docenti per creare le righe della tabella
          foreach ($docenti as $d): 
          ?>
          <!-- data-abilitazioni usato per il filtro JavaScript -->
          <tr data-abilitazioni="<?= $d['num_abilitazioni'] ?>">
            
            <!-- === COLONNA CODICE FISCALE === -->
            <td>
              <!-- Visualizza il CF come badge per evidenziarlo -->
              <span class="badge bg-secondary"><?= htmlspecialchars($d['cf']) ?></span>
            </td>
            
            <!-- === COLONNA COGNOME === -->
            <td><?= htmlspecialchars($d['cognome']) ?></td>
            
            <!-- === COLONNA CITTÀ DI NASCITA === -->
            <td><?= htmlspecialchars($d['cittaNascita']) ?></td>
            
            <!-- === COLONNA TIPO DOCENTE === -->
            <td>
              <!-- Badge colorato diversamente a seconda del tipo -->
              <span class="badge <?= $d['tipo'] === 'I' ? 'bg-info' : 'bg-warning' ?>">
                <?= $d['tipo'] === 'I' ? 'Interno' : 'Consulente' ?>
              </span>
            </td>
            
            <!-- === COLONNA TELEFONI === -->
            <td>
              <?php if ($d['telefoni']): ?>
                <?php 
                // Se ci sono telefoni, li divide e crea link cliccabili
                foreach (explode(', ', $d['telefoni']) as $tel): 
                ?>
                  <!-- Link tel: permette di chiamare direttamente su dispositivi mobili -->
                  <a href="tel:<?= htmlspecialchars($tel) ?>" class="text-decoration-none">
                    <i class="fa-solid fa-phone me-1 text-success"></i><?= htmlspecialchars($tel) ?>
                  </a><br>
                <?php endforeach; ?>
              <?php else: ?>
                <!-- Messaggio quando non ci sono telefoni -->
                <span class="text-muted fst-italic">Non disponibile</span>
              <?php endif; ?>
            </td>
            
            <!-- === COLONNA NUMERO ABILITAZIONI === -->
            <td class="text-center">
              <!-- Badge colorato: verde se ha abilitazioni, grigio se non ne ha -->
              <span class="badge <?= $d['num_abilitazioni'] > 0 ? 'bg-success' : 'bg-secondary' ?> rounded-pill count-badge">
                <?= $d['num_abilitazioni'] ?>
              </span>
            </td>
            
            <!-- === COLONNA NUMERO EDIZIONI === -->
            <td class="text-center">
              <!-- Badge colorato: blu se insegna edizioni, grigio se non ne ha -->
              <span class="badge <?= $d['num_edizioni'] > 0 ? 'bg-primary' : 'bg-secondary' ?> rounded-pill count-badge">
                <?= $d['num_edizioni'] ?>
              </span>
            </td>
            
            <!-- === COLONNA AZIONI === -->
            <td class="text-center">
              <div class="action-buttons">
                
                <!-- === PULSANTE MODIFICA === -->
                <!-- Link alla pagina di modifica del docente -->
                <a href="edit_docente.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn edit-btn" title="Modifica">
                  <span class="unicode-icon">✏️</span>
                </a>
                
                <!-- === PULSANTE ELIMINA === -->
                <!-- Link alla pagina di eliminazione del docente -->
                <a href="delete_docente.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn delete-btn" title="Elimina">
                  <span class="unicode-icon">🗑️</span>
                </a>
                
                <!-- === PULSANTE DETTAGLI ABILITAZIONI === -->
                <!-- Mostra solo se il docente ha abilitazioni -->
                <?php if ($d['num_abilitazioni'] > 0): ?>
                <a href="abilitazioni.php?cf=<?= urlencode($d['cf']) ?>" class="action-btn details-btn" title="Abilitazioni">
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
    
    <!-- === SEZIONE STATISTICHE === -->
    <div class="card mt-4 shadow-sm border-0">
      <div class="card-header bg-light border-0">
        <h5 class="card-title mb-0">
          <i class="fa-solid fa-chart-simple text-primary me-2"></i>Statistiche Docenti
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-4">
          
          <!-- === STATISTICA: TOTALE DOCENTI === -->
          <div class="col-md-3">
            <div class="stat-card bg-light-blue rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Totale Docenti</h6>
              <h2 class="display-4 fw-bold mb-0"><?= count($docenti) ?></h2>
              <!-- Barra di progresso sempre al 100% per il totale -->
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
              </div>
            </div>
          </div>
          
          <!-- === STATISTICA: DOCENTI CON ABILITAZIONI === -->
          <div class="col-md-3">
            <div class="stat-card bg-light-green rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Con Abilitazioni</h6>
              <?php
              // Calcola quanti docenti hanno almeno un'abilitazione
              $docentiConAbilitazioni = array_filter($docenti, function($d) {
                return $d['num_abilitazioni'] > 0;
              });
              ?>
              <h2 class="display-4 fw-bold mb-0"><?= count($docentiConAbilitazioni) ?></h2>
              <!-- Barra di progresso proporzionale alla percentuale -->
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= count($docenti) > 0 ? (count($docentiConAbilitazioni) / count($docenti) * 100) : 0 ?>%"></div>
              </div>
            </div>
          </div>
          
          <!-- === STATISTICA: DOCENTI CON EDIZIONI === -->
          <div class="col-md-3">
            <div class="stat-card bg-light-yellow rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Con Edizioni</h6>
              <?php
              // Calcola quanti docenti hanno almeno un'edizione assegnata
              $docentiConEdizioni = array_filter($docenti, function($d) {
                return $d['num_edizioni'] > 0;
              });
              ?>
              <h2 class="display-4 fw-bold mb-0"><?= count($docentiConEdizioni) ?></h2>
              <!-- Barra di progresso proporzionale alla percentuale -->
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= count($docenti) > 0 ? (count($docentiConEdizioni) / count($docenti) * 100) : 0 ?>%"></div>
              </div>
            </div>
          </div>
          
          <!-- === STATISTICA: DOCENTI INTERNI === -->
          <div class="col-md-3">
            <div class="stat-card bg-light rounded p-4 text-center h-60">
              <h6 class="text-uppercase text-muted mb-2">Docenti Interni</h6>
              <?php
              // Calcola quanti docenti sono di tipo 'I' (Interno)
              $docentiInterni = array_filter($docenti, function($d) {
                return $d['tipo'] === 'I';
              });
              ?>
              <h2 class="display-4 fw-bold mb-0"><?= count($docentiInterni) ?></h2>
              <!-- Barra di progresso proporzionale alla percentuale -->
              <div class="progress mt-3" style="height: 8px;">
                <div class="progress-bar bg-info" role="progressbar" style="width: <?= count($docenti) > 0 ? (count($docentiInterni) / count($docenti) * 100) : 0 ?>%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<!-- === CSS PERSONALIZZATO === -->
<style>
  /* === STILI PER LE CARD STATISTICHE === */
  .stat-card {
    transition: all 0.3s ease; /* Transizione fluida per animazioni hover */
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Ombra leggera */
  }
  
  /* Effetto hover per le statistiche */
  .stat-card:hover {
    transform: translateY(-5px); /* Solleva la card */
    box-shadow: 0 8px 24px rgba(0,0,0,0.1); /* Ombra più pronunciata */
  }
  
  /* Colori di sfondo per le diverse statistiche */
  .bg-light-blue {
    background-color: #e6f2ff; /* Azzurro chiaro */
  }
  
  .bg-light-green {
    background-color: #e6fff2; /* Verde chiaro */
  }
  
  .bg-light-yellow {
    background-color: #fffbe6; /* Giallo chiaro */
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

<!-- === JAVASCRIPT PER INTERATTIVITÀ === -->
<script>
// Attende che il DOM sia completamente caricato
document.addEventListener('DOMContentLoaded', function() {
  
  // === INIZIALIZZAZIONE TOOLTIP ===
  // Inizializza i tooltip di Bootstrap per tutti gli elementi con attributo 'title'
  const tooltips = document.querySelectorAll('[title]');
  tooltips.forEach(tooltip => {
    new bootstrap.Tooltip(tooltip);
  });

  // === FUNZIONALITÀ DI RICERCA IN TEMPO REALE ===
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    // Aggiunge un listener per l'evento 'input' (quando l'utente digita)
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase(); // Converte in minuscolo per ricerca case-insensitive
      const rows = document.querySelectorAll('#docentiTable tbody tr'); // Seleziona tutte le righe della tabella
      
      // Cicla attraverso ogni riga per verificare se contiene il termine di ricerca
      rows.forEach(row => {
        const text = row.textContent.toLowerCase(); // Ottiene tutto il testo della riga
        // Mostra o nasconde la riga a seconda che contenga il termine di ricerca
        row.style.display = text.includes(searchTerm) ? '' : 'none';
      });
    });
  }
  
  // === FILTRI PER VISUALIZZAZIONE ===
  const viewAllBtn = document.getElementById('viewAll');     // Pulsante "Tutti"
  const viewActiveBtn = document.getElementById('viewActive'); // Pulsante "Attivi"
  
  if (viewAllBtn && viewActiveBtn) {
    
    // === FILTRO "TUTTI" ===
    // Mostra tutti i docenti
    viewAllBtn.addEventListener('click', function() {
      // Aggiorna lo stato dei pulsanti
      this.classList.add('active');
      viewActiveBtn.classList.remove('active');
      
      // Mostra tutte le righe
      const rows = document.querySelectorAll('#docentiTable tbody tr');
      rows.forEach(row => {
        row.style.display = '';
      });
    });
    
    // === FILTRO "ATTIVI" ===
    // Mostra solo i docenti con almeno un'abilitazione
    viewActiveBtn.addEventListener('click', function() {
      // Aggiorna lo stato dei pulsanti
      this.classList.add('active');
      viewAllBtn.classList.remove('active');
      
      // Filtra le righe in base al numero di abilitazioni
      const rows = document.querySelectorAll('#docentiTable tbody tr');
      rows.forEach(row => {
        // Legge il numero di abilitazioni dal data attribute
        const abilitazioni = parseInt(row.getAttribute('data-abilitazioni'), 10);
        // Mostra solo se ha almeno un'abilitazione
        row.style.display = abilitazioni > 0 ? '' : 'none';
      });
    });
  }
});
</script>
</body>
</html>