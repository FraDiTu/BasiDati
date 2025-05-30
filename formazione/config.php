<?php
/**
 * File: config.php
 * Descrizione: File di configurazione centralizzato per la Scuola di Formazione
 * 
 * Questo file contiene tutte le configurazioni globali dell'applicazione organizzate
 * in sezioni logiche. Viene incluso da altri file per ottenere impostazioni
 * consistent in tutta l'applicazione.
 * 
 * Struttura del file:
 * - Configurazione database
 * - Impostazioni generali dell'applicazione
 * - Percorsi del filesystem
 * - Configurazione email
 * 
 * Il file restituisce un array associativo che può essere utilizzato
 * con la funzione config() definita in utils.php
 */

return [
    // === CONFIGURAZIONE DEL DATABASE ===
    // Parametri per la connessione al database MySQL/MariaDB
    'db' => [
        'host'    => 'localhost',    // Host del server database (di solito localhost in sviluppo)
        'user'    => 'root',         // Username per l'accesso al database
        'pass'    => '',             // Password per l'accesso al database (vuota per XAMPP di default)
        'name'    => 'formazione',   // Nome del database (IMPORTANTE: minuscolo come da schema SQL)
        'charset' => 'utf8mb4'       // Set di caratteri che supporta emoji e caratteri speciali
    ],
    
    // === IMPOSTAZIONI GENERALI DELL'APPLICAZIONE ===
    // Configurazioni che influenzano il comportamento globale del sistema
    'settings' => [
        'debug_mode'   => true,      // Modalità debug: true in sviluppo, false in produzione
                                     // Controlla la visualizzazione degli errori e i log dettagliati
        
        'log_enabled'  => true,      // Abilitazione del sistema di logging
                                     // Se true, gli eventi vengono registrati nei file di log
        
        'app_name'     => 'Scuola di Formazione', // Nome dell'applicazione mostrato nell'interfaccia
        
        'app_version'  => '1.0.0',   // Versione dell'applicazione per tracking delle modifiche
        
        'timezone'     => 'Europe/Rome', // Fuso orario dell'applicazione (importante per date/orari)
        
        'locale'       => 'it_IT',   // Locale per formattazione di date, numeri e testi
    ],
    
    // === PERCORSI DELL'APPLICAZIONE ===
    // Definisce i percorsi principali utilizzati dall'applicazione
    'paths' => [
        'root'     => __DIR__ . '/..',           // Directory root dell'applicazione (parent della cartella corrente)
        'uploads'  => __DIR__ . '/../uploads',   // Directory per file caricati dagli utenti
        'logs'     => __DIR__ . '/../logs',      // Directory per i file di log dell'applicazione
        'assets'   => '/formazione/assets',      // Percorso web per risorse statiche (CSS, JS, immagini)
    ],
    
    // === CONFIGURAZIONE EMAIL ===
    // Parametri per l'invio di email dal sistema (future implementazioni)
    'email' => [
        'from'      => 'noreply@scuolaformazione.it', // Indirizzo email mittente di default
        'from_name' => 'Scuola di Formazione',         // Nome visualizzato come mittente
        'smtp'      => false,                          // Se usare SMTP o mail() di PHP
        'smtp_host' => '',                             // Host del server SMTP (es. smtp.gmail.com)
        'smtp_port' => 587,                            // Porta SMTP (587 per TLS, 465 per SSL, 25 per non crittografato)
        'smtp_user' => '',                             // Username per autenticazione SMTP
        'smtp_pass' => '',                             // Password per autenticazione SMTP
    ],
];
