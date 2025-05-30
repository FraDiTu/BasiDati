<?php
/**
 * File: utils.php
 * Descrizione: Libreria di funzioni di utilità per la Scuola di Formazione
 * 
 * Questo file fornisce funzioni helper utilizzate in tutto il sistema per:
 * - Gestione della configurazione centralizzata
 * - Logging delle operazioni e degli errori
 * - Formattazione di date, prezzi e stringhe
 * - Sicurezza (CSRF, sanitizzazione)
 * - Gestione delle sessioni e redirect
 * - Utilità per manipolazione stringhe e validazione
 */

/**
 * Funzione di configurazione che restituisce valori dal file config.php
 * 
 * Questa funzione implementa un pattern singleton per caricare la configurazione
 * una sola volta e permettere l'accesso ai valori tramite notazione dot (es. 'db.host').
 * 
 * @param string $key Chiave di configurazione nel formato 'section.key' o 'key'
 * @param mixed $default Valore predefinito se la chiave non esiste
 * @return mixed Valore di configurazione o valore predefinito
 * 
 * Esempi di uso:
 * - config('db.host') -> restituisce l'host del database
 * - config('app_name', 'Default App') -> restituisce il nome app o 'Default App'
 */
function config($key, $default = null) {
    // === VARIABILE STATICA PER SINGLETON ===
    // La variabile statica mantiene la configurazione caricata tra le chiamate
    static $config = null;
    
    // === CARICAMENTO LAZY DELLA CONFIGURAZIONE ===
    // Carica la configurazione solo al primo utilizzo
    if ($config === null) {
        $config_file = __DIR__ . '/config.php';
        
        if (file_exists($config_file)) {
            // Include il file di configurazione che restituisce un array
            $config = include $config_file;
        } else {
            // Se il file non esiste, inizializza un array vuoto
            $config = [];
        }
    }
    
    // === GESTIONE NOTAZIONE DOT ===
    // Permette di accedere a valori nested come 'db.host' invece di $config['db']['host']
    if (strpos($key, '.') !== false) {
        list($section, $subkey) = explode('.', $key, 2); // Limita a 2 parti per sicurezza
        return isset($config[$section][$subkey]) ? $config[$section][$subkey] : $default;
    }
    
    // === ACCESSO DIRETTO ===
    // Per chiavi senza punto, accesso diretto all'array
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Registra un messaggio nel log dell'applicazione
 * 
 * Sistema di logging robusto che scrive messaggi in file giornalieri
 * con timestamp e livelli di severità. Utile per debugging e monitoring.
 * 
 * @param string $message Messaggio da registrare nel log
 * @param string $level Livello del log: 'info', 'warning', 'error', 'debug'
 * @return bool True se il log è stato scritto con successo, False altrimenti
 * 
 * Esempi di uso:
 * - app_log("Utente loggato", "info")
 * - app_log("Errore database", "error")
 */
function app_log($message, $level = 'info') {
    // === CONTROLLO ABILITAZIONE LOGGING ===
    // Verifica se il logging è abilitato nella configurazione
    $log_enabled = config('settings.log_enabled', true);
    if (!$log_enabled) {
        return false; // Esce silenziosamente se il logging è disabilitato
    }
    
    // === CREAZIONE DIRECTORY LOG ===
    // Assicura che esista la directory per i file di log
    $log_dir = __DIR__ . '/../logs';
    if (!is_dir($log_dir)) {
        // Crea la directory con permessi 755 (rwxr-xr-x)
        mkdir($log_dir, 0755, true); // true = ricorsivo
    }
    
    // === GENERAZIONE NOME FILE LOG ===
    // Un file per giorno nel formato app_YYYY-MM-DD.log
    $log_file = $log_dir . '/app_' . date('Y-m-d') . '.log';
    
    // === FORMATTAZIONE MESSAGGIO ===
    // Formato: [YYYY-MM-DD HH:MM:SS] [LEVEL] Message
    $timestamp = date('Y-m-d H:i:s');
    $formatted_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // === SCRITTURA NEL FILE ===
    // FILE_APPEND aggiunge al file esistente invece di sovrascriverlo
    return file_put_contents($log_file, $formatted_message, FILE_APPEND);
}

/**
 * Formatta una data dal formato del database al formato di visualizzazione
 * 
 * Converte date dal formato ISO 8601 (Y-m-d) del database in formati
 * più leggibili per l'interfaccia utente italiana.
 * 
 * @param string $date Data nel formato Y-m-d (es. "2024-03-15")
 * @param string $format Formato di output (default: 'd/m/Y' per formato italiano)
 * @return string Data formattata o stringa vuota se input non valido
 * 
 * Esempi di uso:
 * - format_date('2024-03-15') -> '15/03/2024'
 * - format_date('2024-03-15', 'd M Y') -> '15 Mar 2024'
 */
function format_date($date, $format = 'd/m/Y') {
    // === CONTROLLO INPUT VUOTO ===
    if (!$date) return '';
    
    // === PARSING SICURO DELLA DATA ===
    // createFromFormat è più sicuro di strtotime per formati specifici
    $date_obj = DateTime::createFromFormat('Y-m-d', $date);
    
    // === GESTIONE ERRORI DI PARSING ===
    if (!$date_obj) {
        // Se il parsing fallisce, restituisce la stringa originale
        return $date;
    }
    
    // === FORMATTAZIONE OUTPUT ===
    return $date_obj->format($format);
}

/**
 * Formatta un valore numerico come prezzo con valuta
 * 
 * Utility per formattare prezzi secondo le convenzioni italiane
 * (virgola per decimali, punto per migliaia).
 * 
 * @param float $value Valore numerico da formattare
 * @param int $decimals Numero di decimali da mostrare (default: 2)
 * @param string $currency Simbolo della valuta (default: '€')
 * @return string Prezzo formattato con valuta
 * 
 * Esempi di uso:
 * - format_price(1234.56) -> '1.234,56 €'
 * - format_price(100, 0, '$') -> '100 $'
 */
function format_price($value, $decimals = 2, $currency = '€') {
    // === FORMATTAZIONE NUMERICA ITALIANA ===
    // Virgola per decimali, punto per separatore migliaia
    return number_format($value, $decimals, ',', '.') . ' ' . $currency;
}

/**
 * Genera un token CSRF per proteggere i form da attacchi Cross-Site Request Forgery
 * 
 * Implementa la protezione CSRF generando token casuali per ogni sessione.
 * I token devono essere inclusi nei form e verificati prima dell'elaborazione.
 * 
 * @return string Token CSRF univoco per la sessione corrente
 * 
 * Uso tipico:
 * - Nel form: <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
 * - Nella validazione: if (verify_csrf_token($_POST['csrf_token'])) { ... }
 */
function generate_csrf_token() {
    // === INIZIALIZZAZIONE SESSIONE (SE NECESSARIA) ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // === GENERAZIONE TOKEN UNICO PER SESSIONE ===
    if (!isset($_SESSION['csrf_token'])) {
        // random_bytes genera byte casuali crittograficamente sicuri
        // bin2hex converte in stringa esadecimale leggibile
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // 64 caratteri hex
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verifica se un token CSRF è valido confrontandolo con quello della sessione
 * 
 * Valida i token CSRF per prevenire attacchi. Usa hash_equals per confronto
 * timing-safe che previene attacchi timing.
 * 
 * @param string $token Token da verificare (di solito da $_POST)
 * @return bool True se il token è valido, False altrimenti
 */
function verify_csrf_token($token) {
    // === INIZIALIZZAZIONE SESSIONE (SE NECESSARIA) ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // === CONFRONTO TIMING-SAFE ===
    // hash_equals previene timing attacks confrontando sempre tutti i caratteri
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Reindirizza a un'altra pagina con parametri GET opzionali
 * 
 * Utility per redirect sicuri con costruzione automatica della query string
 * e gestione dei codici di stato HTTP appropriati.
 * 
 * @param string $url URL di destinazione (relativo o assoluto)
 * @param array $params Array associativo di parametri GET da aggiungere
 * @param int $status Codice di stato HTTP (default: 303 See Other)
 * 
 * Esempi di uso:
 * - redirect('docenti.php') -> Semplice redirect
 * - redirect('edit.php', ['id' => 123]) -> Redirect con parametri
 * - redirect('login.php', [], 401) -> Redirect con status specifico
 */
function redirect($url, $params = [], $status = 303) {
    // === COSTRUZIONE QUERY STRING ===
    if (!empty($params)) {
        // Determina il separatore (? se non presente, & se già presente)
        $url .= (strpos($url, '?') === false) ? '?' : '&';
        // http_build_query gestisce automaticamente l'encoding URL
        $url .= http_build_query($params);
    }
    
    // === INVIO HEADER DI REDIRECT ===
    // 303 See Other è lo status più appropriato per redirect POST->GET
    header('Location: ' . $url, true, $status);
    exit; // Importante: interrompe l'esecuzione dello script
}

/**
 * Restituisce un messaggio flash dalla sessione e lo rimuove
 * 
 * Sistema di messaggi temporanei per comunicare tra pagine (es. conferme post-redirect).
 * I messaggi vengono automaticamente rimossi dopo la lettura.
 * 
 * @param string $key Chiave del messaggio flash
 * @param mixed $default Valore predefinito se il messaggio non esiste
 * @return mixed Messaggio flash o valore predefinito
 * 
 * Uso tipico:
 * - Impostazione: set_flash('success', 'Operazione completata')
 * - Lettura: $message = get_flash('success', 'Nessun messaggio')
 */
function get_flash($key, $default = null) {
    // === INIZIALIZZAZIONE SESSIONE (SE NECESSARIA) ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // === LETTURA E RIMOZIONE ===
    if (isset($_SESSION['flash'][$key])) {
        $value = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]); // Rimuove dopo la lettura (pattern flash)
        return $value;
    }
    
    return $default;
}

/**
 * Imposta un messaggio flash nella sessione
 * 
 * Memorizza messaggi temporanei che sopravvivono ai redirect ma vengono
 * automaticamente rimossi dopo la prima lettura.
 * 
 * @param string $key Chiave del messaggio flash
 * @param mixed $value Valore del messaggio da memorizzare
 * 
 * Esempi di uso:
 * - set_flash('error', 'Operazione fallita')
 * - set_flash('info', ['titolo' => 'Info', 'dettagli' => 'Dettagli...'])
 */
function set_flash($key, $value) {
    // === INIZIALIZZAZIONE SESSIONE (SE NECESSARIA) ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // === MEMORIZZAZIONE MESSAGGIO ===
    $_SESSION['flash'][$key] = $value;
}

/**
 * Verifica se un utente è loggato controllando la sessione
 * 
 * Utility per controllo autenticazione. Da usare per proteggere
 * pagine riservate e personalizzare l'interfaccia.
 * 
 * @return bool True se l'utente è autenticato, False altrimenti
 * 
 * Uso tipico:
 * - if (!is_logged_in()) { redirect('login.php'); }
 * - if (is_logged_in()) { echo "Benvenuto, " . $_SESSION['username']; }
 */
function is_logged_in() {
    // === INIZIALIZZAZIONE SESSIONE (SE NECESSARIA) ===
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // === CONTROLLO PRESENZA ID UTENTE ===
    // Assumes that user_id is set when user logs in
    return isset($_SESSION['user_id']);
}

/**
 * Formatta un codice fiscale in modo più leggibile
 * 
 * Utility specifica per il sistema italiano che formatta i codici fiscali
 * in gruppi per migliorare la leggibilità.
 * 
 * @param string $cf Codice fiscale di 16 caratteri
 * @return string Codice fiscale formattato con trattini
 * 
 * Esempi di uso:
 * - format_cf('RSSMRA80A01H501Z') -> 'RSSMRA-80-A-01-H-501-Z'
 */
function format_cf($cf) {
    // === NORMALIZZAZIONE INPUT ===
    $cf = strtoupper($cf); // Converte in maiuscolo
    
    // === VALIDAZIONE LUNGHEZZA ===
    if (strlen($cf) !== 16) {
        return $cf; // Restituisce inalterato se non è valido
    }
    
    // === FORMATTAZIONE IN GRUPPI ===
    // Formatta seguendo la struttura logica del codice fiscale italiano:
    // 6 lettere (cognome+nome) - 2 cifre (anno) - 1 lettera (mese) - 
    // 2 cifre (giorno) - 1 lettera (genere) - 3 cifre (comune) - 1 lettera (controllo)
    return substr($cf, 0, 6) . '-' .   // Cognome + Nome (6)
           substr($cf, 6, 2) . '-' .   // Anno (2)
           substr($cf, 8, 1) . '-' .   // Mese (1)
           substr($cf, 9, 2) . '-' .   // Giorno (2)
           substr($cf, 11, 1) . '-' .  // Genere (1)
           substr($cf, 12, 3) . '-' .  // Comune (3)
           substr($cf, 15, 1);         // Controllo (1)
}

/**
 * Rimuove i caratteri speciali da una stringa mantenendo solo alfanumerici e spazi
 * 
 * Utility per sanitizzazione base di input utente, rimuovendo caratteri
 * potenzialmente problematici ma mantenendo la leggibilità.
 * 
 * @param string $string Stringa da pulire
 * @return string Stringa con solo caratteri alfanumerici e spazi
 * 
 * Esempi di uso:
 * - clean_string('Hello @#$ World!') -> 'Hello  World'
 * - clean_string('Café & Résumé') -> 'Caf  Rsum'
 */
function clean_string($string) {
    // === RIMOZIONE CARATTERI SPECIALI ===
    // Regex che mantiene solo lettere (a-z, A-Z), numeri (0-9) e spazi (\s)
    return preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
}

/**
 * Tronca una stringa alla lunghezza specificata aggiungendo punti di sospensione
 * 
 * Utility per limitare la lunghezza del testo nelle interfacce mantenendo
 * la leggibilità e indicando che il testo è stato troncato.
 * 
 * @param string $string Stringa da troncare
 * @param int $length Lunghezza massima (default: 100 caratteri)
 * @param string $append Stringa da aggiungere se troncata (default: '...')
 * @return string Stringa troncata con indicatore se necessario
 * 
 * Esempi di uso:
 * - truncate('Lorem ipsum dolor sit amet...', 10) -> 'Lorem ipsu...'
 * - truncate('Short text', 50) -> 'Short text' (non troncato)
 * - truncate('Long text here', 8, ' [more]') -> 'Long tex [more]'
 */
function truncate($string, $length = 100, $append = '...') {
    // === CONTROLLO NECESSITÀ DI TRONCAMENTO ===
    if (strlen($string) <= $length) {
        return $string; // Restituisce inalterato se è già abbastanza corto
    }
    
    // === TRONCAMENTO E PULIZIA ===
    $string = substr($string, 0, $length);     // Tronca alla lunghezza desiderata
    return rtrim($string) . $append;           // Rimuove spazi finali e aggiunge indicatore
}

/**
 * Genera un slug URL-friendly da una stringa
 * 
 * Converte stringhe in formato adatto per URL, rimuovendo accenti,
 * caratteri speciali e sostituendo spazi con trattini.
 * 
 * @param string $string Stringa di input da convertire
 * @return string Slug generato (formato: parole-separate-da-trattini)
 * 
 * Esempi di uso:
 * - generate_slug('Gestione Docenti & Corsi') -> 'gestione-docenti-corsi'
 * - generate_slug('Àccentì spéciàli') -> 'accenti-speciali'
 * - generate_slug('  Multiple   Spaces  ') -> 'multiple-spaces'
 */
function generate_slug($string) {
    // === NORMALIZZAZIONE CASE ===
    // Converte tutto in minuscolo per consistenza
    $string = strtolower($string);
    
    // === RIMOZIONE ACCENTI ===
    // iconv con TRANSLIT sostituisce caratteri accentati con equivalenti ASCII
    // es: 'à' -> 'a', 'è' -> 'e', 'ñ' -> 'n'
    $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
    
    // === RIMOZIONE CARATTERI SPECIALI ===
    // Mantiene solo lettere, numeri e spazi
    $string = preg_replace('/[^a-z0-9\s]/', '', $string);
    
    // === SOSTITUZIONE SPAZI CON TRATTINI ===
    // \s+ matches uno o più spazi/whitespace consecutivi
    $string = preg_replace('/\s+/', '-', $string);
    
    // === PULIZIA TRATTINI MULTIPLI ===
    // Sostituisce sequenze di trattini con un singolo trattino
    $string = preg_replace('/-+/', '-', $string);
    
    // === RIMOZIONE TRATTINI INIZIALI/FINALI ===
    // trim rimuove caratteri specificati dall'inizio e fine della stringa
    return trim($string, '-');
}

/**
 * Valida un indirizzo email secondo gli standard RFC
 * 
 * Wrapper attorno alla funzione nativa PHP filter_var con filtro email,
 * che implementa una validazione completa secondo gli standard.
 * 
 * @param string $email Indirizzo email da validare
 * @return bool True se l'email è valida, False altrimenti
 * 
 * Esempi di uso:
 * - is_valid_email('user@example.com') -> true
 * - is_valid_email('invalid.email') -> false
 * - is_valid_email('test@') -> false
 */
function is_valid_email($email) {
    // === VALIDAZIONE NATIVA PHP ===
    // filter_var con FILTER_VALIDATE_EMAIL è il metodo raccomandato
    // Implementa la validazione completa secondo RFC 5322
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitizza una stringa per output HTML sicuro
 * 
 * Wrapper migliorato attorno a htmlspecialchars per prevenire XSS
 * con configurazione ottimale per applicazioni web moderne.
 * 
 * @param string $string Stringa da sanitizzare
 * @param bool $preserve_newlines Se preservare i newline convertendoli in <br>
 * @return string Stringa sicura per output HTML
 * 
 * Esempi di uso:
 * - html_escape('<script>alert("xss")</script>') -> '&lt;script&gt;alert("xss")&lt;/script&gt;'
 * - html_escape("Line 1\nLine 2", true) -> 'Line 1<br>Line 2'
 */
function html_escape($string, $preserve_newlines = false) {
    // === ESCAPE HTML ===
    // ENT_QUOTES: escape sia singole che doppie quotes
    // ENT_HTML5: usa entità HTML5
    // 'UTF-8': specifica encoding per sicurezza
    $escaped = htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // === PRESERVAZIONE NEWLINE (OPZIONALE) ===
    if ($preserve_newlines) {
        // Converte \n in <br> per preservare formatting in HTML
        $escaped = nl2br($escaped);
    }
    
    return $escaped;
}

/**
 * Genera una password casuale sicura
 * 
 * Crea password con caratteri misti per alta entropia, utile per
 * password temporanee o reset password.
 * 
 * @param int $length Lunghezza della password (default: 12)
 * @param bool $include_symbols Se includere simboli speciali (default: true)
 * @return string Password generata casualmente
 * 
 * Esempi di uso:
 * - generate_password() -> 'aB3$fG9#mN2k'
 * - generate_password(8, false) -> 'aB3fG9mN'
 */
function generate_password($length = 12, $include_symbols = true) {
    // === DEFINIZIONE SET DI CARATTERI ===
    $chars = 'abcdefghijklmnopqrstuvwxyz';      // Lettere minuscole
    $chars .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';     // Lettere maiuscole  
    $chars .= '0123456789';                     // Numeri
    
    // === AGGIUNTA SIMBOLI (OPZIONALE) ===
    if ($include_symbols) {
        $chars .= '!@#$%^&*()_+-=[]{}|;:,.<>?'; // Simboli speciali
    }
    
    // === GENERAZIONE PASSWORD ===
    $password = '';
    $chars_length = strlen($chars) - 1;        // -1 perché array è 0-indexed
    
    for ($i = 0; $i < $length; $i++) {
        // random_int è crittograficamente sicuro (meglio di rand())
        $password .= $chars[random_int(0, $chars_length)];
    }
    
    return $password;
}

/**
 * Calcola il tempo trascorso in formato human-readable
 * 
 * Converte timestamp in descrizioni relative come "2 ore fa" o "ieri",
 * utile per interfacce social e feed di attività.
 * 
 * @param string|int $datetime Timestamp o stringa data da convertire
 * @return string Descrizione del tempo trascorso
 * 
 * Esempi di uso:
 * - time_ago(time() - 3600) -> '1 ora fa'
 * - time_ago('2024-01-01 10:00:00') -> '3 mesi fa'
 */
function time_ago($datetime) {
    // === NORMALIZZAZIONE INPUT ===
    // Converte vari formati in timestamp Unix
    if (is_string($datetime)) {
        $datetime = strtotime($datetime);
    }
    
    // === CALCOLO DIFFERENZA ===
    $difference = time() - $datetime;
    
    // === GESTIONE FUTURO ===
    if ($difference < 0) {
        return 'in futuro'; // Gestisce date future
    }
    
    // === ARRAY DI UNITÀ TEMPORALI ===
    // Ordinato dal più grande al più piccolo
    $periods = [
        'anno'    => 31536000,  // 365 * 24 * 60 * 60
        'mese'    => 2628000,   // 30.44 * 24 * 60 * 60 (media)
        'settimana' => 604800,  // 7 * 24 * 60 * 60
        'giorno'  => 86400,     // 24 * 60 * 60
        'ora'     => 3600,      // 60 * 60
        'minuto'  => 60,
        'secondo' => 1
    ];
    
    // === DETERMINAZIONE UNITÀ APPROPRIATA ===
    foreach ($periods as $unit => $seconds) {
        if ($difference >= $seconds) {
            $count = floor($difference / $seconds);
            
            // === PLURALIZZAZIONE ITALIANA ===
            if ($count == 1) {
                return "1 $unit fa";
            } else {
                // Gestione plurali irregolari italiani
                $plural = $unit;
                if ($unit === 'anno') $plural = 'anni';
                elseif ($unit === 'mese') $plural = 'mesi';
                elseif ($unit === 'settimana') $plural = 'settimane';
                elseif ($unit === 'giorno') $plural = 'giorni';
                elseif ($unit === 'ora') $plural = 'ore';
                elseif ($unit === 'minuto') $plural = 'minuti';
                elseif ($unit === 'secondo') $plural = 'secondi';
                
                return "$count $plural fa";
            }
        }
    }
    
    // === FALLBACK ===
    return 'proprio ora'; // Per differenze < 1 secondo
}

/**
 * Converte byte in formato human-readable (KB, MB, GB, etc.)
 * 
 * Utility per mostrare dimensioni di file in formato comprensibile,
 * usando il sistema binario (1024) o decimale (1000).
 * 
 * @param int $bytes Numero di byte da convertire
 * @param int $precision Decimali da mostrare (default: 2)
 * @param bool $binary_system Se usare sistema binario 1024 vs decimale 1000
 * @return string Dimensione formattata con unità
 * 
 * Esempi di uso:
 * - format_bytes(1536) -> '1.50 KB'
 * - format_bytes(1048576) -> '1.00 MB'
 * - format_bytes(1000000, 1, false) -> '1.0 MB' (sistema decimale)
 */
function format_bytes($bytes, $precision = 2, $binary_system = true) {
    // === GESTIONE CASO ZERO ===
    if ($bytes == 0) {
        return '0 B';
    }
    
    // === DEFINIZIONE UNITÀ E BASE ===
    if ($binary_system) {
        // Sistema binario: 1024 byte = 1 KB
        $base = 1024;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    } else {
        // Sistema decimale: 1000 byte = 1 KB  
        $base = 1000;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    }
    
    // === CALCOLO UNITÀ APPROPRIATA ===
    // log() calcola in quale "scala" si trova il numero
    $power = floor(log($bytes, $base));
    
    // === LIMITAZIONE AGLI INDICI DISPONIBILI ===
    $power = min($power, count($units) - 1);
    
    // === FORMATTAZIONE FINALE ===
    $size = $bytes / pow($base, $power);
    
    return round($size, $precision) . ' ' . $units[$power];
}
