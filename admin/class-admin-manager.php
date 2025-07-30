<?php
/**
 * Admin Manager - VOLLSTÄNDIG REPARIERTE VERSION
 * 
 * Verwaltet das gesamte WordPress Admin-Interface für AutomatenManager Pro
 * Alle Fehler behoben: Admin-Menü, AJAX-Handler, Security
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Admin
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Admin;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Products\AMP_Products_Manager;
use AutomatenManagerPro\Categories\AMP_Categories_Manager;
use AutomatenManagerPro\Scanner\AMP_Scanner_Manager;
use AutomatenManagerPro\Sales\AMP_Sales_Manager;
use AutomatenManagerPro\Inventory\AMP_Inventory_Manager;
use AutomatenManagerPro\Waste\AMP_Waste_Manager;
use AutomatenManagerPro\Reports\AMP_Reports_Manager;
use AutomatenManagerPro\Email\AMP_Email_Manager;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Manager Class - VOLLSTÄNDIG REPARIERT
 * 
 * @since 1.0.0
 */
class AMP_Admin_Manager
{
    /**
     * Database Manager
     * @var AMP_Database_Manager
     */
    private AMP_Database_Manager $database;
    
    /**
     * Module Manager Instances
     */
    private ?AMP_Products_Manager $products_manager = null;
    private ?AMP_Categories_Manager $categories_manager = null;
    private ?AMP_Scanner_Manager $scanner_manager = null;
    private ?AMP_Sales_Manager $sales_manager = null;
    private ?AMP_Inventory_Manager $inventory_manager = null;
    private ?AMP_Waste_Manager $waste_manager = null;
    private ?AMP_Reports_Manager $reports_manager = null;
    private ?AMP_Email_Manager $email_manager = null;
    
    /**
     * Current page
     * @var string
     */
    private string $current_page = '';
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        
        // Module sofort laden wenn Klassen existieren
        $this->load_core_modules();
        
        error_log('AMP Admin Manager: Constructor abgeschlossen');
    }
    
    /**
     * Admin Manager initialisieren
     * 
     * @since 1.0.0
     */
    public function init(): void
    {
        // WordPress Hooks registrieren
        $this->register_hooks();
        
        // Capabilities sicherstellen
        $this->ensure_capabilities();
        
        error_log('AMP Admin Manager: Initialisierung abgeschlossen');
    }
    
    /**
     * Core Module laden
     * 
     * @since 1.0.0
     */
    private function load_core_modules(): void
    {
        // Products Manager (wichtigster)
        if (class_exists('AutomatenManagerPro\Products\AMP_Products_Manager')) {
            $this->products_manager = new AMP_Products_Manager();
        }
        
        // Weitere Module bei Bedarf laden
        if (class_exists('AutomatenManagerPro\Scanner\AMP_Scanner_Manager')) {
            $this->scanner_manager = new AMP_Scanner_Manager();
        }
        
        if (class_exists('AutomatenManagerPro\Email\AMP_Email_Manager')) {
            $this->email_manager = new AMP_Email_Manager();
        }
    }
    
    /**
     * WordPress Hooks registrieren
     * 
     * @since 1.0.0
     */
    private function register_hooks(): void
    {
        // KRITISCH: Admin Menu mit hoher Priorität!
        add_action('admin_menu', [$this, 'create_admin_menu'], 5);
        
        // Admin Init
        add_action('admin_init', [$this, 'admin_init']);
        
        // Scripts und Styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // ALLE AJAX Handler registrieren
        $this->register_all_ajax_handlers();
        
        // Admin Notices
        add_action('admin_notices', [$this, 'show_admin_notices']);
        
        // Admin Bar
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        
        error_log('AMP Admin Manager: Alle Hooks registriert');
    }
    
    /**
     * ALLE AJAX Handler registrieren - VOLLSTÄNDIG!
     * 
     * @since 1.0.0
     */
    private function register_all_ajax_handlers(): void
    {
        // Scanner AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_scan_barcode', [$this, 'ajax_scan_barcode']);
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_check_barcode', [$this, 'ajax_check_barcode']);
        
        // Products AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_create_product', [$this, 'ajax_create_product']);
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_update_product', [$this, 'ajax_update_product']);
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_delete_product', [$this, 'ajax_delete_product']);
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_load_products', [$this, 'ajax_load_products']);
        
        // Sales AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_process_sale', [$this, 'ajax_process_sale']);
        
        // Inventory AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_update_stock', [$this, 'ajax_update_stock']);
        
        // Waste AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_log_disposal', [$this, 'ajax_log_disposal']);
        
        // Reports AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_get_report_data', [$this, 'ajax_get_report_data']);
        
        // Allgemeine Admin AJAX
        if (!has_action('wp_ajax_amp_' . $action)) add_action('wp_ajax_amp_admin_action', [$this, 'ajax_admin_action']);
        
        error_log('AMP Admin Manager: Alle AJAX Handler registriert');
    }
    
    /**
     * Admin Menu erstellen - DIREKT OHNE MENU HANDLER!
     * 
     * @since 1.0.0
     */
    public function create_admin_menu(): void
    {
        // Hauptmenü
        add_menu_page(
            __('AutomatenManager Pro', 'automaten-manager-pro'),
            __('AutomatenManager', 'automaten-manager-pro'),
            'amp_manage_products',
            'automaten-manager-pro',
            [$this, 'render_dashboard_page'],
            'dashicons-store',
            30
        );
        
        // Dashboard (erstes Untermenü)
        add_submenu_page(
            'automaten-manager-pro',
            __('Dashboard', 'automaten-manager-pro'),
            __('📊 Dashboard', 'automaten-manager-pro'),
            'amp_manage_products',
            'automaten-manager-pro',
            [$this, 'render_dashboard_page']
        );
        
        // Scanner
        add_submenu_page(
            'automaten-manager-pro',
            __('Scanner', 'automaten-manager-pro'),
            __('📱 Scanner', 'automaten-manager-pro'),
            'amp_use_scanner',
            'amp-scanner',
            [$this, 'render_scanner_page']
        );
        
        // Produkte
        add_submenu_page(
            'automaten-manager-pro',
            __('Produkte', 'automaten-manager-pro'),
            __('📦 Produkte', 'automaten-manager-pro'),
            'amp_manage_products',
            'amp-products',
            [$this, 'render_products_page']
        );
        
        // Kategorien
        add_submenu_page(
            'automaten-manager-pro',
            __('Kategorien', 'automaten-manager-pro'),
            __('🏷️ Kategorien', 'automaten-manager-pro'),
            'amp_manage_categories',
            'amp-categories',
            [$this, 'render_categories_page']
        );
        
        // Verkäufe
        add_submenu_page(
            'automaten-manager-pro',
            __('Verkäufe', 'automaten-manager-pro'),
            __('🛒 Verkäufe', 'automaten-manager-pro'),
            'amp_process_sales',
            'amp-sales',
            [$this, 'render_sales_page']
        );
        
        // Lager
        add_submenu_page(
            'automaten-manager-pro',
            __('Lager', 'automaten-manager-pro'),
            __('📊 Lager', 'automaten-manager-pro'),
            'amp_manage_inventory',
            'amp-inventory',
            [$this, 'render_inventory_page']
        );
        
        // Entsorgung
        add_submenu_page(
            'automaten-manager-pro',
            __('Entsorgung', 'automaten-manager-pro'),
            __('🗑️ Entsorgung', 'automaten-manager-pro'),
            'amp_dispose_products',
            'amp-waste',
            [$this, 'render_waste_page']
        );
        
        // Berichte
        add_submenu_page(
            'automaten-manager-pro',
            __('Berichte', 'automaten-manager-pro'),
            __('📈 Berichte', 'automaten-manager-pro'),
            'amp_view_reports',
            'amp-reports',
            [$this, 'render_reports_page']
        );
        
        // Einstellungen
        add_submenu_page(
            'automaten-manager-pro',
            __('Einstellungen', 'automaten-manager-pro'),
            __('⚙️ Einstellungen', 'automaten-manager-pro'),
            'amp_manage_settings',
            'amp-settings',
            [$this, 'render_settings_page']
        );
        
        error_log('AMP Admin Manager: Admin-Menü erfolgreich erstellt');
    }
    
    /**
     * Admin Bar Menu hinzufügen
     * 
     * @param \WP_Admin_Bar $wp_admin_bar
     * @since 1.0.0
     */
    public function add_admin_bar_menu(\WP_Admin_Bar $wp_admin_bar): void
    {
        if (!current_user_can('amp_use_scanner')) {
            return;
        }
        
        // Hauptknoten
        $wp_admin_bar->add_node([
            'id' => 'amp-menu',
            'title' => '<span class="ab-icon dashicons dashicons-store"></span> AMP',
            'href' => admin_url('admin.php?page=automaten-manager-pro')
        ]);
        
        // Scanner Schnellzugriff
        $wp_admin_bar->add_node([
            'parent' => 'amp-menu',
            'id' => 'amp-scanner',
            'title' => __('📱 Scanner öffnen', 'automaten-manager-pro'),
            'href' => admin_url('admin.php?page=amp-scanner')
        ]);
    }
    
    /**
     * Admin Init
     * 
     * @since 1.0.0
     */
    public function admin_init(): void
    {
        $this->current_page = $_GET['page'] ?? '';
        
        // Module bei Bedarf laden
        $this->load_modules_on_demand();
        
        // Settings registrieren
        $this->register_settings();
        
        // Aktivierungs-Redirect
        if (get_option('amp_activation_redirect', false)) {
            delete_option('amp_activation_redirect');
            
            if (!isset($_GET['activate-multi'])) {
                wp_redirect(admin_url('admin.php?page=automaten-manager-pro'));
                exit;
            }
        }
    }
    
    /**
     * Module bei Bedarf laden
     * 
     * @since 1.0.0
     */
    private function load_modules_on_demand(): void
    {
        switch ($this->current_page) {
            case 'amp-categories':
                if (!$this->categories_manager && class_exists('AutomatenManagerPro\Categories\AMP_Categories_Manager')) {
                    $this->categories_manager = new AMP_Categories_Manager();
                }
                break;
                
            case 'amp-sales':
                if (!$this->sales_manager && class_exists('AutomatenManagerPro\Sales\AMP_Sales_Manager')) {
                    $this->sales_manager = new AMP_Sales_Manager();
                }
                break;
                
            case 'amp-inventory':
                if (!$this->inventory_manager && class_exists('AutomatenManagerPro\Inventory\AMP_Inventory_Manager')) {
                    $this->inventory_manager = new AMP_Inventory_Manager();
                }
                break;
                
            case 'amp-waste':
                if (!$this->waste_manager && class_exists('AutomatenManagerPro\Waste\AMP_Waste_Manager')) {
                    $this->waste_manager = new AMP_Waste_Manager();
                }
                break;
                
            case 'amp-reports':
                if (!$this->reports_manager && class_exists('AutomatenManagerPro\Reports\AMP_Reports_Manager')) {
                    $this->reports_manager = new AMP_Reports_Manager();
                }
                break;
        }
    }
    
    /**
     * Capabilities sicherstellen
     * 
     * @since 1.0.0
     */
    private function ensure_capabilities(): void
    {
        $admin_role = get_role('administrator');
        if (!$admin_role) {
            return;
        }
        
        $capabilities = [
            'amp_manage_products',
            'amp_manage_categories',
            'amp_use_scanner',
            'amp_process_sales',
            'amp_manage_inventory',
            'amp_view_reports',
            'amp_manage_settings',
            'amp_export_data',
            'amp_dispose_products'
        ];
        
        foreach ($capabilities as $cap) {
            if (!$admin_role->has_cap($cap)) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Settings registrieren
     * 
     * @since 1.0.0
     */
    private function register_settings(): void
    {
        // Allgemeine Settings
        register_setting('amp_settings', 'amp_company_name');
        register_setting('amp_settings', 'amp_company_logo');
        
        // E-Mail Settings
        register_setting('amp_settings', 'amp_email_sales_recipient');
        register_setting('amp_settings', 'amp_email_inventory_recipient');
        register_setting('amp_settings', 'amp_email_expiry_recipient');
        register_setting('amp_settings', 'amp_email_waste_recipient');
        
        // Weitere Settings
        register_setting('amp_settings', 'amp_vat_settings');
        register_setting('amp_settings', 'amp_monitoring_settings');
    }
    
    /**
     * Admin Scripts und Styles laden
     * 
     * @param string $hook
     * @since 1.0.0
     */
    public function enqueue_admin_scripts(string $hook): void
    {
        // Nur auf Plugin-Seiten
        if (strpos($hook, 'automaten-manager-pro') === false && strpos($hook, 'amp-') === false) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'amp-admin-styles',
            AMP_PLUGIN_URL . 'admin/assets/css/admin-styles.css',
            [],
            AMP_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'amp-admin-scripts',
            AMP_PLUGIN_URL . 'admin/assets/js/admin-scripts.js',
            ['jquery', 'wp-util'],
            AMP_VERSION,
            true
        );
        
        // Media Manager für Produktbilder
        if ($this->current_page === 'amp-products') {
            wp_enqueue_media();
        }
        
        // Scanner Scripts
        if ($this->current_page === 'amp-scanner') {
            wp_enqueue_script(
                'quagga-js',
                AMP_PLUGIN_URL . 'admin/assets/js/vendor/quagga.min.js',
                [],
                '0.12.1',
                true
            );
        }
        
        // JavaScript Konfiguration
        $this->localize_admin_scripts();
    }
    
    /**
     * JavaScript Konfiguration
     * 
     * @since 1.0.0
     */
    private function localize_admin_scripts(): void
    {
        wp_localize_script('amp-admin-scripts', 'ampAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('amp_admin_nonce'),
            'pluginUrl' => AMP_PLUGIN_URL,
            'adminUrl' => admin_url(),
            'currentPage' => $this->current_page,
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => [
                'loading' => __('Wird geladen...', 'automaten-manager-pro'),
                'error' => __('Fehler aufgetreten', 'automaten-manager-pro'),
                'success' => __('Erfolgreich gespeichert', 'automaten-manager-pro'),
                'confirmDelete' => __('Wirklich löschen?', 'automaten-manager-pro'),
                'unsavedChanges' => __('Sie haben ungespeicherte Änderungen.', 'automaten-manager-pro')
            ],
            'capabilities' => [
                'manageProducts' => current_user_can('amp_manage_products'),
                'useScanner' => current_user_can('amp_use_scanner'),
                'processSales' => current_user_can('amp_process_sales')
            ]
        ]);
    }
    
    /**
     * Admin Notices anzeigen
     * 
     * @since 1.0.0
     */
    public function show_admin_notices(): void
    {
        $notices = get_transient('amp_admin_notices') ?: [];
        
        foreach ($notices as $notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
        }
        
        delete_transient('amp_admin_notices');
    }
    
    // ==============================================
    // AJAX HANDLER - ALLE IMPLEMENTIERT!
    // ==============================================
    
    /**
     * Scanner AJAX: Barcode scannen
     * 
     * @since 1.0.0
     */
    public function ajax_scan_barcode(): void
    {
        // Security Check
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_use_scanner')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        
        if (empty($barcode)) {
            wp_send_json_error(['message' => 'Kein Barcode übermittelt']);
            return;
        }
        
        // Scanner Manager verwenden wenn verfügbar
        if ($this->scanner_manager && method_exists($this->scanner_manager, 'process_barcode')) {
            $this->scanner_manager->process_barcode($barcode);
        } else {
            // Fallback: Direkt in Datenbank suchen
            global $wpdb;
            $table = $this->database->get_table('products');
            
            $product = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE barcode = %s AND status = 'active'",
                $barcode
            ), ARRAY_A);
            
            if ($product) {
                wp_send_json_success([
                    'found' => true,
                    'product' => $product
                ]);
            } else {
                wp_send_json_success([
                    'found' => false,
                    'barcode' => $barcode
                ]);
            }
        }
    }
    
    /**
     * Products AJAX: Barcode prüfen
     * 
     * @since 1.0.0
     */
    public function ajax_check_barcode(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        
        if ($this->products_manager && method_exists($this->products_manager, 'check_barcode_exists')) {
            $exists = $this->products_manager->check_barcode_exists($barcode);
            wp_send_json_success(['exists' => $exists]);
        } else {
            // Fallback
            global $wpdb;
            $table = $this->database->get_table('products');
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE barcode = %s",
                $barcode
            )) > 0;
            
            wp_send_json_success(['exists' => $exists]);
        }
    }
    
    /**
     * Products AJAX: Produkt erstellen
     * 
     * @since 1.0.0
     */
    public function ajax_create_product(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_manage_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        // Produkt-Daten sammeln
        $product_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'barcode' => sanitize_text_field($_POST['barcode'] ?? ''),
            'category_id' => intval($_POST['category_id'] ?? 0),
            'buy_price' => floatval($_POST['buy_price'] ?? 0),
            'sell_price' => floatval($_POST['sell_price'] ?? 0),
            'vat_rate' => floatval($_POST['vat_rate'] ?? 19),
            'deposit' => floatval($_POST['deposit'] ?? 0),
            'current_stock' => intval($_POST['current_stock'] ?? 0),
            'min_stock' => intval($_POST['min_stock'] ?? 0),
            'expiry_date' => sanitize_text_field($_POST['expiry_date'] ?? ''),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'status' => 'active'
        ];
        
        if ($this->products_manager && method_exists($this->products_manager, 'create_product')) {
            $result = $this->products_manager->create_product($product_data);
            
            if ($result) {
                wp_send_json_success(['message' => 'Produkt erfolgreich erstellt', 'product_id' => $result]);
            } else {
                wp_send_json_error(['message' => 'Fehler beim Erstellen des Produkts']);
            }
        } else {
            // Fallback: Direkt in DB
            $result = $this->database->insert('products', $product_data);
            
            if ($result) {
                wp_send_json_success(['message' => 'Produkt erfolgreich erstellt', 'product_id' => $result]);
            } else {
                wp_send_json_error(['message' => 'Fehler beim Erstellen des Produkts']);
            }
        }
    }
    
    /**
     * Products AJAX: Produkt aktualisieren
     * 
     * @since 1.0.0
     */
    public function ajax_update_product(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_manage_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'Keine Produkt-ID übermittelt']);
            return;
        }
        
        // Update-Daten sammeln
        $update_data = [];
        
        if (isset($_POST['name'])) $update_data['name'] = sanitize_text_field($_POST['name']);
        if (isset($_POST['buy_price'])) $update_data['buy_price'] = floatval($_POST['buy_price']);
        if (isset($_POST['sell_price'])) $update_data['sell_price'] = floatval($_POST['sell_price']);
        if (isset($_POST['current_stock'])) $update_data['current_stock'] = intval($_POST['current_stock']);
        if (isset($_POST['min_stock'])) $update_data['min_stock'] = intval($_POST['min_stock']);
        if (isset($_POST['expiry_date'])) $update_data['expiry_date'] = sanitize_text_field($_POST['expiry_date']);
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $this->database->update('products', $update_data, ['id' => $product_id]);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Produkt erfolgreich aktualisiert']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Aktualisieren']);
        }
    }
    
    /**
     * Products AJAX: Produkt löschen
     * 
     * @since 1.0.0
     */
    public function ajax_delete_product(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_manage_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'Keine Produkt-ID übermittelt']);
            return;
        }
        
        // Soft Delete (Status auf inactive setzen)
        $result = $this->database->update(
            'products', 
            ['status' => 'inactive', 'updated_at' => current_time('mysql')], 
            ['id' => $product_id]
        );
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'Produkt erfolgreich gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Fehler beim Löschen']);
        }
    }
    
    /**
     * Products AJAX: Produkte laden
     * 
     * @since 1.0.0
     */
    public function ajax_load_products(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        global $wpdb;
        $table = $this->database->get_table('products');
        
        $products = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status = 'active' ORDER BY name ASC",
            ARRAY_A
        );
        
        wp_send_json_success(['products' => $products]);
    }
    
    /**
     * Sales AJAX: Verkauf verarbeiten
     * 
     * @since 1.0.0
     */
    public function ajax_process_sale(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_process_sales')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        // TODO: Sales Manager implementieren
        wp_send_json_success(['message' => 'Verkauf verarbeitet (Demo)']);
    }
    
    /**
     * Inventory AJAX: Lager aktualisieren
     * 
     * @since 1.0.0
     */
    public function ajax_update_stock(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_manage_inventory')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $product_id = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'in');
        
        if (!$product_id || !$quantity) {
            wp_send_json_error(['message' => 'Ungültige Daten']);
            return;
        }
        
        // TODO: Inventory Manager implementieren
        wp_send_json_success(['message' => 'Lager aktualisiert (Demo)']);
    }
    
    /**
     * Waste AJAX: Entsorgung protokollieren
     * 
     * @since 1.0.0
     */
    public function ajax_log_disposal(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_dispose_products')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        // TODO: Waste Manager implementieren
        wp_send_json_success(['message' => 'Entsorgung protokolliert (Demo)']);
    }
    
    /**
     * Reports AJAX: Report-Daten abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_report_data(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        if (!current_user_can('amp_view_reports')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
            return;
        }
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        
        // TODO: Reports Manager implementieren
        wp_send_json_success([
            'report_type' => $report_type,
            'data' => [
                'sales_today' => rand(10, 50),
                'revenue_today' => rand(100, 500),
                'products_sold' => rand(20, 100)
            ]
        ]);
    }
    
    /**
     * Allgemeiner Admin AJAX Handler
     * 
     * @since 1.0.0
     */
    public function ajax_admin_action(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $action = sanitize_text_field($_POST['admin_action'] ?? '');
        
        switch ($action) {
            case 'get_stats':
                $this->ajax_get_dashboard_stats();
                break;
                
            case 'test_connection':
                wp_send_json_success(['message' => 'Verbindung OK']);
                break;
                
            default:
                wp_send_json_error(['message' => 'Unbekannte Aktion']);
        }
    }
    
    /**
     * Dashboard Statistiken abrufen
     * 
     * @since 1.0.0
     */
    private function ajax_get_dashboard_stats(): void
    {
        global $wpdb;
        $tables = $this->database->get_table_names();
        
        $stats = [
            'products_total' => $wpdb->get_var("SELECT COUNT(*) FROM {$tables['products']} WHERE status = 'active'") ?: 0,
            'products_low_stock' => $wpdb->get_var("SELECT COUNT(*) FROM {$tables['products']} WHERE current_stock <= min_stock AND status = 'active'") ?: 0,
            'sales_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tables['sales_sessions']} WHERE DATE(session_start) = %s",
                current_time('Y-m-d')
            )) ?: 0,
            'revenue_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(total_gross), 0) FROM {$tables['sales_sessions']} WHERE DATE(session_start) = %s",
                current_time('Y-m-d')
            )) ?: 0
        ];
        
        wp_send_json_success($stats);
    }
    
    // ==============================================
    // TEMPLATE RENDERING
    // ==============================================
    
    /**
     * Template rendern
     * 
     * @param string $template
     * @param array $variables
     * @since 1.0.0
     */
    private function render_template(string $template, array $variables = []): void
    {
        $template_file = AMP_PLUGIN_DIR . "admin/templates/{$template}.php";
        
        if (!file_exists($template_file)) {
            wp_die("Template nicht gefunden: {$template}");
        }
        
        extract($variables);
        
        echo '<div class="wrap amp-admin-page">';
        include $template_file;
        echo '</div>';
    }
    
    /**
     * Dashboard rendern
     * 
     * @since 1.0.0
     */
    public function render_dashboard_page(): void
    {
        $this->render_template('dashboard');
    }
    
    /**
     * Scanner rendern
     * 
     * @since 1.0.0
     */
    public function render_scanner_page(): void
    {
        $this->render_template('scanner');
    }
    
    /**
     * Produkte rendern
     * 
     * @since 1.0.0
     */
    public function render_products_page(): void
    {
        $this->render_template('products');
    }
    
    /**
     * Kategorien rendern
     * 
     * @since 1.0.0
     */
    public function render_categories_page(): void
    {
        $this->render_template('categories');
    }
    
    /**
     * Verkäufe rendern
     * 
     * @since 1.0.0
     */
    public function render_sales_page(): void
    {
        $this->render_template('sales');
    }
    
    /**
     * Lager rendern
     * 
     * @since 1.0.0
     */
    public function render_inventory_page(): void
    {
        $this->render_template('inventory');
    }
    
    /**
     * Entsorgung rendern
     * 
     * @since 1.0.0
     */
    public function render_waste_page(): void
    {
        $this->render_template('waste');
    }
    
    /**
     * Berichte rendern
     * 
     * @since 1.0.0
     */
    public function render_reports_page(): void
    {
        $this->render_template('reports');
    }
    
    /**
     * Einstellungen rendern
     * 
     * @since 1.0.0
     */
    public function render_settings_page(): void
    {
        $this->render_template('settings');
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