<?php
/**
 * PSR-4 Autoloader for AutomatenManager Pro
 * 
 * Diese Klasse lädt automatisch alle Plugin-Klassen basierend auf dem PSR-4 Standard.
 * WICHTIG: Diese Datei hat KEINEN Namespace, da sie vor der Namespace-Struktur geladen wird.
 *
 * @package     AutomatenManagerPro
 * @subpackage  Core
 * @version     1.0.0
 * @since       1.0.0
 */

// Sicherheitscheck - Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PSR-4 Autoloader Klasse
 * 
 * Lädt automatisch Plugin-Klassen basierend auf Namespace und Dateistruktur.
 * Folgt dem PSR-4 Standard für Autoloading.
 * 
 * @since 1.0.0
 */
class AMP_Autoloader
{
    /**
     * Array der registrierten Namespaces
     * 
     * @var array
     * @since 1.0.0
     */
    private static $namespaces = array();

    /**
     * Singleton Instance
     * 
     * @var AMP_Autoloader
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Debug Mode für Entwicklung
     * 
     * @var bool
     * @since 1.0.0
     */
    private static $debug_mode = false;

    /**
     * Private Constructor für Singleton Pattern
     * 
     * @since 1.0.0
     */
    private function __construct()
    {
        // Debug Mode aktivieren wenn WP_DEBUG an ist
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::$debug_mode = true;
        }
    }

    /**
     * Singleton Instance abrufen
     * 
     * @return AMP_Autoloader
     * @since 1.0.0
     */
    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Autoloader registrieren
     * 
     * Registriert den Autoloader bei PHP's spl_autoload_register
     * und konfiguriert alle Plugin-Namespaces.
     * 
     * @return bool True bei erfolgreicher Registrierung
     * @since 1.0.0
     */
    public static function register()
    {
        $instance = self::get_instance();
        
        // Autoloader-Funktion registrieren
        $registered = spl_autoload_register(array($instance, 'load_class'));
        
        if ($registered) {
            // Plugin-Namespaces konfigurieren
            $instance->register_namespaces();
            
            if (self::$debug_mode) {
                error_log('AMP Autoloader: Erfolgreich registriert');
            }
            
            return true;
        }
        
        if (self::$debug_mode) {
            error_log('AMP Autoloader: Registrierung fehlgeschlagen');
        }
        
        return false;
    }

    /**
     * Autoloader deregistrieren
     * 
     * @return bool True bei erfolgreichem Deregistrieren
     * @since 1.0.0
     */
    public static function unregister()
    {
        $instance = self::get_instance();
        
        $unregistered = spl_autoload_unregister(array($instance, 'load_class'));
        
        if ($unregistered && self::$debug_mode) {
            error_log('AMP Autoloader: Erfolgreich deregistriert');
        }
        
        return $unregistered;
    }

    /**
     * Plugin-Namespaces registrieren
     * 
     * KRITISCH: Diese Mappings müssen exakt der Verzeichnisstruktur entsprechen!
     * 
     * @since 1.0.0
     */
    private function register_namespaces()
    {
        // Base Plugin Directory
        $base_dir = defined('AMP_PLUGIN_DIR') ? AMP_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));

        // Core Namespaces (includes/ Verzeichnis)
        $this->add_namespace('AutomatenManagerPro\\Core\\', $base_dir . 'includes/');

        // Database Namespace (database/ Verzeichnis) - WICHTIG: Eigenes Verzeichnis!
        $this->add_namespace('AutomatenManagerPro\\Database\\', $base_dir . 'database/');

        // Admin Namespace (admin/ Verzeichnis)
        $this->add_namespace('AutomatenManagerPro\\Admin\\', $base_dir . 'admin/');

        // Module Namespaces (modules/ Unterverzeichnisse)
        $this->add_namespace('AutomatenManagerPro\\Products\\', $base_dir . 'modules/products/');
        $this->add_namespace('AutomatenManagerPro\\Categories\\', $base_dir . 'modules/categories/');
        $this->add_namespace('AutomatenManagerPro\\Scanner\\', $base_dir . 'modules/scanner/');
        $this->add_namespace('AutomatenManagerPro\\Sales\\', $base_dir . 'modules/sales/');
        $this->add_namespace('AutomatenManagerPro\\Inventory\\', $base_dir . 'modules/inventory/');
        $this->add_namespace('AutomatenManagerPro\\Waste\\', $base_dir . 'modules/waste/');
        $this->add_namespace('AutomatenManagerPro\\Reports\\', $base_dir . 'modules/reports/');
        $this->add_namespace('AutomatenManagerPro\\Email\\', $base_dir . 'modules/email/');

        if (self::$debug_mode) {
            error_log('AMP Autoloader: ' . count(self::$namespaces) . ' Namespaces registriert');
            error_log('AMP Autoloader Namespaces: ' . print_r(array_keys(self::$namespaces), true));
        }
    }

    /**
     * Namespace zu Verzeichnis hinzufügen
     * 
     * @param string $namespace Der Namespace (mit abschließendem \\)
     * @param string $directory Das Verzeichnis (mit abschließendem /)
     * @since 1.0.0
     */
    private function add_namespace($namespace, $directory)
    {
        // Namespace normalisieren (mit abschließendem Backslash)
        $namespace = rtrim($namespace, '\\') . '\\';
        
        // Directory normalisieren (mit abschließendem Slash)
        $directory = rtrim($directory, '/') . '/';
        
        // Prüfen ob Verzeichnis existiert
        if (!is_dir($directory)) {
            if (self::$debug_mode) {
                error_log("AMP Autoloader Warning: Verzeichnis nicht gefunden: {$directory}");
            }
        }
        
        // Namespace registrieren
        self::$namespaces[$namespace] = $directory;
        
        if (self::$debug_mode) {
            error_log("AMP Autoloader: Namespace '{$namespace}' -> '{$directory}' registriert");
        }
    }

    /**
     * Klasse automatisch laden
     * 
     * Diese Methode wird von PHP's Autoloader aufgerufen wenn eine Klasse
     * noch nicht geladen ist.
     * 
     * @param string $class_name Vollständiger Klassenname mit Namespace
     * @return bool True wenn Klasse erfolgreich geladen wurde
     * @since 1.0.0
     */
    public function load_class($class_name)
    {
        if (self::$debug_mode) {
            error_log("AMP Autoloader: Versuche Klasse zu laden: {$class_name}");
        }

        // Prüfen ob Klasse zu unserem Plugin gehört
        if (strpos($class_name, 'AutomatenManagerPro\\') !== 0) {
            // Nicht unser Namespace - ignorieren
            return false;
        }

        // Passenden Namespace finden
        $namespace_found = false;
        $file_path = '';

        foreach (self::$namespaces as $namespace => $directory) {
            if (strpos($class_name, $namespace) === 0) {
                // Namespace gefunden
                $namespace_found = true;
                
                // Relativen Klassennamen extrahieren (ohne Namespace)
                $relative_class = substr($class_name, strlen($namespace));
                
                // Dateinamen generieren
                $file_name = $this->class_name_to_file_name($relative_class);
                
                // Vollständigen Dateipfad erstellen
                $file_path = $directory . $file_name;
                
                if (self::$debug_mode) {
                    error_log("AMP Autoloader: Namespace '{$namespace}' gefunden");
                    error_log("AMP Autoloader: Relative Klasse: {$relative_class}");
                    error_log("AMP Autoloader: Dateiname: {$file_name}");
                    error_log("AMP Autoloader: Vollständiger Pfad: {$file_path}");
                }
                
                break;
            }
        }

        // Wenn kein passender Namespace gefunden wurde
        if (!$namespace_found) {
            if (self::$debug_mode) {
                error_log("AMP Autoloader Error: Kein passender Namespace für '{$class_name}' gefunden");
                error_log("AMP Autoloader: Verfügbare Namespaces: " . implode(', ', array_keys(self::$namespaces)));
            }
            return false;
        }

        // Datei laden wenn sie existiert
        if (file_exists($file_path)) {
            require_once $file_path;
            
            if (self::$debug_mode) {
                error_log("AMP Autoloader: Datei erfolgreich geladen: {$file_path}");
            }
            
            // Prüfen ob Klasse jetzt existiert
            if (class_exists($class_name)) {
                if (self::$debug_mode) {
                    error_log("AMP Autoloader: Klasse '{$class_name}' erfolgreich geladen");
                }
                return true;
            } else {
                if (self::$debug_mode) {
                    error_log("AMP Autoloader Error: Klasse '{$class_name}' existiert nach dem Laden nicht");
                }
                return false;
            }
        } else {
            if (self::$debug_mode) {
                error_log("AMP Autoloader Error: Datei nicht gefunden: {$file_path}");
            }
            return false;
        }
    }

    /**
     * Klassenname zu Dateiname konvertieren
     * 
     * Konvertiert einen Klassennamen nach WordPress/Plugin Konventionen:
     * AMP_Database_Manager -> class-database-manager.php
     * 
     * @param string $class_name Der Klassenname (ohne Namespace)
     * @return string Der Dateiname
     * @since 1.0.0
     */
    private function class_name_to_file_name($class_name)
    {
        // Entferne AMP_ Prefix falls vorhanden
        if (strpos($class_name, 'AMP_') === 0) {
            $class_name = substr($class_name, 4);
        }

        // Konvertiere zu lowercase und ersetze Underscores mit Bindestrichen
        $file_name = strtolower(str_replace('_', '-', $class_name));
        
        // Füge class- Prefix und .php Extension hinzu
        $file_name = 'class-' . $file_name . '.php';

        if (self::$debug_mode) {
            error_log("AMP Autoloader: Klassenname-Konvertierung: '{$class_name}' -> '{$file_name}'");
        }

        return $file_name;
    }

    /**
     * Debug-Information abrufen
     * 
     * Gibt alle registrierten Namespaces zurück (für Debugging)
     * 
     * @return array Array der registrierten Namespaces
     * @since 1.0.0
     */
    public static function get_debug_info()
    {
        return array(
            'namespaces' => self::$namespaces,
            'debug_mode' => self::$debug_mode,
            'instance_created' => (self::$instance !== null)
        );
    }

    /**
     * Namespace-Validierung für Entwicklung
     * 
     * Prüft ob alle Namespace-Verzeichnisse existieren
     * 
     * @return array Array mit Validierungsergebnissen
     * @since 1.0.0
     */
    public static function validate_namespaces()
    {
        $results = array();
        
        foreach (self::$namespaces as $namespace => $directory) {
            $results[$namespace] = array(
                'directory' => $directory,
                'exists' => is_dir($directory),
                'readable' => is_readable($directory)
            );
        }
        
        return $results;
    }

    /**
     * Manuelle Klassen-Suche
     * 
     * Hilfsfunktion für Debugging - findet alle PHP Klassen-Dateien
     * 
     * @param string $directory Zu durchsuchendes Verzeichnis
     * @return array Array mit gefundenen Dateien
     * @since 1.0.0
     */
    public static function find_class_files($directory = null)
    {
        if ($directory === null) {
            $directory = defined('AMP_PLUGIN_DIR') ? AMP_PLUGIN_DIR : plugin_dir_path(dirname(__FILE__));
        }

        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 
                $file->getExtension() === 'php' && 
                strpos($file->getFilename(), 'class-') === 0) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}

// Autoloader sofort registrieren wenn Datei geladen wird
if (!class_exists('AMP_Autoloader')) {
    // Fehler - Klasse sollte definiert sein
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('AMP Autoloader Error: Klasse konnte nicht definiert werden');
    }
} else {
    // Autoloader registrieren
    $autoloader_registered = AMP_Autoloader::register();
    
    if (!$autoloader_registered && defined('WP_DEBUG') && WP_DEBUG) {
        error_log('AMP Autoloader Error: Registrierung fehlgeschlagen');
    }
}

// Hook für Plugin-Deaktivierung
if (function_exists('add_action')) {
    add_action('shutdown', function() {
        // Cleanup bei Plugin-Shutdown
        if (class_exists('AMP_Autoloader')) {
            // Autoloader deregistrieren ist normalerweise nicht nötig,
            // aber für saubere Deaktivierung enthalten
        }
    });
}

// Ende der Datei - Kein schließendes PHP-Tag notwendig