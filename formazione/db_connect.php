<?php
/**
 * db_connect.php — Connessione al database Formazione
 * 
 * Questo file gestisce la connessione al database con il nuovo schema del professore.
 */

// Includi il file utils.php che contiene la funzione config()
require_once 'utils.php';

// Configurazione database dal file config.php
$config_db = config('db', [
    'host'   => 'localhost',  // Host del database
    'user'   => 'root',       // Username MySQL
    'pass'   => '',           // Password MySQL
    'name'   => 'formazione', // Nome del database
    'charset'=> 'utf8mb4'     // Set di caratteri (supporta emoji e caratteri speciali)
]);

// Report errori solo in ambiente di sviluppo
$debug_mode = config('settings.debug_mode', true);

// Inizializza la connessione al database
try {
    // Inizializzazione della connessione MySQLi
    $mysqli = new mysqli(
        $config_db['host'], 
        $config_db['user'], 
        $config_db['pass'], 
        $config_db['name']
    );
    
    // Verifica errori di connessione
    if ($mysqli->connect_errno) {
        throw new Exception("Errore di connessione al database: " . $mysqli->connect_error);
    }
    
    // Imposta il charset
    if (!$mysqli->set_charset($config_db['charset'])) {
        throw new Exception("Errore nell'impostazione del set di caratteri: " . $mysqli->error);
    }
    
    // Imposta modalità SQL strict per maggiore sicurezza
    $mysqli->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
    // Imposta il fuso orario (utile per le date)
    $mysqli->query("SET time_zone = '+01:00'"); // CET (Europa/Roma)
    
    // Registra un log di connessione riuscita
    if (function_exists('app_log')) {
        app_log("Connessione al database riuscita", "info");
    }
    
} catch (Exception $e) {
    // Gestione dell'errore di connessione
    if ($debug_mode) {
        // In modalità debug, mostra dettagli dell'errore
        die("<div class='alert alert-danger'><strong>Errore di connessione al database:</strong> " . 
             htmlspecialchars($e->getMessage()) . "</div>");
    } else {
        // In produzione, mostra un messaggio generico
        if (function_exists('app_log')) {
            app_log("Errore DB: " . $e->getMessage(), "error");
        }
        die("<div class='alert alert-danger'>Errore di connessione al database. Contattare l'amministratore.</div>");
    }
}

/**
 * Funzione di sanitizzazione per prevenire SQL Injection
 * 
 * @param mixed $input Input da sanitizzare
 * @return mixed Input sanitizzato
 */
function sanitize_input($input) {
    global $mysqli;
    
    if (is_array($input)) {
        // Sanitizza ogni elemento dell'array
        return array_map('sanitize_input', $input);
    }
    
    // Se è una stringa, puliscila con real_escape_string
    if (is_string($input)) {
        return $mysqli->real_escape_string(trim($input));
    }
    
    // Altrimenti restituisci l'input inalterato
    return $input;
}

/**
 * Esegue una query SQL in modo sicuro
 * 
 * @param string $query Query SQL da eseguire
 * @param array $params Parametri per la query
 * @param string $types Tipi di dati (s=string, i=integer, d=double, b=blob)
 * @return mysqli_result|bool Risultato della query
 */
function execute_query($query, $params = [], $types = '') {
    global $mysqli;
    
    // Se non ci sono parametri, esegui una query semplice
    if (empty($params)) {
        return $mysqli->query($query);
    }
    
    // Prepara lo statement
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Errore nella preparazione della query: " . $mysqli->error);
    }
    
    // Se non sono specificati i tipi, determina automaticamente
    if (empty($types)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } elseif (is_string($param)) {
                $types .= 's';
            } else {
                $types .= 'b'; // blob per default
            }
        }
    }
    
    // Bind dei parametri
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    // Esegui la query
    $stmt->execute();
    
    // Restituisci il risultato
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result ? $result : true;
}

/**
 * Ottiene l'ID dell'ultimo record inserito
 * 
 * @return int ID dell'ultimo record inserito
 */
function last_insert_id() {
    global $mysqli;
    return $mysqli->insert_id;
}

/**
 * Inizia una transazione
 */
function begin_transaction() {
    global $mysqli;
    $mysqli->begin_transaction();
}

/**
 * Conferma una transazione
 */
function commit_transaction() {
    global $mysqli;
    $mysqli->commit();
}

/**
 * Annulla una transazione
 */
function rollback_transaction() {
    global $mysqli;
    $mysqli->rollback();
}

/**
 * Chiude la connessione al database
 */
function close_connection() {
    global $mysqli;
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
        if (function_exists('app_log')) {
            app_log("Connessione al database chiusa", "info");
        }
    }
}

// Registra una funzione di chiusura automatica della connessione
register_shutdown_function('close_connection');