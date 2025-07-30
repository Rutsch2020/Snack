<?php
/**
 * Admin Menu Handler - REPARIERT
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Admin
 * @version     1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles WordPress Admin Menu Registration
 */
class AMP_Menu_Handler
{
    private array $menu_pages = [];
    
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    public function register_admin_menu(): void
    {
        // Hauptmenü
        add_menu_page(
            'AutomatenManager Pro',
            'AutomatenManager Pro',
            'manage_options',
            'automaten-manager-pro',
            [$this, 'dashboard_page'],
            'dashicons-store',
            30
        );
        
        // Unterseiten
        $this->menu_pages = [
            'dashboard' => ['Dashboard', 'automaten-manager-pro'],
            'scanner' => ['Scanner', 'amp-scanner'],
            'products' => ['Produkte', 'amp-products'],
            'categories' => ['Kategorien', 'amp-categories'],
            'sales' => ['Verkäufe', 'amp-sales'],
            'inventory' => ['Lager', 'amp-inventory'],
            'waste' => ['Entsorgung', 'amp-waste'],
            'reports' => ['Berichte', 'amp-reports'],
            'settings' => ['Einstellungen', 'amp-settings']
        ];
        
        foreach ($this->menu_pages as $key => $page) {
            add_submenu_page(
                'automaten-manager-pro',
                $page[0],
                $page[0],
                'manage_options',
                $page[1],
                [$this, $key . '_page']
            );
        }
    }
    
    public function enqueue_admin_assets($hook): void
    {
        if (strpos($hook, 'automaten-manager-pro') !== false || strpos($hook, 'amp-') !== false) {
            wp_enqueue_style(
                'amp-admin-styles',
                plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin-styles.css',
                [],
                '1.0.0'
            );
            
            wp_enqueue_script(
                'amp-admin-scripts',
                plugin_dir_url(dirname(__FILE__)) . 'assets/js/admin-scripts.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            // Scanner-spezifische Assets
            if (strpos($hook, 'amp-scanner') !== false) {
                wp_enqueue_script(
                    'amp-quagga',
                    plugin_dir_url(dirname(__FILE__)) . 'assets/js/vendor/quagga.min.js',
                    [],
                    '1.0.0',
                    true
                );
            }
            
            // AJAX-Konfiguration
            wp_localize_script('amp-admin-scripts', 'ampAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('amp_nonce'),
                'scanning' => true
            ]);
        }
    }
    
    // Page Callbacks
    public function dashboard_page(): void { $this->render_template('dashboard'); }
    public function scanner_page(): void { $this->render_template('scanner'); }
    public function products_page(): void { $this->render_template('products'); }
    public function categories_page(): void { $this->render_template('categories'); }
    public function sales_page(): void { $this->render_template('sales'); }
    public function inventory_page(): void { $this->render_template('inventory'); }
    public function waste_page(): void { $this->render_template('waste'); }
    public function reports_page(): void { $this->render_template('reports'); }
    public function settings_page(): void { $this->render_template('settings'); }
    
    private function render_template(string $template): void
    {
        $template_file = AMP_PLUGIN_DIR . 'admin/templates/' . $template . '.php';
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>Template fehlt: ' . esc_html($template) . '</p></div>';
        }
    }
}