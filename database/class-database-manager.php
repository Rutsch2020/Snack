<?php
/**
 * Database Manager for AutomatenManager Pro - FINALE VERSION MIT SECURITY LOG
 * 
 * Verwaltet alle Datenbank-Operationen für das Plugin.
 * Erstellt Tabellen, führt CRUD-Operationen durch und verwaltet Beziehungen.
 * 
 * ÄNDERUNGEN IN DIESER VERSION:
 * - Security Log Tabelle hinzugefügt
 * - Tabellen-Array erweitert
 * - create_tables() auf 9 Tabellen aktualisiert
 *
 * @package     AutomatenManagerPro
 * @subpackage  Database
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Database;

// Sicherheitscheck
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database Manager Klasse - FINALE VERSION MIT SECURITY LOG
 * 
 * Zentrale Verwaltung aller Datenbank-Operationen.
 * Erstellt und verwaltet alle Plugin-spezifischen Tabellen.
 * 
 * @since 1.0.0
 */
class AMP_Database_Manager
{
    /**
     * WordPress Database Instance
     */
    private \wpdb $wpdb;

    /**
     * Tabellen-Prefix
     */
    private string $table_prefix;

    /**
     * Plugin-Tabellen
     */
    private array $tables;

    /**
     * Database Version für Updates
     */
    private string $db_version = '1.0.0';

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_prefix = $wpdb->prefix . 'amp_';
        
        // Tabellen-Namen definieren (MIT SECURITY LOG)
        $this->tables = array(
            'categories' => $this->table_prefix . 'categories',
            'products' => $this->table_prefix . 'products',
            'sales_sessions' => $this->table_prefix . 'sales_sessions',
            'sales_items' => $this->table_prefix . 'sales_items',
            'stock_movements' => $this->table_prefix . 'stock_movements',
            'waste_log' => $this->table_prefix . 'waste_log',
            'email_log' => $this->table_prefix . 'email_log',
            'settings' => $this->table_prefix . 'settings',
            'security_log' => $this->table_prefix . 'security_log' // NEU!
        );
    }

    /**
     * Alle Tabellen erstellen - MIT SECURITY LOG
     * 
     * @return bool True bei Erfolg, False bei Fehler
     * @since 1.0.0
     */
    public function create_tables(): bool
    {
        try {
            error_log('AMP Database: Starte optimierte Tabellen-Erstellung');
            
            // WordPress dbDelta für saubere Tabellen-Erstellung
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            
            $success_count = 0;
            $total_tables = 9; // ERHÖHT VON 8 AUF 9
            
            // Tabellen in korrekter Reihenfolge erstellen (wegen Foreign Keys)
            $success_count += $this->create_categories_table() ? 1 : 0;
            $success_count += $this->create_products_table() ? 1 : 0;
            $success_count += $this->create_sales_sessions_table() ? 1 : 0;
            $success_count += $this->create_sales_items_table() ? 1 : 0;
            $success_count += $this->create_stock_movements_table() ? 1 : 0;
            $success_count += $this->create_waste_log_table() ? 1 : 0;
            $success_count += $this->create_email_log_table() ? 1 : 0;
            $success_count += $this->create_settings_table() ? 1 : 0;
            $success_count += $this->create_security_log_table() ? 1 : 0; // NEU!
            
            // Standard-Daten einfügen
            if ($success_count >= 7) { // ERHÖHT VON 6 AUF 7
                $this->insert_default_data();
                
                // Database Version speichern
                update_option('amp_db_version', $this->db_version);
                
                error_log("AMP Database: {$success_count}/{$total_tables} Tabellen erfolgreich erstellt");
                return true;
            } else {
                error_log("AMP Database: Nur {$success_count}/{$total_tables} Tabellen erstellt - Fehler!");
                return false;
            }
            
        } catch (Exception $e) {
            error_log('AMP Database: Fehler bei Tabellen-Erstellung: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kategorien-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_categories_table(): bool
    {
        $table_name = $this->tables['categories'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            color varchar(7) DEFAULT '#007cba',
            parent_id int(11) unsigned DEFAULT NULL,
            sort_order int(11) DEFAULT 0,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY status_sort (status, sort_order),
            KEY name_index (name(50))
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        // Prüfen ob Tabelle wirklich erstellt wurde
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Kategorien-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Kategorien-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Produkte-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_products_table(): bool
    {
        $table_name = $this->tables['products'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            barcode varchar(50) NOT NULL,
            image_id int(11) unsigned DEFAULT NULL,
            category_id int(11) unsigned DEFAULT NULL,
            buy_price decimal(10,2) NOT NULL DEFAULT 0.00,
            sell_price decimal(10,2) NOT NULL DEFAULT 0.00,
            vat_rate decimal(5,2) NOT NULL DEFAULT 19.00,
            deposit decimal(10,2) NOT NULL DEFAULT 0.00,
            current_stock int(11) NOT NULL DEFAULT 0,
            min_stock int(11) NOT NULL DEFAULT 0,
            expiry_date date DEFAULT NULL,
            status enum('active','inactive') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY barcode_unique (barcode),
            KEY category_id (category_id),
            KEY status_name (status, name(50)),
            KEY expiry_date (expiry_date),
            KEY stock_check (current_stock, min_stock),
            KEY price_index (sell_price),
            FULLTEXT KEY search_index (name, description, barcode)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        // Prüfen ob Tabelle wirklich erstellt wurde
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Produkte-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Produkte-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Verkaufs-Sessions-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_sales_sessions_table(): bool
    {
        $table_name = $this->tables['sales_sessions'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            session_start datetime NOT NULL,
            session_end datetime DEFAULT NULL,
            user_id int(11) unsigned NOT NULL,
            total_items int(11) NOT NULL DEFAULT 0,
            subtotal_net decimal(10,2) NOT NULL DEFAULT 0.00,
            total_vat decimal(10,2) NOT NULL DEFAULT 0.00,
            total_deposit decimal(10,2) NOT NULL DEFAULT 0.00,
            total_gross decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method enum('cash','card','mixed') NOT NULL DEFAULT 'cash',
            payment_received decimal(10,2) DEFAULT NULL,
            notes text,
            email_sent_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_sessions (user_id, session_start),
            KEY date_range (session_start, session_end),
            KEY payment_method (payment_method),
            KEY total_gross (total_gross),
            KEY email_status (email_sent_at)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Verkaufs-Sessions-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Verkaufs-Sessions-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Verkaufs-Items-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_sales_items_table(): bool
    {
        $table_name = $this->tables['sales_items'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            session_id int(11) unsigned NOT NULL,
            product_id int(11) unsigned NOT NULL,
            product_name varchar(255) NOT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            unit_deposit decimal(10,2) NOT NULL DEFAULT 0.00,
            vat_rate decimal(5,2) NOT NULL,
            line_total_net decimal(10,2) NOT NULL,
            line_total_vat decimal(10,2) NOT NULL,
            line_total_gross decimal(10,2) NOT NULL,
            discount_percent decimal(5,2) DEFAULT 0.00,
            discount_amount decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_items (session_id, product_id),
            KEY product_sales (product_id, created_at),
            KEY line_total (line_total_gross),
            KEY quantity_index (quantity)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Verkaufs-Items-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Verkaufs-Items-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Lager-Bewegungen-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_stock_movements_table(): bool
    {
        $table_name = $this->tables['stock_movements'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            product_id int(11) unsigned NOT NULL,
            movement_type enum('in','out','disposal','disposal_reversal','adjustment','initial') NOT NULL,
            quantity int(11) NOT NULL,
            previous_stock int(11) NOT NULL,
            new_stock int(11) NOT NULL,
            reference_type varchar(50) DEFAULT NULL,
            reference_id int(11) unsigned DEFAULT NULL,
            reason varchar(255) DEFAULT NULL,
            notes text,
            user_id int(11) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_movements (product_id, created_at),
            KEY movement_type (movement_type),
            KEY user_movements (user_id, created_at),
            KEY reference_lookup (reference_type, reference_id),
            KEY stock_tracking (product_id, movement_type, created_at)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Lager-Bewegungen-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Lager-Bewegungen-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Entsorgungsprotokoll-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_waste_log_table(): bool
    {
        $table_name = $this->tables['waste_log'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            product_id int(11) unsigned NOT NULL,
            product_name varchar(255) NOT NULL,
            quantity int(11) NOT NULL,
            disposal_reason enum('expired','damaged','contaminated','recall','inventory_correction','other') NOT NULL,
            disposal_notes text,
            disposal_value decimal(10,2) NOT NULL DEFAULT 0.00,
            photo_paths text DEFAULT NULL,
            user_id int(11) unsigned NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_waste (product_id, created_at),
            KEY disposal_reason (disposal_reason),
            KEY user_waste (user_id, created_at),
            KEY disposal_value (disposal_value),
            KEY waste_tracking (disposal_reason, created_at)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Entsorgungsprotokoll-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Entsorgungsprotokoll-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * E-Mail-Log-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_email_log_table(): bool
    {
        $table_name = $this->tables['email_log'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            email_type enum('sales','stock','expiry','waste','notification') NOT NULL,
            recipient varchar(255) NOT NULL,
            subject varchar(500) NOT NULL,
            content longtext,
            attachment_paths text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            status enum('sent','failed','pending','retry') DEFAULT 'pending',
            error_message text,
            attempts int(11) DEFAULT 0,
            reference_id int(11) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            KEY email_type_status (email_type, status),
            KEY recipient_emails (recipient(50), sent_at),
            KEY status_attempts (status, attempts),
            KEY sent_date (sent_at),
            KEY reference_lookup (reference_id, email_type)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: E-Mail-Log-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: E-Mail-Log-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Einstellungen-Tabelle erstellen
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_settings_table(): bool
    {
        $table_name = $this->tables['settings'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            setting_group varchar(50) DEFAULT 'general',
            description text,
            autoload enum('yes','no') DEFAULT 'yes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key_unique (setting_key),
            KEY setting_group (setting_group),
            KEY autoload_settings (autoload, setting_group)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Einstellungen-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Einstellungen-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Security-Log-Tabelle erstellen - NEU!
     * 
     * @return bool
     * @since 1.0.0
     */
    private function create_security_log_table(): bool
    {
        $table_name = $this->tables['security_log'];
        
        $sql = "CREATE TABLE {$table_name} (
            id int(11) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            details longtext,
            ip_address varchar(45) NOT NULL,
            user_agent text,
            user_id int(11) unsigned DEFAULT NULL,
            timestamp datetime NOT NULL,
            severity enum('info','warning','critical') DEFAULT 'info',
            response_action varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY event_type_severity (event_type, severity),
            KEY ip_address (ip_address),
            KEY user_id (user_id),
            KEY timestamp (timestamp),
            KEY severity (severity)
        ) {$this->wpdb->get_charset_collate()};";
        
        $result = dbDelta($sql);
        
        $table_exists = $this->wpdb->get_var($this->wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if ($table_exists) {
            error_log('AMP Database: Security-Log-Tabelle erfolgreich erstellt');
            return true;
        } else {
            error_log('AMP Database: Security-Log-Tabelle konnte nicht erstellt werden');
            return false;
        }
    }

    /**
     * Standard-Daten einfügen
     * 
     * @return void
     * @since 1.0.0
     */
    private function insert_default_data(): void
    {
        try {
            // Standard-Kategorien einfügen
            $this->insert_default_categories();
            
            // Standard-Einstellungen einfügen
            $this->insert_default_settings();
            
            // Test-Produkte einfügen (nur für Demo)
            if (defined('AMP_INSERT_DEMO_DATA') && AMP_INSERT_DEMO_DATA) {
                $this->insert_demo_products();
            }
            
            error_log('AMP Database: Standard-Daten erfolgreich eingefügt');
            
        } catch (Exception $e) {
            error_log('AMP Database: Fehler bei Standard-Daten: ' . $e->getMessage());
        }
    }

    /**
     * Standard-Kategorien einfügen
     * 
     * @return void
     * @since 1.0.0
     */
    private function insert_default_categories(): void
    {
        $categories = array(
            array(
                'name' => 'Getränke',
                'description' => 'Alle Arten von Getränken',
                'color' => '#007cba',
                'sort_order' => 1
            ),
            array(
                'name' => 'Snacks',
                'description' => 'Herzhafte Snacks und Knabbereien',
                'color' => '#00a32a',
                'sort_order' => 2
            ),
            array(
                'name' => 'Süßwaren',
                'description' => 'Süßigkeiten und Schokolade',
                'color' => '#dba617',
                'sort_order' => 3
            ),
            array(
                'name' => 'Milchprodukte',
                'description' => 'Milch, Joghurt und andere Milchprodukte',
                'color' => '#826eb4',
                'sort_order' => 4
            ),
            array(
                'name' => 'Backwaren',
                'description' => 'Brot, Brötchen und Gebäck',
                'color' => '#d63638',
                'sort_order' => 5
            )
        );

        foreach ($categories as $category) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->tables['categories']} WHERE name = %s",
                $category['name']
            ));

            if (!$existing) {
                $result = $this->wpdb->insert(
                    $this->tables['categories'],
                    $category,
                    ['%s', '%s', '%s', '%d']
                );
                
                if ($result === false) {
                    error_log('AMP Database: Fehler beim Einfügen der Kategorie: ' . $category['name']);
                }
            }
        }
    }

    /**
     * Standard-Einstellungen einfügen
     * 
     * @return void
     * @since 1.0.0
     */
    private function insert_default_settings(): void
    {
        $admin_email = get_option('admin_email', 'admin@example.com');
        
        $settings = array(
            // Allgemeine Einstellungen
            array(
                'setting_key' => 'company_name',
                'setting_value' => 'AutomatenManager Pro',
                'setting_group' => 'general',
                'description' => 'Name des Unternehmens'
            ),
            array(
                'setting_key' => 'company_logo_id',
                'setting_value' => '0',
                'setting_group' => 'general',
                'description' => 'WordPress Media ID des Firmenlogos'
            ),
            array(
                'setting_key' => 'currency',
                'setting_value' => 'EUR',
                'setting_group' => 'general',
                'description' => 'Standard-Währung'
            ),
            
            // E-Mail-Einstellungen
            array(
                'setting_key' => 'email_sales_recipient',
                'setting_value' => $admin_email,
                'setting_group' => 'email',
                'description' => 'E-Mail-Adresse für Verkaufsberichte'
            ),
            array(
                'setting_key' => 'email_stock_recipient',
                'setting_value' => $admin_email,
                'setting_group' => 'email',
                'description' => 'E-Mail-Adresse für Lagerberichte'
            ),
            array(
                'setting_key' => 'email_expiry_recipient',
                'setting_value' => $admin_email,
                'setting_group' => 'email',
                'description' => 'E-Mail-Adresse für Ablaufwarnungen'
            ),
            array(
                'setting_key' => 'email_waste_recipient',
                'setting_value' => $admin_email,
                'setting_group' => 'email',
                'description' => 'E-Mail-Adresse für Entsorgungsberichte'
            ),
            array(
                'setting_key' => 'email_enabled',
                'setting_value' => 'yes',
                'setting_group' => 'email',
                'description' => 'E-Mail-Benachrichtigungen aktiviert'
            ),
            
            // Steuer-Einstellungen
            array(
                'setting_key' => 'vat_rate_standard',
                'setting_value' => '19.00',
                'setting_group' => 'tax',
                'description' => 'Standard Mehrwertsteuersatz (%)'
            ),
            array(
                'setting_key' => 'vat_rate_reduced',
                'setting_value' => '7.00',
                'setting_group' => 'tax',
                'description' => 'Reduzierter Mehrwertsteuersatz (%)'
            ),
            
            // Monitoring-Einstellungen
            array(
                'setting_key' => 'expiry_warning_days',
                'setting_value' => '2',
                'setting_group' => 'monitoring',
                'description' => 'Tage vor Ablauf für Warnung'
            ),
            array(
                'setting_key' => 'low_stock_threshold',
                'setting_value' => '10',
                'setting_group' => 'monitoring',
                'description' => 'Standard-Schwellenwert für niedrigen Bestand'
            ),
            array(
                'setting_key' => 'monitoring_enabled',
                'setting_value' => 'yes',
                'setting_group' => 'monitoring',
                'description' => 'Automatische Überwachung aktiviert'
            ),
            
            // Scanner-Einstellungen
            array(
                'setting_key' => 'scanner_sound_enabled',
                'setting_value' => 'yes',
                'setting_group' => 'scanner',
                'description' => 'Scanner-Töne aktiviert'
            ),
            array(
                'setting_key' => 'scanner_vibration_enabled',
                'setting_value' => 'yes',
                'setting_group' => 'scanner',
                'description' => 'Scanner-Vibration aktiviert'
            ),
            
            // Performance-Einstellungen
            array(
                'setting_key' => 'cache_enabled',
                'setting_value' => 'yes',
                'setting_group' => 'performance',
                'description' => 'Caching aktiviert'
            ),
            array(
                'setting_key' => 'debug_mode',
                'setting_value' => 'no',
                'setting_group' => 'performance',
                'description' => 'Debug-Modus aktiviert'
            )
        );

        foreach ($settings as $setting) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->tables['settings']} WHERE setting_key = %s",
                $setting['setting_key']
            ));

            if (!$existing) {
                $result = $this->wpdb->insert(
                    $this->tables['settings'],
                    $setting,
                    ['%s', '%s', '%s', '%s']
                );
                
                if ($result === false) {
                    error_log('AMP Database: Fehler beim Einfügen der Einstellung: ' . $setting['setting_key']);
                }
            }
        }
    }

    /**
     * Demo-Produkte einfügen (optional)
     * 
     * @return void
     * @since 1.0.0
     */
    private function insert_demo_products(): void
    {
        // Kategorie-IDs holen
        $categories = $this->wpdb->get_results(
            "SELECT id, name FROM {$this->tables['categories']}",
            ARRAY_A
        );
        
        $category_map = array();
        foreach ($categories as $cat) {
            $category_map[$cat['name']] = $cat['id'];
        }
        
        $demo_products = array(
            array(
                'name' => 'Coca Cola 0,5L',
                'description' => 'Erfrischende Cola in der 0,5L Flasche',
                'barcode' => '4711234567890',
                'category_id' => $category_map['Getränke'] ?? null,
                'buy_price' => 0.75,
                'sell_price' => 1.50,
                'vat_rate' => 19.00,
                'deposit' => 0.25,
                'current_stock' => 50,
                'min_stock' => 10,
                'expiry_date' => date('Y-m-d', strtotime('+6 months')),
                'status' => 'active'
            ),
            array(
                'name' => 'Chips Paprika',
                'description' => 'Knusprige Kartoffelchips mit Paprika-Geschmack',
                'barcode' => '9876543210123',
                'category_id' => $category_map['Snacks'] ?? null,
                'buy_price' => 1.20,
                'sell_price' => 2.50,
                'vat_rate' => 19.00,
                'deposit' => 0.00,
                'current_stock' => 25,
                'min_stock' => 5,
                'expiry_date' => date('Y-m-d', strtotime('+3 months')),
                'status' => 'active'
            ),
            array(
                'name' => 'Milch 3,5% 1L',
                'description' => 'Frische Vollmilch 3,5% Fettgehalt',
                'barcode' => '1357924680246',
                'category_id' => $category_map['Milchprodukte'] ?? null,
                'buy_price' => 0.89,
                'sell_price' => 1.49,
                'vat_rate' => 7.00,
                'deposit' => 0.00,
                'current_stock' => 15,
                'min_stock' => 8,
                'expiry_date' => date('Y-m-d', strtotime('+5 days')),
                'status' => 'active'
            )
        );
        
        foreach ($demo_products as $product) {
            $existing = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT id FROM {$this->tables['products']} WHERE barcode = %s",
                $product['barcode']
            ));
            
            if (!$existing) {
                $result = $this->wpdb->insert(
                    $this->tables['products'],
                    $product,
                    ['%s', '%s', '%s', '%d', '%f', '%f', '%f', '%f', '%d', '%d', '%s', '%s']
                );
                
                if ($result !== false) {
                    $product_id = $this->wpdb->insert_id;
                    
                    // Initial stock movement
                    $this->wpdb->insert(
                        $this->tables['stock_movements'],
                        array(
                            'product_id' => $product_id,
                            'movement_type' => 'initial',
                            'quantity' => $product['current_stock'],
                            'previous_stock' => 0,
                            'new_stock' => $product['current_stock'],
                            'reason' => 'Demo-Daten Initialisierung',
                            'user_id' => get_current_user_id() ?: 1,
                            'created_at' => current_time('mysql')
                        ),
                        ['%d', '%s', '%d', '%d', '%d', '%s', '%d', '%s']
                    );
                }
            }
        }
    }

    /**
     * CRUD-Helper: Insert
     * 
     * @param string $table_key Tabellen-Schlüssel
     * @param array $data Zu inserierende Daten
     * @param array $format Format-Array (optional)
     * @return int|false Insert-ID oder false bei Fehler
     * @since 1.0.0
     */
    public function insert(string $table_key, array $data, array $format = null)
    {
        if (!isset($this->tables[$table_key])) {
            error_log("AMP Database: Unbekannte Tabelle: {$table_key}");
            return false;
        }
        
        $result = $this->wpdb->insert($this->tables[$table_key], $data, $format);
        
        if ($result === false) {
            error_log("AMP Database: Insert-Fehler in {$table_key}: " . $this->wpdb->last_error);
            return false;
        }
        
        return $this->wpdb->insert_id;
    }

    /**
     * CRUD-Helper: Update
     * 
     * @param string $table_key Tabellen-Schlüssel
     * @param array $data Zu aktualisierende Daten
     * @param array $where WHERE-Bedingungen
     * @param array $format Format-Array (optional)
     * @param array $where_format WHERE-Format-Array (optional)
     * @return int|false Anzahl betroffener Zeilen oder false bei Fehler
     * @since 1.0.0
     */
    public function update(string $table_key, array $data, array $where, array $format = null, array $where_format = null)
    {
        if (!isset($this->tables[$table_key])) {
            error_log("AMP Database: Unbekannte Tabelle: {$table_key}");
            return false;
        }
        
        $result = $this->wpdb->update($this->tables[$table_key], $data, $where, $format, $where_format);
        
        if ($result === false) {
            error_log("AMP Database: Update-Fehler in {$table_key}: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }

    /**
     * CRUD-Helper: Delete
     * 
     * @param string $table_key Tabellen-Schlüssel
     * @param array $where WHERE-Bedingungen
     * @param array $where_format WHERE-Format-Array (optional)
     * @return int|false Anzahl gelöschter Zeilen oder false bei Fehler
     * @since 1.0.0
     */
    public function delete(string $table_key, array $where, array $where_format = null)
    {
        if (!isset($this->tables[$table_key])) {
            error_log("AMP Database: Unbekannte Tabelle: {$table_key}");
            return false;
        }
        
        $result = $this->wpdb->delete($this->tables[$table_key], $where, $where_format);
        
        if ($result === false) {
            error_log("AMP Database: Delete-Fehler in {$table_key}: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }

    /**
     * CRUD-Helper: Select
     * 
     * @param string $table_key Tabellen-Schlüssel
     * @param array $where WHERE-Bedingungen (optional)
     * @param string $output Ausgabe-Format (OBJECT, ARRAY_A, etc.)
     * @return mixed Query-Ergebnis oder false bei Fehler
     * @since 1.0.0
     */
    public function select(string $table_key, array $where = array(), string $output = OBJECT)
    {
        if (!isset($this->tables[$table_key])) {
            error_log("AMP Database: Unbekannte Tabelle: {$table_key}");
            return false;
        }
        
        $sql = "SELECT * FROM {$this->tables[$table_key]}";
        $values = array();
        
        if (!empty($where)) {
            $conditions = array();
            foreach ($where as $column => $value) {
                $conditions[] = "{$column} = %s";
                $values[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if (!empty($values)) {
            $result = $this->wpdb->get_results($this->wpdb->prepare($sql, $values), $output);
        } else {
            $result = $this->wpdb->get_results($sql, $output);
        }
        
        if ($result === null && !empty($this->wpdb->last_error)) {
            error_log("AMP Database: Select-Fehler in {$table_key}: " . $this->wpdb->last_error);
            return false;
        }
        
        return $result;
    }

    /**
     * Insert-ID der letzten Operation abrufen
     * 
     * @return int
     * @since 1.0.0
     */
    public function get_insert_id(): int
    {
        return $this->wpdb->insert_id;
    }

    /**
     * Prüfen ob alle Tabellen existieren
     * 
     * @return array Status-Array aller Tabellen
     * @since 1.0.0
     */
    public function check_tables_exist(): array
    {
        $status = array();
        
        foreach ($this->tables as $key => $table_name) {
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            )) === $table_name;
            
            $status[$key] = $exists;
            
            // Zusätzlich Spalten prüfen für erweiterte Diagnose
            if ($exists) {
                $columns = $this->wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
                $status["{$key}_columns"] = count($columns);
            }
        }
        
        return $status;
    }

    /**
     * Datenbank-Reparatur durchführen
     * 
     * @return array Reparatur-Ergebnis
     * @since 1.0.0
     */
    public function repair_database(): array
    {
        $results = array();
        
        try {
            // Prüfe alle Tabellen
            $table_status = $this->check_tables_exist();
            
            foreach ($this->tables as $key => $table_name) {
                if (!$table_status[$key]) {
                    $results[] = "Tabelle {$key} fehlt - wird neu erstellt...";
                    
                    // Versuche Tabelle zu erstellen
                    $method_name = "create_{$key}_table";
                    if (method_exists($this, $method_name)) {
                        $success = $this->$method_name();
                        $results[] = $success ? "✓ Tabelle {$key} erfolgreich erstellt" : "✗ Fehler beim Erstellen von {$key}";
                    }
                } else {
                    $results[] = "✓ Tabelle {$key} OK";
                }
            }
            
            // Prüfe Standard-Daten
            $categories_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['categories']}");
            if ($categories_count == 0) {
                $this->insert_default_categories();
                $results[] = "✓ Standard-Kategorien wiederhergestellt";
            }
            
            $settings_count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['settings']}");
            if ($settings_count == 0) {
                $this->insert_default_settings();
                $results[] = "✓ Standard-Einstellungen wiederhergestellt";
            }
            
        } catch (Exception $e) {
            $results[] = "✗ Reparatur-Fehler: " . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Tabellen-Namen abrufen
     * 
     * @return array
     * @since 1.0.0
     */
    public function get_table_names(): array
    {
        return $this->tables;
    }

    /**
     * Einzelne Tabelle abrufen
     * 
     * @param string $table_key
     * @return string|null
     * @since 1.0.0
     */
    public function get_table(string $table_key): ?string
    {
        return $this->tables[$table_key] ?? null;
    }

    /**
     * Plugin-Tabellen löschen (für Deinstallation)
     * 
     * @return bool
     * @since 1.0.0
     */
    public function drop_tables(): bool
    {
        try {
            // In umgekehrter Reihenfolge löschen wegen Foreign Keys
            foreach (array_reverse($this->tables) as $table_name) {
                $this->wpdb->query("DROP TABLE IF EXISTS {$table_name}");
            }
            
            // Plugin-Optionen löschen
            delete_option('amp_db_version');
            
            error_log('AMP Database: Alle Plugin-Tabellen erfolgreich gelöscht');
            return true;
            
        } catch (Exception $e) {
            error_log('AMP Database: Fehler beim Löschen der Tabellen: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Datenbank-Statistiken abrufen
     * 
     * @return array
     * @since 1.0.0
     */
    public function get_database_stats(): array
    {
        $stats = array();
        
        try {
            foreach ($this->tables as $key => $table_name) {
                $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$table_name}");
                $stats[$key] = (int)$count;
            }
            
            // Zusätzliche Statistiken
            $stats['total_records'] = array_sum($stats);
            $stats['db_version'] = get_option('amp_db_version', '0.0.0');
            
        } catch (Exception $e) {
            error_log('AMP Database: Fehler beim Abrufen der Statistiken: ' . $e->getMessage());
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
}

// Ende der Datei - Kein schließendes PHP-Tag notwendig