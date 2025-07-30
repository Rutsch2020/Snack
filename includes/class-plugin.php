<?php
/**
 * Main Plugin Class - VOLLSTÄNDIG VEREINFACHTE VERSION
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Core
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Core;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Admin\AMP_Admin_Manager;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class - VEREINFACHT
 * 
 * @since 1.0.0
 */
class AMP_Plugin
{
    private string $version;
    private ?AMP_Database_Manager $database = null;
    private ?AMP_Admin_Manager $admin = null;
    private bool $initialized = false;
    private static ?AMP_Plugin $instance = null;

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->version = defined('AMP_VERSION') ? AMP_VERSION : '1.0.0';
    }

    /**
     * Singleton Instance
     * 
     * @return AMP_Plugin
     * @since 1.0.0
     */
    public static function get_instance(): AMP_Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Plugin initialisieren
     * 
     * @return void
     * @since 1.0.0
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            // Database Manager
            $this->database = new AMP_Database_Manager();
            
            // WordPress Hooks
            $this->register_hooks();
            
            // Admin nur im Backend
            if (is_admin()) {
                $this->init_admin();
            }
            
            $this->initialized = true;
            
            error_log('AMP Plugin: Erfolgreich initialisiert');
            
        } catch (Exception $e) {
            error_log('AMP Plugin Init Fehler: ' . $e->getMessage());
            add_action('admin_notices', function() use ($e) {
                echo '<div class="notice notice-error"><p>AutomatenManager Pro: ' . esc_html($e->getMessage()) . '</p></div>';
            });
        }
    }

    /**
     * WordPress Hooks registrieren
     * 
     * @return void
     * @since 1.0.0
     */
    private function register_hooks(): void
    {
        // Basis-Hooks
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_loaded', [$this, 'wp_loaded']);
        
        // Cron-Hooks
        add_action('amp_daily_expiry_check', [$this, 'handle_daily_expiry_check']);
        add_action('amp_weekly_cleanup', [$this, 'handle_weekly_cleanup']);
        add_action('amp_cleanup_logs', [$this, 'handle_cleanup_logs']);
        add_action('amp_process_email_queue', [$this, 'handle_email_queue']);
        
        // Plugin Action Links
        if (defined('AMP_PLUGIN_BASENAME')) {
            add_filter('plugin_action_links_' . AMP_PLUGIN_BASENAME, [$this, 'plugin_action_links']);
        }
    }

    /**
     * Admin initialisieren
     * 
     * @return void
     * @since 1.0.0
     */
    private function init_admin(): void
    {
        try {
            $this->admin = new AMP_Admin_Manager();
            $this->admin->init();
            
            error_log('AMP Plugin: Admin Manager initialisiert');
            
        } catch (Exception $e) {
            error_log('AMP Plugin: Admin Init Fehler: ' . $e->getMessage());
        }
    }

    /**
     * Nach WordPress geladen
     * 
     * @return void
     * @since 1.0.0
     */
    public function wp_loaded(): void
    {
        // Cron-Intervalle registrieren
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
        
        do_action('amp_plugin_loaded', $this);
    }

    /**
     * Cron-Intervalle hinzufügen
     * 
     * @param array $schedules
     * @return array
     * @since 1.0.0
     */
    public function add_cron_intervals(array $schedules): array
    {
        $schedules['amp_five_minutes'] = [
            'interval' => 300,
            'display' => __('Alle 5 Minuten', 'automaten-manager-pro')
        ];
        
        return $schedules;
    }

    /**
     * Textdomain laden
     * 
     * @return void
     * @since 1.0.0
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'automaten-manager-pro',
            false,
            dirname(plugin_basename(AMP_PLUGIN_FILE)) . '/languages'
        );
    }

    /**
     * Plugin Action Links
     * 
     * @param array $links
     * @return array
     * @since 1.0.0
     */
    public function plugin_action_links(array $links): array
    {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=automaten-manager-pro') . '">' . __('Dashboard', 'automaten-manager-pro') . '</a>',
            '<a href="' . admin_url('admin.php?page=amp-scanner') . '" style="color: #0073aa; font-weight: bold;">' . __('Scanner', 'automaten-manager-pro') . '</a>'
        ];
        
        return array_merge($plugin_links, $links);
    }

    /**
     * Cron: Tägliche Ablaufprüfung
     * 
     * @return void
     * @since 1.0.0
     */
    public function handle_daily_expiry_check(): void
    {
        try {
            global $wpdb;
            
            if (!$this->database) {
                return;
            }
            
            $tables = $this->database->get_table_names();
            
            // Produkte die in 2 Tagen ablaufen
            $expiring_products = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tables['products']} 
                WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL %d DAY)
                AND status = 'active'",
                get_option('amp_expiry_warning_days', 2)
            ));
            
            if (!empty($expiring_products)) {
                // E-Mail senden
                $this->send_expiry_warning_email($expiring_products);
            }
            
            error_log('AMP Plugin: Tägliche Ablaufprüfung abgeschlossen. ' . count($expiring_products) . ' Produkte laufen bald ab.');
            
        } catch (Exception $e) {
            error_log('AMP Plugin: Fehler bei Ablaufprüfung: ' . $e->getMessage());
        }
    }

    /**
     * Cron: Wöchentliche Bereinigung
     * 
     * @return void
     * @since 1.0.0
     */
    public function handle_weekly_cleanup(): void
    {
        try {
            // Alte temporäre Dateien löschen
            $temp_dir = wp_upload_dir()['basedir'] . '/automaten-manager-pro/temp/';
            if (is_dir($temp_dir)) {
                $files = glob($temp_dir . '*');
                $now = time();
                
                foreach ($files as $file) {
                    if (is_file($file) && ($now - filemtime($file) > 604800)) { // 7 Tage
                        unlink($file);
                    }
                }
            }
            
            // Cache leeren
            wp_cache_flush();
            
            error_log('AMP Plugin: Wöchentliche Bereinigung abgeschlossen');
            
        } catch (Exception $e) {
            error_log('AMP Plugin: Fehler bei wöchentlicher Bereinigung: ' . $e->getMessage());
        }
    }

    /**
     * Cron: Logs bereinigen
     * 
     * @return void
     * @since 1.0.0
     */
    public function handle_cleanup_logs(): void
    {
        try {
            if (!$this->database) {
                return;
            }
            
            global $wpdb;
            $tables = $this->database->get_table_names();
            
            // E-Mail Logs älter als 90 Tage löschen
            if (isset($tables['email_log'])) {
                $wpdb->query(
                    "DELETE FROM {$tables['email_log']} 
                    WHERE sent_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
                );
            }
            
            // Security Logs älter als 180 Tage löschen
            if (isset($tables['security_log'])) {
                $wpdb->query(
                    "DELETE FROM {$tables['security_log']} 
                    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 180 DAY)"
                );
            }
            
            error_log('AMP Plugin: Log-Bereinigung abgeschlossen');
            
        } catch (Exception $e) {
            error_log('AMP Plugin: Fehler bei Log-Bereinigung: ' . $e->getMessage());
        }
    }

    /**
     * Cron: E-Mail Queue verarbeiten
     * 
     * @return void
     * @since 1.0.0
     */
    public function handle_email_queue(): void
    {
        // TODO: E-Mail Queue implementieren
        error_log('AMP Plugin: E-Mail Queue verarbeitet');
    }

    /**
     * Ablaufwarnung E-Mail senden
     * 
     * @param array $products
     * @return void
     * @since 1.0.0
     */
    private function send_expiry_warning_email(array $products): void
    {
        $recipient = get_option('amp_email_expiry_recipient', get_option('admin_email'));
        
        if (!is_email($recipient)) {
            return;
        }
        
        $subject = sprintf(
            '[%s] Ablaufwarnung: %d Produkte laufen bald ab',
            get_bloginfo('name'),
            count($products)
        );
        
        $message = "Folgende Produkte laufen in den nächsten Tagen ab:\n\n";
        
        foreach ($products as $product) {
            $message .= sprintf(
                "- %s (Barcode: %s)\n  Ablaufdatum: %s\n  Bestand: %d Stück\n\n",
                $product->name,
                $product->barcode,
                date_i18n('d.m.Y', strtotime($product->expiry_date)),
                $product->current_stock
            );
        }
        
        $message .= "\nBitte ergreifen Sie entsprechende Maßnahmen.";
        
        wp_mail($recipient, $subject, $message);
    }

    /**
     * Database Manager abrufen
     * 
     * @return AMP_Database_Manager|null
     * @since 1.0.0
     */
    public function get_database(): ?AMP_Database_Manager
    {
        return $this->database;
    }

    /**
     * Plugin-Version abrufen
     * 
     * @return string
     * @since 1.0.0
     */
    public function get_version(): string
    {
        return $this->version;
    }
}