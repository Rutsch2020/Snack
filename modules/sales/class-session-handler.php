<?php
/**
 * Session Handler für AutomatenManager Pro
 * 
 * Erweiterte Session-Management Funktionen für robuste Verkaufs-Sessions
 * mit Multi-User Support, Session-Recovery und Advanced Cart-Operations
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Sales
 * @version     1.0.0
 * @since       1.0.0
 * @author      AutomatenManager Pro Team
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Sales;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Products\AMP_Products_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Session Handler für erweiterte Verkaufs-Session-Funktionen
 * 
 * Verwaltet:
 * - Multi-User Sessions (gleichzeitige Verkäufe)
 * - Session Recovery (Wiederherstellung bei Fehlern)
 * - Advanced Cart Operations (Merge, Split, Transfer)
 * - Session Locks (Prevents concurrent access)
 * - Automatic Session Cleanup
 * - Session Analytics und Monitoring
 * 
 * @since 1.0.0
 */
class AMP_Session_Handler
{
    /**
     * Database Manager Instance
     */
    private AMP_Database_Manager $database;
    
    /**
     * Products Manager Instance
     */
    private AMP_Products_Manager $products;
    
    /**
     * Session Configuration
     */
    private array $session_config;
    
    /**
     * Active Sessions Cache
     */
    private array $active_sessions_cache = [];
    
    /**
     * Session Lock Timeout (Sekunden)
     */
    private const SESSION_LOCK_TIMEOUT = 300; // 5 Minuten
    
    /**
     * Session Cleanup Interval (Sekunden)
     */
    private const CLEANUP_INTERVAL = 3600; // 1 Stunde
    
    /**
     * Maximum Session Duration (Sekunden)
     */
    private const MAX_SESSION_DURATION = 43200; // 12 Stunden
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->products = new AMP_Products_Manager();
        
        $this->session_config = [
            'max_concurrent_sessions' => get_option('amp_max_concurrent_sessions', 10),
            'session_timeout' => get_option('amp_session_timeout', 1800), // 30 Minuten
            'auto_save_interval' => get_option('amp_auto_save_interval', 30), // 30 Sekunden
            'enable_recovery' => get_option('amp_enable_session_recovery', true)
        ];
        
        $this->init_hooks();
    }
    
    /**
     * WordPress Hooks initialisieren
     * 
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        // Session Cleanup Cron
        add_action('amp_session_cleanup', [$this, 'cleanup_expired_sessions']);
        
        // Auto-Save Sessions
        add_action('amp_auto_save_sessions', [$this, 'auto_save_active_sessions']);
        
        // AJAX Handlers
        add_action('wp_ajax_amp_recover_session', [$this, 'ajax_recover_session']);
        add_action('wp_ajax_amp_transfer_session', [$this, 'ajax_transfer_session']);
        add_action('wp_ajax_amp_merge_sessions', [$this, 'ajax_merge_sessions']);
        add_action('wp_ajax_amp_split_session', [$this, 'ajax_split_session']);
        add_action('wp_ajax_amp_get_session_status', [$this, 'ajax_get_session_status']);
        
        // Schedule Cleanup wenn nicht existiert
        if (!wp_next_scheduled('amp_session_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'amp_session_cleanup');
        }
        
        // Schedule Auto-Save wenn nicht existiert
        if (!wp_next_scheduled('amp_auto_save_sessions')) {
            wp_schedule_event(time(), 'every_minute', 'amp_auto_save_sessions');
        }
    }
    
    /**
     * Neue Session erstellen mit erweiterten Features
     * 
     * @param int $user_id User ID
     * @param array $options Session Optionen
     * @return array Session Daten oder WP_Error
     * @since 1.0.0
     */
    public function create_session(int $user_id, array $options = []): array
    {
        try {
            // Prüfe maximale concurrent Sessions
            if ($this->get_active_session_count() >= $this->session_config['max_concurrent_sessions']) {
                return [
                    'success' => false,
                    'error' => 'Maximale Anzahl gleichzeitiger Sessions erreicht.',
                    'code' => 'MAX_SESSIONS_EXCEEDED'
                ];
            }
            
            // Session Lock für User prüfen
            if ($this->has_active_session_lock($user_id)) {
                return [
                    'success' => false,
                    'error' => 'Benutzer hat bereits eine aktive Session.',
                    'code' => 'USER_LOCKED'
                ];
            }
            
            $session_data = [
                'user_id' => $user_id,
                'session_start' => current_time('mysql'),
                'session_status' => 'active',
                'session_type' => $options['type'] ?? 'standard',
                'session_device' => $options['device'] ?? $this->detect_device(),
                'session_ip' => $this->get_client_ip(),
                'session_lock' => null,
                'last_activity' => current_time('mysql'),
                'auto_save_data' => '{}',
                'recovery_data' => '{}',
                'session_notes' => $options['notes'] ?? '',
                'created_at' => current_time('mysql')
            ];
            
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $wpdb->insert($table, $session_data);
            
            if ($wpdb->last_error) {
                error_log('AMP Session Creation Error: ' . $wpdb->last_error);
                return [
                    'success' => false,
                    'error' => 'Fehler beim Erstellen der Session.',
                    'code' => 'DATABASE_ERROR'
                ];
            }
            
            $session_id = $wpdb->insert_id;
            
            // Session-Lock setzen
            $this->set_session_lock($session_id, $user_id);
            
            // Cache aktualisieren
            $this->update_session_cache($session_id, array_merge($session_data, ['id' => $session_id]));
            
            // Session-Start protokollieren
            $this->log_session_activity($session_id, 'session_created', [
                'user_id' => $user_id,
                'device' => $session_data['session_device'],
                'ip' => $session_data['session_ip']
            ]);
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'session_data' => array_merge($session_data, ['id' => $session_id]),
                'message' => 'Session erfolgreich erstellt.'
            ];
            
        } catch (Exception $e) {
            error_log('AMP Session Creation Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Unerwarteter Fehler beim Erstellen der Session.',
                'code' => 'EXCEPTION_ERROR'
            ];
        }
    }
    
    /**
     * Session Recovery - Unterbrochene Session wiederherstellen
     * 
     * @param int $session_id Session ID
     * @param int $user_id User ID
     * @return array Recovery Ergebnis
     * @since 1.0.0
     */
    public function recover_session(int $session_id, int $user_id): array
    {
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d AND user_id = %d",
                $session_id,
                $user_id
            ), ARRAY_A);
            
            if (!$session) {
                return [
                    'success' => false,
                    'error' => 'Session nicht gefunden.',
                    'code' => 'SESSION_NOT_FOUND'
                ];
            }
            
            // Prüfe ob Session wiederherstellbar ist
            if (!$this->is_session_recoverable($session)) {
                return [
                    'success' => false,
                    'error' => 'Session kann nicht wiederhergestellt werden.',
                    'code' => 'NOT_RECOVERABLE'
                ];
            }
            
            // Recovery Daten laden
            $recovery_data = json_decode($session['recovery_data'], true) ?: [];
            $auto_save_data = json_decode($session['auto_save_data'], true) ?: [];
            
            // Session Items wiederherstellen
            $recovered_items = $this->recover_session_items($session_id);
            
            // Session reaktivieren
            $wpdb->update(
                $table,
                [
                    'session_status' => 'active',
                    'last_activity' => current_time('mysql'),
                    'session_lock' => time()
                ],
                ['id' => $session_id]
            );
            
            // Session Lock setzen
            $this->set_session_lock($session_id, $user_id);
            
            // Recovery protokollieren
            $this->log_session_activity($session_id, 'session_recovered', [
                'items_count' => count($recovered_items),
                'recovery_method' => 'manual'
            ]);
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'recovered_items' => $recovered_items,
                'recovery_data' => $recovery_data,
                'auto_save_data' => $auto_save_data,
                'message' => 'Session erfolgreich wiederhergestellt.'
            ];
            
        } catch (Exception $e) {
            error_log('AMP Session Recovery Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler bei der Session-Wiederherstellung.',
                'code' => 'RECOVERY_ERROR'
            ];
        }
    }
    
    /**
     * Sessions zusammenführen (Merge)
     * 
     * @param int $target_session_id Ziel-Session
     * @param int $source_session_id Quell-Session
     * @param int $user_id User ID
     * @return array Merge Ergebnis
     * @since 1.0.0
     */
    public function merge_sessions(int $target_session_id, int $source_session_id, int $user_id): array
    {
        try {
            // Beide Sessions validieren
            $target_session = $this->get_session($target_session_id);
            $source_session = $this->get_session($source_session_id);
            
            if (!$target_session || !$source_session) {
                return [
                    'success' => false,
                    'error' => 'Eine oder beide Sessions nicht gefunden.',
                    'code' => 'SESSIONS_NOT_FOUND'
                ];
            }
            
            // Berechtigung prüfen
            if ($target_session['user_id'] !== $user_id || $source_session['user_id'] !== $user_id) {
                return [
                    'success' => false,
                    'error' => 'Keine Berechtigung für Session-Merge.',
                    'code' => 'PERMISSION_DENIED'
                ];
            }
            
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                // Source Session Items zu Target Session verschieben
                $items_table = $wpdb->prefix . 'amp_sales_items';
                
                $moved_items = $wpdb->update(
                    $items_table,
                    ['session_id' => $target_session_id],
                    ['session_id' => $source_session_id]
                );
                
                // Source Session als merged markieren
                $sessions_table = $wpdb->prefix . 'amp_sales_sessions';
                $wpdb->update(
                    $sessions_table,
                    [
                        'session_status' => 'merged',
                        'session_end' => current_time('mysql'),
                        'merged_into' => $target_session_id
                    ],
                    ['id' => $source_session_id]
                );
                
                // Target Session Totals neu berechnen
                $this->recalculate_session_totals($target_session_id);
                
                $wpdb->query('COMMIT');
                
                // Merge protokollieren
                $this->log_session_activity($target_session_id, 'session_merged', [
                    'source_session_id' => $source_session_id,
                    'moved_items' => $moved_items
                ]);
                
                return [
                    'success' => true,
                    'target_session_id' => $target_session_id,
                    'moved_items' => $moved_items,
                    'message' => 'Sessions erfolgreich zusammengeführt.'
                ];
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('AMP Session Merge Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Zusammenführen der Sessions.',
                'code' => 'MERGE_ERROR'
            ];
        }
    }
    
    /**
     * Session aufteilen (Split)
     * 
     * @param int $session_id Session ID
     * @param array $items_to_split Items die abgetrennt werden sollen
     * @param int $user_id User ID
     * @return array Split Ergebnis
     * @since 1.0.0
     */
    public function split_session(int $session_id, array $items_to_split, int $user_id): array
    {
        try {
            $session = $this->get_session($session_id);
            
            if (!$session || $session['user_id'] !== $user_id) {
                return [
                    'success' => false,
                    'error' => 'Session nicht gefunden oder keine Berechtigung.',
                    'code' => 'SESSION_ACCESS_DENIED'
                ];
            }
            
            if (empty($items_to_split)) {
                return [
                    'success' => false,
                    'error' => 'Keine Items zum Aufteilen angegeben.',
                    'code' => 'NO_ITEMS_SPECIFIED'
                ];
            }
            
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                // Neue Session für Split erstellen
                $new_session_result = $this->create_session($user_id, [
                    'type' => 'split',
                    'notes' => 'Split von Session #' . $session_id
                ]);
                
                if (!$new_session_result['success']) {
                    throw new Exception('Neue Session konnte nicht erstellt werden: ' . $new_session_result['error']);
                }
                
                $new_session_id = $new_session_result['session_id'];
                
                // Items verschieben
                $items_table = $wpdb->prefix . 'amp_sales_items';
                $moved_items = 0;
                
                foreach ($items_to_split as $item_id) {
                    $result = $wpdb->update(
                        $items_table,
                        ['session_id' => $new_session_id],
                        [
                            'id' => intval($item_id),
                            'session_id' => $session_id
                        ]
                    );
                    
                    if ($result) {
                        $moved_items++;
                    }
                }
                
                // Totals für beide Sessions neu berechnen
                $this->recalculate_session_totals($session_id);
                $this->recalculate_session_totals($new_session_id);
                
                $wpdb->query('COMMIT');
                
                // Split protokollieren
                $this->log_session_activity($session_id, 'session_split', [
                    'new_session_id' => $new_session_id,
                    'moved_items' => $moved_items
                ]);
                
                return [
                    'success' => true,
                    'original_session_id' => $session_id,
                    'new_session_id' => $new_session_id,
                    'moved_items' => $moved_items,
                    'message' => 'Session erfolgreich aufgeteilt.'
                ];
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('AMP Session Split Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Aufteilen der Session.',
                'code' => 'SPLIT_ERROR'
            ];
        }
    }
    
    /**
     * Session übertragen (Transfer zwischen Benutzern)
     * 
     * @param int $session_id Session ID
     * @param int $from_user_id Von User ID
     * @param int $to_user_id Zu User ID
     * @return array Transfer Ergebnis
     * @since 1.0.0
     */
    public function transfer_session(int $session_id, int $from_user_id, int $to_user_id): array
    {
        try {
            // Berechtigungen prüfen
            if (!current_user_can('amp_manage_sessions')) {
                return [
                    'success' => false,
                    'error' => 'Keine Berechtigung für Session-Transfer.',
                    'code' => 'PERMISSION_DENIED'
                ];
            }
            
            $session = $this->get_session($session_id);
            
            if (!$session || $session['user_id'] !== $from_user_id) {
                return [
                    'success' => false,
                    'error' => 'Session nicht gefunden oder falscher Benutzer.',
                    'code' => 'SESSION_MISMATCH'
                ];
            }
            
            // Ziel-User prüfen
            if (!get_user_by('id', $to_user_id)) {
                return [
                    'success' => false,
                    'error' => 'Ziel-Benutzer nicht gefunden.',
                    'code' => 'TARGET_USER_NOT_FOUND'
                ];
            }
            
            // Prüfen ob Ziel-User bereits Session hat
            if ($this->has_active_session_lock($to_user_id)) {
                return [
                    'success' => false,
                    'error' => 'Ziel-Benutzer hat bereits eine aktive Session.',
                    'code' => 'TARGET_USER_LOCKED'
                ];
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $result = $wpdb->update(
                $table,
                [
                    'user_id' => $to_user_id,
                    'transferred_from' => $from_user_id,
                    'transferred_at' => current_time('mysql'),
                    'last_activity' => current_time('mysql')
                ],
                ['id' => $session_id]
            );
            
            if ($result === false) {
                return [
                    'success' => false,
                    'error' => 'Fehler beim Session-Transfer.',
                    'code' => 'TRANSFER_ERROR'
                ];
            }
            
            // Session Lock aktualisieren
            $this->clear_session_lock($session_id);
            $this->set_session_lock($session_id, $to_user_id);
            
            // Transfer protokollieren
            $this->log_session_activity($session_id, 'session_transferred', [
                'from_user_id' => $from_user_id,
                'to_user_id' => $to_user_id,
                'transferred_by' => get_current_user_id()
            ]);
            
            return [
                'success' => true,
                'session_id' => $session_id,
                'from_user_id' => $from_user_id,
                'to_user_id' => $to_user_id,
                'message' => 'Session erfolgreich übertragen.'
            ];
            
        } catch (Exception $e) {
            error_log('AMP Session Transfer Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Session-Transfer.',
                'code' => 'TRANSFER_EXCEPTION'
            ];
        }
    }
    
    /**
     * Automatisches Speichern aktiver Sessions
     * 
     * @since 1.0.0
     */
    public function auto_save_active_sessions(): void
    {
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $active_sessions = $wpdb->get_results(
                "SELECT id, user_id FROM {$table} WHERE session_status = 'active'",
                ARRAY_A
            );
            
            foreach ($active_sessions as $session) {
                $this->auto_save_session($session['id']);
            }
            
        } catch (Exception $e) {
            error_log('AMP Auto Save Sessions Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Abgelaufene Sessions bereinigen
     * 
     * @since 1.0.0
     */
    public function cleanup_expired_sessions(): void
    {
        try {
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $expired_time = date('Y-m-d H:i:s', time() - $this->session_config['session_timeout']);
            
            // Inactive Sessions als expired markieren
            $expired_sessions = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM {$table} 
                 WHERE session_status = 'active' 
                 AND last_activity < %s",
                $expired_time
            ), ARRAY_A);
            
            foreach ($expired_sessions as $session) {
                $this->expire_session($session['id']);
            }
            
            // Alte Sessions archivieren (älter als 30 Tage)
            $archive_time = date('Y-m-d H:i:s', time() - (30 * 24 * 3600));
            
            $wpdb->update(
                $table,
                ['session_status' => 'archived'],
                [
                    'session_status' => 'expired',
                    'created_at <' => $archive_time
                ]
            );
            
        } catch (Exception $e) {
            error_log('AMP Session Cleanup Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Session Status und Details abrufen
     * 
     * @param int $session_id Session ID
     * @return array|null Session Details
     * @since 1.0.0
     */
    public function get_session_details(int $session_id): ?array
    {
        try {
            global $wpdb;
            $sessions_table = $wpdb->prefix . 'amp_sales_sessions';
            $items_table = $wpdb->prefix . 'amp_sales_items';
            $products_table = $wpdb->prefix . 'amp_products';
            
            // Session-Grunddaten
            $session = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, u.display_name as user_name 
                 FROM {$sessions_table} s
                 LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
                 WHERE s.id = %d",
                $session_id
            ), ARRAY_A);
            
            if (!$session) {
                return null;
            }
            
            // Session Items
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT si.*, p.name as product_name, p.barcode
                 FROM {$items_table} si
                 LEFT JOIN {$products_table} p ON si.product_id = p.id
                 WHERE si.session_id = %d
                 ORDER BY si.created_at",
                $session_id
            ), ARRAY_A);
            
            // Session Statistiken
            $stats = [
                'total_items' => count($items),
                'unique_products' => count(array_unique(array_column($items, 'product_id'))),
                'session_duration' => $this->calculate_session_duration($session),
                'is_locked' => $this->is_session_locked($session_id),
                'can_recover' => $this->is_session_recoverable($session),
                'last_activity_ago' => human_time_diff(strtotime($session['last_activity']))
            ];
            
            return [
                'session' => $session,
                'items' => $items,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            error_log('AMP Get Session Details Exception: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * AJAX: Session Recovery
     * 
     * @since 1.0.0
     */
    public function ajax_recover_session(): void
    {
        try {
            check_ajax_referer('amp_session_action', 'nonce');
            
            if (!current_user_can('amp_process_sales')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $user_id = get_current_user_id();
            
            $result = $this->recover_session($session_id, $user_id);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Session Transfer
     * 
     * @since 1.0.0
     */
    public function ajax_transfer_session(): void
    {
        try {
            check_ajax_referer('amp_session_action', 'nonce');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $from_user_id = intval($_POST['from_user_id'] ?? 0);
            $to_user_id = intval($_POST['to_user_id'] ?? 0);
            
            $result = $this->transfer_session($session_id, $from_user_id, $to_user_id);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Sessions zusammenführen
     * 
     * @since 1.0.0
     */
    public function ajax_merge_sessions(): void
    {
        try {
            check_ajax_referer('amp_session_action', 'nonce');
            
            if (!current_user_can('amp_process_sales')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $target_session_id = intval($_POST['target_session_id'] ?? 0);
            $source_session_id = intval($_POST['source_session_id'] ?? 0);
            $user_id = get_current_user_id();
            
            $result = $this->merge_sessions($target_session_id, $source_session_id, $user_id);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Session aufteilen
     * 
     * @since 1.0.0
     */
    public function ajax_split_session(): void
    {
        try {
            check_ajax_referer('amp_session_action', 'nonce');
            
            if (!current_user_can('amp_process_sales')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $items_to_split = array_map('intval', $_POST['items_to_split'] ?? []);
            $user_id = get_current_user_id();
            
            $result = $this->split_session($session_id, $items_to_split, $user_id);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Session Status abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_session_status(): void
    {
        try {
            check_ajax_referer('amp_session_action', 'nonce');
            
            $session_id = intval($_POST['session_id'] ?? 0);
            $details = $this->get_session_details($session_id);
            
            if ($details) {
                wp_send_json_success($details);
            } else {
                wp_send_json_error(['message' => 'Session nicht gefunden.']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    // ===============================
    // PRIVATE HELPER METHODS
    // ===============================
    
    /**
     * Session-Lock setzen
     */
    private function set_session_lock(int $session_id, int $user_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $wpdb->update(
            $table,
            ['session_lock' => time()],
            ['id' => $session_id]
        );
    }
    
    /**
     * Session-Lock entfernen
     */
    private function clear_session_lock(int $session_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $wpdb->update(
            $table,
            ['session_lock' => null],
            ['id' => $session_id]
        );
    }
    
    /**
     * Prüfen ob User aktive Session-Lock hat
     */
    private function has_active_session_lock(int $user_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE user_id = %d 
             AND session_status = 'active' 
             AND session_lock IS NOT NULL 
             AND session_lock > %d",
            $user_id,
            time() - self::SESSION_LOCK_TIMEOUT
        ));
        
        return intval($count) > 0;
    }
    
    /**
     * Anzahl aktiver Sessions abrufen
     */
    private function get_active_session_count(): int
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE session_status = 'active'"
        );
        
        return intval($count);
    }
    
    /**
     * Session abrufen
     */
    private function get_session(int $session_id): ?array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $session_id
        ), ARRAY_A);
    }
    
    /**
     * Prüfen ob Session wiederherstellbar ist
     */
    private function is_session_recoverable(array $session): bool
    {
        // Session muss in den letzten 24 Stunden aktiv gewesen sein
        $max_recovery_time = time() - (24 * 3600);
        $last_activity = strtotime($session['last_activity']);
        
        return $last_activity > $max_recovery_time && 
               in_array($session['session_status'], ['interrupted', 'expired', 'error']);
    }
    
    /**
     * Session Items für Recovery laden
     */
    private function recover_session_items(int $session_id): array
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_items';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE session_id = %d",
            $session_id
        ), ARRAY_A);
    }
    
    /**
     * Session Totals neu berechnen
     */
    private function recalculate_session_totals(int $session_id): void
    {
        global $wpdb;
        $items_table = $wpdb->prefix . 'amp_sales_items';
        $sessions_table = $wpdb->prefix . 'amp_sales_sessions';
        
        $totals = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_items,
                SUM(line_total_net) as total_net,
                SUM(line_total_vat) as total_vat,
                SUM(line_total_gross) as total_gross
             FROM {$items_table} 
             WHERE session_id = %d",
            $session_id
        ), ARRAY_A);
        
        $wpdb->update(
            $sessions_table,
            [
                'total_items' => $totals['total_items'] ?: 0,
                'total_net' => $totals['total_net'] ?: 0.00,
                'total_vat' => $totals['total_vat'] ?: 0.00,
                'total_gross' => $totals['total_gross'] ?: 0.00
            ],
            ['id' => $session_id]
        );
    }
    
    /**
     * Session als abgelaufen markieren
     */
    private function expire_session(int $session_id): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $wpdb->update(
            $table,
            [
                'session_status' => 'expired',
                'session_end' => current_time('mysql')
            ],
            ['id' => $session_id]
        );
        
        $this->log_session_activity($session_id, 'session_expired', [
            'expired_by' => 'system',
            'reason' => 'timeout'
        ]);
    }
    
    /**
     * Auto-Save für einzelne Session
     */
    private function auto_save_session(int $session_id): void
    {
        try {
            $session_details = $this->get_session_details($session_id);
            
            if (!$session_details) {
                return;
            }
            
            $auto_save_data = [
                'timestamp' => time(),
                'items_count' => $session_details['stats']['total_items'],
                'session_duration' => $session_details['stats']['session_duration'],
                'last_activity' => $session_details['session']['last_activity']
            ];
            
            global $wpdb;
            $table = $wpdb->prefix . 'amp_sales_sessions';
            
            $wpdb->update(
                $table,
                [
                    'auto_save_data' => json_encode($auto_save_data),
                    'last_activity' => current_time('mysql')
                ],
                ['id' => $session_id]
            );
            
        } catch (Exception $e) {
            error_log('AMP Auto Save Session Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Session-Aktivität protokollieren
     */
    private function log_session_activity(int $session_id, string $activity, array $data = []): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_session_activity_log';
        
        $wpdb->insert($table, [
            'session_id' => $session_id,
            'activity_type' => $activity,
            'activity_data' => json_encode($data),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Client IP ermitteln
     */
    private function get_client_ip(): string
    {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim($_SERVER[$key]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Device Type erkennen
     */
    private function detect_device(): string
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/mobile|android|iphone|ipad/i', $user_agent)) {
            return 'mobile';
        } elseif (preg_match('/tablet/i', $user_agent)) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    /**
     * Session Dauer berechnen
     */
    private function calculate_session_duration(array $session): int
    {
        $start = strtotime($session['session_start']);
        $end = $session['session_end'] ? strtotime($session['session_end']) : time();
        
        return max(0, $end - $start);
    }
    
    /**
     * Prüfen ob Session gesperrt ist
     */
    private function is_session_locked(int $session_id): bool
    {
        global $wpdb;
        $table = $wpdb->prefix . 'amp_sales_sessions';
        
        $lock_time = $wpdb->get_var($wpdb->prepare(
            "SELECT session_lock FROM {$table} WHERE id = %d",
            $session_id
        ));
        
        if (!$lock_time) {
            return false;
        }
        
        return (time() - intval($lock_time)) < self::SESSION_LOCK_TIMEOUT;
    }
    
    /**
     * Session Cache aktualisieren
     */
    private function update_session_cache(int $session_id, array $session_data): void
    {
        $this->active_sessions_cache[$session_id] = $session_data;
    }
}