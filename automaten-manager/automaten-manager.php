<?php
/**
 * Plugin Name: Automaten Manager Pro
 * Plugin URI: https://deine-domain.de/automaten-manager
 * Description: Ultramodernes Automaten-Verwaltungssystem mit mobilem Barcode-Scanner
 * Version: 1.0.0
 * Author: Dein Name
 * Text Domain: automaten-manager
 */

// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('AM_PLUGIN_VERSION', '1.0.0');
define('AM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AUTOMATEN_MANAGER_VERSION', '1.0.0');
define('AUTOMATEN_MANAGER_URL', plugin_dir_url(__FILE__));
define('AUTOMATEN_MANAGER_PATH', plugin_dir_path(__FILE__));

// Hauptklasse des Plugins
class AutomatenManager {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        // Hooks für Aktivierung und Deaktivierung
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        // Admin-Bereich initialisieren
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        
        // AJAX-Handler registrieren
        add_action('wp_ajax_am_save_product', [$this, 'ajaxSaveProduct']);
        add_action('wp_ajax_am_get_products', [$this, 'ajaxGetProducts']);
        add_action('wp_ajax_am_delete_product', [$this, 'ajaxDeleteProduct']);
        add_action('wp_ajax_am_save_category', [$this, 'ajaxSaveCategory']);
        add_action('wp_ajax_am_get_categories', [$this, 'ajaxGetCategories']);
        add_action('wp_ajax_am_delete_category', [$this, 'ajaxDeleteCategory']);
        
        // Scanner AJAX-Handler
        add_action('wp_ajax_am_check_product', [$this, 'ajaxCheckProduct']);
        add_action('wp_ajax_am_process_scan', [$this, 'ajaxProcessScan']);
        
        // Mobile-optimierte Admin-Styles
        add_action('admin_head', [$this, 'addMobileViewport']);
    }
    
    public function activate() {
        $this->createDatabaseTables();
        $this->createDefaultData();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function createDatabaseTables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Kategorien-Tabelle
        $table_categories = $wpdb->prefix . 'am_categories';
        $sql_categories = "CREATE TABLE IF NOT EXISTS $table_categories (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            color varchar(7) DEFAULT '#3498db',
            icon varchar(50) DEFAULT 'box',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Produkte-Tabelle mit erweiterten Feldern
        $table_products = $wpdb->prefix . 'am_products';
        $sql_products = "CREATE TABLE IF NOT EXISTS $table_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            barcode varchar(50) NOT NULL UNIQUE,
            name varchar(200) NOT NULL,
            price decimal(10,2) NOT NULL,
            stock int(11) DEFAULT 0,
            category_id mediumint(9),
            image_url text,
            description text,
            min_stock int(11) DEFAULT 10,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY barcode_index (barcode),
            KEY category_id (category_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categories);
        dbDelta($sql_products);
    }
    
    private function createDefaultData() {
        global $wpdb;
        $categories_table = $wpdb->prefix . 'am_categories';
        
        // Prüfen ob bereits Kategorien existieren
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
        
        if ($count == 0) {
            // Standard-Kategorien anlegen
            $default_categories = [
                ['name' => 'Getränke', 'color' => '#3498db', 'icon' => 'coffee'],
                ['name' => 'Snacks', 'color' => '#e74c3c', 'icon' => 'cookie'],
                ['name' => 'Süßwaren', 'color' => '#f39c12', 'icon' => 'candy-cane'],
                ['name' => 'Gesund', 'color' => '#27ae60', 'icon' => 'apple-alt']
            ];
            
            foreach ($default_categories as $category) {
                $wpdb->insert($categories_table, $category);
            }
        }
    }
    
    public function addAdminMenu() {
        // Hauptmenü
        add_menu_page(
            'Automaten Manager',
            'Automaten Manager',
            'manage_options',
            'automaten-manager',
            [$this, 'renderDashboard'],
            'dashicons-store',
            30
        );
        
        // Untermenüs
        add_submenu_page(
            'automaten-manager',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'automaten-manager',
            [$this, 'renderDashboard']
        );
        
        add_submenu_page(
            'automaten-manager',
            'Produkte',
            'Produkte',
            'manage_options',
            'am-products',
            [$this, 'renderProducts']
        );
        
        add_submenu_page(
            'automaten-manager',
            'Kategorien',
            'Kategorien',
            'manage_options',
            'am-categories',
            [$this, 'renderCategories']
        );
        
        add_submenu_page(
            'automaten-manager',
            'Barcode Scanner',
            'Scanner',
            'manage_options',
            'am-scanner',
            [$this, 'renderScanner']
        );
    }
    
    public function enqueueAdminAssets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'automaten-manager') === false && strpos($hook, 'am-') === false) {
            return;
        }
        
        // Modern CSS Framework & Custom Styles
        wp_enqueue_style('am-admin-styles', AM_PLUGIN_URL . 'assets/css/admin-styles.css', [], AM_PLUGIN_VERSION);
        wp_enqueue_style('am-animations', AM_PLUGIN_URL . 'assets/css/animations.css', [], AM_PLUGIN_VERSION);
        wp_enqueue_style('am-scanner', AM_PLUGIN_URL . 'assets/css/scanner.css', [], AM_PLUGIN_VERSION);
        
        // Font Awesome for Icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
        
        // jQuery (ensure it's loaded)
        wp_enqueue_script('jquery');
        
        // QuaggaJS for Barcode Scanner (load first)
        wp_enqueue_script(
            'quagga',
            'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js',
            [],
            '0.12.1',
            true
        );
        
        // Scanner JS with dependency on QuaggaJS
        wp_enqueue_script(
            'am-scanner',
            AM_PLUGIN_URL . 'assets/js/scanner.js',
            ['jquery', 'quagga'],
            AM_PLUGIN_VERSION,
            true
        );
        
        // Product Modal JS
        wp_enqueue_script(
            'am-product-modal',
            AM_PLUGIN_URL . 'assets/js/product-modal.js',
            ['jquery', 'am-scanner'],
            AM_PLUGIN_VERSION,
            true
        );
        
        // Main Admin Script
        wp_enqueue_script(
            'am-admin-script',
            AM_PLUGIN_URL . 'assets/js/admin-script.js',
            ['jquery', 'am-scanner', 'am-product-modal'],
            AM_PLUGIN_VERSION,
            true
        );
        
        // Localize scripts
        wp_localize_script('am-scanner', 'automatenManager', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('am_scanner_nonce'),
            'adminUrl' => admin_url(),
            'defaultImage' => AM_PLUGIN_URL . 'assets/images/no-image.png'
        ]);

        wp_localize_script('am-admin-script', 'am_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('am_ajax_nonce')
        ]);
    }
    
    public function addMobileViewport() {
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">';
        echo '<meta name="apple-mobile-web-app-capable" content="yes">';
        echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">';
    }
    
    public function renderDashboard() {
        include AM_PLUGIN_PATH . 'templates/dashboard.php';
        include AM_PLUGIN_PATH . 'templates/scanner.php';
        include AM_PLUGIN_PATH . 'templates/product-create-modal.php'; 
    }
    
    public function renderProducts() {
        include AM_PLUGIN_PATH . 'templates/products.php';
        include AM_PLUGIN_PATH . 'templates/scanner.php';
        include AM_PLUGIN_PATH . 'templates/product-create-modal.php'; 
    }
    
    public function renderCategories() {
        include AM_PLUGIN_PATH . 'templates/categories.php';
    }
    
    public function renderScanner() {
        include AM_PLUGIN_PATH . 'templates/scanner-page.php';
        include AM_PLUGIN_PATH . 'templates/scanner.php';
        include AM_PLUGIN_PATH . 'templates/product-create-modal.php'; 
    }
    
    // AJAX Handler für Produkte
    public function ajaxSaveProduct() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'am_products';
        
        $barcode = sanitize_text_field($_POST['barcode']);
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $image_url = esc_url_raw($_POST['image_url']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $min_stock = intval($_POST['min_stock'] ?? 10);
        
        // Validation
        if (empty($barcode)) {
            wp_send_json_error(['message' => 'Barcode ist erforderlich']);
        }
        
        if (empty($name)) {
            wp_send_json_error(['message' => 'Produktname ist erforderlich']);
        }
        
        if ($price <= 0) {
            wp_send_json_error(['message' => 'Gültiger Preis ist erforderlich']);
        }
        
        if ($stock < 0) {
            wp_send_json_error(['message' => 'Lagerbestand darf nicht negativ sein']);
        }

        $data = [
            'barcode' => $barcode,
            'name' => $name,
            'price' => $price,
            'stock' => $stock,
            'category_id' => $category_id,
            'image_url' => $image_url,
            'description' => $description,
            'min_stock' => $min_stock
        ];

        $format = ['%s', '%s', '%f', '%d', '%d', '%s', '%s', '%d'];

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $product_id = intval($_POST['id']);
            
            // Check for duplicate barcode (excluding current product)
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE barcode = %s AND id != %d",
                $barcode, $product_id
            ));
            
            if ($existing) {
                wp_send_json_error(['message' => 'Barcode bereits vorhanden']);
            }
            
            $result = $wpdb->update($table, $data, ['id' => $product_id], $format, ['%d']);
        } else {
            // Check for duplicate barcode
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE barcode = %s",
                $barcode
            ));
            
            if ($existing) {
                wp_send_json_error(['message' => 'Barcode bereits vorhanden']);
            }
            
            $result = $wpdb->insert($table, $data, $format);
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Produkt erfolgreich gespeichert']);
        } else {
            wp_send_json_error(['message' => 'Datenbankfehler: ' . $wpdb->last_error]);
        }
    }
    
    public function ajaxGetProducts() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $products_table = $wpdb->prefix . 'am_products';
        $categories_table = $wpdb->prefix . 'am_categories';
        
        $products = $wpdb->get_results("
            SELECT p.*, c.name as category_name, c.color as category_color 
            FROM $products_table p 
            LEFT JOIN $categories_table c ON p.category_id = c.id 
            ORDER BY p.created_at DESC
        ");
        
        wp_send_json_success($products);
    }
    
    public function ajaxDeleteProduct() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'am_products';
        
        $id = intval($_POST['id']);
        $result = $wpdb->delete($table, ['id' => $id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Produkt gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen']);
        }
    }
    
    // AJAX Handler für Kategorien
    public function ajaxSaveCategory() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'am_categories';
        
        $data = [
            'name' => sanitize_text_field($_POST['name']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon'])
        ];
        
        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $result = $wpdb->update($table, $data, ['id' => intval($_POST['id'])]);
        } else {
            $result = $wpdb->insert($table, $data);
        }
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Kategorie gespeichert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Speichern']);
        }
    }
    
    public function ajaxGetCategories() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'am_categories';
        
        $categories = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
        
        wp_send_json_success($categories);
    }
    
    public function ajaxDeleteCategory() {
        check_ajax_referer('am_ajax_nonce', 'nonce');
        
        global $wpdb;
        $table = $wpdb->prefix . 'am_categories';
        
        $id = intval($_POST['id']);
        $result = $wpdb->delete($table, ['id' => $id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Kategorie gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen']);
        }
    }
    
    // Scanner AJAX Handler
    public function ajaxCheckProduct() {
        check_ajax_referer('am_scanner_nonce', 'nonce');
        
        $barcode = sanitize_text_field($_POST['barcode']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'am_products';
        
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT p.*, c.name as category_name, c.color as category_color 
             FROM $table_name p 
             LEFT JOIN {$wpdb->prefix}am_categories c ON p.category_id = c.id 
             WHERE p.barcode = %s",
            $barcode
        ));
        
        if ($product) {
            wp_send_json_success(['product' => $product]);
        } else {
            wp_send_json_success(['product' => null]);
        }
    }
    
    public function ajaxProcessScan() {
        check_ajax_referer('am_scanner_nonce', 'nonce');
        
        $barcode = sanitize_text_field($_POST['barcode']);
        $action = sanitize_text_field($_POST['scan_action']);
        $quantity = intval($_POST['quantity']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'am_products';
        
        if ($action === 'sell') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name 
                 SET stock = GREATEST(0, stock - %d) 
                 WHERE barcode = %s",
                $quantity,
                $barcode
            ));
            
            if ($result !== false) {
                do_action('am_product_sold', $barcode, $quantity);
                
                wp_send_json_success([
                    'message' => sprintf('%d Produkt(e) verkauft', $quantity)
                ]);
            }
        } elseif ($action === 'restock') {
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table_name 
                 SET stock = stock + %d 
                 WHERE barcode = %s",
                $quantity,
                $barcode
            ));
            
            if ($result !== false) {
                do_action('am_product_restocked', $barcode, $quantity);
                
                wp_send_json_success([
                    'message' => sprintf('%d Produkt(e) aufgefüllt', $quantity)
                ]);
            }
        }
        
        wp_send_json_error(['message' => 'Fehler bei der Verarbeitung']);
    }
}

// Plugin initialisieren
AutomatenManager::getInstance();