<?php
/**
 * File: head.php
 * Descrizione: Template per la sezione <head> di tutte le pagine HTML
 * 
 * Questo file include:
 * - Meta tags essenziali per SEO e responsive design
 * - Importazione di librerie CSS (Bootstrap, Font Awesome, stili custom)
 * - Configurazione favicon
 * - Meta tags per condivisione social (Open Graph)
 * - Ottimizzazioni per performance e accessibilità
 * 
 * Viene incluso subito dopo il tag <!DOCTYPE html> in ogni pagina
 * per garantire consistenza nell'header HTML di tutto il sito.
 */
?>
<head>
  <!-- === META TAGS ESSENZIALI === -->
  <!-- Definisce la codifica dei caratteri come UTF-8 per supporto Unicode completo -->
  <meta charset="UTF-8">
  
  <!-- Viewport meta tag per responsive design - essenziale per dispositivi mobili -->
  <!-- width=device-width: larghezza uguale al device, initial-scale=1: zoom iniziale 100% -->
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- === TITOLO DINAMICO DELLA PAGINA === -->
  <!-- Usa la variabile $pageTitle se definita, altrimenti un titolo di default -->
  <!-- htmlspecialchars previene XSS nel caso $pageTitle contenga caratteri speciali -->
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Scuola di Formazione' ?></title>

  <!-- === FAVICON === -->
  <!-- Icona mostrata nel browser tab e nei bookmark -->
  <!-- type="image/png" specifica il formato dell'immagine -->
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  
  <!-- === FRAMEWORK CSS BOOTSTRAP 5 === -->
  <!-- Bootstrap fornisce componenti UI, grid system e utilità CSS -->
  <!-- Caricato da CDN per performance e caching ottimale -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- === STILI PERSONALIZZATI === -->
  <!-- CSS custom per theming e stili specifici dell'applicazione -->
  <!-- Caricato dopo Bootstrap per permettere override delle classi -->
  <link href="/formazione/assets/css/style.css" rel="stylesheet">  
  
  <!-- === META TAGS PER SEO === -->
  <!-- Descrizione della pagina per motori di ricerca e condivisioni -->
  <meta name="description" content="Interfaccia per la gestione della Scuola di Formazione">
  
  <!-- Keywords rilevanti per il contenuto del sito -->
  <meta name="keywords" content="formazione, corsi, docenti, partecipanti, scuola">
  
  <!-- Autore del sito/applicazione -->
  <meta name="author" content="Studente">
  
  <!-- === OPEN GRAPH META TAGS === -->
  <!-- Meta tags per condivisione ottimizzata sui social media (Facebook, LinkedIn, etc.) -->
  
  <!-- Titolo quando la pagina viene condivisa -->
  <meta property="og:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Scuola di Formazione' ?>">
  
  <!-- Descrizione per le condivisioni social -->
  <meta property="og:description" content="Interfaccia per la gestione della Scuola di Formazione">
  
  <!-- URL completo della pagina corrente -->
  <!-- Costruisce l'URL completo usando le variabili server HTTP/HTTPS -->
  <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
  
  <!-- Tipo di contenuto - "website" per siti web generici -->
  <meta property="og:type" content="website">
</head>
