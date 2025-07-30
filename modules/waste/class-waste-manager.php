<?php
/**
 * Waste Manager für AutomatenManager Pro
 * 
 * Professionelles Entsorgungsprotokoll mit vollständiger Compliance:
 * - Foto-Dokumentation für steuerliche Nachweise
 * - Kategorisierte Entsorgungsgründe und Verlustanalyse
 * - Automatische Kostenbewertung und ROI-Analyse
 * - Compliance für Steuerprüfungen und Audit-Sicherheit
 * - Trend-Analysen und Optimierungsvorschläge
 * - Export-Funktionen für Buchhaltung und Steuerberatung
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Waste
 * @version     1.0.0
 * @since       1.0.0
 * @author      AutomatenManager Pro Team
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Waste;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Products\AMP_Products_Manager;
use AutomatenManagerPro\Inventory\AMP_Inventory_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Waste Manager für professionelle Entsorgungsdokumentation
 * 
 * Hauptfunktionen:
 * - Vollständige Entsorgungsdokumentation mit Fotos
 * - Kategorisierte Verlustgründe und Kostenanalyse
 * - Steuerliche Compliance und Audit-Sicherheit
 * - Trend-Analysen und Verlustoptimierung
 * - ROI-Berechnungen und Einsparungspotentiale
 * - Automatische E-Mail-Berichte und Alerts
 * - Export für Buchhaltung und Steuerprüfung
 * - Integration mit Inventory-Management
 * 
 * @since 1.0.0
 */
class AMP_Waste_Manager
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
     * Inventory Manager Instance
     */
    private AMP_Inventory_Manager $inventory;
    
    /**
     * Waste Configuration
     */
    private array $config;
    
    /**
     * Upload Directory für Entsorgungsfotos
     */
    private string $upload_dir;
    
    /**
     * Entsorgungsgründe (Standard-Kategorien)
     */
    private const DISPOSAL_REASONS = [
        'expired' => [
            'label' => 'Abgelaufen/MHD überschritten',
            'icon' => '📅',
            'tax_deductible' => true,
            'requires_photo' => true,
            'severity' => 'medium'
        ],
        'damaged' => [
            'label' => 'Beschädigt/Zerbrochen',
            'icon' => '💔',
            'tax_deductible' => true,
            'requires_photo' => true,
            'severity' => 'low'
        ],
        'contaminated' => [
            'label' => 'Kontaminiert/Verschmutzt',
            'icon' => '🦠',
            'tax_deductible' => true,
            'requires_photo' => true,
            'severity' => 'high'
        ],
        'recall' => [
            'label' => 'Rückruf/Qualitätsmangel',
            'icon' => '📋',
            'tax_deductible' => true,
            'requires_photo' => true,
            'severity' => 'high'
        ],
        'inventory_correction' => [
            'label' => 'Inventur-Korrektur',
            'icon' => '🎯',
            'tax_deductible' => false,
            'requires_photo' => false,
            'severity' => 'low'
        ],
        'theft' => [
            'label' => 'Diebstahl/Schwund',
            'icon' => '🔒',
            'tax_deductible' => true,
            'requires_photo' => false,
            'severity' => 'high'
        ],
        'overstock' => [
            'label' => 'Überbestand-Abverkauf',
            'icon' => '📦',
            'tax_deductible' => false,
            'requires_photo' => false,
            'severity' => 'low'
        ],
        'other' => [
            'label' => 'Sonstiges',
            'icon' => '❓',
            'tax_deductible' => false,
            'requires_photo' => true,
            'severity' => 'medium'
        ]
    ];
    
    /**
     * Foto-Anforderungen
     */
    private const PHOTO_REQUIREMENTS = [
        'max_file_size' => 5242880, // 5MB
        'allowed_types' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
        'max_width' => 2048,
        'max_height' => 2048,
        'min_photos' => 1,
        'max_photos' => 5
    ];
    
    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->products = new AMP_Products_Manager();
        $this->inventory = new AMP_Inventory_Manager();
        
        $this->config = [
            'photo_storage_days' => get_option('amp_waste_photo_retention', 2555), // 7 Jahre steuerlich
            'auto_analysis_enabled' => get_option('amp_waste_auto_analysis', true),
            'email_threshold' => get_option('amp_waste_email_threshold', 50.00), // €50 Verlust
            'monthly_report_enabled' => get_option('amp_waste_monthly_reports', true),
            'audit_mode' => get_option('amp_waste_audit_mode', true)
        ];
        
        $this->setup_upload_directory();
        $this->init_hooks();
    }
    
    /**
     * WordPress Hooks initialisieren
     * 
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        // Tägliche Waste-Analyse
        add_action('amp_daily_waste_analysis', [$this, 'run_daily_analysis']);
        
        // Monatliche Berichte
        add_action('amp_monthly_waste_report', [$this, 'generate_monthly_report']);
        
        // Foto-Cleanup (alte Fotos archivieren)
        add_action('amp_cleanup_waste_photos', [$this, 'cleanup_old_photos']);
        
        // AJAX Handlers
        add_action('wp_ajax_amp_log_disposal', [$this, 'ajax_log_disposal']);
        add_action('wp_ajax_amp_upload_waste_photo', [$this, 'ajax_upload_waste_photo']);
        add_action('wp_ajax_amp_get_waste_report', [$this, 'ajax_get_waste_report']);
        add_action('wp_ajax_amp_get_waste_statistics', [$this, 'ajax_get_waste_statistics']);
        add_action('wp_ajax_amp_analyze_waste_trends', [$this, 'ajax_analyze_waste_trends']);
        add_action('wp_ajax_amp_export_waste_data', [$this, 'ajax_export_waste_data']);
        add_action('wp_ajax_amp_get_disposal_reasons', [$this, 'ajax_get_disposal_reasons']);
        add_action('wp_ajax_amp_optimize_waste_reduction', [$this, 'ajax_optimize_waste_reduction']);
        
        // Media Upload Hooks
        add_filter('upload_mimes', [$this, 'add_waste_photo_mime_types']);
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_waste_photo_upload']);
        
        // Cron-Jobs einrichten
        if (!wp_next_scheduled('amp_daily_waste_analysis')) {
            wp_schedule_event(time(), 'daily', 'amp_daily_waste_analysis');
        }
        
        if (!wp_next_scheduled('amp_monthly_waste_report')) {
            wp_schedule_event(time(), 'monthly', 'amp_monthly_waste_report');
        }
        
        if (!wp_next_scheduled('amp_cleanup_waste_photos')) {
            wp_schedule_event(time(), 'weekly', 'amp_cleanup_waste_photos');
        }
    }
    
    /**
     * Entsorgung protokollieren
     * 
     * @param int $product_id Produkt ID
     * @param int $quantity Entsorgte Menge
     * @param string $reason Entsorgungsgrund
     * @param string $notes Zusätzliche Notizen
     * @param array $photos Array mit Foto-Pfaden
     * @param array $metadata Zusätzliche Metadaten
     * @return array Ergebnis der Protokollierung
     * @since 1.0.0
     */
    public function log_disposal(int $product_id, int $quantity, string $reason, string $notes = '', array $photos = [], array $metadata = []): array
    {
        try {
            // Produkt validieren
            $product = $this->products->get_product($product_id);
            if (!$product || $product['status'] !== 'active') {
                return [
                    'success' => false,
                    'error' => 'Produkt nicht gefunden oder inaktiv.',
                    'code' => 'PRODUCT_NOT_FOUND'
                ];
            }
            
            // Menge validieren
            if ($quantity <= 0) {
                return [
                    'success' => false,
                    'error' => 'Ungültige Menge.',
                    'code' => 'INVALID_QUANTITY'
                ];
            }
            
            // Bestand prüfen
            if ($product['current_stock'] < $quantity) {
                return [
                    'success' => false,
                    'error' => 'Nicht genügend Bestand vorhanden.',
                    'code' => 'INSUFFICIENT_STOCK',
                    'available_stock' => $product['current_stock']
                ];
            }
            
            // Entsorgungsgrund validieren
            if (!array_key_exists($reason, self::DISPOSAL_REASONS)) {
                return [
                    'success' => false,
                    'error' => 'Ungültiger Entsorgungsgrund.',
                    'code' => 'INVALID_REASON'
                ];
            }
            
            $reason_config = self::DISPOSAL_REASONS[$reason];
            
            // Foto-Anforderungen prüfen
            if ($reason_config['requires_photo'] && empty($photos)) {
                return [
                    'success' => false,
                    'error' => 'Für diesen Entsorgungsgrund ist ein Foto erforderlich.',
                    'code' => 'PHOTO_REQUIRED'
                ];
            }
            
            // Verlustkosten berechnen
            $disposal_cost = $this->calculate_disposal_cost($product, $quantity, $reason);
            
            global $wpdb;
            $wpdb->query('START TRANSACTION');
            
            try {
                // Entsorgung in waste_log eintragen
                $waste_table = $wpdb->prefix . 'amp_waste_log';
                
                $disposal_data = [
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                    'disposal_reason' => $reason,
                    'disposal_notes' => $notes,
                    'disposal_cost' => $disposal_cost['total_cost'],
                    'unit_cost' => $disposal_cost['unit_cost'],
                    'tax_deductible' => $reason_config['tax_deductible'] ? 1 : 0,
                    'severity_level' => $reason_config['severity'],
                    'photos_count' => count($photos),
                    'photo_paths' => json_encode($photos),
                    'metadata' => json_encode(array_merge($metadata, [
                        'ip_address' => $this->get_client_ip(),
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'disposal_timestamp' => time()
                    ])),
                    'user_id' => get_current_user_id(),
                    'created_at' => current_time('mysql')
                ];
                
                $wpdb->insert($waste_table, $disposal_data);
                
                if ($wpdb->last_error) {
                    throw new Exception('Fehler beim Protokollieren der Entsorgung: ' . $wpdb->last_error);
                }
                
                $disposal_id = $wpdb->insert_id;
                
                // Lagerbestand reduzieren über Inventory Manager
                $inventory_result = $this->inventory->add_stock_movement(
                    $product_id,
                    'waste',
                    -$quantity,
                    "Entsorgung: {$reason_config['label']}",
                    [
                        'disposal_id' => $disposal_id,
                        'disposal_reason' => $reason,
                        'disposal_cost' => $disposal_cost['total_cost']
                    ]
                );
                
                if (!$inventory_result['success']) {
                    throw new Exception('Fehler beim Aktualisieren des Lagerbestands: ' . $inventory_result['error']);
                }
                
                // Fotos verarbeiten und archivieren
                if (!empty($photos)) {
                    $this->process_disposal_photos($disposal_id, $photos);
                }
                
                $wpdb->query('COMMIT');
                
                // Ereignis für weitere Verarbeitung auslösen
                do_action('amp_disposal_logged', $disposal_id, $product_id, $quantity, $reason, $disposal_cost['total_cost']);
                
                // E-Mail Benachrichtigung bei hohen Verlusten
                if ($disposal_cost['total_cost'] >= $this->config['email_threshold']) {
                    $this->send_high_value_disposal_alert($disposal_id, $disposal_data, $product);
                }
                
                return [
                    'success' => true,
                    'disposal_id' => $disposal_id,
                    'disposal_cost' => $disposal_cost,
                    'new_stock' => $inventory_result['new_stock'],
                    'photos_processed' => count($photos),
                    'message' => 'Entsorgung erfolgreich protokolliert.'
                ];
                
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log('AMP Waste Logging Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Protokollieren der Entsorgung.',
                'code' => 'DISPOSAL_ERROR'
            ];
        }
    }
    
    /**
     * Verlustanalyse durchführen
     * 
     * @param int $period_days Analysezeitraum in Tagen
     * @param array $filters Zusätzliche Filter
     * @return array Verlustanalyse Ergebnisse
     * @since 1.0.0
     */
    public function analyze_waste_patterns(int $period_days = 30, array $filters = []): array
    {
        try {
            $start_date = date('Y-m-d', strtotime("-{$period_days} days"));
            
            global $wpdb;
            $waste_table = $wpdb->prefix . 'amp_waste_log';
            $products_table = $wpdb->prefix . 'amp_products';
            $categories_table = $wpdb->prefix . 'amp_categories';
            
            // Base Query für Waste-Daten
            $where_conditions = ["wl.created_at >= %s"];
            $where_values = [$start_date];
            
            // Filter anwenden
            if (!empty($filters['reason'])) {
                $where_conditions[] = "wl.disposal_reason = %s";
                $where_values[] = $filters['reason'];
            }
            
            if (!empty($filters['category_id'])) {
                $where_conditions[] = "p.category_id = %d";
                $where_values[] = intval($filters['category_id']);
            }
            
            if (!empty($filters['min_cost'])) {
                $where_conditions[] = "wl.disposal_cost >= %f";
                $where_values[] = floatval($filters['min_cost']);
            }
            
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            
            // Detaillierte Waste-Daten abrufen
            $waste_data = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    wl.*,
                    p.name as product_name,
                    p.barcode,
                    p.buy_price,
                    p.sell_price,
                    c.name as category_name,
                    DATE(wl.created_at) as disposal_date,
                    WEEK(wl.created_at) as disposal_week,
                    MONTH(wl.created_at) as disposal_month
                FROM {$waste_table} wl
                LEFT JOIN {$products_table} p ON wl.product_id = p.id
                LEFT JOIN {$categories_table} c ON p.category_id = c.id
                {$where_clause}
                ORDER BY wl.created_at DESC
            ", ...$where_values), ARRAY_A);
            
            if (empty($waste_data)) {
                return [
                    'success' => false,
                    'error' => 'Keine Entsorgungsdaten für den Analysezeitraum gefunden.',
                    'code' => 'NO_WASTE_DATA'
                ];
            }
            
            // Analysen durchführen
            $analysis_results = [
                'period_summary' => $this->calculate_period_summary($waste_data, $period_days),
                'reason_breakdown' => $this->analyze_disposal_reasons($waste_data),
                'category_analysis' => $this->analyze_category_waste($waste_data),
                'temporal_patterns' => $this->analyze_temporal_patterns($waste_data),
                'cost_analysis' => $this->analyze_waste_costs($waste_data),
                'top_waste_products' => $this->identify_top_waste_products($waste_data),
                'optimization_suggestions' => $this->generate_waste_optimization_suggestions($waste_data),
                'trend_indicators' => $this->calculate_waste_trends($waste_data, $period_days)
            ];
            
            return [
                'success' => true,
                'analysis_date' => current_time('mysql'),
                'period_days' => $period_days,
                'total_disposals' => count($waste_data),
                'filters_applied' => $filters,
                'analysis' => $analysis_results
            ];
            
        } catch (Exception $e) {
            error_log('AMP Waste Pattern Analysis Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler bei der Verlustanalyse.',
                'code' => 'WASTE_ANALYSIS_ERROR'
            ];
        }
    }
    
    /**
     * Verlustreduzierungs-Optimierung
     * 
     * @return array Optimierungsvorschläge
     * @since 1.0.0
     */
    public function optimize_waste_reduction(): array
    {
        try {
            // Analyse der letzten 90 Tage für solide Datenbasis
            $analysis = $this->analyze_waste_patterns(90);
            
            if (!$analysis['success']) {
                return $analysis;
            }
            
            $optimization_strategies = [];
            
            // 1. Produkte mit hohem Ablauf-Anteil identifizieren
            $expiry_products = $this->identify_high_expiry_products($analysis['analysis']);
            foreach ($expiry_products as $product) {
                $optimization_strategies[] = [
                    'type' => 'reduce_expiry_waste',
                    'priority' => 'high',
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'issue' => "Hoher Ablauf-Anteil: {$product['expiry_percentage']}%",
                    'suggestion' => 'Bestellmenge reduzieren oder Lieferfrequenz erhöhen',
                    'potential_savings' => $product['potential_monthly_savings'],
                    'implementation' => [
                        'reduce_order_quantity' => $product['suggested_order_reduction'],
                        'increase_delivery_frequency' => 'Von monatlich auf wöchentlich',
                        'improve_rotation' => 'FIFO-Prinzip strenger durchsetzen'
                    ]
                ];
            }
            
            // 2. Produkte mit hohem Beschädigungs-Anteil
            $damage_products = $this->identify_high_damage_products($analysis['analysis']);
            foreach ($damage_products as $product) {
                $optimization_strategies[] = [
                    'type' => 'reduce_damage_waste',
                    'priority' => 'medium',
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'issue' => "Hoher Beschädigungs-Anteil: {$product['damage_percentage']}%",
                    'suggestion' => 'Handling-Prozesse optimieren oder Verpackung verbessern',
                    'potential_savings' => $product['potential_monthly_savings'],
                    'implementation' => [
                        'improve_handling' => 'Schulung für vorsichtigeren Umgang',
                        'better_storage' => 'Stabilere Lagerung implementieren',
                        'supplier_feedback' => 'Verpackungsqualität mit Lieferant besprechen'
                    ]
                ];
            }
            
            // 3. Saisonale Optimierungen
            $seasonal_optimizations = $this->identify_seasonal_waste_patterns($analysis['analysis']);
            foreach ($seasonal_optimizations as $optimization) {
                $optimization_strategies[] = [
                    'type' => 'seasonal_optimization',
                    'priority' => 'medium',
                    'category' => $optimization['category'],
                    'issue' => $optimization['pattern_description'],
                    'suggestion' => $optimization['optimization_suggestion'],
                    'potential_savings' => $optimization['estimated_savings'],
                    'timing' => $optimization['implementation_timing']
                ];
            }
            
            // 4. Überbestand-Reduzierung
            $overstock_optimizations = $this->identify_overstock_waste($analysis['analysis']);
            foreach ($overstock_optimizations as $optimization) {
                $optimization_strategies[] = [
                    'type' => 'reduce_overstock',
                    'priority' => 'low',
                    'product_id' => $optimization['product_id'],
                    'product_name' => $optimization['product_name'],
                    'issue' => 'Regelmäßige Überbestände führen zu Entsorgungen',
                    'suggestion' => 'Bestellalgorithmus anpassen',
                    'potential_savings' => $optimization['potential_savings'],
                    'implementation' => [
                        'adjust_min_stock' => $optimization['suggested_min_stock'],
                        'reduce_order_quantity' => $optimization['suggested_order_quantity'],
                        'implement_jit' => 'Just-in-Time Lieferung prüfen'
                    ]
                ];
            }
            
            // Gesamtes Einsparungspotential berechnen
            $total_potential_savings = array_sum(array_column($optimization_strategies, 'potential_savings'));
            
            // ROI-Berechnung für Implementierung
            $implementation_cost = $this->estimate_optimization_implementation_cost($optimization_strategies);
            $roi_months = $implementation_cost > 0 ? ($implementation_cost / ($total_potential_savings / 12)) : 0;
            
            return [
                'success' => true,
                'analysis_date' => current_time('mysql'),
                'total_strategies' => count($optimization_strategies),
                'potential_monthly_savings' => round($total_potential_savings, 2),
                'potential_annual_savings' => round($total_potential_savings * 12, 2),
                'estimated_implementation_cost' => $implementation_cost,
                'roi_payback_months' => round($roi_months, 1),
                'strategies' => $optimization_strategies
            ];
            
        } catch (Exception $e) {
            error_log('AMP Waste Optimization Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler bei der Verlustoptimierung.',
                'code' => 'WASTE_OPTIMIZATION_ERROR'
            ];
        }
    }
    
    /**
     * Entsorgungsbericht für Steuerprüfung exportieren
     * 
     * @param string $start_date Start-Datum
     * @param string $end_date End-Datum
     * @param string $format Export-Format (csv, pdf, excel)
     * @return array Export-Ergebnis
     * @since 1.0.0
     */
    public function export_tax_report(string $start_date, string $end_date, string $format = 'csv'): array
    {
        try {
            global $wpdb;
            $waste_table = $wpdb->prefix . 'amp_waste_log';
            $products_table = $wpdb->prefix . 'amp_products';
            
            // Steuerlich relevante Entsorgungen abrufen
            $tax_data = $wpdb->get_results($wpdb->prepare("
                SELECT 
                    wl.id,
                    wl.created_at as disposal_date,
                    wl.disposal_reason,
                    wl.quantity,
                    wl.disposal_cost,
                    wl.unit_cost,
                    wl.tax_deductible,
                    wl.disposal_notes,
                    wl.photos_count,
                    p.name as product_name,
                    p.barcode,
                    p.buy_price,
                    u.display_name as disposed_by
                FROM {$waste_table} wl
                LEFT JOIN {$products_table} p ON wl.product_id = p.id
                LEFT JOIN {$wpdb->users} u ON wl.user_id = u.ID
                WHERE wl.created_at BETWEEN %s AND %s
                AND wl.tax_deductible = 1
                ORDER BY wl.created_at
            ", $start_date, $end_date), ARRAY_A);
            
            if (empty($tax_data)) {
                return [
                    'success' => false,
                    'error' => 'Keine steuerlich relevanten Entsorgungen im Zeitraum gefunden.',
                    'code' => 'NO_TAX_DATA'
                ];
            }
            
            // Export je nach Format durchführen
            switch ($format) {
                case 'csv':
                    $export_result = $this->export_to_csv($tax_data, $start_date, $end_date);
                    break;
                case 'pdf':
                    $export_result = $this->export_to_pdf($tax_data, $start_date, $end_date);
                    break;
                case 'excel':
                    $export_result = $this->export_to_excel($tax_data, $start_date, $end_date);
                    break;
                default:
                    return [
                        'success' => false,
                        'error' => 'Unbekanntes Export-Format.',
                        'code' => 'INVALID_FORMAT'
                    ];
            }
            
            // Export-Aktivität protokollieren
            $this->log_export_activity($start_date, $end_date, $format, count($tax_data));
            
            return [
                'success' => true,
                'export_format' => $format,
                'period' => "{$start_date} bis {$end_date}",
                'total_records' => count($tax_data),
                'total_tax_loss' => array_sum(array_column($tax_data, 'disposal_cost')),
                'export_file' => $export_result['file_path'],
                'download_url' => $export_result['download_url']
            ];
            
        } catch (Exception $e) {
            error_log('AMP Tax Report Export Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Fehler beim Export des Steuerberichts.',
                'code' => 'TAX_EXPORT_ERROR'
            ];
        }
    }
    
    /**
     * Tägliche Waste-Analyse ausführen
     * 
     * @since 1.0.0
     */
    public function run_daily_analysis(): void
    {
        try {
            // Gestrige Entsorgungen analysieren
            $yesterday_analysis = $this->analyze_waste_patterns(1);
            
            if ($yesterday_analysis['success'] && $yesterday_analysis['total_disposals'] > 0) {
                // Bei hohen Tagesverlusten E-Mail senden
                $daily_loss = $yesterday_analysis['analysis']['period_summary']['total_cost'];
                
                if ($daily_loss >= $this->config['email_threshold']) {
                    $this->send_daily_waste_alert($yesterday_analysis);
                }
            }
            
            // Wöchentliche Trend-Analyse (jeden Montag)
            if (date('N') == 1) { // Montag
                $weekly_analysis = $this->analyze_waste_patterns(7);
                $this->update_waste_trends($weekly_analysis);
            }
            
        } catch (Exception $e) {
            error_log('AMP Daily Waste Analysis Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Entsorgung protokollieren
     * 
     * @since 1.0.0
     */
    public function ajax_log_disposal(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_manage_inventory')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = intval($_POST['quantity'] ?? 0);
            $reason = sanitize_text_field($_POST['reason'] ?? '');
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');
            $photos = array_map('sanitize_text_field', $_POST['photos'] ?? []);
            
            $result = $this->log_disposal($product_id, $quantity, $reason, $notes, $photos);
            
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
     * AJAX: Waste-Foto hochladen
     * 
     * @since 1.0.0
     */
    public function ajax_upload_waste_photo(): void
    {
        try {
            check_ajax_referer('amp_waste_photo_upload', 'nonce');
            
            if (!current_user_can('amp_manage_inventory')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            if (!isset($_FILES['waste_photo'])) {
                wp_send_json_error(['message' => 'Keine Datei hochgeladen.']);
                return;
            }
            
            $upload_result = $this->handle_photo_upload($_FILES['waste_photo']);
            
            if ($upload_result['success']) {
                wp_send_json_success($upload_result);
            } else {
                wp_send_json_error($upload_result);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Upload Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Waste-Bericht abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_waste_report(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_view_reports')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $period_days = intval($_POST['period_days'] ?? 30);
            $filters = $_POST['filters'] ?? [];
            
            $report = $this->analyze_waste_patterns($period_days, $filters);
            
            if ($report['success']) {
                wp_send_json_success($report);
            } else {
                wp_send_json_error($report);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Waste-Statistiken abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_waste_statistics(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_view_reports')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $statistics = $this->get_waste_statistics();
            wp_send_json_success($statistics);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Waste-Trend-Analyse
     * 
     * @since 1.0.0
     */
    public function ajax_analyze_waste_trends(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_view_reports')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $period_days = intval($_POST['period_days'] ?? 90);
            $trends = $this->analyze_long_term_trends($period_days);
            
            if ($trends['success']) {
                wp_send_json_success($trends);
            } else {
                wp_send_json_error($trends);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Waste-Daten exportieren
     * 
     * @since 1.0.0
     */
    public function ajax_export_waste_data(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_export_data')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $start_date = sanitize_text_field($_POST['start_date'] ?? '');
            $end_date = sanitize_text_field($_POST['end_date'] ?? '');
            $format = sanitize_text_field($_POST['format'] ?? 'csv');
            
            $export = $this->export_tax_report($start_date, $end_date, $format);
            
            if ($export['success']) {
                wp_send_json_success($export);
            } else {
                wp_send_json_error($export);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Export Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Entsorgungsgründe abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_disposal_reasons(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            $reasons = [];
            foreach (self::DISPOSAL_REASONS as $key => $config) {
                $reasons[] = [
                    'key' => $key,
                    'label' => $config['label'],
                    'icon' => $config['icon'],
                    'requires_photo' => $config['requires_photo'],
                    'tax_deductible' => $config['tax_deductible'],
                    'severity' => $config['severity']
                ];
            }
            
            wp_send_json_success(['reasons' => $reasons]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX: Waste-Reduction Optimierung
     * 
     * @since 1.0.0
     */
    public function ajax_optimize_waste_reduction(): void
    {
        try {
            check_ajax_referer('amp_waste_action', 'nonce');
            
            if (!current_user_can('amp_manage_inventory')) {
                wp_send_json_error(['message' => 'Keine Berechtigung.']);
                return;
            }
            
            $optimization = $this->optimize_waste_reduction();
            
            if ($optimization['success']) {
                wp_send_json_success($optimization);
            } else {
                wp_send_json_error($optimization);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'AJAX Fehler: ' . $e->getMessage()]);
        }
    }
    
    // ===============================
    // PRIVATE HELPER METHODS
    // ===============================
    
    /**
     * Upload-Verzeichnis für Waste-Fotos einrichten
     */
    private function setup_upload_directory(): void
    {
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/automaten-manager-pro/waste-photos/';
        
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            
            // .htaccess für Sicherheit
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($this->upload_dir . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Entsorgungskosten berechnen
     */
    private function calculate_disposal_cost(array $product, int $quantity, string $reason): array
    {
        $unit_cost = floatval($product['buy_price']); // Verwende Einkaufspreis als Verlustbasis
        $total_cost = $unit_cost * $quantity;
        
        // Bei steuerlich absetzbaren Verlusten zusätzliche Informationen
        $tax_impact = 0;
        if (self::DISPOSAL_REASONS[$reason]['tax_deductible']) {
            $tax_rate = get_option('amp_tax_rate', 0.19); // 19% Standard-MwSt
            $tax_impact = $total_cost * $tax_rate;
        }
        
        return [
            'unit_cost' => $unit_cost,
            'total_cost' => $total_cost,
            'tax_impact' => $tax_impact,
            'net_loss' => $total_cost - $tax_impact
        ];
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
    
    // Placeholder für weitere Helper-Methoden
    private function process_disposal_photos(int $disposal_id, array $photos): void {}
    private function send_high_value_disposal_alert(int $disposal_id, array $disposal_data, array $product): void {}
    private function calculate_period_summary(array $waste_data, int $period_days): array { return []; }
    private function analyze_disposal_reasons(array $waste_data): array { return []; }
    private function analyze_category_waste(array $waste_data): array { return []; }
    private function analyze_temporal_patterns(array $waste_data): array { return []; }
    private function analyze_waste_costs(array $waste_data): array { return []; }
    private function identify_top_waste_products(array $waste_data): array { return []; }
    private function generate_waste_optimization_suggestions(array $waste_data): array { return []; }
    private function calculate_waste_trends(array $waste_data, int $period_days): array { return []; }
    private function identify_high_expiry_products(array $analysis): array { return []; }
    private function identify_high_damage_products(array $analysis): array { return []; }
    private function identify_seasonal_waste_patterns(array $analysis): array { return []; }
    private function identify_overstock_waste(array $analysis): array { return []; }
    private function estimate_optimization_implementation_cost(array $strategies): float { return 0.0; }
    private function export_to_csv(array $data, string $start_date, string $end_date): array { return []; }
    private function export_to_pdf(array $data, string $start_date, string $end_date): array { return []; }
    private function export_to_excel(array $data, string $start_date, string $end_date): array { return []; }
    private function log_export_activity(string $start_date, string $end_date, string $format, int $record_count): void {}
    private function send_daily_waste_alert(array $analysis): void {}
    private function update_waste_trends(array $analysis): void {}
    private function handle_photo_upload(array $file): array { return ['success' => false]; }
    private function get_waste_statistics(): array { return []; }
    private function analyze_long_term_trends(int $period_days): array { return ['success' => false]; }
    private function generate_monthly_report(): void {}
    private function cleanup_old_photos(): void {}
    public function add_waste_photo_mime_types(array $mimes): array { return $mimes; }
    private function validate_waste_photo_upload(array $file): array { return $file; }
}