<?php
// head.php migliorato — includi subito dopo <!DOCTYPE html>
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Scuola di Formazione' ?></title>

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Stile personalizzato migliorato -->
  <link href="/formazione/assets/css/style.css" rel="stylesheet">  
  
  <!-- Meta tags per SEO e condivisione -->
  <meta name="description" content="Interfaccia per la gestione della Scuola di Formazione">
  <meta name="keywords" content="formazione, corsi, docenti, partecipanti, scuola">
  <meta name="author" content="Studente">
  
  <!-- Open Graph per condivisione social -->
  <meta property="og:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Scuola di Formazione' ?>">
  <meta property="og:description" content="Interfaccia per la gestione della Scuola di Formazione">
  <meta property="og:url" content="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]" ?>">
  <meta property="og:type" content="website">
</head>