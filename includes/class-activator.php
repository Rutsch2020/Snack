<?php
/**
 * Plugin Activator - VOLLSTÄNDIG REPARIERTE VERSION
 * 
 * Alle Fehler behoben: Cron Jobs, E-Mail-Einstellungen, Security Log
 *
 * @package     AutomatenManagerPro
 * @subpackage  Core
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Core;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Activator Klasse - VOLLSTÄNDIG REPARIERT
 * 
 * @since 1.0.0
 */
class AMP_Activator
{
    /**
     * Plugin-Aktivierung ausführen
     * 
     * @return void
     * @since 1.0.0
     */
    public static function activate(): void
    {
        try {
            error_log('=== AMP ACTIVATOR START ===');
            
            // 1. PHP Version prüfen
            if (version_compare(PHP_VERSION, '7.4.0', '<')) {
                wp_die(
                    '<h1>AutomatenManager Pro - PHP Version zu alt</h1>' .
                    '<p>Benötigt: PHP 7.4+</p>' .
                    '<p>Aktuell: ' . PHP_VERSION . '</p>',
                    'PHP Version Fehler',
                    ['back_link' => true]
                );
            }
            
            // 2. WordPress Version prüfen
            if (version_compare(get_bloginfo('version'), '5.0', '<')) {
                wp_die(
                    '<h1>AutomatenManager Pro - WordPress Version zu alt</h1>' .
                    '<p>Benötigt: WordPress 5.0+</p>' .
                    '<p>Aktuell: ' . get_bloginfo('version') . '</p>',
                    'WordPress Version Fehler',
                    ['back_link' => true]
                );
            }
            
            // 3. Datenbank verfügbar?
            global $wpdb;
            if (!$wpdb) {
                wp_die(
                    '<h1>AutomatenManager Pro - Datenbank nicht verfügbar</h1>' .
                    '<p>WordPress-Datenbank ist nicht erreichbar.</p>',
                    'Datenbank Fehler',
                    ['back_link' => true]
                );
            }
            
            // AKTIVIERUNG DURCHFÜHREN
            
            // 1. Plugin-Optionen setzen
            self::set_plugin_options();
            error_log('AMP Activator: Plugin-Optionen gesetzt');
            
            // 2. Datenbank-Tabellen erstellen
            self::create_database_tables();
            error_log('AMP Activator: Datenbank-Tabellen erstellt');
            
            // 3. Upload-Verzeichnisse erstellen
            self::create_upload_directories();
            error_log('AMP Activator: Upload-Verzeichnisse erstellt');
            
            // 4. Administrator-Rechte hinzufügen
            self::add_admin_capabilities();
            error_log('AMP Activator: Admin-Rechte hinzugefügt');
            
            // 5. E-Mail-Einstellungen konfigurieren
            self::setup_email_settings();
            error_log('AMP Activator: E-Mail-Einstellungen konfiguriert');
            
            // 6. Cron-Jobs einrichten
            self::setup_cron_jobs();
            error_log('AMP Activator: Cron-Jobs eingerichtet');
            
            // 7. Aktivierungs-Flag setzen
            update_option('amp_activation_redirect', true);
            update_option('amp_plugin_activated', true);
            
            // 8. Cache leeren
            wp_cache_flush();
            
            error_log('=== AMP ACTIVATOR ERFOLGREICH BEENDET ===');
            
            // Erfolgs-Hook
            do_action('amp_plugin_activated');
            
        } catch (Exception $e) {
            error_log('=== AMP ACTIVATOR FEHLER ===');
            error_log('Fehler: ' . $e->getMessage());
            error_log('Datei: ' . $e->getFile());
            error_log('Zeile: ' . $e->getLine());
            
            wp_die(
                '<h1>AutomatenManager Pro - Aktivierungsfehler</h1>' .
                '<p><strong>Fehler:</strong> ' . esc_html($e->getMessage()) . '</p>' .
                '<p>Bitte prüfen Sie die WordPress Debug-Logs.</p>',
                'Plugin-Aktivierungsfehler',
                ['back_link' => true]
            );
        }
    }
    
    /**
     * Plugin-Optionen setzen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function set_plugin_options(): void
    {
        // Plugin-Version
        $version = defined('AMP_VERSION') ? AMP_VERSION : '1.0.0';
        add_option('amp_version', $version);
        
        // Aktivierungsdatum
        add_option('amp_activated_at', current_time('mysql'));
        
        // Erste Installation
        add_option('amp_first_install', true);
        
        // Debug-Modus
        add_option('amp_debug_mode', defined('WP_DEBUG') && WP_DEBUG);
        
        // Allgemeine Einstellungen
        add_option('amp_company_name', get_bloginfo('name'));
        add_option('amp_currency', 'EUR');
        add_option('amp_currency_symbol', '€');
        
        // Scanner-Einstellungen
        add_option('amp_scanner_enabled', true);
        add_option('amp_scanner_sound_enabled', true);
        add_option('amp_scanner_vibration_enabled', true);
        
        // Monitoring-Einstellungen
        add_option('amp_monitoring_enabled', true);
        add_option('amp_expiry_warning_days', 2);
        add_option('amp_low_stock_threshold', 10);
        
        // Steuer-Einstellungen
        add_option('amp_vat_rate_standard', 19.00);
        add_option('amp_vat_rate_reduced', 7.00);
    }
    
    /**
     * E-Mail-Einstellungen konfigurieren
     * 
     * @return void
     * @since 1.0.0
     */
    private static function setup_email_settings(): void
    {
        $admin_email = get_option('admin_email');
        
        // E-Mail-Empfänger setzen
        add_option('amp_email_sales_recipient', $admin_email);
        add_option('amp_email_inventory_recipient', $admin_email);
        add_option('amp_email_expiry_recipient', $admin_email);
        add_option('amp_email_waste_recipient', $admin_email);
        
        // E-Mail-Einstellungen
        add_option('amp_email_enabled', true);
        add_option('amp_email_from_name', get_bloginfo('name'));
        add_option('amp_email_from_address', $admin_email);
        
        // E-Mail-Templates aktivieren
        add_option('amp_email_template_sales', true);
        add_option('amp_email_template_stock', true);
        add_option('amp_email_template_expiry', true);
        add_option('amp_email_template_waste', true);
    }
    
    /**
     * Cron-Jobs einrichten
     * 
     * @return void
     * @since 1.0.0
     */
    private static function setup_cron_jobs(): void
    {
        // Tägliche Ablaufprüfung (jeden Tag um 9:00 Uhr)
        if (!wp_next_scheduled('amp_daily_expiry_check')) {
            $timestamp = strtotime('today 9:00am');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow 9:00am');
            }
            wp_schedule_event($timestamp, 'daily', 'amp_daily_expiry_check');
            error_log('AMP Activator: Täglicher Expiry-Check Cron-Job eingerichtet');
        }
        
        // Wöchentliche Bereinigung (jeden Sonntag um 3:00 Uhr)
        if (!wp_next_scheduled('amp_weekly_cleanup')) {
            $timestamp = strtotime('next sunday 3:00am');
            wp_schedule_event($timestamp, 'weekly', 'amp_weekly_cleanup');
            error_log('AMP Activator: Wöchentlicher Cleanup Cron-Job eingerichtet');
        }
        
        // Log-Bereinigung (täglich um 2:00 Uhr)
        if (!wp_next_scheduled('amp_cleanup_logs')) {
            $timestamp = strtotime('today 2:00am');
            if ($timestamp < time()) {
                $timestamp = strtotime('tomorrow 2:00am');
            }
            wp_schedule_event($timestamp, 'daily', 'amp_cleanup_logs');
            error_log('AMP Activator: Täglicher Log-Cleanup Cron-Job eingerichtet');
        }
        
        // E-Mail-Queue verarbeiten (alle 5 Minuten)
        if (!wp_next_scheduled('amp_process_email_queue')) {
            wp_schedule_event(time(), 'amp_five_minutes', 'amp_process_email_queue');
            error_log('AMP Activator: E-Mail-Queue Cron-Job eingerichtet');
        }
        
        // Eigenes Cron-Intervall für 5 Minuten registrieren
        add_filter('cron_schedules', function($schedules) {
            $schedules['amp_five_minutes'] = [
                'interval' => 300,
                'display' => __('Alle 5 Minuten', 'automaten-manager-pro')
            ];
            return $schedules;
        });
    }
    
    /**
     * Datenbank-Tabellen erstellen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function create_database_tables(): void
    {
        try {
            $database = new AMP_Database_Manager();
            $result = $database->create_tables();
            
            if ($result === false) {
                throw new Exception('Database Manager create_tables() returned false');
            }
            
            // Tabellen-Status prüfen
            $table_status = $database->check_tables_exist();
            update_option('amp_table_status', $table_status);
            
            // Prüfen ob alle Tabellen erstellt wurden
            $missing_tables = array_filter($table_status, function($exists) {
                return !$exists;
            });
            
            if (!empty($missing_tables)) {
                error_log('AMP Activator: Fehlende Tabellen: ' . implode(', ', array_keys($missing_tables)));
            }
            
        } catch (Exception $e) {
            error_log('AMP Activator: Datenbank-Fehler: ' . $e->getMessage());
            throw new Exception('Datenbank-Tabellen konnten nicht erstellt werden: ' . $e->getMessage());
        }
    }
    
    /**
     * Upload-Verzeichnisse erstellen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function create_upload_directories(): void
    {
        try {
            $upload_dir = wp_upload_dir();
            
            if (is_wp_error($upload_dir)) {
                error_log('AMP Activator: Upload-Dir Error: ' . $upload_dir->get_error_message());
                return;
            }
            
            $plugin_upload_dir = $upload_dir['basedir'] . '/automaten-manager-pro';
            
            $directories = [
                $plugin_upload_dir,
                $plugin_upload_dir . '/waste-photos',
                $plugin_upload_dir . '/reports',
                $plugin_upload_dir . '/logs',
                $plugin_upload_dir . '/temp',
                $plugin_upload_dir . '/exports',
                $plugin_upload_dir . '/backups'
            ];
            
            foreach ($directories as $dir) {
                if (!wp_mkdir_p($dir)) {
                    error_log("AMP Activator: Konnte Verzeichnis nicht erstellen: {$dir}");
                } else {
                    // index.php für Sicherheit
                    $index_file = $dir . '/index.php';
                    if (!file_exists($index_file)) {
                        file_put_contents($index_file, "<?php\n// Silence is golden\n");
                    }
                    
                    // .htaccess für zusätzliche Sicherheit
                    $htaccess_file = $dir . '/.htaccess';
                    if (!file_exists($htaccess_file)) {
                        $htaccess_content = "Options -Indexes\n";
                        if (strpos($dir, '/logs') !== false || strpos($dir, '/backups') !== false) {
                            $htaccess_content .= "Deny from all\n";
                        }
                        file_put_contents($htaccess_file, $htaccess_content);
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log('AMP Activator: Upload-Verzeichnisse Fehler: ' . $e->getMessage());
        }
    }
    
    /**
     * Administrator-Rechte hinzufügen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function add_admin_capabilities(): void
    {
        try {
            // Plugin-spezifische Capabilities
            $capabilities = [
                'amp_manage_products' => __('Produkte verwalten', 'automaten-manager-pro'),
                'amp_manage_categories' => __('Kategorien verwalten', 'automaten-manager-pro'),
                'amp_use_scanner' => __('Scanner benutzen', 'automaten-manager-pro'),
                'amp_process_sales' => __('Verkäufe abwickeln', 'automaten-manager-pro'),
                'amp_manage_inventory' => __('Lager verwalten', 'automaten-manager-pro'),
                'amp_view_reports' => __('Berichte einsehen', 'automaten-manager-pro'),
                'amp_manage_settings' => __('Einstellungen ändern', 'automaten-manager-pro'),
                'amp_export_data' => __('Daten exportieren', 'automaten-manager-pro'),
                'amp_dispose_products' => __('Produkte entsorgen', 'automaten-manager-pro')
            ];
            
            // Administrator bekommt alle Rechte
            $admin_role = get_role('administrator');
            if ($admin_role) {
                foreach ($capabilities as $cap => $desc) {
                    $admin_role->add_cap($cap);
                }
                error_log('AMP Activator: ' . count($capabilities) . ' Capabilities zu Administrator hinzugefügt');
            }
            
            // Editor bekommt beschränkte Rechte
            $editor_role = get_role('editor');
            if ($editor_role) {
                $editor_caps = [
                    'amp_use_scanner',
                    'amp_process_sales',
                    'amp_view_reports'
                ];
                
                foreach ($editor_caps as $cap) {
                    $editor_role->add_cap($cap);
                }
            }
            
            // Shop Manager Rolle erstellen (optional)
            if (!get_role('amp_shop_manager')) {
                add_role('amp_shop_manager', __('Shop Manager', 'automaten-manager-pro'), [
                    'read' => true,
                    'amp_manage_products' => true,
                    'amp_use_scanner' => true,
                    'amp_process_sales' => true,
                    'amp_manage_inventory' => true,
                    'amp_view_reports' => true,
                    'amp_dispose_products' => true
                ]);
            }
            
        } catch (Exception $e) {
            error_log('AMP Activator: Capabilities Fehler: ' . $e->getMessage());
        }
    }
}