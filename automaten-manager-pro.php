<?php
/**
 * Plugin Name: AutomatenManager Pro
 * Plugin URI: https://beispiel.de/automaten-manager-pro
 * Description: Professionelle Lagerverwaltung mit integriertem Barcode-Scanner für WordPress Backend. Vollständiges System für Produktverwaltung, session-basierte Verkäufe, automatische E-Mail-Berichte und Haltbarkeitsdatum-Überwachung.
 * Version: 1.0.0
 * Author: Ihr Firmenname
 * Author URI: https://beispiel.de
 * Text Domain: automaten-manager-pro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package     AutomatenManagerPro
 * @version     1.0.0
 * @since       1.0.0
 */

// Sicherheitscheck - Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Plugin-Konstanten definieren
define('AMP_VERSION', '1.0.0');
define('AMP_PLUGIN_FILE', __FILE__);
define('AMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AMP_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('AMP_MIN_PHP_VERSION', '8.0');
define('AMP_MIN_WP_VERSION', '6.0');

/**
 * PHP Version Check
 * Überprüft ob die minimale PHP Version erfüllt ist
 * 
 * @since 1.0.0
 */
function amp_check_php_version() {
    if (version_compare(PHP_VERSION, AMP_MIN_PHP_VERSION, '<')) {
        add_action('admin_notices', 'amp_php_version_notice');
        return false;
    }
    return true;
}

/**
 * WordPress Version Check
 * Überprüft ob die minimale WordPress Version erfüllt ist
 * 
 * @since 1.0.0
 */
function amp_check_wp_version() {
    global $wp_version;
    if (version_compare($wp_version, AMP_MIN_WP_VERSION, '<')) {
        add_action('admin_notices', 'amp_wp_version_notice');
        return false;
    }
    return true;
}

/**
 * PHP Version Warning Notice
 * Zeigt Admin-Warnung bei unzureichender PHP Version
 * 
 * @since 1.0.0
 */
function amp_php_version_notice() {
    $message = sprintf(
        '<div class="notice notice-error"><p><strong>AutomatenManager Pro:</strong> Benötigt PHP %s oder höher. Ihre Version: %s</p></div>',
        AMP_MIN_PHP_VERSION,
        PHP_VERSION
    );
    echo wp_kses_post($message);
}

/**
 * WordPress Version Warning Notice
 * Zeigt Admin-Warnung bei unzureichender WordPress Version
 * 
 * @since 1.0.0
 */
function amp_wp_version_notice() {
    global $wp_version;
    $message = sprintf(
        '<div class="notice notice-error"><p><strong>AutomatenManager Pro:</strong> Benötigt WordPress %s oder höher. Ihre Version: %s</p></div>',
        AMP_MIN_WP_VERSION,
        $wp_version
    );
    echo wp_kses_post($message);
}

/**
 * Plugin laden nur bei erfüllten Systemanforderungen
 * 
 * @since 1.0.0
 */
function amp_load_plugin() {
    // System-Checks durchführen
    if (!amp_check_php_version() || !amp_check_wp_version()) {
        return;
    }

    // Autoloader einbinden
    require_once AMP_PLUGIN_DIR . 'includes/class-autoloader.php';

    // Plugin-Haupt-Klasse laden
    if (class_exists('AutomatenManagerPro\\Core\\AMP_Plugin')) {
        $plugin = new AutomatenManagerPro\Core\AMP_Plugin();
        $plugin->init();
    }
}

/**
 * Plugin-Aktivierung
 * Wird beim Aktivieren des Plugins ausgeführt
 * 
 * @since 1.0.0
 */
function amp_activate_plugin() {
    // System-Checks vor Aktivierung
    if (!amp_check_php_version() || !amp_check_wp_version()) {
        wp_die(
            '<h1>Aktivierung fehlgeschlagen</h1>' .
            '<p>AutomatenManager Pro benötigt PHP ' . AMP_MIN_PHP_VERSION . '+ und WordPress ' . AMP_MIN_WP_VERSION . '+</p>' .
            '<p><a href="' . admin_url('plugins.php') . '">&larr; Zurück zu den Plugins</a></p>',
            'Systemanforderungen nicht erfüllt',
            array('back_link' => true)
        );
    }

    // Autoloader einbinden
    require_once AMP_PLUGIN_DIR . 'includes/class-autoloader.php';

    // Aktivator laden und ausführen
    if (class_exists('AutomatenManagerPro\\Core\\AMP_Activator')) {
        AutomatenManagerPro\Core\AMP_Activator::activate();
    }

    // Umleitung zur Plugin-Seite nach Aktivierung setzen
    add_option('amp_activation_redirect', true);
}

/**
 * Plugin-Deaktivierung
 * Wird beim Deaktivieren des Plugins ausgeführt
 * 
 * @since 1.0.0
 */
function amp_deactivate_plugin() {
    // Autoloader einbinden
    require_once AMP_PLUGIN_DIR . 'includes/class-autoloader.php';

    // Deactivator laden und ausführen
    if (class_exists('AutomatenManagerPro\\Core\\AMP_Deactivator')) {
        AutomatenManagerPro\Core\AMP_Deactivator::deactivate();
    }
}

/**
 * Plugin-Deinstallation
 * Verweist auf separate uninstall.php
 * 
 * @since 1.0.0
 */
// Deinstallation wird über uninstall.php gehandhabt

/**
 * WordPress Hooks registrieren
 */
register_activation_hook(__FILE__, 'amp_activate_plugin');
register_deactivation_hook(__FILE__, 'amp_deactivate_plugin');

/**
 * Plugin nach WordPress Initialisierung laden
 * Priorität 10 - Standard-Priorität für Plugin-Loading
 */
add_action('plugins_loaded', 'amp_load_plugin', 10);

/**
 * Textdomain für Übersetzungen laden
 * Wird über WordPress 'init' Hook geladen
 * 
 * @since 1.0.0
 */
function amp_load_textdomain() {
    load_plugin_textdomain(
        'automaten-manager-pro',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('init', 'amp_load_textdomain');

/**
 * Plugin-Links in der Plugin-Liste erweitern
 * Fügt "Einstellungen" Link hinzu
 * 
 * @param array $links Bestehende Plugin-Links
 * @return array Erweiterte Plugin-Links
 * @since 1.0.0
 */
function amp_plugin_action_links($links) {
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        admin_url('admin.php?page=automaten-manager-pro'),
        __('Dashboard', 'automaten-manager-pro')
    );
    
    $scanner_link = sprintf(
        '<a href="%s" style="color: #0073aa; font-weight: bold;">%s</a>',
        admin_url('admin.php?page=amp-scanner'),
        __('Scanner', 'automaten-manager-pro')
    );
    
    // Links am Anfang hinzufügen
    array_unshift($links, $settings_link, $scanner_link);
    
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'amp_plugin_action_links');

/**
 * Meta-Links in der Plugin-Liste erweitern
 * Fügt Support/Dokumentation Links hinzu
 * 
 * @param array $links Bestehende Meta-Links
 * @param string $file Plugin-Datei
 * @return array Erweiterte Meta-Links
 * @since 1.0.0
 */
function amp_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(__FILE__)) {
        $meta_links = array(
            'docs' => '<a href="https://beispiel.de/docs" target="_blank">' . __('Dokumentation', 'automaten-manager-pro') . '</a>',
            'support' => '<a href="https://beispiel.de/support" target="_blank">' . __('Support', 'automaten-manager-pro') . '</a>',
        );
        
        $links = array_merge($links, $meta_links);
    }
    
    return $links;
}
add_filter('plugin_row_meta', 'amp_plugin_row_meta', 10, 2);

/**
 * WordPress Admin Dashboard Widget
 * Zeigt Plugin-Schnellinfo im Dashboard
 * 
 * @since 1.0.0
 */
function amp_dashboard_widget() {
    if (current_user_can('manage_options')) {
        add_meta_box(
            'amp_dashboard_widget',
            'AutomatenManager Pro',
            'amp_dashboard_widget_content',
            'dashboard',
            'side',
            'high'
        );
    }
}
add_action('wp_dashboard_setup', 'amp_dashboard_widget');

/**
 * Dashboard Widget Inhalt
 * 
 * @since 1.0.0
 */
function amp_dashboard_widget_content() {
    echo '<div style="padding: 10px;">';
    echo '<p><strong>' . __('Schnellzugriff:', 'automaten-manager-pro') . '</strong></p>';
    echo '<p><a href="' . admin_url('admin.php?page=amp-scanner') . '" class="button button-primary" style="margin-right: 10px;">' . __('Scanner öffnen', 'automaten-manager-pro') . '</a></p>';
    echo '<p><a href="' . admin_url('admin.php?page=automaten-manager-pro') . '">' . __('Dashboard', 'automaten-manager-pro') . '</a> | ';
    echo '<a href="' . admin_url('admin.php?page=amp-products') . '">' . __('Produkte', 'automaten-manager-pro') . '</a> | ';
    echo '<a href="' . admin_url('admin.php?page=amp-reports') . '">' . __('Berichte', 'automaten-manager-pro') . '</a></p>';
    echo '</div>';
}

/**
 * Debug Information (nur für Entwicklung)
 * Kann später entfernt werden
 * 
 * @since 1.0.0
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    /**
     * Debug-Info in Admin Footer
     */
    function amp_debug_info() {
        if (current_user_can('manage_options')) {
            echo '<!-- AutomatenManager Pro Debug: Version ' . AMP_VERSION . ' | PHP ' . PHP_VERSION . ' | WP ' . get_bloginfo('version') . ' -->';
        }
    }
    add_action('admin_footer', 'amp_debug_info');
}

/**
 * Plugin fertig initialisiert
 * Letzter Hook für Post-Loading Aktionen
 * 
 * @since 1.0.0
 */
do_action('amp_plugin_loaded');

// Ende der Datei - Kein schließendes PHP-Tag notwendig