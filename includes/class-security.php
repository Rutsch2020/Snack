<?php
/**
 * Security Manager - Enterprise Security Layer
 * 
 * Umfassende Sicherheits- und Validation-Funktionen für AutomatenManager Pro.
 * Bietet Schutz vor häufigen Web-Angriffen und stellt Enterprise-Level Security sicher.
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Core
 * @version     1.0.0
 * @since       1.0.0
 * @author      AutomatenManager Pro Team
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Core;

use AutomatenManagerPro\Database\AMP_Database_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enterprise Security Manager
 * 
 * Zentrale Sicherheitsklasse für:
 * - Input-Sanitization und Validation
 * - CSRF-Protection für alle AJAX-Requests
 * - SQL-Injection Schutz mit Prepared Statements
 * - File-Upload Validation und Security
 * - Rate-Limiting für Scanner-Requests
 * - Security Headers und Content-Security-Policy
 * - Audit-Logging für Security-Events
 * 
 * @since 1.0.0
 */
class AMP_Security_Manager
{
    private AMP_Database_Manager $database;
    
    /**
     * Rate-Limiting Cache
     * @var array
     */
    private static array $rate_limit_cache = [];
    
    /**
     * Erlaubte Dateitypen für Uploads
     * @var array
     */
    private array $allowed_file_types = [
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    /**
     * Maximale Dateigröße für Uploads (in Bytes)
     * @var int
     */
    private int $max_file_size = 5242880; // 5MB
    
    /**
     * Security Manager Konstruktor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->init_security_hooks();
    }
    
    /**
     * WordPress Security Hooks initialisieren
     * 
     * @since 1.0.0
     */
    private function init_security_hooks(): void
    {
        // WordPress Security Headers
        add_action('send_headers', [$this, 'add_security_headers']);
        
        // AJAX Security
        add_action('wp_ajax_nopriv_*', [$this, 'block_unauthorized_ajax'], 1);
        
        // File Upload Security
        add_filter('wp_handle_upload_prefilter', [$this, 'secure_file_upload']);
        add_filter('upload_mimes', [$this, 'restrict_upload_mimes']);
        
        // Form Security
        add_action('admin_init', [$this, 'verify_admin_forms']);
        
        // Content Security Policy
        add_action('wp_head', [$this, 'add_content_security_policy']);
        add_action('admin_head', [$this, 'add_content_security_policy']);
        
        // Rate Limiting für Scanner
        add_action('wp_ajax_amp_process_scan', [$this, 'check_scanner_rate_limit'], 1);
        
        // Security Audit Logging
        add_action('wp_login', [$this, 'log_security_event_login'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_security_event_failed_login']);
        
        // Plugin-spezifische Security
        add_action('init', [$this, 'init_plugin_security']);
    }
    
    /**
     * HTTP Security Headers hinzufügen
     * 
     * @since 1.0.0
     */
    public function add_security_headers(): void
    {
        // Nur für AutomatenManager Pro Admin-Seiten
        if (!$this->is_amp_admin_page()) {
            return;
        }
        
        // X-Frame-Options (Clickjacking-Schutz)
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            
            // X-Content-Type-Options (MIME-Type Sniffing verhindern)
            header('X-Content-Type-Options: nosniff');
            
            // X-XSS-Protection (XSS-Filter aktivieren)
            header('X-XSS-Protection: 1; mode=block');
            
            // Referrer Policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Permissions Policy (Feature Policy)
            header('Permissions-Policy: camera=self, microphone=(), geolocation=(), payment=()');
            
            // Strict-Transport-Security (HTTPS erzwingen)
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
            }
        }
    }
    
    /**
     * Content Security Policy hinzufügen
     * 
     * @since 1.0.0
     */
    public function add_content_security_policy(): void
    {
        if (!$this->is_amp_admin_page()) {
            return;
        }
        
        $nonce = wp_create_nonce('amp_csp_nonce');
        
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' https://fonts.gstatic.com",
            "connect-src 'self' https:",
            "media-src 'self' blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'"
        ];
        
        $csp_header = implode('; ', $csp_directives);
        
        echo "<meta http-equiv='Content-Security-Policy' content='{$csp_header}'>\n";
        echo "<script nonce='{$nonce}'>window.amp_csp_nonce = '{$nonce}';</script>\n";
    }
    
    /**
     * Input-Sanitization für alle Plugin-Eingaben
     * 
     * @param mixed $input Benutzer-Eingabe
     * @param string $type Eingabe-Typ
     * @param array $options Zusätzliche Optionen
     * @return mixed Sanitized Input
     * @since 1.0.0
     */
    public function sanitize_input($input, string $type = 'text', array $options = [])
    {
        if (is_null($input)) {
            return null;
        }
        
        switch ($type) {
            case 'text':
                return sanitize_text_field($input);
                
            case 'textarea':
                return sanitize_textarea_field($input);
                
            case 'email':
                $email = sanitize_email($input);
                return is_email($email) ? $email : '';
                
            case 'url':
                return esc_url_raw($input);
                
            case 'int':
                return (int) $input;
                
            case 'float':
                return (float) $input;
                
            case 'decimal':
                $decimal = number_format((float) $input, $options['decimals'] ?? 2, '.', '');
                return (float) $decimal;
                
            case 'barcode':
                // Barcode-spezifische Sanitization
                $barcode = preg_replace('/[^0-9A-Za-z\-]/', '', $input);
                return substr($barcode, 0, 50); // Max 50 Zeichen
                
            case 'filename':
                return sanitize_file_name($input);
                
            case 'slug':
                return sanitize_title($input);
                
            case 'html':
                return wp_kses_post($input);
                
            case 'json':
                $decoded = json_decode($input, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
                
            case 'date':
                return $this->sanitize_date($input);
                
            case 'currency':
                return $this->sanitize_currency($input);
                
            case 'array':
                return is_array($input) ? array_map('sanitize_text_field', $input) : [];
                
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * CSRF-Protection für AJAX-Requests
     * 
     * @param string $action AJAX-Action
     * @param string $nonce Nonce-Wert
     * @return bool Gültiger Nonce
     * @since 1.0.0
     */
    public function verify_ajax_nonce(string $action = 'amp_admin_nonce', string $nonce = ''): bool
    {
        if (empty($nonce)) {
            $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        }
        
        if (!wp_verify_nonce($nonce, $action)) {
            $this->log_security_event('csrf_violation', [
                'action' => $action,
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'referer' => wp_get_referer()
            ]);
            
            return false;
        }
        
        return true;
    }
    
    /**
     * SQL-Injection Schutz durch Prepared Statements
     * 
     * @param string $query SQL-Query mit Platzhaltern
     * @param array $params Parameter für Prepared Statement
     * @return string|null Sichere SQL-Query oder null bei Fehler
     * @since 1.0.0
     */
    public function prepare_query(string $query, array $params = []): ?string
    {
        global $wpdb;
        
        try {
            // Gefährliche SQL-Keywords prüfen
            $dangerous_keywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'EXEC', 'UNION'];
            $query_upper = strtoupper($query);
            
            foreach ($dangerous_keywords as $keyword) {
                if (strpos($query_upper, $keyword) !== false && !$this->is_allowed_sql_operation($keyword, $query)) {
                    $this->log_security_event('sql_injection_attempt', [
                        'query' => $query,
                        'keyword' => $keyword,
                        'ip' => $this->get_client_ip()
                    ]);
                    return null;
                }
            }
            
            // Prepared Statement erstellen
            if (!empty($params)) {
                return $wpdb->prepare($query, $params);
            }
            
            return $query;
            
        } catch (Exception $e) {
            $this->log_security_event('sql_preparation_error', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            return null;
        }
    }
    
    /**
     * File-Upload Validation und Security
     * 
     * @param array $file WordPress Upload-File Array
     * @return array Validiertes File Array
     * @since 1.0.0
     */
    public function secure_file_upload(array $file): array
    {
        // Nur für AutomatenManager Pro Uploads
        if (!$this->is_amp_upload()) {
            return $file;
        }
        
        // Dateigröße prüfen
        if ($file['size'] > $this->max_file_size) {
            $file['error'] = sprintf(
                'Datei zu groß. Maximum: %s MB',
                number_format($this->max_file_size / 1024 / 1024, 1)
            );
            return $file;
        }
        
        // MIME-Type validieren
        if (!in_array($file['type'], $this->allowed_file_types)) {
            $file['error'] = 'Dateityp nicht erlaubt. Erlaubt: JPG, PNG, GIF, WebP';
            return $file;
        }
        
        // Dateiinhalt validieren (Magic Bytes)
        if (!$this->validate_file_content($file['tmp_name'], $file['type'])) {
            $file['error'] = 'Dateiinhalt entspricht nicht dem angegebenen Typ';
            return $file;
        }
        
        // Dateiname sanitizen
        $file['name'] = $this->sanitize_filename($file['name']);
        
        // Malware-Scan (einfache Heuristik)
        if ($this->contains_malware_signatures($file['tmp_name'])) {
            $file['error'] = 'Datei enthält verdächtige Inhalte';
            $this->log_security_event('malware_upload_attempt', [
                'filename' => $file['name'],
                'ip' => $this->get_client_ip()
            ]);
            return $file;
        }
        
        return $file;
    }
    
    /**
     * Rate-Limiting für Scanner-Requests
     * 
     * @param string $identifier Eindeutige Kennung (IP, User-ID)
     * @param int $limit Maximale Requests
     * @param int $window Zeitfenster in Sekunden
     * @return bool Rate-Limit überschritten
     * @since 1.0.0
     */
    public function check_rate_limit(string $identifier, int $limit = 60, int $window = 60): bool
    {
        $cache_key = "amp_rate_limit_{$identifier}";
        $current_time = time();
        
        // Cache-Eintrag abrufen
        $requests = get_transient($cache_key) ?: [];
        
        // Alte Requests entfernen (außerhalb des Zeitfensters)
        $requests = array_filter($requests, function($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        });
        
        // Rate-Limit prüfen
        if (count($requests) >= $limit) {
            $this->log_security_event('rate_limit_exceeded', [
                'identifier' => $identifier,
                'limit' => $limit,
                'window' => $window,
                'requests_count' => count($requests)
            ]);
            return true;
        }
        
        // Aktuellen Request hinzufügen
        $requests[] = $current_time;
        
        // Cache aktualisieren
        set_transient($cache_key, $requests, $window + 10);
        
        return false;
    }
    
    /**
     * Scanner Rate-Limit prüfen (Hook)
     * 
     * @since 1.0.0
     */
    public function check_scanner_rate_limit(): void
    {
        $identifier = $this->get_rate_limit_identifier();
        
        // 60 Scans pro Minute erlaubt
        if ($this->check_rate_limit($identifier, 60, 60)) {
            wp_send_json_error([
                'message' => 'Rate-Limit erreicht. Bitte warten Sie eine Minute.',
                'code' => 'RATE_LIMIT_EXCEEDED'
            ]);
        }
    }
    
    /**
     * Unauthorized AJAX-Requests blockieren
     * 
     * @since 1.0.0
     */
    public function block_unauthorized_ajax(): void
    {
        $action = $_REQUEST['action'] ?? '';
        
        // Nur AutomatenManager Pro AJAX-Actions prüfen
        if (strpos($action, 'amp_') === 0) {
            $this->log_security_event('unauthorized_ajax_attempt', [
                'action' => $action,
                'ip' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            wp_die('Unauthorized', 'Unauthorized', ['response' => 403]);
        }
    }
    
    /**
     * Admin-Formulare verifizieren
     * 
     * @since 1.0.0
     */
    public function verify_admin_forms(): void
    {
        // Nur für AutomatenManager Pro Admin-Seiten
        if (!$this->is_amp_admin_page()) {
            return;
        }
        
        // POST-Requests prüfen
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce = $_POST['amp_nonce'] ?? '';
            
            if (!wp_verify_nonce($nonce, 'amp_admin_action')) {
                $this->log_security_event('invalid_form_nonce', [
                    'page' => $_GET['page'] ?? '',
                    'ip' => $this->get_client_ip()
                ]);
                
                wp_die('Security check failed', 'Security Error', ['response' => 403]);
            }
        }
    }
    
    /**
     * Plugin-spezifische Security initialisieren
     * 
     * @since 1.0.0
     */
    public function init_plugin_security(): void
    {
        // Disable file editing im Admin
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        // WordPress-Versionsinfo verstecken
        remove_action('wp_head', 'wp_generator');
        
        // XML-RPC deaktivieren (falls nicht benötigt)
        add_filter('xmlrpc_enabled', '__return_false');
        
        // Login-Fehlermeldungen verschleiern
        add_filter('login_errors', function() {
            return 'Ungültige Anmeldedaten.';
        });
        
        // Plugin-Verzeichnis-Browsing verhindern
        $this->create_security_files();
    }
    
    /**
     * Security-Event protokollieren
     * 
     * @param string $event_type Art des Security-Events
     * @param array $details Event-Details
     * @since 1.0.0
     */
    public function log_security_event(string $event_type, array $details = []): void
    {
        global $wpdb;
        
        $table_security_log = $wpdb->prefix . 'amp_security_log';
        
        $log_data = [
            'event_type' => $event_type,
            'details' => json_encode($details),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'severity' => $this->get_event_severity($event_type)
        ];
        
        // Tabelle erstellen falls nicht vorhanden
        $this->ensure_security_log_table();
        
        $wpdb->insert($table_security_log, $log_data);
        
        // Kritische Events zusätzlich in Error-Log
        if ($log_data['severity'] === 'critical') {
            error_log(sprintf(
                'AMP Security Alert [%s]: %s - IP: %s',
                $event_type,
                json_encode($details),
                $log_data['ip_address']
            ));
        }
    }
    
    /**
     * Login-Event protokollieren
     * 
     * @param string $user_login Username
     * @param WP_User $user User-Objekt
     * @since 1.0.0
     */
    public function log_security_event_login(string $user_login, $user): void
    {
        $this->log_security_event('successful_login', [
            'username' => $user_login,
            'user_id' => $user->ID,
            'user_roles' => $user->roles
        ]);
    }
    
    /**
     * Failed Login-Event protokollieren
     * 
     * @param string $username Username
     * @since 1.0.0
     */
    public function log_security_event_failed_login(string $username): void
    {
        $this->log_security_event('failed_login', [
            'username' => $username
        ]);
    }
    
    /**
     * Upload-MIME-Types einschränken
     * 
     * @param array $mimes Erlaubte MIME-Types
     * @return array Gefilterte MIME-Types
     * @since 1.0.0
     */
    public function restrict_upload_mimes(array $mimes): array
    {
        if (!$this->is_amp_upload()) {
            return $mimes;
        }
        
        // Nur sichere Bildformate erlauben
        $allowed_mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        return $allowed_mimes;
    }
    
    // =======================
    // VALIDATION HELPERS
    // =======================
    
    /**
     * Datum sanitizen und validieren
     * 
     * @param string $date Datum-String
     * @return string|null Gültiges Datum oder null
     * @since 1.0.0
     */
    private function sanitize_date(string $date): ?string
    {
        $timestamp = strtotime($date);
        
        if ($timestamp === false) {
            return null;
        }
        
        // Datum muss in vernünftigem Bereich liegen
        $min_date = strtotime('1900-01-01');
        $max_date = strtotime('+10 years');
        
        if ($timestamp < $min_date || $timestamp > $max_date) {
            return null;
        }
        
        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Währungsbetrag sanitizen
     * 
     * @param mixed $amount Betrag
     * @return float Sanitized Betrag
     * @since 1.0.0
     */
    private function sanitize_currency($amount): float
    {
        // Nur Zahlen, Komma und Punkt erlauben
        $cleaned = preg_replace('/[^0-9,.-]/', '', $amount);
        
        // Komma durch Punkt ersetzen (DE -> EN)
        $cleaned = str_replace(',', '.', $cleaned);
        
        // Auf 2 Dezimalstellen runden
        return round((float) $cleaned, 2);
    }
    
    /**
     * Dateiname sicher sanitizen
     * 
     * @param string $filename Original-Dateiname
     * @return string Sicherer Dateiname
     * @since 1.0.0
     */
    private function sanitize_filename(string $filename): string
    {
        // WordPress sanitize_file_name erweitern
        $filename = sanitize_file_name($filename);
        
        // Zusätzliche Sicherheits-Prüfungen
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Dopple Punkte vermeiden
        $filename = preg_replace('/\.+/', '.', $filename);
        
        // Prefix hinzufügen für Eindeutigkeit
        $filename = 'amp_' . time() . '_' . $filename;
        
        return substr($filename, 0, 100); // Max 100 Zeichen
    }
    
    /**
     * Dateiinhalt validieren (Magic Bytes)
     * 
     * @param string $file_path Pfad zur temporären Datei
     * @param string $expected_mime MIME-Type
     * @return bool Gültiger Dateiinhalt
     * @since 1.0.0
     */
    private function validate_file_content(string $file_path, string $expected_mime): bool
    {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_content = file_get_contents($file_path, false, null, 0, 50);
        
        if ($file_content === false) {
            return false;
        }
        
        // Magic Bytes für gängige Bildformate
        $magic_bytes = [
            'image/jpeg' => ['\xFF\xD8\xFF'],
            'image/png' => ['\x89PNG\r\n\x1a\n'],
            'image/gif' => ['GIF87a', 'GIF89a'],
            'image/webp' => ['RIFF']
        ];
        
        if (!isset($magic_bytes[$expected_mime])) {
            return false;
        }
        
        foreach ($magic_bytes[$expected_mime] as $magic) {
            if (strpos($file_content, $magic) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Einfache Malware-Signatur-Prüfung
     * 
     * @param string $file_path Pfad zur Datei
     * @return bool Enthält verdächtige Signaturen
     * @since 1.0.0
     */
    private function contains_malware_signatures(string $file_path): bool
    {
        if (!file_exists($file_path)) {
            return false;
        }
        
        $content = file_get_contents($file_path);
        
        if ($content === false) {
            return false;
        }
        
        // Verdächtige Strings in Bilddateien
        $suspicious_patterns = [
            '<?php',
            '<%',
            '<script',
            'eval(',
            'base64_decode',
            'shell_exec',
            'system(',
            'exec(',
            'passthru(',
            'file_get_contents',
            'curl_exec'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * SQL-Operation erlaubt prüfen
     * 
     * @param string $keyword SQL-Keyword
     * @param string $query Vollständige Query
     * @return bool Operation erlaubt
     * @since 1.0.0
     */
    private function is_allowed_sql_operation(string $keyword, string $query): bool
    {
        // Whitelist für erlaubte Operationen
        $allowed_operations = [
            'CREATE' => ['CREATE TABLE IF NOT EXISTS wp_amp_'], // Nur Plugin-Tabellen
            'DROP' => ['DROP TABLE IF EXISTS wp_amp_'],          // Nur Plugin-Tabellen
            'DELETE' => ['DELETE FROM wp_amp_'],                 // Nur Plugin-Tabellen
            'ALTER' => ['ALTER TABLE wp_amp_']                   // Nur Plugin-Tabellen
        ];
        
        if (!isset($allowed_operations[$keyword])) {
            return false;
        }
        
        foreach ($allowed_operations[$keyword] as $allowed_pattern) {
            if (strpos($query, $allowed_pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    // =======================
    // HELPER METHODEN
    // =======================
    
    /**
     * Client-IP sicher ermitteln
     * 
     * @return string Client-IP
     * @since 1.0.0
     */
    private function get_client_ip(): string
    {
        $ip_headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Mehrere IPs durch Komma getrennt
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // IP validieren
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Rate-Limit Identifier generieren
     * 
     * @return string Eindeutige Kennung
     * @since 1.0.0
     */
    private function get_rate_limit_identifier(): string
    {
        $user_id = get_current_user_id();
        
        if ($user_id > 0) {
            return "user_{$user_id}";
        }
        
        return "ip_" . md5($this->get_client_ip());
    }
    
    /**
     * AutomatenManager Pro Admin-Seite prüfen
     * 
     * @return bool Ist AMP Admin-Seite
     * @since 1.0.0
     */
    private function is_amp_admin_page(): bool
    {
        if (!is_admin()) {
            return false;
        }
        
        $page = $_GET['page'] ?? '';
        return strpos($page, 'automaten-manager') === 0;
    }
    
    /**
     * AutomatenManager Pro Upload prüfen
     * 
     * @return bool Ist AMP Upload
     * @since 1.0.0
     */
    private function is_amp_upload(): bool
    {
        $context = $_POST['context'] ?? $_GET['context'] ?? '';
        return $context === 'amp_disposal_photo' || $context === 'amp_product_image';
    }
    
    /**
     * Event-Schweregrad bestimmen
     * 
     * @param string $event_type Event-Typ
     * @return string Schweregrad
     * @since 1.0.0
     */
    private function get_event_severity(string $event_type): string
    {
        $critical_events = [
            'sql_injection_attempt',
            'malware_upload_attempt',
            'unauthorized_admin_access'
        ];
        
        $warning_events = [
            'csrf_violation',
            'rate_limit_exceeded',
            'failed_login',
            'unauthorized_ajax_attempt'
        ];
        
        if (in_array($event_type, $critical_events)) {
            return 'critical';
        } elseif (in_array($event_type, $warning_events)) {
            return 'warning';
        }
        
        return 'info';
    }
    
    /**
     * Security-Log Tabelle sicherstellen
     * 
     * @since 1.0.0
     */
    private function ensure_security_log_table(): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_security_log';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$table_name} (
                id int(11) NOT NULL AUTO_INCREMENT,
                event_type varchar(100) NOT NULL,
                details longtext,
                ip_address varchar(45) NOT NULL,
                user_agent text,
                user_id int(11) DEFAULT NULL,
                timestamp datetime NOT NULL,
                severity enum('info','warning','critical') DEFAULT 'info',
                PRIMARY KEY (id),
                KEY event_type (event_type),
                KEY ip_address (ip_address),
                KEY timestamp (timestamp),
                KEY severity (severity)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Security-Dateien erstellen (.htaccess, index.php)
     * 
     * @since 1.0.0
     */
    private function create_security_files(): void
    {
        $protected_dirs = [
            AMP_PLUGIN_DIR . 'logs/',
            AMP_PLUGIN_DIR . 'cache/',
            AMP_PLUGIN_DIR . 'uploads/'
        ];
        
        foreach ($protected_dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            
            // .htaccess für Zugriffsbeschränkung
            $htaccess_file = $dir . '.htaccess';
            if (!file_exists($htaccess_file)) {
                file_put_contents($htaccess_file, "deny from all\n");
            }
            
            // index.php gegen Directory-Browsing
            $index_file = $dir . 'index.php';
            if (!file_exists($index_file)) {
                file_put_contents($index_file, "<?php\n// Silence is golden.\n");
            }
        }
    }
    
    /**
     * Aktueller Nonce für JavaScript
     * 
     * @return string Nonce-Wert
     * @since 1.0.0
     */
    public function get_current_nonce(): string
    {
        return wp_create_nonce('amp_admin_nonce');
    }
    
    /**
     * Security-Status abrufen
     * 
     * @return array Security-Status
     * @since 1.0.0
     */
    public function get_security_status(): array
    {
        return [
            'https_enabled' => is_ssl(),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'file_editing_disabled' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT,
            'xmlrpc_disabled' => !has_filter('xmlrpc_enabled'),
            'security_headers_active' => true,
            'rate_limiting_active' => true,
            'upload_restrictions_active' => true,
            'audit_logging_active' => true
        ];
    }
    
    /**
     * Security-Empfehlungen generieren
     * 
     * @return array Empfehlungen
     * @since 1.0.0
     */
    public function get_security_recommendations(): array
    {
        $recommendations = [];
        
        if (!is_ssl()) {
            $recommendations[] = [
                'type' => 'warning',
                'message' => 'HTTPS ist nicht aktiviert. SSL-Zertifikat empfohlen.',
                'action' => 'SSL-Zertifikat installieren'
            ];
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $recommendations[] = [
                'type' => 'info',
                'message' => 'WordPress Debug-Modus ist aktiviert.',
                'action' => 'Debug-Modus in Produktion deaktivieren'
            ];
        }
        
        return $recommendations;
    }
}