<?php
/**
 * utils.php - Funzioni di utilità per la Scuola di Formazione
 * 
 * Questo file contiene funzioni di utilità utilizzate in tutto il sistema.
 */

/**
 * Funzione di configurazione che restituisce i valori dal file di configurazione
 * 
 * @param string $key Chiave di configurazione nel formato 'section.key'
 * @param mixed $default Valore predefinito se la chiave non esiste
 * @return mixed Valore di configurazione o valore predefinito
 */
function config($key, $default = null) {
    static $config = null;
    
    // Carica la configurazione se non è ancora stata caricata
    if ($config === null) {
        $config_file = __DIR__ . '/config.php';
        if (file_exists($config_file)) {
            $config = include $config_file;
        } else {
            $config = [];
        }
    }
    
    // Gestisce chiavi nel formato 'section.key'
    if (strpos($key, '.') !== false) {
        list($section, $subkey) = explode('.', $key, 2);
        return isset($config[$section][$subkey]) ? $config[$section][$subkey] : $default;
    }
    
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Registra un messaggio nel log dell'applicazione
 * 
 * @param string $message Messaggio da registrare
 * @param string $level Livello del log (info, warning, error)
 * @return bool True se il log è stato scritto con successo
 */
function app_log($message, $level = 'info') {
    $log_enabled = config('settings.log_enabled', true);
    if (!$log_enabled) {
        return false;
    }
    
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    return file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

/**
 * Formatta una data dal formato del database al formato di visualizzazione
 * 
 * @param string $date Data nel formato Y-m-d
 * @param string $format Formato di output (default: d/m/Y)
 * @return string Data formattata
 */
function format_date($date, $format = 'd/m/Y') {
    if (!$date) return '';
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$date_obj) return $date;
    return $date_obj->format($format);
}

/**
 * Formatta un valore numerico come prezzo
 * 
 * @param float $value Valore da formattare
 * @param int $decimals Numero di decimali
 * @param string $currency Simbolo della valuta
 * @return string Prezzo formattato
 */
function format_price($value, $decimals = 2, $currency = '€') {
    return number_format($value, $decimals, ',', '.') . ' ' . $currency;
}

/**
 * Genera un token CSRF per proteggere i form
 * 
 * @return string Token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se un token CSRF è valido
 * 
 * @param string $token Token da verificare
 * @return bool True se il token è valido
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Reindirizza a un'altra pagina
 * 
 * @param string $url URL di destinazione
 * @param array $params Parametri GET opzionali
 * @param int $status Codice di stato HTTP
 */
function redirect($url, $params = [], $status = 303) {
    if (!empty($params)) {
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        $url .= http_build_query($params);
    }
    
    header('Location: ' . $url, true, $status);
    exit;
}

/**
 * Restituisce un messaggio flash dalla sessione
 * 
 * @param string $key Chiave del messaggio
 * @param mixed $default Valore predefinito se il messaggio non esiste
 * @return mixed Messaggio o valore predefinito
 */
function get_flash($key, $default = null) {
    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $value;
    }
    return $default;
}

/**
 * Imposta un messaggio flash nella sessione
 * 
 * @param string $key Chiave del messaggio
 * @param mixed $value Valore del messaggio
 */
function set_flash($key, $value) {
    $_SESSION['flash'][$key] = $value;
}

/**
 * Verifica se un utente è loggato
 * 
 * @return bool True se l'utente è loggato
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Formatta un codice fiscale in modo leggibile
 * 
 * @param string $cf Codice fiscale
 * @return string Codice fiscale formattato
 */
function format_cf($cf) {
    $cf = strtoupper($cf);
    if (strlen($cf) !== 16) return $cf;
    
    // Formatta in gruppi per una migliore leggibilità
    return substr($cf, 0, 6) . '-' . 
           substr($cf, 6, 2) . '-' . 
           substr($cf, 8, 1) . '-' . 
           substr($cf, 9, 2) . '-' . 
           substr($cf, 11, 1) . '-' . 
           substr($cf, 12, 3) . '-' . 
           substr($cf, 15, 1);
}

/**
 * Rimuove i caratteri speciali da una stringa
 * 
 * @param string $string Stringa da pulire
 * @return string Stringa pulita
 */
function clean_string($string) {
    return preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
}

/**
 * Tronca una stringa alla lunghezza specificata
 * 
 * @param string $string Stringa da troncare
 * @param int $length Lunghezza massima
 * @param string $append Stringa da aggiungere alla fine se troncata
 * @return string Stringa troncata
 */
function truncate($string, $length = 100, $append = '...') {
    if (strlen($string) <= $length) {
        return $string;
    }
    
    $string = substr($string, 0, $length);
    return rtrim($string) . $append;
}

/**
 * Genera un slug da una stringa
 * 
 * @param string $string Stringa di input
 * @return string Slug generato
 */
function generate_slug($string) {
    // Converti in minuscolo
    $string = strtolower($string);
    
    // Rimuovi accenti
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    
    // Rimuovi caratteri speciali
    $string = preg_replace('/[^a-z0-9\s]/', '', $string);
    
    // Sostituisci spazi con trattini
    $string = preg_replace('/\s+/', '-', $string);
    
    // Rimuovi trattini multipli
    $string = preg_replace('/-+/', '-', $string);
    
    // Rimuovi trattini all'inizio e alla fine
    return trim($string, '-');
}