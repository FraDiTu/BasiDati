<?php
/**
 * config.php - File di configurazione per la Scuola di Formazione
 * 
 * Questo file contiene le configurazioni globali dell'applicazione.
 */

return [
    // Configurazione del database
    'db' => [
        'host'    => 'localhost',    // Host del database
        'user'    => 'root',         // Username MySQL
        'pass'    => '',             // Password MySQL
        'name'    => 'Formazione',   // Nome del database
        'charset' => 'utf8mb4'       // Set di caratteri
    ],
    
    // Impostazioni generali
    'settings' => [
        'debug_mode'   => true,      // Modalità debug (true in sviluppo, false in produzione)
        'log_enabled'  => true,      // Abilitazione dei log
        'app_name'     => 'Scuola di Formazione', // Nome dell'applicazione
        'app_version'  => '1.0.0',   // Versione dell'applicazione
        'timezone'     => 'Europe/Rome', // Fuso orario
        'locale'       => 'it_IT',   // Locale
    ],
    
    // Percorsi dell'applicazione
    'paths' => [
        'root'     => __DIR__ . '/..',
        'uploads'  => __DIR__ . '/../uploads',
        'logs'     => __DIR__ . '/../logs',
        'assets'   => '/formazione/assets',
    ],
    
    // Configurazione email
    'email' => [
        'from'      => 'noreply@scuolaformazione.it',
        'from_name' => 'Scuola di Formazione',
        'smtp'      => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_user' => '',
        'smtp_pass' => '',
    ],
];