<?php
/**
 * Plugin Name: Automaten Manager Pro
 * Plugin URI: https://example.com/automaten-manager-pro
 * Description: Ultramodernes WordPress-Plugin für die Verwaltung von Verkaufsautomaten mit mobile-first Barcode-Scanner
 * Version: 2.0.0
 * Author: Ihr Name
 * License: GPL v2 or later
 * Text Domain: automaten-manager
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('AUTOMATEN_VERSION', '2.0.0');
define('AUTOMATEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AUTOMATEN_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AUTOMATEN_MIN_PHP_VERSION', '7.4');
define('AUTOMATEN_MIN_WP_VERSION', '5.0');

/**
 * Hauptklasse des Automaten Manager Pro Plugins
 */
class AutomatenManager {
    
    private static $instance = null;
    
    /**
     * Singleton Pattern - Plugin-Instanz abrufen
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Konstruktor - Plugin initialisieren
     */
    private function __construct() {
        // System-Anforderungen prüfen
        if (!$this->check_requirements()) {
            return;
        }
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('AutomatenManager', 'uninstall'));
    }
    
    /**
     * System-Anforderungen prüfen
     */
    private function check_requirements() {
        if (version_compare(PHP_VERSION, AUTOMATEN_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(__('Automaten Manager Pro erfordert PHP %s oder höher. Ihre Version: %s', 'automaten-manager'), 
                           AUTOMATEN_MIN_PHP_VERSION, PHP_VERSION);
                echo '</p></div>';
            });
            return false;
        }
        
        global $wp_version;
        if (version_compare($wp_version, AUTOMATEN_MIN_WP_VERSION, '<')) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo sprintf(__('Automaten Manager Pro erfordert WordPress %s oder höher. Ihre Version: %s', 'automaten-manager'), 
                           AUTOMATEN_MIN_WP_VERSION, $GLOBALS['wp_version']);
                echo '</p></div>';
            });
            return false;
        }
        
        return true;
    }
    
    /**
     * Plugin initialisieren
     */
    public function init() {
        // Admin-Interface laden
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_action('admin_init', array($this, 'handle_admin_actions'));
        }
        
        // AJAX-Handler registrieren
        $this->register_ajax_handlers();
        
        // Textdomain für Übersetzungen laden
        load_plugin_textdomain('automaten-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Custom capabilities hinzufügen
        add_action('admin_init', array($this, 'add_capabilities'));
    }
    
    /**
     * Custom Capabilities hinzufügen
     */
    public function add_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_automaten');
            $role->add_cap('scan_automaten_products');
            $role->add_cap('edit_automaten_products');
        }
    }
    
    /**
     * Plugin-Aktivierung
     */
    public function activate() {
        // Mindestanforderungen prüfen
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Plugin konnte nicht aktiviert werden. Systemanforderungen nicht erfüllt.', 'automaten-manager'));
        }
        
        $this->create_tables();
        $this->insert_default_data();
        $this->create_upload_directory();
        
        // Plugin-Version speichern
        update_option('automaten_version', AUTOMATEN_VERSION);
        update_option('automaten_install_date', current_time('mysql'));
        
        // Rewrite-Regeln aktualisieren
        flush_rewrite_rules();
        
        // Willkommens-Flag setzen
        set_transient('automaten_welcome_notice', true, 30);
    }
    
    /**
     * Plugin-Deaktivierung
     */
    public function deactivate() {
        // Cleanup falls nötig
        flush_rewrite_rules();
        
        // Cron-Jobs entfernen
        wp_clear_scheduled_hook('automaten_daily_cleanup');
        
        // Transients löschen
        delete_transient('automaten_welcome_notice');
    }
    
    /**
     * Plugin-Deinstallation
     */
    public static function uninstall() {
        global $wpdb;
        
        // Tabellen löschen (optional - nur wenn Benutzer es wünscht)
        $delete_data = get_option('automaten_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}automaten_scan_logs");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}automaten_activities");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}automaten_products");
            $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}automaten_categories");
        }
        
        // Optionen löschen
        delete_option('automaten_version');
        delete_option('automaten_install_date');
        delete_option('automaten_settings');
        delete_option('automaten_delete_data_on_uninstall');
        
        // User Meta löschen
        $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'automaten_%'");
    }
    
    /**
     * Upload-Verzeichnis erstellen
     */
    private function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $automaten_dir = $upload_dir['basedir'] . '/automaten-manager';
        
        if (!file_exists($automaten_dir)) {
            wp_mkdir_p($automaten_dir);
            
            // .htaccess für Sicherheit
            $htaccess_content = "Options -Indexes\nDeny from all\n";
            file_put_contents($automaten_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Datenbanktabellen erstellen (KORRIGIERTE VERSION - ohne FOREIGN KEY)
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Kategorien-Tabelle
        $categories_table = $wpdb->prefix . 'automaten_categories';
        $categories_sql = "CREATE TABLE $categories_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            color varchar(7) DEFAULT '#6366f1',
            icon varchar(50) DEFAULT 'fas fa-cube',
            description text,
            sort_order int(11) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY sort_order (sort_order),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Produkte-Tabelle (OHNE FOREIGN KEY - dbDelta unterstützt das nicht)
        $products_table = $wpdb->prefix . 'automaten_products';
        $products_sql = "CREATE TABLE $products_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            barcode varchar(100) NULL,
            name varchar(200) NOT NULL,
            price decimal(10,2) DEFAULT 0.00,
            stock_quantity int(11) NOT NULL DEFAULT 0,
            min_stock int(11) DEFAULT 5,
            max_stock int(11) DEFAULT 100,
            category_id int(11) DEFAULT 1,
            brand varchar(100) DEFAULT NULL,
            description text,
            image_url text,
            external_data longtext,
            weight decimal(8,3) DEFAULT NULL,
            dimensions varchar(50) DEFAULT NULL,
            expiry_date date DEFAULT NULL,
            supplier varchar(100) DEFAULT NULL,
            cost_price decimal(10,2) DEFAULT NULL,
            margin_percent decimal(5,2) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY barcode (barcode),
            KEY category_id (category_id),
            KEY is_active (is_active),
            KEY stock_quantity (stock_quantity),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        // Scan-Logs-Tabelle (OHNE FOREIGN KEY)
        $scan_logs_table = $wpdb->prefix . 'automaten_scan_logs';
        $scan_logs_sql = "CREATE TABLE $scan_logs_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            barcode varchar(100) NOT NULL,
            product_id int(11) DEFAULT NULL,
            scan_type enum('manual','camera','api') DEFAULT 'manual',
            result_type enum('success','error','warning','new','existing') DEFAULT 'success',
            result_message text,
            quantity int(11) DEFAULT 1,
            action_type enum('view','add','update','sell','restock') DEFAULT 'view',
            user_id int(11) NOT NULL,
            session_id varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            location_data text,
            processing_time_ms int(11) DEFAULT NULL,
            scanned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY barcode (barcode),
            KEY product_id (product_id),
            KEY user_id (user_id),
            KEY scan_type (scan_type),
            KEY result_type (result_type),
            KEY scanned_at (scanned_at)
        ) $charset_collate;";
        
        // Aktivitäten-Log-Tabelle
        $activities_table = $wpdb->prefix . 'automaten_activities';
        $activities_sql = "CREATE TABLE $activities_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            activity_type varchar(50) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id int(11) DEFAULT NULL,
            user_id int(11) NOT NULL,
            description text NOT NULL,
            metadata longtext,
            ip_address varchar(45) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY activity_type (activity_type),
            KEY object_type (object_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($categories_sql);
        dbDelta($products_sql);
        dbDelta($scan_logs_sql);
        dbDelta($activities_sql);
    }
    
    /**
     * Standard-Daten einfügen
     */
    private function insert_default_data() {
        global $wpdb;
        
        $categories_table = $wpdb->prefix . 'automaten_categories';
        
        // Prüfen ob bereits Kategorien existieren
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
        
        if ($existing_categories == 0) {
            // Standard-Kategorien erstellen
            $default_categories = array(
                array(
                    'name' => 'Getränke', 
                    'color' => '#3b82f6', 
                    'icon' => 'fas fa-glass-water',
                    'description' => 'Erfrischungsgetränke, Säfte, Wasser',
                    'sort_order' => 1
                ),
                array(
                    'name' => 'Snacks', 
                    'color' => '#f59e0b', 
                    'icon' => 'fas fa-cookie-bite',
                    'description' => 'Chips, Nüsse, salzige Snacks',
                    'sort_order' => 2
                ),
                array(
                    'name' => 'Süßwaren', 
                    'color' => '#ec4899', 
                    'icon' => 'fas fa-candy-cane',
                    'description' => 'Schokolade, Bonbons, süße Leckereien',
                    'sort_order' => 3
                ),
                array(
                    'name' => 'Kaffee & Tee', 
                    'color' => '#8b5cf6', 
                    'icon' => 'fas fa-coffee',
                    'description' => 'Kaffee, Tee, Heißgetränke',
                    'sort_order' => 4
                ),
                array(
                    'name' => 'Bürobedarf', 
                    'color' => '#10b981', 
                    'icon' => 'fas fa-paperclip',
                    'description' => 'Büromaterial, Schreibwaren',
                    'sort_order' => 5
                ),
                array(
                    'name' => 'Allgemein', 
                    'color' => '#6b7280', 
                    'icon' => 'fas fa-cube',
                    'description' => 'Verschiedene Produkte',
                    'sort_order' => 6
                )
            );
            
            foreach ($default_categories as $category) {
                $wpdb->insert(
                    $categories_table,
                    $category,
                    array('%s', '%s', '%s', '%s', '%d')
                );
            }
            
            // Willkommens-Aktivität loggen
            $this->log_activity('system', 'Plugin aktiviert und Standard-Kategorien erstellt');
        }
    }
    
    /**
     * Admin-Aktionen verarbeiten
     */
    public function handle_admin_actions() {
        // CSV-Import
        if (isset($_POST['automaten_import_csv']) && wp_verify_nonce($_POST['automaten_nonce'], 'automaten_import')) {
            $this->handle_csv_import();
        }
        
        // Settings speichern
        if (isset($_POST['automaten_save_settings']) && wp_verify_nonce($_POST['automaten_nonce'], 'automaten_settings')) {
            $this->save_settings();
        }
    }
    
    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        // Hauptmenü
        $main_page = add_menu_page(
            'Automaten Manager',
            'Automaten Manager',
            'manage_automaten',
            'automaten-manager',
            array($this, 'dashboard_page'),
            'dashicons-store',
            30
        );
        
        // Untermenüs
        add_submenu_page(
            'automaten-manager',
            'Dashboard',
            'Dashboard',
            'manage_automaten',
            'automaten-manager',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'automaten-manager',
            'Scanner',
            '📱 Scanner',
            'scan_automaten_products',
            'automaten-scanner',
            array($this, 'scanner_page')
        );
        
        add_submenu_page(
            'automaten-manager',
            'Produkte',
            'Produkte',
            'edit_automaten_products',
            'automaten-products',
            array($this, 'products_page')
        );
        
        add_submenu_page(
            'automaten-manager',
            'Kategorien',
            'Kategorien',
            'manage_automaten',
            'automaten-categories',
            array($this, 'categories_page')
        );
        
        add_submenu_page(
            'automaten-manager',
            'Berichte',
            'Berichte',
            'manage_automaten',
            'automaten-reports',
            array($this, 'reports_page')
        );
        
        add_submenu_page(
            'automaten-manager',
            'Einstellungen',
            'Einstellungen',
            'manage_automaten',
            'automaten-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Admin-Assets einbinden
     */
    public function enqueue_admin_assets($hook) {
        // Nur auf Plugin-Seiten laden
        if (strpos($hook, 'automaten') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'automaten-admin-styles',
            AUTOMATEN_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            AUTOMATEN_VERSION
        );
        
        wp_enqueue_style(
            'automaten-animations',
            AUTOMATEN_PLUGIN_URL . 'assets/css/animations.css',
            array(),
            AUTOMATEN_VERSION
        );
        
        // Scanner-spezifische Styles
        if (strpos($hook, 'automaten-scanner') !== false) {
            wp_enqueue_style(
                'automaten-scanner',
                AUTOMATEN_PLUGIN_URL . 'assets/css/scanner.css',
                array('automaten-admin-styles'),
                AUTOMATEN_VERSION
            );
        }
        
        // FontAwesome für Icons
        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            array(),
            '6.4.0'
        );
        
        // JavaScript
        wp_enqueue_script(
            'automaten-admin-script',
            AUTOMATEN_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            AUTOMATEN_VERSION,
            true
        );
        
        // Scanner-JavaScript
        if (strpos($hook, 'automaten-scanner') !== false) {
            wp_enqueue_script(
                'automaten-scanner-script',
                AUTOMATEN_PLUGIN_URL . 'assets/js/scanner.js',
                array('jquery', 'automaten-admin-script'),
                AUTOMATEN_VERSION,
                true
            );
        }
        
        // AJAX-URL für JavaScript bereitstellen
        wp_localize_script('automaten-admin-script', 'automatenAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('automaten_nonce'),
            'pluginUrl' => AUTOMATEN_PLUGIN_URL,
            'messages' => array(
                'delete_confirm' => __('Sind Sie sicher, dass Sie dieses Element löschen möchten?', 'automaten-manager'),
                'save_success' => __('Erfolgreich gespeichert!', 'automaten-manager'),
                'error_occurred' => __('Ein Fehler ist aufgetreten.', 'automaten-manager'),
                'loading' => __('Wird geladen...', 'automaten-manager'),
                'scan_success' => __('Barcode erfolgreich gescannt!', 'automaten-manager'),
                'camera_error' => __('Kamera konnte nicht gestartet werden.', 'automaten-manager')
            ),
            'settings' => array(
                'scan_cooldown' => get_option('automaten_scan_cooldown', 2000),
                'auto_save' => get_option('automaten_auto_save', true),
                'sound_enabled' => get_option('automaten_sound_enabled', true)
            )
        ));
        
        // Scanner-spezifische Localization
        if (strpos($hook, 'automaten-scanner') !== false) {
            wp_localize_script('automaten-scanner-script', 'automatenNonce', wp_create_nonce('automaten_nonce'));
            wp_localize_script('automaten-scanner-script', 'ajaxurl', admin_url('admin-ajax.php'));
        }
    }
    
    /**
     * AJAX-Handler registrieren
     */
    private function register_ajax_handlers() {
        // Bestehende Produkt-Aktionen
        add_action('wp_ajax_automaten_save_product', array($this, 'ajax_save_product'));
        add_action('wp_ajax_automaten_get_products', array($this, 'ajax_get_products'));
        add_action('wp_ajax_automaten_delete_product', array($this, 'ajax_delete_product'));
        add_action('wp_ajax_automaten_get_product', array($this, 'ajax_get_product'));
        
        // Kategorie-Aktionen
        add_action('wp_ajax_automaten_save_category', array($this, 'ajax_save_category'));
        add_action('wp_ajax_automaten_get_categories', array($this, 'ajax_get_categories'));
        add_action('wp_ajax_automaten_delete_category', array($this, 'ajax_delete_category'));
        
        // Erweiterte Scanner-Aktionen
        add_action('wp_ajax_automaten_check_product', array($this, 'ajax_check_product'));
        add_action('wp_ajax_automaten_add_product', array($this, 'ajax_add_product'));
        add_action('wp_ajax_automaten_log_scan', array($this, 'ajax_log_scan'));
        add_action('wp_ajax_automaten_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_automaten_process_scan', array($this, 'ajax_process_scan'));
        
        // Dashboard-Aktionen
        add_action('wp_ajax_automaten_get_dashboard_data', array($this, 'ajax_get_dashboard_data'));
        add_action('wp_ajax_automaten_get_recent_activity', array($this, 'ajax_get_recent_activity'));
        
        // Import/Export
        add_action('wp_ajax_automaten_export_products', array($this, 'ajax_export_products'));
        add_action('wp_ajax_automaten_import_products', array($this, 'ajax_import_products'));
        
        // Settings
        add_action('wp_ajax_automaten_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_automaten_test_api', array($this, 'ajax_test_api'));
        
        // Settings AJAX Handler
        add_action('wp_ajax_automaten_create_backup', array($this, 'ajax_create_backup'));
        add_action('wp_ajax_automaten_export_csv', array($this, 'ajax_export_csv'));
        add_action('wp_ajax_automaten_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_automaten_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_automaten_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_automaten_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_automaten_auto_save_settings', array($this, 'ajax_auto_save_settings'));
    }
    
    /**
     * Template-Seiten anzeigen
     */
    public function dashboard_page() {
        $this->include_template('dashboard');
    }
    
    public function scanner_page() {
        $this->include_template('scanner');
    }
    
    public function products_page() {
        $this->include_template('products');
    }
    
    public function categories_page() {
        $this->include_template('categories');
    }
    
    public function reports_page() {
        $this->include_template('reports');
    }
    
    public function settings_page() {
        $this->include_template('settings');
    }
    
    /**
     * Template-Datei sicher einbinden
     */
    private function include_template($template_name) {
        $template_path = AUTOMATEN_PLUGIN_PATH . "templates/{$template_name}.php";
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div class="notice notice-error"><p>';
            echo sprintf(__('Template-Datei nicht gefunden: %s', 'automaten-manager'), $template_name);
            echo '</p></div>';
        }
    }
    
    // ===== AJAX HANDLERS =====
    
    /**
     * AJAX: Produkt per Barcode prüfen
     */
    public function ajax_check_product() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('scan_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        $barcode = sanitize_text_field($_POST['barcode']);
        
        if (empty($barcode)) {
            wp_send_json_error('Barcode ist erforderlich');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'automaten_products';
        
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.color as category_color, c.icon as category_icon 
             FROM $table_name p 
             LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id 
             WHERE p.barcode = %s AND p.is_active = 1",
            $barcode
        ));
        
        if ($product) {
            // External data dekodieren
            if ($product->external_data) {
                $product->external_data = json_decode($product->external_data, true);
            }
            
            wp_send_json_success($product);
        } else {
            wp_send_json_error('Produkt nicht gefunden');
        }
    }
    
    /**
     * AJAX: Neues Produkt hinzufügen
     */
    public function ajax_add_product() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('edit_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        $product_data = json_decode(stripslashes($_POST['product_data']), true);
        
        if (empty($product_data['name'])) {
            wp_send_json_error('Produktname ist erforderlich');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'automaten_products';
        
        // Check if barcode already exists
        if (!empty($product_data['barcode'])) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE barcode = %s",
                $product_data['barcode']
            ));
            
            if ($existing) {
                wp_send_json_error('Ein Produkt mit diesem Barcode existiert bereits');
            }
        }
        
        // Prepare data for insertion
        $insert_data = array(
            'name' => sanitize_text_field($product_data['name']),
            'category_id' => !empty($product_data['category_id']) ? intval($product_data['category_id']) : null,
            'price' => !empty($product_data['price']) ? floatval($product_data['price']) : null,
            'stock_quantity' => !empty($product_data['stock_quantity']) ? intval($product_data['stock_quantity']) : 0,
            'barcode' => !empty($product_data['barcode']) ? sanitize_text_field($product_data['barcode']) : null,
            'description' => !empty($product_data['description']) ? sanitize_textarea_field($product_data['description']) : null,
            'image_url' => !empty($product_data['image_url']) ? esc_url_raw($product_data['image_url']) : null,
            'brand' => !empty($product_data['brand']) ? sanitize_text_field($product_data['brand']) : null,
            'external_data' => !empty($product_data['external_data']) ? wp_json_encode($product_data['external_data']) : null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Remove null values
        $insert_data = array_filter($insert_data, function($value) {
            return $value !== null;
        });
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result === false) {
            wp_send_json_error('Fehler beim Speichern des Produkts: ' . $wpdb->last_error);
        }
        
        $product_id = $wpdb->insert_id;
        
        // Log the addition
        $this->log_activity('product_added', "Produkt hinzugefügt: {$product_data['name']}", $product_id);
        
        wp_send_json_success(array(
            'id' => $product_id,
            'message' => 'Produkt erfolgreich hinzugefügt'
        ));
    }
    
    /**
     * AJAX: Scan-Aktivität loggen
     */
    public function ajax_log_scan() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('scan_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        $barcode = sanitize_text_field($_POST['barcode']);
        $scan_type = sanitize_text_field($_POST['scan_type']);
        $result_message = sanitize_text_field($_POST['result_message']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'automaten_scan_logs';
        
        // Get product ID if exists
        $product_id = null;
        if (!empty($barcode)) {
            $product_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}automaten_products WHERE barcode = %s",
                $barcode
            ));
        }
        
        $wpdb->insert($table_name, array(
            'barcode' => $barcode,
            'product_id' => $product_id,
            'scan_type' => 'camera', // oder 'manual'
            'result_type' => $scan_type,
            'result_message' => $result_message,
            'user_id' => get_current_user_id(),
            'session_id' => session_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'scanned_at' => current_time('mysql')
        ));
        
        wp_send_json_success('Scan geloggt');
    }
    
    /**
     * AJAX: Scanner-Statistiken abrufen
     */
    public function ajax_get_stats() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('scan_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $stats = array();
        
        // Scans heute
        $stats['scans_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_scan_logs 
             WHERE DATE(scanned_at) = %s",
            current_time('Y-m-d')
        ));
        
        // Gesamte Produkte
        $stats['total_products'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE is_active = 1"
        );
        
        // Scans diese Woche
        $stats['scans_week'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_scan_logs 
             WHERE scanned_at >= %s",
            date('Y-m-d', strtotime('-7 days'))
        ));
        
        // Niedrige Lagerbestände
        $stats['low_stock_count'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products 
             WHERE stock_quantity <= min_stock AND is_active = 1"
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX: Scan verarbeiten (Legacy-Support)
     */
    public function ajax_process_scan() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('scan_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $barcode = sanitize_text_field($_POST['barcode']);
        $action = sanitize_text_field($_POST['action']); // 'sell' oder 'restock'
        $quantity = intval($_POST['quantity']);
        
        // Produkt finden
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}automaten_products WHERE barcode = %s AND is_active = 1",
            $barcode
        ));
        
        if (!$product) {
            wp_send_json_error(array('message' => 'Produkt nicht gefunden'));
            return;
        }
        
        // Lagerbestand aktualisieren
        $new_stock = $product->stock_quantity;
        
        if ($action === 'sell') {
            if ($product->stock_quantity < $quantity) {
                wp_send_json_error(array('message' => 'Nicht genügend Lagerbestand'));
                return;
            }
            $new_stock = $product->stock_quantity - $quantity;
        } elseif ($action === 'restock') {
            $new_stock = $product->stock_quantity + $quantity;
        }
        
        // Lagerbestand aktualisieren
        $wpdb->update(
            $wpdb->prefix . 'automaten_products',
            array('stock_quantity' => $new_stock, 'updated_at' => current_time('mysql')),
            array('id' => $product->id)
        );
        
        // Scan-Log erstellen
        $wpdb->insert(
            $wpdb->prefix . 'automaten_scan_logs',
            array(
                'barcode' => $barcode,
                'product_id' => $product->id,
                'action_type' => $action,
                'quantity' => $quantity,
                'result_type' => 'success',
                'result_message' => ucfirst($action) . " von {$quantity} Einheit(en)",
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'scanned_at' => current_time('mysql')
            )
        );
        
        // Aktivität loggen
        $this->log_activity($action, "Produkt {$product->name}: {$quantity} Einheit(en)", $product->id);
        
        wp_send_json_success(array(
            'message' => ucfirst($action) . ' erfolgreich',
            'new_stock' => $new_stock,
            'product' => $product
        ));
    }
    
    /**
     * AJAX: Produkt speichern (erweitert)
     */
    public function ajax_save_product() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('edit_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'automaten_products';
        
        $product_data = array(
            'barcode' => sanitize_text_field($_POST['barcode']),
            'name' => sanitize_text_field($_POST['name']),
            'price' => floatval($_POST['price']),
            'stock_quantity' => intval($_POST['stock_quantity']),
            'min_stock' => intval($_POST['min_stock']),
            'max_stock' => intval($_POST['max_stock']),
            'category_id' => intval($_POST['category_id']),
            'brand' => sanitize_text_field($_POST['brand']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image_url' => esc_url_raw($_POST['image_url']),
            'weight' => !empty($_POST['weight']) ? floatval($_POST['weight']) : null,
            'dimensions' => sanitize_text_field($_POST['dimensions']),
            'supplier' => sanitize_text_field($_POST['supplier']),
            'cost_price' => !empty($_POST['cost_price']) ? floatval($_POST['cost_price']) : null,
            'margin_percent' => !empty($_POST['margin_percent']) ? floatval($_POST['margin_percent']) : null,
            'expiry_date' => !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) : null,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $product_id = intval($_POST['product_id']);
        
        if ($product_id > 0) {
            // Update
            $result = $wpdb->update($products_table, $product_data, array('id' => $product_id));
            $action = 'updated';
        } else {
            // Insert
            $product_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($products_table, $product_data);
            $product_id = $wpdb->insert_id;
            $action = 'created';
        }
        
        if ($result !== false) {
            // Aktivität loggen
            $this->log_activity("product_{$action}", "Produkt {$product_data['name']} {$action}", $product_id);
            
            wp_send_json_success(array(
                'message' => 'Produkt gespeichert',
                'product_id' => $product_id
            ));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Speichern: ' . $wpdb->last_error));
        }
    }
    
    /**
     * AJAX: Einzelnes Produkt abrufen
     */
    public function ajax_get_product() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('edit_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $product_id = intval($_GET['product_id']);
        
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as category_name 
             FROM {$wpdb->prefix}automaten_products p 
             LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id 
             WHERE p.id = %d",
            $product_id
        ));
        
        if ($product) {
            if ($product->external_data) {
                $product->external_data = json_decode($product->external_data, true);
            }
            wp_send_json_success($product);
        } else {
            wp_send_json_error(array('message' => 'Produkt nicht gefunden'));
        }
    }
    
    /**
     * AJAX: Produkte abrufen (erweitert)
     */
    public function ajax_get_products() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('edit_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'active';
        $order_by = isset($_GET['order_by']) ? sanitize_text_field($_GET['order_by']) : 'updated_at';
        $order_dir = isset($_GET['order_dir']) ? sanitize_text_field($_GET['order_dir']) : 'DESC';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        
        // Basis-Query
        $sql = "SELECT p.*, c.name as category_name, c.color as category_color, c.icon as category_icon
                FROM {$wpdb->prefix}automaten_products p 
                LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        $params = array();
        
        // Filter anwenden
        if (!empty($search)) {
            $sql .= " AND (p.name LIKE %s OR p.barcode LIKE %s OR p.brand LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if ($category > 0) {
            $sql .= " AND p.category_id = %d";
            $params[] = $category;
        }
        
        if ($status === 'active') {
            $sql .= " AND p.is_active = 1";
        } elseif ($status === 'inactive') {
            $sql .= " AND p.is_active = 0";
        } elseif ($status === 'low_stock') {
            $sql .= " AND p.stock_quantity <= p.min_stock";
        }
        
        // Sortierung
        $allowed_order_by = array('name', 'barcode', 'price', 'stock_quantity', 'created_at', 'updated_at');
        if (in_array($order_by, $allowed_order_by)) {
            $order_dir = ($order_dir === 'ASC') ? 'ASC' : 'DESC';
            $sql .= " ORDER BY p.{$order_by} {$order_dir}";
        } else {
            $sql .= " ORDER BY p.updated_at DESC";
        }
        
        // Limit
        if ($limit > 0) {
            $sql .= " LIMIT %d OFFSET %d";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        if (!empty($params)) {
            $products = $wpdb->get_results($wpdb->prepare($sql, $params));
        } else {
            $products = $wpdb->get_results($sql);
        }
        
        wp_send_json_success(array(
            'products' => $products
        ));
    }
    
    /**
     * AJAX: Produkt löschen
     */
    public function ajax_delete_product() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('edit_automaten_products')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $product_id = intval($_POST['product_id']);
        
        // Produkt-Info für Log holen
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}automaten_products WHERE id = %d",
            $product_id
        ));
        
        if (!$product) {
            wp_send_json_error(array('message' => 'Produkt nicht gefunden'));
            return;
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'automaten_products',
            array('id' => $product_id),
            array('%d')
        );
        
        if ($result !== false) {
            // Aktivität loggen
            $this->log_activity('product_deleted', "Produkt gelöscht: {$product->name}", $product_id);
            
            wp_send_json_success(array('message' => 'Produkt gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    /**
     * AJAX: Kategorie speichern
     */
    public function ajax_save_category() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $categories_table = $wpdb->prefix . 'automaten_categories';
        
        $category_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'description' => sanitize_textarea_field($_POST['description']),
            'sort_order' => intval($_POST['sort_order']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'updated_at' => current_time('mysql')
        );
        
        $category_id = intval($_POST['category_id']);
        
        if ($category_id > 0) {
            $result = $wpdb->update($categories_table, $category_data, array('id' => $category_id));
            $action = 'updated';
        } else {
            $category_data['created_at'] = current_time('mysql');
            $result = $wpdb->insert($categories_table, $category_data);
            $category_id = $wpdb->insert_id;
            $action = 'created';
        }
        
        if ($result !== false) {
            $this->log_activity("category_{$action}", "Kategorie {$category_data['name']} {$action}", $category_id);
            wp_send_json_success(array('message' => 'Kategorie gespeichert', 'category_id' => $category_id));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Speichern'));
        }
    }
    
    /**
     * AJAX: Kategorien abrufen
     */
    public function ajax_get_categories() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        global $wpdb;
        $categories = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}automaten_categories 
             ORDER BY sort_order ASC, name ASC"
        );
        
        wp_send_json_success($categories);
    }
    
    /**
     * AJAX: Kategorie löschen
     */
    public function ajax_delete_category() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        $category_id = intval($_POST['category_id']);
        
        // Kategorie-Info für Log holen
        $category = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}automaten_categories WHERE id = %d",
            $category_id
        ));
        
        if (!$category) {
            wp_send_json_error(array('message' => 'Kategorie nicht gefunden'));
            return;
        }
        
        // Prüfen ob Kategorie verwendet wird
        $products_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE category_id = %d",
            $category_id
        ));
        
        if ($products_count > 0) {
            wp_send_json_error(array('message' => "Kategorie wird noch von {$products_count} Produkt(en) verwendet"));
            return;
        }
        
        $result = $wpdb->delete(
            $wpdb->prefix . 'automaten_categories',
            array('id' => $category_id),
            array('%d')
        );
        
        if ($result !== false) {
            $this->log_activity('category_deleted', "Kategorie gelöscht: {$category->name}", $category_id);
            wp_send_json_success(array('message' => 'Kategorie gelöscht'));
        } else {
            wp_send_json_error(array('message' => 'Fehler beim Löschen'));
        }
    }
    
    /**
     * AJAX: Dashboard-Daten abrufen
     */
    public function ajax_get_dashboard_data() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $data = array();
        
        // Basis-Statistiken
        $data['total_products'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE is_active = 1"
        );
        
        $data['total_categories'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_categories WHERE is_active = 1"
        );
        
        $data['low_stock_count'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products 
             WHERE stock_quantity <= min_stock AND is_active = 1"
        );
        
        $data['total_scans_today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}automaten_scan_logs 
             WHERE DATE(scanned_at) = %s",
            current_time('Y-m-d')
        ));
        
        wp_send_json_success($data);
    }
    
    // ===== SETTINGS AJAX HANDLERS =====
    
    /**
     * AJAX: Backup erstellen
     */
    public function ajax_create_backup() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        try {
            $backup_data = array(
                'version' => AUTOMATEN_VERSION,
                'timestamp' => current_time('mysql'),
                'categories' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}automaten_categories"),
                'products' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}automaten_products"),
                'settings' => get_option('automaten_settings', array())
            );
            
            $filename = 'automaten-backup-' . date('Y-m-d-H-i-s') . '.json';
            $backup_json = wp_json_encode($backup_data, JSON_PRETTY_PRINT);
            
            // Backup-Datei erstellen
            $upload_dir = wp_upload_dir();
            $backup_path = $upload_dir['basedir'] . '/automaten-manager/' . $filename;
            
            if (file_put_contents($backup_path, $backup_json)) {
                wp_send_json_success(array(
                    'message' => 'Backup erfolgreich erstellt',
                    'filename' => $filename,
                    'download_url' => $upload_dir['baseurl'] . '/automaten-manager/' . $filename
                ));
            } else {
                wp_send_json_error('Backup-Datei konnte nicht erstellt werden');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Erstellen des Backups: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: CSV Export
     */
    public function ajax_export_csv() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        global $wpdb;
        
        $products = $wpdb->get_results("
            SELECT p.*, c.name as category_name 
            FROM {$wpdb->prefix}automaten_products p 
            LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id 
            ORDER BY p.name
        ");
        
        $csv_data = "ID,Barcode,Name,Preis,Lagerbestand,Mindestbestand,Kategorie,Marke,Beschreibung,Aktiv\n";
        
        foreach ($products as $product) {
            $csv_data .= sprintf(
                "%d,%s,%s,%.2f,%d,%d,%s,%s,%s,%s\n",
                $product->id,
                $product->barcode,
                '"' . str_replace('"', '""', $product->name) . '"',
                $product->price,
                $product->stock_quantity,
                $product->min_stock,
                '"' . str_replace('"', '""', $product->category_name) . '"',
                '"' . str_replace('"', '""', $product->brand) . '"',
                '"' . str_replace('"', '""', $product->description) . '"',
                $product->is_active ? 'Ja' : 'Nein'
            );
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="automaten-export-' . date('Y-m-d') . '.csv"');
        echo $csv_data;
        exit;
    }
    
    /**
     * AJAX: Einstellungen zurücksetzen
     */
    public function ajax_reset_settings() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        delete_option('automaten_settings');
        wp_send_json_success('Einstellungen zurückgesetzt');
    }
    
    /**
     * AJAX: Verbindung testen
     */
    public function ajax_test_connection() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        $tests = array();
        
        // Database Test
        global $wpdb;
        $tests['database'] = $wpdb->get_var("SELECT 1") ? 'OK' : 'FEHLER';
        
        // OpenFoodFacts API Test
        $response = wp_remote_get('https://world.openfoodfacts.org/api/v0/product/3017620422003.json', array(
            'timeout' => 5
        ));
        $tests['openfoodfacts'] = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200 ? 'OK' : 'FEHLER';
        
        // File System Test
        $upload_dir = wp_upload_dir();
        $tests['filesystem'] = is_writable($upload_dir['basedir']) ? 'OK' : 'FEHLER';
        
        $details = "Datenbank: {$tests['database']}\nOpenFoodFacts API: {$tests['openfoodfacts']}\nDateisystem: {$tests['filesystem']}";
        
        $all_ok = !in_array('FEHLER', $tests);
        
        if ($all_ok) {
            wp_send_json_success(array('details' => $details));
        } else {
            wp_send_json_error($details);
        }
    }
    
    /**
     * AJAX: Cache leeren
     */
    public function ajax_clear_cache() {
        check_ajax_referer('automaten_nonce', 'nonce');
        
        if (!current_user_can('manage_automaten')) {
            wp_die('Unauthorized');
        }
        
        // WordPress Cache leeren
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // Plugin-spezifische Transients löschen
        delete_transient('automaten_stats');
        delete_transient('automaten_dashboard_data');
        
        wp_send_json_success('Cache geleert');
    }
    
    // ===== UTILITY FUNCTIONS =====
    
    /**
     * Client-IP ermitteln
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Aktivität loggen
     */
    private function log_activity($activity_type, $description, $object_id = null, $object_type = 'product') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'automaten_activities',
            array(
                'activity_type' => $activity_type,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'user_id' => get_current_user_id(),
                'description' => $description,
                'ip_address' => $this->get_client_ip(),
                'created_at' => current_time('mysql')
            )
        );
    }
}

// Plugin initialisieren
AutomatenManager::get_instance();