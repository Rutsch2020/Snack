<?php
/**
 * Plugin Deactivator for AutomatenManager Pro
 * 
 * Verwaltet die Plugin-Deaktivierung, führt Cleanup-Operationen durch,
 * entfernt Cron-Jobs und temporäre Daten.
 * WICHTIG: Löscht KEINE persistenten Daten wie Produkte oder Verkäufe!
 *
 * @package     AutomatenManagerPro
 * @subpackage  Core
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Core;

// Sicherheitscheck - Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Deactivator Klasse
 * 
 * Wird beim Deaktivieren des Plugins ausgeführt.
 * Führt Cleanup-Operationen durch, OHNE wichtige Daten zu löschen.
 * 
 * @since 1.0.0
 */
class AMP_Deactivator
{
    /**
     * Plugin-Deaktivierung ausführen
     * 
     * Diese Methode wird beim Deaktivieren des Plugins aufgerufen.
     * Sie führt Cleanup-Operationen durch, behält aber alle wichtigen Daten.
     * 
     * @return void
     * @since 1.0.0
     */
    public static function deactivate(): void
    {
        // Cron-Jobs entfernen
        self::remove_cron_jobs();

        // Aktive Sessions beenden
        self::close_active_sessions();

        // Temporäre Dateien bereinigen
        self::cleanup_temporary_files();

        // Cache leeren
        self::clear_caches();

        // Deaktivierungs-spezifische Optionen
        self::set_deactivation_options();

        // .htaccess Regeln entfernen
        self::remove_htaccess_rules();

        // Deaktivierungs-Log erstellen
        self::log_deactivation();

        // Hook für andere Module
        do_action('amp_plugin_deactivated');

        // Admin-Benachrichtigung hinzufügen
        self::add_deactivation_notice();
    }

    /**
     * Cron-Jobs entfernen
     * 
     * Entfernt alle Plugin-spezifischen Cron-Jobs
     * 
     * @return void
     * @since 1.0.0
     */
    private static function remove_cron_jobs(): void
    {
        $cron_jobs = array(
            'amp_daily_expiry_check',
            'amp_weekly_cleanup',
            'amp_process_email_queue',
            'amp_daily_backup'
        );

        foreach ($cron_jobs as $hook) {
            $timestamp = wp_next_scheduled($hook);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $hook);
            }
            
            // Alle Instanzen dieses Hooks entfernen
            wp_clear_scheduled_hook($hook);
        }

        // Log für Debug-Zwecke
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('AMP Deactivator: ' . count($cron_jobs) . ' Cron-Jobs entfernt');
        }
    }

    /**
     * Aktive Verkaufs-Sessions schließen
     * 
     * Schließt alle offenen Verkaufs-Sessions sicher ab
     * 
     * @return void
     * @since 1.0.0
     */
    private static function close_active_sessions(): void
    {
        global $wpdb;

        // Tabellennamen mit Prefix
        $sales_sessions_table = $wpdb->prefix . 'amp_sales_sessions';

        // Prüfen ob Tabelle existiert
        if ($wpdb->get_var("SHOW TABLES LIKE '{$sales_sessions_table}'") !== $sales_sessions_table) {
            return;
        }

        // Aktive Sessions finden
        $active_sessions = $wpdb->get_results(
            "SELECT id, user_id, session_start 
             FROM {$sales_sessions_table} 
             WHERE status = 'active'"
        );

        if (!empty($active_sessions)) {
            // Sessions als 'abgebrochen' markieren
            $session_ids = wp_list_pluck($active_sessions, 'id');
            $ids_placeholder = implode(',', array_fill(0, count($session_ids), '%d'));

            $wpdb->query(
                $wpdb->prepare(
                    "UPDATE {$sales_sessions_table} 
                     SET status = 'cancelled', 
                         session_end = NOW(),
                         notes = CONCAT(COALESCE(notes, ''), ' [Plugin deaktiviert]')
                     WHERE id IN ({$ids_placeholder})",
                    ...$session_ids
                )
            );

            // Log für Admin
            $message = sprintf(
                __('%d aktive Verkaufs-Sessions wurden beim Deaktivieren automatisch geschlossen.', 'automaten-manager-pro'),
                count($active_sessions)
            );
            
            update_option('amp_deactivation_sessions_closed', $message);
        }
    }

    /**
     * Temporäre Dateien bereinigen
     * 
     * Löscht temporäre Dateien und Cache-Daten
     * 
     * @return void
     * @since 1.0.0
     */
    private static function cleanup_temporary_files(): void
    {
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/automaten-manager-pro';

        $temp_directories = array(
            $plugin_dir . '/temp',
            $plugin_dir . '/cache'
        );

        foreach ($temp_directories as $dir) {
            if (is_dir($dir)) {
                self::delete_directory_contents($dir);
            }
        }

        // Session-Daten aus WordPress löschen
        self::cleanup_session_data();
    }

    /**
     * Verzeichnis-Inhalte löschen (ohne das Verzeichnis selbst)
     * 
     * @param string $dir Verzeichnispfad
     * @return bool
     * @since 1.0.0
     */
    private static function delete_directory_contents(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        return true;
    }

    /**
     * Session-Daten bereinigen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function cleanup_session_data(): void
    {
        // WordPress Transients mit Plugin-Prefix löschen
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_amp_%' 
             OR option_name LIKE '_transient_timeout_amp_%'"
        );

        // Site Transients auch löschen
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_site_transient_amp_%' 
             OR option_name LIKE '_site_transient_timeout_amp_%'"
        );
    }

    /**
     * Caches leeren
     * 
     * @return void
     * @since 1.0.0
     */
    private static function clear_caches(): void
    {
        // WordPress Object Cache leeren
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        // Plugin-spezifische Cache-Keys löschen
        $cache_keys = array(
            'amp_products_list',
            'amp_categories_tree',
            'amp_sales_statistics',
            'amp_inventory_status',
            'amp_expiring_products'
        );

        foreach ($cache_keys as $key) {
            wp_cache_delete($key, 'automaten-manager-pro');
            delete_transient($key);
        }

        // Rewrite Rules flush
        flush_rewrite_rules();
    }

    /**
     * Deaktivierungs-Optionen setzen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function set_deactivation_options(): void
    {
        // Deaktivierungsdatum speichern
        update_option('amp_deactivated_at', current_time('mysql'));
        
        // Plugin-Status
        update_option('amp_plugin_active', false);
        
        // Deaktivierungsgrund (kann später durch Admin-Interface gesetzt werden)
        if (!get_option('amp_deactivation_reason')) {
            add_option('amp_deactivation_reason', 'user_deactivated');
        }

        // Feedback-Anfrage für später
        update_option('amp_deactivation_feedback_requested', true);
        
        // Aktivierungs-Redirect zurücksetzen
        delete_option('amp_activation_redirect');
    }

    /**
     * .htaccess Regeln entfernen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function remove_htaccess_rules(): void
    {
        if (!got_mod_rewrite()) {
            return;
        }

        $htaccess_file = ABSPATH . '.htaccess';
        
        if (!is_writable($htaccess_file)) {
            return;
        }

        $content = file_get_contents($htaccess_file);
        
        // Plugin-Regeln entfernen
        $pattern = '/# AutomatenManager Pro - Security Rules.*?# End AutomatenManager Pro Rules\s*/s';
        $new_content = preg_replace($pattern, '', $content);
        
        if ($new_content !== $content) {
            file_put_contents($htaccess_file, $new_content);
        }
    }

    /**
     * Deaktivierungs-Log erstellen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function log_deactivation(): void
    {
        $log_data = array(
            'timestamp' => current_time('mysql'),
            'plugin_version' => defined('AMP_VERSION') ? AMP_VERSION : 'unknown',
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'user_id' => get_current_user_id(),
            'site_url' => get_site_url(),
            'reason' => get_option('amp_deactivation_reason', 'unknown'),
            'active_sessions_closed' => get_option('amp_deactivation_sessions_closed', 0)
        );

        // Log in WordPress-Option speichern
        update_option('amp_deactivation_log', $log_data);

        // Auch in Log-Datei speichern (falls Verzeichnis noch existiert)
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/automaten-manager-pro/logs/deactivation.log';
        
        if (is_dir(dirname($log_file))) {
            $log_entry = sprintf(
                "[%s] Plugin deaktiviert - Version: %s, User: %d, Grund: %s\n",
                current_time('Y-m-d H:i:s'),
                $log_data['plugin_version'],
                get_current_user_id(),
                $log_data['reason']
            );

            file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Admin-Benachrichtigung für Deaktivierung hinzufügen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function add_deactivation_notice(): void
    {
        $message = __('AutomatenManager Pro wurde deaktiviert. Ihre Daten bleiben erhalten.', 'automaten-manager-pro');
        
        // Zusätzliche Infos wenn Sessions geschlossen wurden
        $sessions_message = get_option('amp_deactivation_sessions_closed');
        if ($sessions_message) {
            $message .= ' ' . $sessions_message;
        }

        set_transient('amp_deactivation_notice', $message, 300); // 5 Minuten
    }

    /**
     * Vollständige Plugin-Deinstallation (VORSICHT!)
     * 
     * Diese Methode löscht ALLE Plugin-Daten und sollte nur bei
     * der echten Deinstallation über uninstall.php aufgerufen werden.
     * 
     * @return void
     * @since 1.0.0
     */
    public static function uninstall(): void
    {
        // Nur bei tatsächlicher Deinstallation ausführen
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }

        // Alle Plugin-Optionen löschen
        self::remove_plugin_options();

        // Benutzerrollen und Capabilities entfernen
        self::remove_user_roles_and_capabilities();

        // Upload-Verzeichnis komplett löschen
        self::remove_upload_directory();

        // Letzte Bereinigungen
        self::final_cleanup();
    }

    /**
     * Alle Plugin-Optionen entfernen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function remove_plugin_options(): void
    {
        global $wpdb;

        // Alle Optionen mit amp_ Prefix löschen
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE 'amp_%'"
        );

        // Site-Optionen auch löschen (bei Multisite)
        if (is_multisite()) {
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta} 
                 WHERE meta_key LIKE 'amp_%'"
            );
        }
    }

    /**
     * Benutzerrollen und Capabilities entfernen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function remove_user_roles_and_capabilities(): void
    {
        // Plugin-spezifische Rollen löschen
        $custom_roles = array(
            'amp_warehouse_manager',
            'amp_cashier',
            'amp_reporter'
        );

        foreach ($custom_roles as $role) {
            remove_role($role);
        }

        // Capabilities von bestehenden Rollen entfernen
        $capabilities = array(
            'amp_manage_products',
            'amp_use_scanner',
            'amp_process_sales',
            'amp_manage_inventory',
            'amp_view_reports',
            'amp_manage_settings',
            'amp_export_data',
            'amp_dispose_products',
            'amp_manage_categories'
        );

        $roles = array('administrator', 'editor', 'author');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }

    /**
     * Upload-Verzeichnis komplett entfernen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function remove_upload_directory(): void
    {
        $upload_dir = wp_upload_dir();
        $plugin_upload_dir = $upload_dir['basedir'] . '/automaten-manager-pro';

        if (is_dir($plugin_upload_dir)) {
            self::delete_directory_recursive($plugin_upload_dir);
        }
    }

    /**
     * Verzeichnis rekursiv löschen
     * 
     * @param string $dir Verzeichnispfad
     * @return bool
     * @since 1.0.0
     */
    private static function delete_directory_recursive(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        return rmdir($dir);
    }

    /**
     * Finale Bereinigungen
     * 
     * @return void
     * @since 1.0.0
     */
    private static function final_cleanup(): void
    {
        // Autoloader deregistrieren falls noch aktiv
        if (class_exists('AMP_Autoloader')) {
            AMP_Autoloader::unregister();
        }

        // WordPress-Features zurücksetzen
        remove_theme_support('post-thumbnails');

        // Image Sizes entfernen
        remove_image_size('amp_product_thumbnail');
        remove_image_size('amp_product_medium');
        remove_image_size('amp_product_large');

        // Final cache flush
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        flush_rewrite_rules();
    }

    /**
     * Debug-Informationen für Deaktivierung abrufen
     * 
     * @return array
     * @since 1.0.0
     */
    public static function get_deactivation_info(): array
    {
        return array(
            'deactivated_at' => get_option('amp_deactivated_at'),
            'reason' => get_option('amp_deactivation_reason'),
            'sessions_closed' => get_option('amp_deactivation_sessions_closed'),
            'plugin_version' => defined('AMP_VERSION') ? AMP_VERSION : 'unknown',
            'wp_version' => get_bloginfo('version'),
            'user_id' => get_current_user_id()
        );
    }
}

// Ende der Datei - Kein schließendes PHP-Tag notwendig