<?php
/**
 * File: db_connect.php
 * Descrizione: Gestione della connessione al database e funzioni di utilità per operazioni sicure
 * 
 * Questo file fornisce:
 * - Connessione sicura al database MySQL usando MySQLi
 * - Funzioni helper per query sicure e prevenzione SQL Injection
 * - Gestione delle transazioni
 * - Logging delle operazioni del database
 * - Configurazione ottimale del database (charset, timezone, modalità SQL)
 */

// === INCLUSIONE DIPENDENZE ===
// Include il file utils.php che contiene la funzione config() per leggere la configurazione
require_once 'utils.php';

// === LETTURA CONFIGURAZIONE DATABASE ===
// Recupera la configurazione dal file config.php con valori di fallback
$config_db = config('db', [
    'host'   => 'localhost',  // Host del database (default: localhost)
    'user'   => 'root',       // Username MySQL (default: root per sviluppo)
    'pass'   => '',           // Password MySQL (default: vuota per XAMPP)
    'name'   => 'formazione', // Nome del database
    'charset'=> 'utf8mb4'     // Set di caratteri (supporta emoji e caratteri speciali)
]);

// === CONFIGURAZIONE MODALITÀ DEBUG ===
// Determina se mostrare errori dettagliati o messaggi generici
$debug_mode = config('settings.debug_mode', true);

// === INIZIALIZZAZIONE CONNESSIONE DATABASE ===
try {
    // === CREAZIONE CONNESSIONE MySQLi ===
    // Crea una nuova istanza di MySQLi con i parametri di configurazione
    $mysqli = new mysqli(
        $config_db['host'],     // Host del database
        $config_db['user'],     // Username
        $config_db['pass'],     // Password
        $config_db['name']      // Nome del database
    );
    
    // === CONTROLLO ERRORI DI CONNESSIONE ===
    // Verifica se la connessione è riuscita
    if ($mysqli->connect_errno) {
        throw new Exception("Errore di connessione al database: " . $mysqli->connect_error);
    }
    
    // === IMPOSTAZIONE SET DI CARATTERI ===
    // Imposta il charset per garantire il corretto handling dei caratteri speciali
    if (!$mysqli->set_charset($config_db['charset'])) {
        throw new Exception("Errore nell'impostazione del set di caratteri: " . $mysqli->error);
    }
    
    // === CONFIGURAZIONE MODALITÀ SQL STRICT ===
    // Imposta modalità SQL rigorosa per maggiore sicurezza e consistenza dei dati
    $mysqli->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    
    // === IMPOSTAZIONE TIMEZONE ===
    // Imposta il fuso orario del database (CET - Central European Time)
    $mysqli->query("SET time_zone = '+01:00'"); // UTC+1 per l'Italia
    
    // === LOGGING CONNESSIONE RIUSCITA ===
    // Registra un log di successo se la funzione di logging è disponibile
    if (function_exists('app_log')) {
        app_log("Connessione al database riuscita", "info");
    }
    
} catch (Exception $e) {
    // === GESTIONE ERRORI DI CONNESSIONE ===
    if ($debug_mode) {
        // === MODALITÀ DEBUG ===
        // In modalità debug, mostra dettagli dell'errore per facilitare la risoluzione
        die("<div class='alert alert-danger'><strong>Errore di connessione al database:</strong> " . 
             htmlspecialchars($e->getMessage()) . "</div>");
    } else {
        // === MODALITÀ PRODUZIONE ===
        // In produzione, nasconde i dettagli sensibili e mostra un messaggio generico
        if (function_exists('app_log')) {
            app_log("Errore DB: " . $e->getMessage(), "error");
        }
        die("<div class='alert alert-danger'>Errore di connessione al database. Contattare l'amministratore.</div>");
    }
}

/**
 * Funzione di sanitizzazione per prevenire SQL Injection
 * 
 * Questa funzione pulisce ricorsivamente i dati di input per renderli sicuri
 * da utilizzare nelle query SQL, prevenendo attacchi di SQL injection.
 * 
 * @param mixed $input Input da sanitizzare (può essere stringa, array, etc.)
 * @return mixed Input sanitizzato dello stesso tipo
 */
function sanitize_input($input) {
    global $mysqli; // Accede alla connessione globale del database
    
    // === GESTIONE ARRAY RICORSIVA ===
    if (is_array($input)) {
        // Se l'input è un array, sanitizza ogni elemento ricorsivamente
        return array_map('sanitize_input', $input);
    }
    
    // === SANITIZZAZIONE STRINGHE ===
    if (is_string($input)) {
        // Per le stringhe, rimuove spazi iniziali/finali e applica escape
        return $mysqli->real_escape_string(trim($input));
    }
    
    // === ALTRI TIPI DI DATI ===
    // Per numeri e altri tipi, restituisce l'input inalterato
    return $input;
}

/**
 * Esegue una query SQL in modo sicuro usando prepared statements
 * 
 * Questa funzione fornisce un'interfaccia semplificata per eseguire query
 * con parametri in modo sicuro, prevenendo SQL injection.
 * 
 * @param string $query Query SQL con placeholder (?)
 * @param array $params Array di parametri da legare alla query
 * @param string $types Tipi di dati (s=string, i=integer, d=double, b=blob)
 * @return mysqli_result|bool Risultato della query o true per query non-SELECT
 */
function execute_query($query, $params = [], $types = '') {
    global $mysqli; // Accede alla connessione globale del database
    
    // === QUERY SEMPLICI SENZA PARAMETRI ===
    if (empty($params)) {
        return $mysqli->query($query);
    }
    
    // === PREPARAZIONE STATEMENT ===
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        throw new Exception("Errore nella preparazione della query: " . $mysqli->error);
    }
    
    // === DETERMINAZIONE AUTOMATICA DEI TIPI ===
    // Se i tipi non sono specificati, li determina automaticamente
    if (empty($types)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';      // integer
            } elseif (is_float($param)) {
                $types .= 'd';      // double/decimal
            } elseif (is_string($param)) {
                $types .= 's';      // string
            } else {
                $types .= 'b';      // blob (per default)
            }
        }
    }
    
    // === BINDING DEI PARAMETRI ===
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params); // Unpacking dell'array con l'operatore ...
    }
    
    // === ESECUZIONE QUERY ===
    $stmt->execute();
    
    // === GESTIONE RISULTATO ===
    $result = $stmt->get_result(); // Ottiene il risultato (se presente)
    $stmt->close();                // Chiude lo statement per liberare risorse
    
    // Restituisce il risultato o true per query non-SELECT (INSERT, UPDATE, DELETE)
    return $result ? $result : true;
}

/**
 * Ottiene l'ID dell'ultimo record inserito
 * 
 * Utile dopo operazioni INSERT con chiavi primarie auto-incrementali
 * 
 * @return int ID dell'ultimo record inserito
 */
function last_insert_id() {
    global $mysqli;
    return $mysqli->insert_id;
}

/**
 * Inizia una transazione database
 * 
 * Le transazioni garantiscono che un gruppo di operazioni venga eseguito
 * atomicamente: o tutte le operazioni riescono, o nessuna viene applicata.
 */
function begin_transaction() {
    global $mysqli;
    $mysqli->begin_transaction();
}

/**
 * Conferma una transazione
 * 
 * Applica definitivamente tutte le modifiche effettuate dall'inizio della transazione.
 */
function commit_transaction() {
    global $mysqli;
    $mysqli->commit();
}

/**
 * Annulla una transazione
 * 
 * Annulla tutte le modifiche effettuate dall'inizio della transazione,
 * riportando il database allo stato precedente.
 */
function rollback_transaction() {
    global $mysqli;
    $mysqli->rollback();
}

/**
 * Chiude la connessione al database
 * 
 * Libera le risorse associate alla connessione database.
 * Viene chiamata automaticamente alla fine dello script.
 */
function close_connection() {
    global $mysqli;
    
    // Verifica che la connessione sia ancora valida prima di chiuderla
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
        
        // Log della chiusura se la funzione di logging è disponibile
        if (function_exists('app_log')) {
            app_log("Connessione al database chiusa", "info");
        }
    }
}

// === REGISTRAZIONE FUNZIONE DI CHIUSURA AUTOMATICA ===
// Registra una funzione che verrà chiamata automaticamente alla fine dello script
// per garantire che la connessione al database venga sempre chiusa correttamente
register_shutdown_function('close_connection');

