<?php
/**
 * File: navbar.php
 * Descrizione: Barra di navigazione principale dell'applicazione
 * 
 * Questo componente fornisce:
 * - Navigazione principale organizzata in dropdown logici
 * - Brand/logo dell'applicazione
 * - Barra di ricerca integrata
 * - Design responsive con hamburger menu per mobile
 * - Struttura semantica per accessibilità
 * 
 * La navbar è organizzata in sezioni logiche:
 * - Anagrafica: Gestione di docenti, partecipanti e dati correlati
 * - Corsi: Catalogo corsi, edizioni e lezioni
 * - Relazioni: Abilitazioni, partecipazioni e collegamenti
 * 
 * Include anche una barra di ricerca per funzionalità di search globale.
 */
?>
<!-- === NAVBAR BOOTSTRAP RESPONSIVE === -->
<!-- navbar-dark: stile scuro, bg-primary: colore di sfondo primario -->
<!-- navbar-expand-lg: espande la navbar su schermi large e superiori -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    
    <!-- === BRAND/LOGO === -->
    <!-- Link che porta alla homepage, con icona FontAwesome e testo -->
    <!-- brand-enhanced: classe CSS custom per styling avanzato del brand -->
    <a class="navbar-brand brand-enhanced" href="index.php">
      <i class="fas fa-graduation-cap me-2"></i>Scuola di Formazione
    </a>
    
    <!-- === HAMBURGER BUTTON PER MOBILE === -->
    <!-- Pulsante che appare solo su schermi piccoli per espandere/collassare la navbar -->
    <!-- data-bs-toggle/target: attributi Bootstrap per controllo del collasso -->
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <!-- === CONTENUTO COLLASSABILE DELLA NAVBAR === -->
    <!-- collapse navbar-collapse: classi Bootstrap per comportamento responsive -->
    <!-- id="mainNav": ID referenziato dal pulsante hamburger -->
    <div class="collapse navbar-collapse" id="mainNav">
      
      <!-- === NAVIGAZIONE PRINCIPALE === -->
      <!-- mx-auto: margin auto orizzontale per centrare la navigazione -->
      <!-- navbar-nav: classe Bootstrap per lista di navigazione -->
      <ul class="navbar-nav mx-auto">
        
        <!-- === LINK HOME === -->
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        
        <!-- === DROPDOWN GESTIONE ANAGRAFICA === -->
        <!-- Sezione per la gestione di persone e dati anagrafici -->
        <li class="nav-item dropdown">
          <!-- Link principale del dropdown con icona chevron -->
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAnagrafica" role="button" 
             data-bs-toggle="dropdown" aria-expanded="false">
            Anagrafica
          </a>
          
          <!-- === MENU DROPDOWN ANAGRAFICA === -->
          <!-- dropdown-menu: contenitore per gli item del dropdown -->
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownAnagrafica">
            
            <!-- Link ai docenti - funzionalità completamente implementata -->
            <li>
              <a class="dropdown-item" href="docenti.php">Docenti</a>
            </li>
            
            <!-- Link ai partecipanti - da implementare -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Partecipanti</a>
            </li>
            
            <!-- === SEPARATORE VISIVO === -->
            <!-- dropdown-divider: linea di separazione per raggruppare item correlati -->
            <li><hr class="dropdown-divider"></li>
            
            <!-- Link alla gestione telefoni - da implementare -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Telefoni</a>
            </li>
          </ul>
        </li>
        
        <!-- === DROPDOWN GESTIONE CORSI === -->
        <!-- Sezione per la gestione di corsi formativi e relative edizioni -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCorsi" role="button" 
             data-bs-toggle="dropdown" aria-expanded="false">
            Corsi
          </a>
          
          <!-- === MENU DROPDOWN CORSI === -->
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownCorsi">
            
            <!-- Link al catalogo dei corsi disponibili -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Catalogo Corsi</a>
            </li>
            
            <!-- Link alla gestione delle edizioni (istanze specifiche di corsi) -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Edizioni</a>
            </li>
            
            <!-- Link alla gestione delle singole lezioni -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Lezioni</a>
            </li>
          </ul>
        </li>
        
        <!-- === DROPDOWN GESTIONE RELAZIONI === -->
        <!-- Sezione per le relazioni many-to-many tra entità del sistema -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownRelazioni" role="button" 
             data-bs-toggle="dropdown" aria-expanded="false">
            Relazioni
          </a>
          
          <!-- === MENU DROPDOWN RELAZIONI === -->
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownRelazioni">
            
            <!-- Link alle abilitazioni docenti-corsi - funzionalità implementata -->
            <li>
              <a class="dropdown-item" href="abilitazioni.php">Abilitazioni</a>
            </li>
            
            <!-- Link alle partecipazioni studenti-edizioni - funzionalità implementata -->
            <li>
              <a class="dropdown-item" href="partecipazioni.php">Partecipazioni</a>
            </li>
            
            <!-- === SEPARATORE PER FUNZIONALITÀ AVANZATE === -->
            <li><hr class="dropdown-divider"></li>
            
            <!-- Link agli impieghi passati - funzionalità da implementare -->
            <li>
              <a class="dropdown-item" href="coming_soon.php">Impieghi Passati</a>
            </li>
          </ul>
        </li>
      </ul>
      
      <!-- === BARRA DI RICERCA === -->
      <!-- search-container: classe CSS custom per styling della ricerca -->
      <div class="search-container">
        <!-- Form che invia GET alla pagina di ricerca -->
        <form class="d-flex mb-0" action="search.php" method="get">
          <div class="input-group">
            
            <!-- === CAMPO DI RICERCA === -->
            <!-- name="q": parametro standard per query di ricerca -->
            <!-- aria-label: descrizione per screen readers -->
            <input type="text" class="form-control" placeholder="Cerca..." name="q" aria-label="Cerca">
            
            <!-- === PULSANTE RICERCA === -->
            <!-- btn-outline-light: stile outline chiaro per contrasto su sfondo scuro -->
            <button class="btn btn-outline-light" type="submit">Cerca</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</nav>

