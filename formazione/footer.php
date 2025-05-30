<?php
/**
 * File: footer.php
 * Descrizione: Footer comune e inclusione degli script JavaScript
 * 
 * Questo file fornisce:
 * - Footer consistente per tutte le pagine del sito
 * - Informazioni di copyright e crediti
 * - Inclusione degli script JavaScript necessari (Bootstrap)
 * - Struttura responsive per il footer
 * - Styling appropriato per completare il layout della pagina
 * 
 * Viene incluso alla fine di ogni pagina, dopo il contenuto principale
 * ma prima della chiusura del tag </body>.
 */
?>

<!-- === FOOTER PRINCIPALE === -->
<!-- bg-primary: sfondo con colore primario per consistenza con navbar -->
<!-- text-white: testo bianco per contrasto su sfondo scuro -->
<!-- py-3: padding verticale (top e bottom) per spaziatura -->
<!-- mt-auto: margin-top auto per push del footer in fondo (se body è flex) -->
<footer class="bg-primary text-white py-3 mt-auto">
  
  <!-- === CONTAINER RESPONSIVE === -->
  <!-- container: classe Bootstrap per contenuto centrato e responsive -->
  <div class="container">
    
    <!-- === ROW PER LAYOUT GRID === -->
    <!-- row: sistema di griglia Bootstrap -->
    <!-- align-items-center: allineamento verticale centrato -->
    <div class="row align-items-center">
      
      <!-- === COLONNA CON COPYRIGHT === -->
      <!-- col-12: occupa tutta la larghezza disponibile -->
      <!-- text-center: allineamento del testo al centro -->
      <div class="col-12 text-center">
        
        <!-- === MESSAGGIO DI COPYRIGHT === -->
        <!-- date('Y'): anno corrente dinamico per mantenere il copyright aggiornato -->
        <!-- &copy;: entità HTML per il simbolo di copyright (©) -->
        <p class="mb-0">&copy; <?= date('Y') ?> Scuola di Formazione. Progetto sviluppato per il corso di Basi di Dati.</p>
      </div>
    </div>
  </div>
</footer>

<!-- === INCLUSIONE SCRIPT JAVASCRIPT === -->

<!-- === BOOTSTRAP 5 JAVASCRIPT BUNDLE === -->
<!-- Script essenziale per il funzionamento dei componenti Bootstrap interattivi -->
<!-- Include sia Bootstrap che Popper.js (per tooltip, dropdown, etc.) -->
<!-- Caricato da CDN per performance ottimale e caching -->
<!-- defer implicito per script esterni - si carica dopo il parsing HTML -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
