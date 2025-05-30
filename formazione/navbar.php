<?php
// navbar.php migliorato — barra di navigazione con funzionalità aggiuntive
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand brand-enhanced" href="index.php">
      <i class="fas fa-graduation-cap me-2"></i>Scuola di Formazione
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false"
            aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNav">
      <!-- Rimuovere me-auto e aggiungere mx-auto per centrare -->
      <ul class="navbar-nav mx-auto">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        
        <!-- Dropdown per Gestione Anagrafica -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAnagrafica" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Anagrafica
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownAnagrafica">
            <li>
              <a class="dropdown-item" href="docenti.php">Docenti</a>
            </li>
            <li>
              <a class="dropdown-item" href="coming_soon.php">Partecipanti</a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="coming_soon.php">Telefoni</a>
            </li>
          </ul>
        </li>
        
        <!-- Dropdown per Gestione Corsi -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownCorsi" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Corsi
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownCorsi">
            <li>
              <a class="dropdown-item" href="coming_soon.php">Catalogo Corsi</a>
            </li>
            <li>
              <a class="dropdown-item" href="coming_soon.php">Edizioni</a>
            </li>
            <li>
              <a class="dropdown-item" href="coming_soon.php">Lezioni</a>
            </li>
          </ul>
        </li>
        
        <!-- Dropdown per Gestione Relazioni -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownRelazioni" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Relazioni
          </a>
          <ul class="dropdown-menu" aria-labelledby="navbarDropdownRelazioni">
            <li>
              <a class="dropdown-item" href="abilitazioni.php">Abilitazioni</a>
            </li>
            <li>
              <a class="dropdown-item" href="partecipazioni.php">Partecipazioni</a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="coming_soon.php">Impieghi Passati</a>
            </li>
          </ul>
        </li>
      </ul>
      
      <!-- Barra di ricerca - rimuovere ms-auto e usare solo me-2 -->
      <div class="search-container">
        <form class="d-flex mb-0" action="search.php" method="get">
          <div class="input-group">
            <input type="text" class="form-control" placeholder="Cerca..." name="q" aria-label="Cerca">
            <button class="btn btn-outline-light" type="submit">Cerca</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</nav>