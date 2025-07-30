<?php
/**
 * Waste Manager - VOLLSTÄNDIG REPARIERTE VERSION
 * 
 * Verwaltet Entsorgungsprotokoll, Verlust-Management und Foto-Dokumentation
 * KRITISCHER FIX: add_waste_photo_mime_types() ist jetzt PUBLIC!
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Waste
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Waste;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Products\AMP_Products_Manager;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Waste Manager Class - VOLLSTÄNDIG REPARIERT
 * 
 * @since 1.0.0
 */
class AMP_Waste_Manager
{
    /**
     * Database Manager
     * @var AMP_Database_Manager
     */
    private AMP_Database_Manager $database;
    
    /**
     * Products Manager
     * @var AMP_Products_Manager|null
     */
    private ?AMP_Products_Manager $products_manager = null;
    
    /**
     * Erlaubte Entsorgungsgründe
     * @var array
     */
    private array $disposal_reasons = [
        'expired' => 'Abgelaufen',
        'damaged' => 'Beschädigt', 
        'contaminated' => 'Kontaminiert',
        'recall' => 'Rückruf',
        'inventory_correction' => 'Inventur-Korrektur',
        'other' => 'Sonstiges'
    ];
    
    /**
     * Upload-Verzeichnis für Beweis-Fotos
     * @var string
     */
    private string $upload_dir = '';
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        
        // Products Manager laden wenn verfügbar
        if (class_exists('AutomatenManagerPro\Products\AMP_Products_Manager')) {
            $this->products_manager = new AMP_Products_Manager();
        }
        
        // Upload-Verzeichnis konfigurieren
        $this->setup_upload_directory();
        
        // WordPress Hooks registrieren
        $this->register_hooks();
        
        error_log('AMP Waste Manager: Erfolgreich initialisiert');
    }
    
    /**
     * WordPress Hooks registrieren
     * 
     * @since 1.0.0
     */
    private function register_hooks(): void
    {
        // KRITISCHER FIX: Foto MIME-Types hinzufügen (PUBLIC Methode!)
        add_filter('upload_mimes', [$this, 'add_waste_photo_mime_types']);
        
        // Upload-Verzeichnis erweitern
        add_filter('wp_handle_upload_prefilter', [$this, 'handle_waste_photo_upload']);
        
        // Admin AJAX Hooks
        add_action('wp_ajax_amp_log_waste_disposal', [$this, 'ajax_log_disposal']);
        add_action('wp_ajax_amp_get_waste_entries', [$this, 'ajax_get_waste_entries']);
        add_action('wp_ajax_amp_delete_waste_entry', [$this, 'ajax_delete_waste_entry']);
        add_action('wp_ajax_amp_download_waste_photos', [$this, 'ajax_download_photos']);
        add_action('wp_ajax_amp_get_waste_stats', [$this, 'ajax_get_waste_stats']);
        
        // Cron-Job für alte Fotos aufräumen
        add_action('amp_cleanup_old_waste_photos', [$this, 'cleanup_old_photos']);
        
        if (!wp_next_scheduled('amp_cleanup_old_waste_photos')) {
            wp_schedule_event(time(), 'weekly', 'amp_cleanup_old_waste_photos');
        }
    }
    
    /**
     * KRITISCHER FIX: MIME-Types für Beweis-Fotos hinzufügen
     * 
     * WICHTIG: Diese Methode MUSS public sein, da sie als WordPress Filter Callback verwendet wird!
     * 
     * @param array $mimes Aktuelle MIME-Types
     * @return array Erweiterte MIME-Types
     * @since 1.0.0
     */
    public function add_waste_photo_mime_types(array $mimes): array
    {
        // Zusätzliche Foto-Formate für Beweis-Dokumentation
        $mimes['heic'] = 'image/heic';           // iPhone Fotos
        $mimes['heif'] = 'image/heif';           // iPhone Fotos
        $mimes['webp'] = 'image/webp';           // Moderne Browser
        $mimes['avif'] = 'image/avif';           // Neueste Browser
        
        error_log('AMP Waste Manager: MIME-Types für Beweis-Fotos hinzugefügt');
        
        return $mimes;
    }
    
    /**
     * Upload-Verzeichnis konfigurieren
     * 
     * @since 1.0.0
     */
    private function setup_upload_directory(): void
    {
        $upload_info = wp_upload_dir();
        $this->upload_dir = $upload_info['basedir'] . '/amp-waste-photos';
        
        // Verzeichnis erstellen falls nicht vorhanden
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // .htaccess für Schutz erstellen
            $htaccess_content = "# AutomatenManager Pro - Waste Photos Protection\n";
            $htaccess_content .= "Options -Indexes\n";
            $htaccess_content .= "<Files \"*.php\">\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($this->upload_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Beweis-Foto Upload behandeln
     * 
     * @param array $file Upload-Datei Informationen
     * @return array Modifizierte Datei-Informationen
     * @since 1.0.0
     */
    public function handle_waste_photo_upload(array $file): array
    {
        // Nur bei Waste-Foto Uploads aktiv
        if (!isset($_POST['amp_waste_photo']) || $_POST['amp_waste_photo'] !== '1') {
            return $file;
        }
        
        // Sicherheitsprüfungen
        if (!current_user_can('amp_dispose_products')) {
            $file['error'] = __('Keine Berechtigung für Foto-Upload', 'automaten-manager-pro');
            return $file;
        }
        
        // Dateiname sicherer machen
        $pathinfo = pathinfo($file['name']);
        $sanitized_name = sanitize_file_name($pathinfo['filename']);
        $timestamp = date('Y-m-d_H-i-s');
        $unique_id = uniqid();
        
        $file['name'] = "waste_{$timestamp}_{$unique_id}_{$sanitized_name}.{$pathinfo['extension']}";
        
        return $file;
    }
    
    /**
     * Entsorgung protokollieren
     * 
     * @param array $disposal_data Entsorgungsdaten
     * @return int|false Entsorgung-ID oder false bei Fehler
     * @since 1.0.0
     */
    public function log_disposal(array $disposal_data)
    {
        try {
            // Validierung
            $validated_data = $this->validate_disposal_data($disposal_data);
            if (is_wp_error($validated_data)) {
                throw new Exception($validated_data->get_error_message());
            }
            
            // Produkt-Informationen abrufen
            $product = $this->get_product_by_id($validated_data['product_id']);
            if (!$product) {
                throw new Exception('Produkt nicht gefunden');
            }
            
            // Entsorgungswert berechnen
            $disposal_value = $this->calculate_disposal_value($product, $validated_data['quantity']);
            
            // Entsorgungseintrag erstellen
            $waste_data = [
                'product_id' => $validated_data['product_id'],
                'quantity' => $validated_data['quantity'],
                'disposal_reason' => $validated_data['disposal_reason'],
                'disposal_notes' => $validated_data['disposal_notes'] ?? '',
                'disposal_value' => $disposal_value,
                'photo_paths' => isset($validated_data['photo_paths']) ? json_encode($validated_data['photo_paths']) : null,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ];
            
            $waste_id = $this->database->insert('waste_log', $waste_data);
            
            if (!$waste_id) {
                throw new Exception('Fehler beim Speichern der Entsorgung');
            }
            
            // Lagerbestand aktualisieren
            $this->update_product_stock($validated_data['product_id'], -$validated_data['quantity'], 'disposal', $waste_id);
            
            // E-Mail-Benachrichtigung senden
            $this->send_disposal_notification($waste_id, $product, $validated_data);
            
            error_log("AMP Waste Manager: Entsorgung #{$waste_id} erfolgreich protokolliert");
            
            return $waste_id;
            
        } catch (Exception $e) {
            error_log("AMP Waste Manager Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Entsorgungsdaten validieren
     * 
     * @param array $data Zu validierende Daten
     * @return array|\WP_Error Validierte Daten oder Fehler
     * @since 1.0.0
     */
    private function validate_disposal_data(array $data)
    {
        $errors = new \WP_Error();
        
        // Produkt-ID prüfen
        if (empty($data['product_id']) || !is_numeric($data['product_id'])) {
            $errors->add('invalid_product', 'Ungültige Produkt-ID');
        }
        
        // Menge prüfen
        if (empty($data['quantity']) || !is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors->add('invalid_quantity', 'Ungültige Menge');
        }
        
        // Entsorgungsgrund prüfen
        if (empty($data['disposal_reason']) || !array_key_exists($data['disposal_reason'], $this->disposal_reasons)) {
            $errors->add('invalid_reason', 'Ungültiger Entsorgungsgrund');
        }
        
        // Notizen sanitizen
        if (isset($data['disposal_notes'])) {
            $data['disposal_notes'] = sanitize_textarea_field($data['disposal_notes']);
        }
        
        if ($errors->has_errors()) {
            return $errors;
        }
        
        return $data;
    }
    
    /**
     * Entsorgungseinträge abrufen
     * 
     * @param array $args Query-Parameter
     * @return array Entsorgungseinträge
     * @since 1.0.0
     */
    public function get_waste_entries(array $args = []): array
    {
        global $wpdb;
        
        $defaults = [
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'date_from' => null,
            'date_to' => null,
            'disposal_reason' => null,
            'product_id' => null,
            'user_id' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $waste_table = $this->database->get_table('waste_log');
        $products_table = $this->database->get_table('products');
        
        $where_conditions = ['1=1'];
        $where_values = [];
        
        // Datumsfilter
        if ($args['date_from']) {
            $where_conditions[] = 'w.created_at >= %s';
            $where_values[] = $args['date_from'];
        }
        
        if ($args['date_to']) {
            $where_conditions[] = 'w.created_at <= %s';
            $where_values[] = $args['date_to'] . ' 23:59:59';
        }
        
        // Entsorgungsgrund
        if ($args['disposal_reason']) {
            $where_conditions[] = 'w.disposal_reason = %s';
            $where_values[] = $args['disposal_reason'];
        }
        
        // Produkt-Filter
        if ($args['product_id']) {
            $where_conditions[] = 'w.product_id = %d';
            $where_values[] = $args['product_id'];
        }
        
        // Benutzer-Filter
        if ($args['user_id']) {
            $where_conditions[] = 'w.user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $query = "
            SELECT w.*, 
                   p.name as product_name,
                   p.barcode as product_barcode,
                   p.buy_price as product_buy_price,
                   u.display_name as user_name
            FROM {$waste_table} w
            LEFT JOIN {$products_table} p ON w.product_id = p.id  
            LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
            WHERE {$where_clause}
            ORDER BY {$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d
        ";
        
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }
        
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Foto-Pfade dekodieren
        foreach ($results as &$entry) {
            if (!empty($entry['photo_paths'])) {
                $entry['photos'] = json_decode($entry['photo_paths'], true) ?: [];
                $entry['photo_count'] = count($entry['photos']);
            } else {
                $entry['photos'] = [];
                $entry['photo_count'] = 0;
            }
        }
        
        return $results;
    }
    
    /**
     * Entsorgungsstatistiken abrufen
     * 
     * @param array $args Parameter für Statistik-Abfrage
     * @return array Statistiken
     * @since 1.0.0
     */
    public function get_waste_statistics(array $args = []): array
    {
        global $wpdb;
        
        $defaults = [
            'date_from' => date('Y-m-d', strtotime('-7 days')),
            'date_to' => date('Y-m-d')
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $waste_table = $this->database->get_table('waste_log');
        
        // Grundstatistiken
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_entries,
                SUM(quantity) as total_quantity,
                SUM(disposal_value) as total_value,
                AVG(disposal_value / quantity) as avg_value_per_item
            FROM {$waste_table}
            WHERE created_at BETWEEN %s AND %s
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'), ARRAY_A);
        
        // Nach Entsorgungsgrund gruppiert
        $by_reason = $wpdb->get_results($wpdb->prepare("
            SELECT 
                disposal_reason,
                COUNT(*) as count,
                SUM(quantity) as quantity,
                SUM(disposal_value) as value
            FROM {$waste_table}
            WHERE created_at BETWEEN %s AND %s
            GROUP BY disposal_reason
            ORDER BY value DESC
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'), ARRAY_A);
        
        // Top Verlust-Produkte
        $products_table = $this->database->get_table('products');
        $top_products = $wpdb->get_results($wpdb->prepare("
            SELECT 
                w.product_id,
                p.name as product_name,
                SUM(w.quantity) as total_quantity,
                SUM(w.disposal_value) as total_value
            FROM {$waste_table} w
            LEFT JOIN {$products_table} p ON w.product_id = p.id
            WHERE w.created_at BETWEEN %s AND %s
            GROUP BY w.product_id
            ORDER BY total_value DESC
            LIMIT 10
        ", $args['date_from'], $args['date_to'] . ' 23:59:59'), ARRAY_A);
        
        // Trend-Vergleich (Vorwoche)
        $prev_week_start = date('Y-m-d', strtotime($args['date_from'] . ' -7 days'));
        $prev_week_end = date('Y-m-d', strtotime($args['date_to'] . ' -7 days'));
        
        $prev_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_entries,
                SUM(disposal_value) as total_value
            FROM {$waste_table}
            WHERE created_at BETWEEN %s AND %s
        ", $prev_week_start, $prev_week_end . ' 23:59:59'), ARRAY_A);
        
        // Trend berechnen
        $value_change = 0;
        if ($prev_stats['total_value'] > 0) {
            $value_change = (($stats['total_value'] - $prev_stats['total_value']) / $prev_stats['total_value']) * 100;
        }
        
        return [
            'period' => [
                'from' => $args['date_from'],
                'to' => $args['date_to']
            ],
            'totals' => $stats,
            'by_reason' => $by_reason,
            'top_products' => $top_products,
            'trends' => [
                'value_change_percent' => round($value_change, 1),
                'previous_period_value' => $prev_stats['total_value']
            ]
        ];
    }
    
    /**
     * Entsorgungseintrag löschen
     * 
     * @param int $waste_id Entsorgung-ID
     * @return bool Erfolg
     * @since 1.0.0
     */
    public function delete_waste_entry(int $waste_id): bool
    {
        try {
            // Eintrag abrufen für Foto-Cleanup
            $entry = $this->get_waste_entry_by_id($waste_id);
            if (!$entry) {
                return false;
            }
            
            // Fotos löschen
            if (!empty($entry['photo_paths'])) {
                $photos = json_decode($entry['photo_paths'], true);
                if (is_array($photos)) {
                    foreach ($photos as $photo_path) {
                        if (file_exists($photo_path)) {
                            unlink($photo_path);
                        }
                    }
                }
            }
            
            // Lagerbestand korrigieren (Entsorgung rückgängig machen)
            $this->update_product_stock($entry['product_id'], $entry['quantity'], 'disposal_reversal', $waste_id);
            
            // Datenbank-Eintrag löschen
            $result = $this->database->delete('waste_log', ['id' => $waste_id]);
            
            error_log("AMP Waste Manager: Entsorgung #{$waste_id} gelöscht");
            
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("AMP Waste Manager Error beim Löschen: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Alte Beweis-Fotos aufräumen
     * 
     * @param int $days_old Alter in Tagen (Standard: 365)
     * @since 1.0.0
     */
    public function cleanup_old_photos(int $days_old = 365): void
    {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d', strtotime("-{$days_old} days"));
        $waste_table = $this->database->get_table('waste_log');
        
        $old_entries = $wpdb->get_results($wpdb->prepare("
            SELECT id, photo_paths 
            FROM {$waste_table} 
            WHERE created_at < %s AND photo_paths IS NOT NULL
        ", $cutoff_date), ARRAY_A);
        
        $deleted_photos = 0;
        
        foreach ($old_entries as $entry) {
            $photos = json_decode($entry['photo_paths'], true);
            if (is_array($photos)) {
                foreach ($photos as $photo_path) {
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                        $deleted_photos++;
                    }
                }
                
                // Foto-Pfade aus Datenbank entfernen
                $wpdb->update(
                    $waste_table,
                    ['photo_paths' => null],
                    ['id' => $entry['id']]
                );
            }
        }
        
        error_log("AMP Waste Manager: {$deleted_photos} alte Beweis-Fotos aufgeräumt");
    }
    
    // ==============================================
    // AJAX HANDLER
    // ==============================================
    
    /**
     * AJAX: Entsorgung protokollieren
     * 
     * @since 1.0.0
     */
    public function ajax_log_disposal(): void
    {
        // Security Check
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_dispose_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $disposal_data = [
            'product_id' => intval($_POST['product_id'] ?? 0),
            'quantity' => intval($_POST['quantity'] ?? 0),
            'disposal_reason' => sanitize_text_field($_POST['disposal_reason'] ?? ''),
            'disposal_notes' => sanitize_textarea_field($_POST['disposal_notes'] ?? '')
        ];
        
        // Foto-Upload verarbeiten
        if (!empty($_FILES['photos'])) {
            $disposal_data['photo_paths'] = $this->handle_multiple_photo_uploads($_FILES['photos']);
        }
        
        $waste_id = $this->log_disposal($disposal_data);
        
        if ($waste_id) {
            wp_send_json_success([
                'message' => 'Entsorgung erfolgreich protokolliert',
                'waste_id' => $waste_id
            ]);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Protokollieren der Entsorgung']);
        }
    }
    
    /**
     * AJAX: Entsorgungseinträge abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_waste_entries(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $args = [
            'limit' => intval($_POST['limit'] ?? 50),
            'offset' => intval($_POST['offset'] ?? 0),
            'date_from' => sanitize_text_field($_POST['date_from'] ?? ''),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? ''),
            'disposal_reason' => sanitize_text_field($_POST['disposal_reason'] ?? ''),
            'product_id' => intval($_POST['product_id'] ?? 0) ?: null
        ];
        
        // Leere Werte entfernen
        $args = array_filter($args, function($value) {
            return $value !== '' && $value !== null;
        });
        
        $entries = $this->get_waste_entries($args);
        
        wp_send_json_success(['entries' => $entries]);
    }
    
    /**
     * AJAX: Entsorgungseintrag löschen
     * 
     * @since 1.0.0
     */
    public function ajax_delete_waste_entry(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_dispose_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $waste_id = intval($_POST['waste_id'] ?? 0);
        
        if (!$waste_id) {
            wp_send_json_error(['message' => 'Keine Entsorgung-ID übermittelt']);
            return;
        }
        
        $success = $this->delete_waste_entry($waste_id);
        
        if ($success) {
            wp_send_json_success(['message' => 'Entsorgungseintrag erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen des Eintrags']);
        }
    }
    
    /**
     * AJAX: Beweis-Fotos herunterladen
     * 
     * @since 1.0.0
     */
    public function ajax_download_photos(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $waste_id = intval($_POST['waste_id'] ?? 0);
        
        if (!$waste_id) {
            wp_send_json_error(['message' => 'Keine Entsorgung-ID übermittelt']);
            return;
        }
        
        $entry = $this->get_waste_entry_by_id($waste_id);
        if (!$entry || empty($entry['photo_paths'])) {
            wp_send_json_error(['message' => 'Keine Fotos gefunden']);
            return;
        }
        
        $photos = json_decode($entry['photo_paths'], true);
        $zip_path = $this->create_photo_zip($photos, $waste_id);
        
        if ($zip_path) {
            wp_send_json_success([
                'download_url' => wp_upload_dir()['baseurl'] . '/amp-waste-photos/zip/' . basename($zip_path)
            ]);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Erstellen des ZIP-Archivs']);
        }
    }
    
    /**
     * AJAX: Waste-Statistiken abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_waste_stats(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $args = [
            'date_from' => sanitize_text_field($_POST['date_from'] ?? date('Y-m-d', strtotime('-7 days'))),
            'date_to' => sanitize_text_field($_POST['date_to'] ?? date('Y-m-d'))
        ];
        
        $stats = $this->get_waste_statistics($args);
        
        wp_send_json_success($stats);
    }
    
    // ==============================================
    // HELPER METHODS
    // ==============================================
    
    /**
     * Produkt nach ID abrufen
     * 
     * @param int $product_id Produkt-ID
     * @return array|null Produkt-Daten
     * @since 1.0.0
     */
    private function get_product_by_id(int $product_id): ?array
    {
        if ($this->products_manager && method_exists($this->products_manager, 'get_product')) {
            return $this->products_manager->get_product($product_id);
        }
        
        // Fallback: Direkte Datenbankabfrage
        global $wpdb;
        $table = $this->database->get_table('products');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d AND status = 'active'",
            $product_id
        ), ARRAY_A);
    }
    
    /**
     * Entsorgungswert berechnen
     * 
     * @param array $product Produkt-Daten
     * @param int $quantity Entsorgte Menge
     * @return float Entsorgungswert
     * @since 1.0.0
     */
    private function calculate_disposal_value(array $product, int $quantity): float
    {
        $unit_value = $product['buy_price'] ?? 0;
        return $unit_value * $quantity;
    }
    
    /**
     * Lagerbestand aktualisieren
     * 
     * @param int $product_id Produkt-ID
     * @param int $quantity Menge (negativ für Reduzierung)
     * @param string $movement_type Bewegungstyp
     * @param int $reference_id Referenz-ID
     * @since 1.0.0
     */
    private function update_product_stock(int $product_id, int $quantity, string $movement_type, int $reference_id): void
    {
        global $wpdb;
        
        $products_table = $this->database->get_table('products');
        $movements_table = $this->database->get_table('stock_movements');
        
        // Aktuellen Bestand abrufen
        $current_stock = $wpdb->get_var($wpdb->prepare(
            "SELECT current_stock FROM {$products_table} WHERE id = %d",
            $product_id
        ));
        
        $new_stock = max(0, $current_stock + $quantity);
        
        // Bestand aktualisieren
        $wpdb->update(
            $products_table,
            ['current_stock' => $new_stock, 'updated_at' => current_time('mysql')],
            ['id' => $product_id]
        );
        
        // Bewegung protokollieren
        $this->database->insert('stock_movements', [
            'product_id' => $product_id,
            'movement_type' => $movement_type,
            'quantity' => $quantity,
            'previous_stock' => $current_stock,
            'new_stock' => $new_stock,
            'reference_type' => 'waste_disposal',
            'reference_id' => $reference_id,
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Entsorgung nach ID abrufen
     * 
     * @param int $waste_id Entsorgung-ID
     * @return array|null Entsorgungsdaten
     * @since 1.0.0
     */
    private function get_waste_entry_by_id(int $waste_id): ?array
    {
        global $wpdb;
        $table = $this->database->get_table('waste_log');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $waste_id
        ), ARRAY_A);
    }
    
    /**
     * Mehrere Foto-Uploads verarbeiten
     * 
     * @param array $files $_FILES Array
     * @return array Array der gespeicherten Dateipfade
     * @since 1.0.0
     */
    private function handle_multiple_photo_uploads(array $files): array
    {
        $uploaded_paths = [];
        
        // Files Array normalisieren (WordPress Standard)
        $normalized_files = [];
        
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                $normalized_files[] = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
            }
        } else {
            $normalized_files[] = $files;
        }
        
        foreach ($normalized_files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploaded_path = $this->save_waste_photo($file);
                if ($uploaded_path) {
                    $uploaded_paths[] = $uploaded_path;
                }
            }
        }
        
        return $uploaded_paths;
    }
    
    /**
     * Einzelnes Beweis-Foto speichern
     * 
     * @param array $file Upload-Datei
     * @return string|false Dateipfad oder false bei Fehler
     * @since 1.0.0
     */
    private function save_waste_photo(array $file)
    {
        // Sicherheitsprüfungen
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];
        
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB Max
            return false;
        }
        
        // Dateiname generieren
        $pathinfo = pathinfo($file['name']);
        $extension = strtolower($pathinfo['extension']);
        $filename = 'waste_' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
        $filepath = $this->upload_dir . '/' . $filename;
        
        // Datei verschieben
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Bildgröße optimieren für Web
            $this->optimize_waste_photo($filepath);
            
            return $filepath;
        }
        
        return false;
    }
    
    /**
     * Beweis-Foto für Web optimieren
     * 
     * @param string $filepath Dateipfad
     * @since 1.0.0
     */
    private function optimize_waste_photo(string $filepath): void
    {
        if (!extension_loaded('gd')) {
            return;
        }
        
        $image_info = getimagesize($filepath);
        if (!$image_info) {
            return;
        }
        
        $max_width = 1920;
        $max_height = 1080;
        $quality = 85;
        
        [$width, $height, $type] = $image_info;
        
        // Nur verkleinern wenn nötig
        if ($width <= $max_width && $height <= $max_height) {
            return;
        }
        
        // Seitenverhältnis berechnen
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);
        
        // Bild laden
        $source = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($filepath);
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filepath);
                break;
            case IMAGETYPE_WEBP:
                $source = imagecreatefromwebp($filepath);
                break;
        }
        
        if (!$source) {
            return;
        }
        
        // Neues Bild erstellen
        $destination = imagecreatetruecolor($new_width, $new_height);
        
        // Transparenz erhalten für PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
        }
        
        // Bild verkleinern
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Speichern
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($destination, $filepath, $quality);
                break;
            case IMAGETYPE_PNG:
                imagepng($destination, $filepath, 9);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($destination, $filepath, $quality);
                break;
        }
        
        // Speicher freigeben
        imagedestroy($source);
        imagedestroy($destination);
    }
    
    /**
     * ZIP-Archiv für Fotos erstellen
     * 
     * @param array $photo_paths Array der Foto-Pfade
     * @param int $waste_id Entsorgung-ID
     * @return string|false ZIP-Pfad oder false bei Fehler
     * @since 1.0.0
     */
    private function create_photo_zip(array $photo_paths, int $waste_id)
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }
        
        $zip_dir = $this->upload_dir . '/zip';
        if (!file_exists($zip_dir)) {
            wp_mkdir_p($zip_dir);
        }
        
        $zip_filename = "waste_{$waste_id}_photos_" . date('Y-m-d_H-i-s') . '.zip';
        $zip_path = $zip_dir . '/' . $zip_filename;
        
        $zip = new \ZipArchive();
        if ($zip->open($zip_path, \ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        
        foreach ($photo_paths as $photo_path) {
            if (file_exists($photo_path)) {
                $zip->addFile($photo_path, basename($photo_path));
            }
        }
        
        $zip->close();
        
        return $zip_path;
    }
    
    /**
     * E-Mail-Benachrichtigung für Entsorgung senden
     * 
     * @param int $waste_id Entsorgung-ID
     * @param array $product Produkt-Daten
     * @param array $disposal_data Entsorgungsdaten
     * @since 1.0.0
     */
    private function send_disposal_notification(int $waste_id, array $product, array $disposal_data): void
    {
        $email_recipient = get_option('amp_email_waste_recipient');
        if (empty($email_recipient)) {
            return;
        }
        
        $user = wp_get_current_user();
        $disposal_reason_label = $this->disposal_reasons[$disposal_data['disposal_reason']] ?? $disposal_data['disposal_reason'];
        
        $subject = sprintf(
            '[AutomatenManager Pro] Entsorgung protokolliert - %s',
            $product['name']
        );
        
        $message = sprintf("
Neue Entsorgung wurde protokolliert:

Entsorgung-ID: #%s
Produkt: %s (%s)
Menge: %d Stück
Grund: %s
Wert: %.2f€
Benutzer: %s
Datum: %s

Notizen: %s

Diese E-Mail wurde automatisch generiert.
        ",
            str_pad($waste_id, 4, '0', STR_PAD_LEFT),
            $product['name'],
            $product['barcode'],
            $disposal_data['quantity'],
            $disposal_reason_label,
            $this->calculate_disposal_value($product, $disposal_data['quantity']),
            $user->display_name,
            current_time('d.m.Y H:i'),
            $disposal_data['disposal_notes'] ?? 'Keine'
        );
        
        wp_mail($email_recipient, $subject, $message);
    }
    
    /**
     * Erlaubte Entsorgungsgründe abrufen
     * 
     * @return array Entsorgungsgründe
     * @since 1.0.0
     */
    public function get_disposal_reasons(): array
    {
        return $this->disposal_reasons;
    }
    
    /**
     * Database Manager abrufen
     * 
     * @return AMP_Database_Manager
     * @since 1.0.0
     */
    public function get_database(): AMP_Database_Manager
    {
        return $this->database;
    }
}