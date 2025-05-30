<?php
/**
 * File: coming_soon.php
 * Descrizione: Pagina placeholder per funzionalità in sviluppo
 * 
 * Questo file serve come:
 * - Placeholder elegante per sezioni non ancora implementate
 * - Comunicazione trasparente dello stato di sviluppo
 * - Mantenimento della user experience durante lo sviluppo
 * - Redirect temporaneo che preserva la navigazione
 * - Informazione sui tempi di implementazione futuri
 * 
 * Riceve un parametro 'section' per personalizzare il messaggio
 * a seconda della funzionalità specifica che l'utente stava cercando.
 * 
 * Esempi di utilizzo:
 * - coming_soon.php?section=Corsi
 * - coming_soon.php?section=Partecipanti
 * - coming_soon.php?section=Telefoni
 */

// Imposta il titolo della pagina
$pageTitle = 'Sezione in sviluppo';

// Include il file di connessione al database (per consistenza)
require_once 'db_connect.php';

// === RECUPERO NOME SEZIONE ===
// Ottiene il nome della sezione dalla query string per personalizzazione
// Default generico se non specificato
$section_name = $_GET['section'] ?? 'Questa sezione';
?>
<!DOCTYPE html>
<html lang="it">
<?php include 'head.php'; ?>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
  <!-- === LAYOUT CENTRATO === -->
  <!-- justify-content-center: centra orizzontalmente il contenuto -->
  <div class="row justify-content-center">
    
    <!-- === COLONNA PRINCIPALE === -->
    <!-- col-lg-8: occupa 8/12 colonne su large screens per reading width ottimale -->
    <!-- text-center: allinea tutto il testo al centro per simmetria -->
    <div class="col-lg-8 text-center">
      
      <!-- === CARD PRINCIPALE === -->
      <!-- shadow: ombra leggera per depth, border-0: rimuove bordo default -->
      <div class="card shadow border-0">
        <div class="card-body p-5">
          
          <!-- === ICONA ANIMATA === -->
          <!-- Icona FontAwesome con animazione spin per indicare "work in progress" -->
          <div class="mb-4">
            <i class="fa-solid fa-gear fa-spin fa-4x text-primary"></i>
          </div>
          
          <!-- === TITOLO DINAMICO === -->
          <!-- display-5: classe Bootstrap per titoli grandi ma non enormi -->
          <!-- fw-bold: font-weight bold per emphasis -->
          <!-- htmlspecialchars: sicurezza XSS per contenuto dinamico -->
          <h1 class="display-5 fw-bold mb-4"><?= htmlspecialchars($section_name) ?> è in fase di sviluppo</h1>
          
          <!-- === MESSAGGIO PRINCIPALE === -->
          <!-- lead: classe Bootstrap per testo di introduzione più grande -->
          <p class="lead mb-4">
            Stiamo lavorando per implementare questa funzionalità nel sistema.
            Torneremo online il prima possibile con nuove caratteristiche e miglioramenti.
          </p>
          
          <!-- === ALERT INFORMATIVO === -->
          <!-- alert-info: stile Bootstrap per informazioni neutre -->
          <div class="alert alert-info mb-4">
            <i class="fa-solid fa-info-circle me-2"></i>
            Le funzionalità esistenti del sistema rimangono pienamente operative.
          </div>
          
          <!-- === CALL-TO-ACTION === -->
          <!-- btn-lg: pulsante grande per prominence -->
          <!-- px-4: padding orizzontale extra per importanza -->
          <a href="index.php" class="btn btn-primary btn-lg px-4">
            <i class="fa-solid fa-home me-2"></i>Torna alla Home
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<!-- === CSS PERSONALIZZATO (OPZIONALE) === -->
<style>
/* === STILI SPECIFICI PER COMING SOON === */

/* Animazione personalizzata per l'icona */
.fa-spin {
  animation: fa-spin 2s infinite linear; /* Rallenta la rotazione standard di FontAwesome */
}

/* Effetti hover per la card principale */
.card {
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-5px); /* Solleva leggermente la card */
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); /* Ombra più pronunciata */
}

/* Stile per il pulsante CTA */
.btn-primary {
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-2px); /* Solleva il pulsante */
  box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3); /* Ombra colorata */
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .display-5 {
    font-size: 2rem; /* Riduce la dimensione del titolo su mobile */
  }
  
  .card-body {
    padding: 2rem !important; /* Riduce il padding su schermi piccoli */
  }
  
  .fa-4x {
    font-size: 3rem !important; /* Riduce l'icona su mobile */
  }
}

</style>

</body>
</html>
