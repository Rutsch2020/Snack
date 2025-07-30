<?php
/**
 * Reports Manager - Business Intelligence Dashboard
 * 
 * Zentrale Klasse für umfassende Berichte und Analytics des AutomatenManager Pro Systems.
 * Vereint alle Module (Sales, Inventory, Waste) zu einem einheitlichen BI-Dashboard.
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Reports
 * @version     1.0.0
 * @since       1.0.0
 * @author      AutomatenManager Pro Team
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Reports;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Sales\AMP_Sales_Manager;
use AutomatenManagerPro\Inventory\AMP_Inventory_Manager;
use AutomatenManagerPro\Waste\AMP_Waste_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Business Intelligence und Reporting Manager
 * 
 * Erstellt umfassende Berichte und Analytics für:
 * - Umsatz-Analysen mit Trend-Prognosen
 * - Lager-Effizienz und ABC-Klassifizierung  
 * - Verlust-Analysen und Optimierungsvorschläge
 * - ROI-Berechnungen und KPI-Dashboards
 * - Export-Funktionen für Management
 * 
 * @since 1.0.0
 */
class AMP_Reports_Manager
{
    private AMP_Database_Manager $database;
    private AMP_Sales_Manager $sales_manager;
    private AMP_Inventory_Manager $inventory_manager;
    private AMP_Waste_Manager $waste_manager;
    
    /**
     * Reports Manager konstruktor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->sales_manager = new AMP_Sales_Manager();
        $this->inventory_manager = new AMP_Inventory_Manager();
        $this->waste_manager = new AMP_Waste_Manager();
        
        $this->init_hooks();
    }
    
    /**
     * WordPress Hooks initialisieren
     * 
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        // AJAX Handlers für Real-time Dashboard
        add_action('wp_ajax_amp_get_dashboard_stats', [$this, 'ajax_get_dashboard_stats']);
        add_action('wp_ajax_amp_get_sales_chart_data', [$this, 'ajax_get_sales_chart_data']);
        add_action('wp_ajax_amp_get_inventory_analytics', [$this, 'ajax_get_inventory_analytics']);
        add_action('wp_ajax_amp_export_report', [$this, 'ajax_export_report']);
        
        // Cron-Jobs für automatische Berichte
        add_action('amp_daily_reports', [$this, 'generate_daily_reports']);
        add_action('amp_weekly_reports', [$this, 'generate_weekly_reports']);
        add_action('amp_monthly_reports', [$this, 'generate_monthly_reports']);
    }
    
    /**
     * Dashboard Haupt-Statistiken abrufen
     * 
     * @param string $period Zeitraum (today, week, month, year)
     * @return array Umfassende Dashboard-Statistiken
     * @since 1.0.0
     */
    public function get_dashboard_stats(string $period = 'today'): array
    {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        
        // Umsatz-Statistiken
        $sales_stats = $this->get_sales_statistics($date_condition);
        
        // Lager-Statistiken  
        $inventory_stats = $this->get_inventory_statistics();
        
        // Verlust-Statistiken
        $waste_stats = $this->get_waste_statistics($date_condition);
        
        // Produkt-Performance
        $product_performance = $this->get_product_performance($date_condition);
        
        // Trend-Analysen
        $trends = $this->calculate_trends($period);
        
        return [
            'period' => $period,
            'generated_at' => current_time('Y-m-d H:i:s'),
            'sales' => $sales_stats,
            'inventory' => $inventory_stats,
            'waste' => $waste_stats,
            'products' => $product_performance,
            'trends' => $trends,
            'kpis' => $this->calculate_kpis($sales_stats, $inventory_stats, $waste_stats)
        ];
    }
    
    /**
     * Umsatz-Statistiken berechnen
     * 
     * @param string $date_condition SQL Datums-Bedingung
     * @return array Umsatz-Daten
     * @since 1.0.0
     */
    private function get_sales_statistics(string $date_condition): array
    {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        
        $query = "
            SELECT 
                COUNT(*) as total_sessions,
                COALESCE(SUM(total_gross), 0) as total_revenue,
                COALESCE(SUM(total_net), 0) as total_net,
                COALESCE(SUM(total_vat), 0) as total_vat,
                COALESCE(SUM(total_deposit), 0) as total_deposit,
                COALESCE(AVG(total_gross), 0) as avg_session_value,
                COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_payments,
                COUNT(CASE WHEN payment_method = 'card' THEN 1 END) as card_payments,
                COUNT(CASE WHEN payment_method = 'mixed' THEN 1 END) as mixed_payments
            FROM {$table_sessions} 
            WHERE status = 'completed' {$date_condition}
        ";
        
        $result = $wpdb->get_row($query, ARRAY_A);
        
        // Artikel-Verkäufe
        $table_items = $wpdb->prefix . 'amp_sales_items';
        $items_query = "
            SELECT 
                COALESCE(SUM(si.quantity), 0) as total_items_sold,
                COUNT(DISTINCT si.product_id) as unique_products_sold
            FROM {$table_items} si
            JOIN {$table_sessions} ss ON si.session_id = ss.id
            WHERE ss.status = 'completed' {$date_condition}
        ";
        
        $items_result = $wpdb->get_row($items_query, ARRAY_A);
        
        return array_merge($result ?: [], $items_result ?: []);
    }
    
    /**
     * Lager-Statistiken berechnen
     * 
     * @return array Lager-Daten mit ABC-Analyse
     * @since 1.0.0
     */
    private function get_inventory_statistics(): array
    {
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'amp_products';
        
        // Grundlegende Lager-Statistiken
        $basic_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_products,
                COALESCE(SUM(current_stock), 0) as total_stock_quantity,
                COALESCE(SUM(current_stock * buy_price), 0) as total_stock_value,
                COALESCE(AVG(current_stock), 0) as avg_stock_per_product,
                COUNT(CASE WHEN current_stock <= min_stock THEN 1 END) as low_stock_count,
                COUNT(CASE WHEN current_stock = 0 THEN 1 END) as out_of_stock_count
            FROM {$table_products} 
            WHERE status = 'active'
        ", ARRAY_A);
        
        // Ablaufende Produkte (nächste 7 Tage)
        $expiring_products = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$table_products} 
            WHERE status = 'active' 
            AND expiry_date IS NOT NULL 
            AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ");
        
        // ABC-Klassifizierung vom Inventory Manager
        $abc_analysis = $this->inventory_manager->get_abc_analysis();
        
        // Umschlagshäufigkeit
        $turnover_analysis = $this->inventory_manager->get_turnover_analysis();
        
        return [
            'basic' => $basic_stats ?: [],
            'expiring_soon' => (int)$expiring_products,
            'abc_analysis' => $abc_analysis,
            'turnover' => $turnover_analysis
        ];
    }
    
    /**
     * Verlust-Statistiken berechnen
     * 
     * @param string $date_condition SQL Datums-Bedingung
     * @return array Verlust-Daten
     * @since 1.0.0
     */
    private function get_waste_statistics(string $date_condition): array
    {
        global $wpdb;
        
        $table_waste = $wpdb->prefix . 'amp_waste_log';
        
        // Grundlegende Verlust-Statistiken
        $basic_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_waste_entries,
                COALESCE(SUM(quantity), 0) as total_quantity_wasted,
                COALESCE(SUM(disposal_value), 0) as total_waste_value,
                COALESCE(AVG(disposal_value), 0) as avg_waste_value
            FROM {$table_waste} 
            WHERE 1=1 {$date_condition}
        ", ARRAY_A);
        
        // Verlust nach Gründen
        $waste_by_reason = $wpdb->get_results("
            SELECT 
                disposal_reason,
                COUNT(*) as count,
                SUM(quantity) as total_quantity,
                SUM(disposal_value) as total_value,
                ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM {$table_waste} WHERE 1=1 {$date_condition}), 2) as percentage
            FROM {$table_waste} 
            WHERE 1=1 {$date_condition}
            GROUP BY disposal_reason
            ORDER BY total_value DESC
        ", ARRAY_A);
        
        // Top verlustreichste Produkte
        $table_products = $wpdb->prefix . 'amp_products';
        $top_waste_products = $wpdb->get_results("
            SELECT 
                p.name,
                p.barcode,
                COUNT(w.id) as waste_count,
                SUM(w.quantity) as total_quantity,
                SUM(w.disposal_value) as total_value
            FROM {$table_waste} w
            JOIN {$table_products} p ON w.product_id = p.id
            WHERE 1=1 {$date_condition}
            GROUP BY w.product_id
            ORDER BY total_value DESC
            LIMIT 10
        ", ARRAY_A);
        
        return [
            'basic' => $basic_stats ?: [],
            'by_reason' => $waste_by_reason ?: [],
            'top_products' => $top_waste_products ?: []
        ];
    }
    
    /**
     * Produkt-Performance analysieren
     * 
     * @param string $date_condition SQL Datums-Bedingung
     * @return array Top-Seller und Performance-Daten
     * @since 1.0.0
     */
    private function get_product_performance(string $date_condition): array
    {
        global $wpdb;
        
        $table_products = $wpdb->prefix . 'amp_products';
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        $table_items = $wpdb->prefix . 'amp_sales_items';
        
        // Top-Seller
        $top_sellers = $wpdb->get_results("
            SELECT 
                p.name,
                p.barcode,
                p.sell_price,
                SUM(si.quantity) as total_sold,
                SUM(si.line_total_gross) as total_revenue,
                COUNT(DISTINCT si.session_id) as sessions_count,
                ROUND(SUM(si.line_total_gross) / SUM(si.quantity), 2) as avg_price_per_unit
            FROM {$table_items} si
            JOIN {$table_sessions} ss ON si.session_id = ss.id
            JOIN {$table_products} p ON si.product_id = p.id
            WHERE ss.status = 'completed' {$date_condition}
            GROUP BY si.product_id
            ORDER BY total_sold DESC
            LIMIT 20
        ", ARRAY_A);
        
        // Umsatzstärkste Produkte
        $top_revenue = $wpdb->get_results("
            SELECT 
                p.name,
                p.barcode,
                SUM(si.quantity) as total_sold,
                SUM(si.line_total_gross) as total_revenue,
                ROUND((p.sell_price - p.buy_price) * SUM(si.quantity), 2) as estimated_profit
            FROM {$table_items} si
            JOIN {$table_sessions} ss ON si.session_id = ss.id
            JOIN {$table_products} p ON si.product_id = p.id
            WHERE ss.status = 'completed' {$date_condition}
            GROUP BY si.product_id
            ORDER BY total_revenue DESC
            LIMIT 20
        ", ARRAY_A);
        
        // Ladenhüter (wenig verkauft)
        $slow_movers = $wpdb->get_results("
            SELECT 
                p.name,
                p.barcode,
                p.current_stock,
                COALESCE(SUM(si.quantity), 0) as total_sold,
                p.current_stock * p.buy_price as tied_capital
            FROM {$table_products} p
            LEFT JOIN {$table_items} si ON p.id = si.product_id
            LEFT JOIN {$table_sessions} ss ON si.session_id = ss.id AND ss.status = 'completed' {$date_condition}
            WHERE p.status = 'active' AND p.current_stock > 0
            GROUP BY p.id
            HAVING total_sold <= 2
            ORDER BY tied_capital DESC
            LIMIT 15
        ", ARRAY_A);
        
        return [
            'top_sellers' => $top_sellers ?: [],
            'top_revenue' => $top_revenue ?: [],
            'slow_movers' => $slow_movers ?: []
        ];
    }
    
    /**
     * Trend-Analysen berechnen
     * 
     * @param string $period Aktueller Zeitraum
     * @return array Trend-Daten
     * @since 1.0.0
     */
    private function calculate_trends(string $period): array
    {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        
        // Vergleichszeitraum bestimmen
        $current_condition = $this->get_date_condition($period);
        $previous_condition = $this->get_previous_period_condition($period);
        
        // Aktuelle Periode
        $current_data = $wpdb->get_row("
            SELECT 
                COUNT(*) as sessions,
                COALESCE(SUM(total_gross), 0) as revenue,
                COALESCE(AVG(total_gross), 0) as avg_session_value
            FROM {$table_sessions} 
            WHERE status = 'completed' {$current_condition}
        ", ARRAY_A);
        
        // Vorherige Periode
        $previous_data = $wpdb->get_row("
            SELECT 
                COUNT(*) as sessions,
                COALESCE(SUM(total_gross), 0) as revenue,
                COALESCE(AVG(total_gross), 0) as avg_session_value
            FROM {$table_sessions} 
            WHERE status = 'completed' {$previous_condition}
        ", ARRAY_A);
        
        return [
            'revenue_change' => $this->calculate_percentage_change(
                $previous_data['revenue'], 
                $current_data['revenue']
            ),
            'sessions_change' => $this->calculate_percentage_change(
                $previous_data['sessions'], 
                $current_data['sessions']
            ),
            'avg_value_change' => $this->calculate_percentage_change(
                $previous_data['avg_session_value'], 
                $current_data['avg_session_value']
            )
        ];
    }
    
    /**
     * KPIs (Key Performance Indicators) berechnen
     * 
     * @param array $sales_stats Verkaufs-Statistiken
     * @param array $inventory_stats Lager-Statistiken
     * @param array $waste_stats Verlust-Statistiken
     * @return array KPI-Dashboard
     * @since 1.0.0
     */
    private function calculate_kpis(array $sales_stats, array $inventory_stats, array $waste_stats): array
    {
        $kpis = [];
        
        // Umsatz-KPIs
        $kpis['revenue_per_session'] = $sales_stats['total_sessions'] > 0 
            ? round($sales_stats['total_revenue'] / $sales_stats['total_sessions'], 2) 
            : 0;
            
        $kpis['items_per_session'] = $sales_stats['total_sessions'] > 0 
            ? round($sales_stats['total_items_sold'] / $sales_stats['total_sessions'], 2) 
            : 0;
        
        // Lager-KPIs
        $kpis['stock_turnover_ratio'] = $inventory_stats['basic']['total_stock_value'] > 0 
            ? round($sales_stats['total_net'] / $inventory_stats['basic']['total_stock_value'], 2) 
            : 0;
            
        $kpis['inventory_value_ratio'] = $sales_stats['total_revenue'] > 0 
            ? round($inventory_stats['basic']['total_stock_value'] / $sales_stats['total_revenue'], 2) 
            : 0;
        
        // Verlust-KPIs
        $kpis['waste_ratio'] = $sales_stats['total_revenue'] > 0 
            ? round(($waste_stats['basic']['total_waste_value'] / $sales_stats['total_revenue']) * 100, 2) 
            : 0;
            
        $kpis['gross_margin'] = $sales_stats['total_revenue'] > 0 
            ? round((($sales_stats['total_revenue'] - $waste_stats['basic']['total_waste_value']) / $sales_stats['total_revenue']) * 100, 2) 
            : 0;
        
        // Effizienz-KPIs
        $kpis['low_stock_ratio'] = $inventory_stats['basic']['total_products'] > 0 
            ? round(($inventory_stats['basic']['low_stock_count'] / $inventory_stats['basic']['total_products']) * 100, 2) 
            : 0;
        
        return $kpis;
    }
    
    /**
     * Verkaufs-Chart Daten für verschiedene Zeiträume
     * 
     * @param string $period Zeitraum (day, week, month, year)
     * @param string $type Chart-Typ (revenue, sessions, items)
     * @return array Chart-Daten für JavaScript
     * @since 1.0.0
     */
    public function get_sales_chart_data(string $period = 'week', string $type = 'revenue'): array
    {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        $table_items = $wpdb->prefix . 'amp_sales_items';
        
        $labels = [];
        $data = [];
        
        switch ($period) {
            case 'day':
                // Letzten 24 Stunden, stündlich
                for ($i = 23; $i >= 0; $i--) {
                    $hour = date('Y-m-d H:00:00', strtotime("-{$i} hours"));
                    $labels[] = date('H:00', strtotime($hour));
                    
                    $value = $this->get_chart_value_for_hour($hour, $type);
                    $data[] = $value;
                }
                break;
                
            case 'week':
                // Letzten 7 Tage
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $labels[] = date('D, j.n', strtotime($date));
                    
                    $value = $this->get_chart_value_for_date($date, $type);
                    $data[] = $value;
                }
                break;
                
            case 'month':
                // Letzten 30 Tage
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-{$i} days"));
                    $labels[] = date('j.n', strtotime($date));
                    
                    $value = $this->get_chart_value_for_date($date, $type);
                    $data[] = $value;
                }
                break;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'type' => $type,
            'period' => $period
        ];
    }
    
    /**
     * Export-Funktionen für verschiedene Formate
     * 
     * @param string $report_type Art des Berichts
     * @param string $format Export-Format (pdf, csv, excel)
     * @param array $filters Filter-Optionen
     * @return array Export-Informationen
     * @since 1.0.0
     */
    public function export_report(string $report_type, string $format = 'pdf', array $filters = []): array
    {
        try {
            $export_data = [];
            $filename = '';
            
            switch ($report_type) {
                case 'sales_summary':
                    $export_data = $this->prepare_sales_export($filters);
                    $filename = 'umsatz_bericht_' . date('Y-m-d_H-i');
                    break;
                    
                case 'inventory_analysis':
                    $export_data = $this->prepare_inventory_export($filters);
                    $filename = 'lager_analyse_' . date('Y-m-d_H-i');
                    break;
                    
                case 'waste_report':
                    $export_data = $this->prepare_waste_export($filters);
                    $filename = 'verlust_bericht_' . date('Y-m-d_H-i');
                    break;
                    
                case 'complete_analytics':
                    $export_data = $this->prepare_complete_export($filters);
                    $filename = 'vollstaendiger_bericht_' . date('Y-m-d_H-i');
                    break;
            }
            
            $file_path = $this->generate_export_file($export_data, $format, $filename);
            
            return [
                'success' => true,
                'file_path' => $file_path,
                'filename' => $filename . '.' . $format,
                'download_url' => $this->get_download_url($file_path)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * AJAX Handler für Dashboard-Statistiken
     * 
     * @since 1.0.0
     */
    public function ajax_get_dashboard_stats(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_view_reports')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'today');
        $stats = $this->get_dashboard_stats($period);
        
        wp_send_json_success($stats);
    }
    
    /**
     * AJAX Handler für Chart-Daten
     * 
     * @since 1.0.0
     */
    public function ajax_get_sales_chart_data(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_view_reports')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $type = sanitize_text_field($_POST['type'] ?? 'revenue');
        
        $chart_data = $this->get_sales_chart_data($period, $type);
        
        wp_send_json_success($chart_data);
    }
    
    /**
     * AJAX Handler für Report-Export
     * 
     * @since 1.0.0
     */
    public function ajax_export_report(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_export_data')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $format = sanitize_text_field($_POST['format'] ?? 'pdf');
        $filters = $_POST['filters'] ?? [];
        
        $result = $this->export_report($report_type, $format, $filters);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }
    
    // =======================
    // HELPER METHODEN
    // =======================
    
    /**
     * SQL Datums-Bedingung für Zeitraum generieren
     * 
     * @param string $period Zeitraum
     * @return string SQL WHERE Bedingung
     * @since 1.0.0
     */
    private function get_date_condition(string $period): string
    {
        switch ($period) {
            case 'today':
                return " AND DATE(created_at) = CURDATE()";
            case 'yesterday':
                return " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            case 'week':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'month':
                return " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case 'year':
                return " AND YEAR(created_at) = YEAR(CURDATE())";
            default:
                return "";
        }
    }
    
    /**
     * Vorherige Periode Bedingung
     * 
     * @param string $period Zeitraum
     * @return string SQL WHERE Bedingung
     * @since 1.0.0
     */
    private function get_previous_period_condition(string $period): string
    {
        switch ($period) {
            case 'today':
                return " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            case 'week':
                return " AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 14 DAY) AND DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case 'month':
                return " AND created_at BETWEEN DATE_SUB(NOW(), INTERVAL 60 DAY) AND DATE_SUB(NOW(), INTERVAL 30 DAY)";
            default:
                return "";
        }
    }
    
    /**
     * Prozentuale Veränderung berechnen
     * 
     * @param float $old_value Alter Wert
     * @param float $new_value Neuer Wert
     * @return float Prozentuale Veränderung
     * @since 1.0.0
     */
    private function calculate_percentage_change(float $old_value, float $new_value): float
    {
        if ($old_value == 0) {
            return $new_value > 0 ? 100.0 : 0.0;
        }
        
        return round((($new_value - $old_value) / $old_value) * 100, 2);
    }
    
    /**
     * Chart-Wert für spezifische Stunde abrufen
     * 
     * @param string $hour Stunde (YYYY-MM-DD HH:00:00)
     * @param string $type Datentyp
     * @return float Wert
     * @since 1.0.0
     */
    private function get_chart_value_for_hour(string $hour, string $type): float
    {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        $table_items = $wpdb->prefix . 'amp_sales_items';
        
        $start_hour = $hour;
        $end_hour = date('Y-m-d H:59:59', strtotime($hour));
        
        switch ($type) {
            case 'revenue':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COALESCE(SUM(total_gross), 0) 
                    FROM {$table_sessions} 
                    WHERE status = 'completed' 
                    AND created_at BETWEEN %s AND %s
                ", $start_hour, $end_hour));
                
            case 'sessions':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$table_sessions} 
                    WHERE status = 'completed' 
                    AND created_at BETWEEN %s AND %s
                ", $start_hour, $end_hour));
                
            case 'items':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COALESCE(SUM(si.quantity), 0) 
                    FROM {$table_items} si
                    JOIN {$table_sessions} ss ON si.session_id = ss.id
                    WHERE ss.status = 'completed' 
                    AND ss.created_at BETWEEN %s AND %s
                ", $start_hour, $end_hour));
        }
        
        return 0.0;
    }
    
    /**
     * Chart-Wert für spezifisches Datum abrufen
     * 
     * @param string $date Datum (YYYY-MM-DD)
     * @param string $type Datentyp
     * @return float Wert
     * @since 1.0.0
     */
    private function get_chart_value_for_date(string $date, string $type): float
    {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'amp_sales_sessions';
        $table_items = $wpdb->prefix . 'amp_sales_items';
        
        switch ($type) {
            case 'revenue':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COALESCE(SUM(total_gross), 0) 
                    FROM {$table_sessions} 
                    WHERE status = 'completed' 
                    AND DATE(created_at) = %s
                ", $date));
                
            case 'sessions':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) 
                    FROM {$table_sessions} 
                    WHERE status = 'completed' 
                    AND DATE(created_at) = %s
                ", $date));
                
            case 'items':
                return (float)$wpdb->get_var($wpdb->prepare("
                    SELECT COALESCE(SUM(si.quantity), 0) 
                    FROM {$table_items} si
                    JOIN {$table_sessions} ss ON si.session_id = ss.id
                    WHERE ss.status = 'completed' 
                    AND DATE(ss.created_at) = %s
                ", $date));
        }
        
        return 0.0;
    }
    
    /**
     * Verkaufs-Export vorbereiten
     * 
     * @param array $filters Filter-Optionen
     * @return array Export-Daten
     * @since 1.0.0
     */
    private function prepare_sales_export(array $filters): array
    {
        // Implementierung für Verkaufs-Export
        $period = $filters['period'] ?? 'month';
        $stats = $this->get_dashboard_stats($period);
        
        return [
            'title' => 'Umsatz-Bericht - ' . ucfirst($period),
            'generated_at' => date('d.m.Y H:i'),
            'data' => $stats['sales'],
            'products' => $stats['products']['top_sellers'],
            'kpis' => $stats['kpis']
        ];
    }
    
    /**
     * Lager-Export vorbereiten
     * 
     * @param array $filters Filter-Optionen
     * @return array Export-Daten
     * @since 1.0.0
     */
    private function prepare_inventory_export(array $filters): array
    {
        $stats = $this->get_dashboard_stats('month');
        
        return [
            'title' => 'Lager-Analyse',
            'generated_at' => date('d.m.Y H:i'),
            'data' => $stats['inventory'],
            'abc_analysis' => $stats['inventory']['abc_analysis'],
            'turnover' => $stats['inventory']['turnover']
        ];
    }
    
    /**
     * Verlust-Export vorbereiten
     * 
     * @param array $filters Filter-Optionen
     * @return array Export-Daten
     * @since 1.0.0
     */
    private function prepare_waste_export(array $filters): array
    {
        $period = $filters['period'] ?? 'month';
        $stats = $this->get_dashboard_stats($period);
        
        return [
            'title' => 'Verlust-Bericht - ' . ucfirst($period),
            'generated_at' => date('d.m.Y H:i'),
            'data' => $stats['waste'],
            'optimization' => $this->waste_manager->get_optimization_suggestions()
        ];
    }
    
    /**
     * Vollständigen Export vorbereiten
     * 
     * @param array $filters Filter-Optionen
     * @return array Export-Daten
     * @since 1.0.0
     */
    private function prepare_complete_export(array $filters): array
    {
        $period = $filters['period'] ?? 'month';
        $stats = $this->get_dashboard_stats($period);
        
        return [
            'title' => 'Vollständiger Business Report - ' . ucfirst($period),
            'generated_at' => date('d.m.Y H:i'),
            'executive_summary' => $stats['kpis'],
            'sales' => $stats['sales'],
            'inventory' => $stats['inventory'],
            'waste' => $stats['waste'],
            'products' => $stats['products'],
            'trends' => $stats['trends']
        ];
    }
    
    /**
     * Export-Datei generieren
     * 
     * @param array $data Export-Daten
     * @param string $format Format (pdf, csv, excel)
     * @param string $filename Dateiname ohne Erweiterung
     * @return string Pfad zur generierten Datei
     * @since 1.0.0
     */
    private function generate_export_file(array $data, string $format, string $filename): string
    {
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/amp-exports/';
        
        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
        }
        
        $file_path = $export_dir . $filename . '.' . $format;
        
        switch ($format) {
            case 'csv':
                $this->generate_csv_export($data, $file_path);
                break;
            case 'pdf':
                $this->generate_pdf_export($data, $file_path);
                break;
            case 'excel':
                $this->generate_excel_export($data, $file_path);
                break;
        }
        
        return $file_path;
    }
    
    /**
     * CSV Export generieren
     * 
     * @param array $data Export-Daten
     * @param string $file_path Dateipfad
     * @since 1.0.0
     */
    private function generate_csv_export(array $data, string $file_path): void
    {
        $fp = fopen($file_path, 'w');
        
        // UTF-8 BOM für Excel-Kompatibilität
        fwrite($fp, "\xEF\xBB\xBF");
        
        // Header
        fputcsv($fp, ['AutomatenManager Pro - ' . $data['title']], ';');
        fputcsv($fp, ['Generiert am: ' . $data['generated_at']], ';');
        fputcsv($fp, [], ';'); // Leerzeile
        
        // Daten je nach Typ
        foreach ($data as $key => $section) {
            if (is_array($section) && $key !== 'title' && $key !== 'generated_at') {
                fputcsv($fp, [strtoupper($key)], ';');
                
                if (is_array($section) && !empty($section)) {
                    $headers = array_keys($section[0]);
                    fputcsv($fp, $headers, ';');
                    
                    foreach ($section as $row) {
                        fputcsv($fp, array_values($row), ';');
                    }
                }
                
                fputcsv($fp, [], ';'); // Leerzeile
            }
        }
        
        fclose($fp);
    }
    
    /**
     * PDF Export generieren (vereinfacht)
     * 
     * @param array $data Export-Daten
     * @param string $file_path Dateipfad
     * @since 1.0.0
     */
    private function generate_pdf_export(array $data, string $file_path): void
    {
        // HTML für PDF vorbereiten
        $html = $this->prepare_pdf_html($data);
        
        // Einfache HTML-zu-PDF Konvertierung
        // In Produktionsumgebung sollte eine echte PDF-Library verwendet werden
        file_put_contents($file_path, $html);
    }
    
    /**
     * HTML für PDF vorbereiten
     * 
     * @param array $data Export-Daten
     * @return string HTML-Inhalt
     * @since 1.0.0
     */
    private function prepare_pdf_html(array $data): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo esc_html($data['title']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .section { margin-bottom: 20px; }
                .section h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                .kpi { display: inline-block; margin: 10px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($data['title']); ?></h1>
                <p>Generiert am: <?php echo esc_html($data['generated_at']); ?></p>
            </div>
            
            <?php if (isset($data['kpis'])): ?>
            <div class="section">
                <h2>Key Performance Indicators</h2>
                <?php foreach ($data['kpis'] as $key => $value): ?>
                    <div class="kpi">
                        <strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong>
                        <?php echo esc_html($value); ?>
                        <?php if (strpos($key, 'ratio') !== false || strpos($key, 'margin') !== false): ?>%<?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Weitere Sektionen würden hier folgen -->
            
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Excel Export generieren (Platzhalter)
     * 
     * @param array $data Export-Daten
     * @param string $file_path Dateipfad
     * @since 1.0.0
     */
    private function generate_excel_export(array $data, string $file_path): void
    {
        // Für Excel würde normalerweise PhpSpreadsheet verwendet
        // Hier als CSV mit .xlsx Endung für Kompatibilität
        $this->generate_csv_export($data, str_replace('.xlsx', '.csv', $file_path));
        rename(str_replace('.xlsx', '.csv', $file_path), $file_path);
    }
    
    /**
     * Download-URL für Export-Datei generieren
     * 
     * @param string $file_path Dateipfad
     * @return string Download-URL
     * @since 1.0.0
     */
    private function get_download_url(string $file_path): string
    {
        $upload_dir = wp_upload_dir();
        $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path);
        
        return $file_url;
    }
    
    /**
     * Cron-Job: Tägliche Berichte generieren
     * 
     * @since 1.0.0
     */
    public function generate_daily_reports(): void
    {
        // Tägliche automatische Berichte
        $stats = $this->get_dashboard_stats('today');
        
        // Log für Debugging
        error_log('AMP Daily Reports: ' . print_r($stats, true));
    }
    
    /**
     * Cron-Job: Wöchentliche Berichte generieren
     * 
     * @since 1.0.0
     */
    public function generate_weekly_reports(): void
    {
        // Wöchentliche Berichte
        $stats = $this->get_dashboard_stats('week');
        
        // Hier würde normalerweise eine E-Mail versendet werden
        error_log('AMP Weekly Reports: ' . print_r($stats, true));
    }
    
    /**
     * Cron-Job: Monatliche Berichte generieren
     * 
     * @since 1.0.0
     */
    public function generate_monthly_reports(): void
    {
        // Monatliche Executive Reports
        $stats = $this->get_dashboard_stats('month');
        
        // Executive Summary erstellen
        $executive_data = [
            'period' => 'month',
            'kpis' => $stats['kpis'],
            'trends' => $stats['trends'],
            'recommendations' => $this->generate_recommendations($stats)
        ];
        
        error_log('AMP Monthly Executive Report: ' . print_r($executive_data, true));
    }
    
    /**
     * Business-Empfehlungen basierend auf Daten generieren
     * 
     * @param array $stats Vollständige Statistiken
     * @return array Empfehlungen
     * @since 1.0.0
     */
    private function generate_recommendations(array $stats): array
    {
        $recommendations = [];
        
        // Lager-Empfehlungen
        if ($stats['inventory']['basic']['low_stock_count'] > 0) {
            $recommendations[] = [
                'type' => 'warning',
                'category' => 'Lager',
                'message' => $stats['inventory']['basic']['low_stock_count'] . ' Artikel haben Niedrigbestand. Nachbestellung empfohlen.'
            ];
        }
        
        // Verlust-Empfehlungen
        if ($stats['kpis']['waste_ratio'] > 5) {
            $recommendations[] = [
                'type' => 'alert',
                'category' => 'Verluste',
                'message' => 'Verlustrate von ' . $stats['kpis']['waste_ratio'] . '% ist überdurchschnittlich hoch. Qualitätsprüfung empfohlen.'
            ];
        }
        
        // Umsatz-Empfehlungen
        if ($stats['trends']['revenue_change'] < 0) {
            $recommendations[] = [
                'type' => 'info',
                'category' => 'Umsatz',
                'message' => 'Umsatzrückgang von ' . abs($stats['trends']['revenue_change']) . '%. Marketing-Maßnahmen prüfen.'
            ];
        }
        
        return $recommendations;
    }
}