<?php
/**
 * Sales Manager - Komplette Verkaufs-Abwicklung und Analytics
 * 
 * Verwaltet alle Verkaufs-Operationen inklusive:
 * - Session-Finalisierung und Zahlungsabwicklung
 * - PDF-Beleg Generierung mit Firmenlogo
 * - Automatische E-Mail-Benachrichtigungen
 * - Umsatz-Analytics und Business Intelligence
 * - MwSt-konforme Berechnungen
 * - Lagerbestand-Updates nach Verkauf
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Sales
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Sales;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Products\AMP_Products_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sales Manager Class
 * 
 * Zentrale Verwaltung aller Verkaufs-Operationen von Session-Finalisierung
 * bis Analytics und Business Intelligence Reporting
 * 
 * @since 1.0.0
 */
class AMP_Sales_Manager
{
    /**
     * Database Manager Instance
     * 
     * @var AMP_Database_Manager
     */
    private AMP_Database_Manager $database;
    
    /**
     * Products Manager Instance
     * 
     * @var AMP_Products_Manager
     */
    private AMP_Products_Manager $products_manager;
    
    /**
     * Tabellen-Namen
     * 
     * @var array
     */
    private array $tables;
    
    /**
     * Verfügbare Zahlungsarten
     * 
     * @var array
     */
    private array $payment_methods = [
        'cash' => [
            'label' => 'Bargeld',
            'icon' => '💵',
            'requires_change' => true,
            'processing_fee' => 0.0
        ],
        'card' => [
            'label' => 'EC-Karte',
            'icon' => '💳',
            'requires_change' => false,
            'processing_fee' => 0.0
        ],
        'mixed' => [
            'label' => 'Bargeld + EC',
            'icon' => '💵💳',
            'requires_change' => true,
            'processing_fee' => 0.0
        ]
    ];
    
    /**
     * PDF-Konfiguration
     * 
     * @var array
     */
    private array $pdf_config = [
        'page_size' => 'A4',
        'orientation' => 'portrait',
        'margin_top' => 20,
        'margin_bottom' => 20,
        'margin_left' => 15,
        'margin_right' => 15,
        'font_family' => 'Arial',
        'font_size' => 10
    ];

    /**
     * Constructor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        global $wpdb;
        
        $this->database = new AMP_Database_Manager();
        $this->products_manager = new AMP_Products_Manager();
        
        $this->tables = [
            'sessions' => $wpdb->prefix . 'amp_sales_sessions',
            'items' => $wpdb->prefix . 'amp_sales_items',
            'products' => $wpdb->prefix . 'amp_products',
            'stock_movements' => $wpdb->prefix . 'amp_stock_movements',
            'email_log' => $wpdb->prefix . 'amp_email_log'
        ];
        
        $this->init_hooks();
    }
    
    /**
     * Initialisiert WordPress Hooks
     * 
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        // AJAX Handlers für Sales-Operationen
        add_action('wp_ajax_amp_finalize_sale', [$this, 'ajax_finalize_sale']);
        add_action('wp_ajax_amp_process_payment', [$this, 'ajax_process_payment']);
        add_action('wp_ajax_amp_void_sale', [$this, 'ajax_void_sale']);
        add_action('wp_ajax_amp_get_sale_details', [$this, 'ajax_get_sale_details']);
        
        // Analytics und Reporting
        add_action('wp_ajax_amp_get_sales_analytics', [$this, 'ajax_get_sales_analytics']);
        add_action('wp_ajax_amp_get_sales_report', [$this, 'ajax_get_sales_report']);
        add_action('wp_ajax_amp_export_sales_data', [$this, 'ajax_export_sales_data']);
        
        // PDF und E-Mail
        add_action('wp_ajax_amp_generate_receipt_pdf', [$this, 'ajax_generate_receipt_pdf']);
        add_action('wp_ajax_amp_resend_receipt_email', [$this, 'ajax_resend_receipt_email']);
        
        // Scheduled Tasks
        add_action('amp_daily_sales_summary', [$this, 'send_daily_sales_summary']);
    }
    
    /**
     * Finalisiert eine Verkaufs-Session
     * 
     * @param array $session_data Session-Daten vom Scanner
     * @param string $payment_method Zahlungsart
     * @param array $payment_details Zahlungsdetails
     * @return array Ergebnis der Finalisierung
     * @since 1.0.0
     */
    public function finalize_sale(array $session_data, string $payment_method, array $payment_details = []): array
    {
        try {
            global $wpdb;
            
            // Validierung der Session-Daten
            $validation = $this->validate_session_data($session_data);
            if (!$validation['success']) {
                return $validation;
            }
            
            // Zahlungsart validieren
            if (!array_key_exists($payment_method, $this->payment_methods)) {
                return [
                    'success' => false,
                    'message' => 'Ungültige Zahlungsart: ' . $payment_method
                ];
            }
            
            // Bestandsprüfung für alle Artikel
            $stock_check = $this->validate_stock_availability($session_data['items']);
            if (!$stock_check['success']) {
                return $stock_check;
            }
            
            $wpdb->query('START TRANSACTION');
            
            // 1. Sales Session in Datenbank erstellen
            $session_id = $this->create_sales_session($session_data, $payment_method, $payment_details);
            if (!$session_id) {
                $wpdb->query('ROLLBACK');
                return [
                    'success' => false,
                    'message' => 'Fehler beim Erstellen der Verkaufs-Session'
                ];
            }
            
            // 2. Sales Items erstellen
            $items_result = $this->create_sales_items($session_id, $session_data['items']);
            if (!$items_result['success']) {
                $wpdb->query('ROLLBACK');
                return $items_result;
            }
            
            // 3. Lagerbestände aktualisieren
            $stock_result = $this->update_product_stocks($session_data['items'], $session_id);
            if (!$stock_result['success']) {
                $wpdb->query('ROLLBACK');
                return $stock_result;
            }
            
            $wpdb->query('COMMIT');
            
            // 4. Session-Details für weitere Verarbeitung laden
            $completed_session = $this->get_session_details($session_id);
            
            // 5. PDF-Beleg generieren
            $pdf_result = $this->generate_receipt_pdf($completed_session);
            
            // 6. E-Mail-Benachrichtigung senden
            $email_result = $this->send_sales_notification($completed_session, $pdf_result['pdf_path'] ?? null);
            
            return [
                'success' => true,
                'message' => 'Verkauf erfolgreich abgeschlossen',
                'session_id' => $session_id,
                'session' => $completed_session,
                'pdf_url' => $pdf_result['pdf_url'] ?? null,
                'email_sent' => $email_result['success'] ?? false,
                'summary' => [
                    'total_items' => $completed_session['total_items'],
                    'total_amount' => $completed_session['total_gross'],
                    'payment_method' => $this->payment_methods[$payment_method]['label'],
                    'receipt_number' => $this->generate_receipt_number($session_id)
                ]
            ];
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => 'Unerwarteter Fehler beim Verkauf: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Erstellt Sales Session in Datenbank
     * 
     * @param array $session_data Session-Daten
     * @param string $payment_method Zahlungsart
     * @param array $payment_details Zahlungsdetails
     * @return int|false Session ID oder false
     * @since 1.0.0
     */
    private function create_sales_session(array $session_data, string $payment_method, array $payment_details)
    {
        global $wpdb;
        
        $totals = $session_data['totals'];
        $now = current_time('mysql');
        
        $session_data_db = [
            'session_start' => $session_data['started_at'] ?? $now,
            'session_end' => $now,
            'user_id' => get_current_user_id(),
            'total_items' => $totals['item_count'],
            'subtotal_net' => $totals['subtotal_net'],
            'total_vat' => $totals['total_vat'],
            'total_deposit' => $totals['total_deposit'],
            'total_gross' => $totals['total_gross'],
            'payment_method' => $payment_method,
            'payment_received' => floatval($payment_details['amount_received'] ?? $totals['total_gross']),
            'change_given' => floatval($payment_details['change_given'] ?? 0),
            'notes' => sanitize_textarea_field($payment_details['notes'] ?? ''),
            'created_at' => $now
        ];
        
        $result = $wpdb->insert(
            $this->tables['sessions'],
            $session_data_db,
            ['%s', '%s', '%d', '%d', '%f', '%f', '%f', '%f', '%s', '%f', '%f', '%s', '%s']
        );
        
        if ($result !== false) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Erstellt Sales Items für Session
     * 
     * @param int $session_id Session ID
     * @param array $items Items Array
     * @return array Ergebnis
     * @since 1.0.0
     */
    private function create_sales_items(int $session_id, array $items): array
    {
        global $wpdb;
        
        foreach ($items as $item) {
            $unit_price = floatval($item['unit_price']);
            $unit_deposit = floatval($item['unit_deposit']);
            $quantity = intval($item['quantity']);
            $vat_rate = intval($item['vat_rate']);
            
            // Berechnungen für Einzelposten
            $line_gross = $quantity * $unit_price;
            $line_net = $line_gross / (1 + ($vat_rate / 100));
            $line_vat = $line_gross - $line_net;
            $line_deposit = $quantity * $unit_deposit;
            $line_total = $line_gross + $line_deposit;
            
            $item_data = [
                'session_id' => $session_id,
                'product_id' => intval($item['product_id']),
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'unit_deposit' => $unit_deposit,
                'vat_rate' => $vat_rate,
                'line_net' => round($line_net, 2),
                'line_vat' => round($line_vat, 2),
                'line_deposit' => round($line_deposit, 2),
                'line_total' => round($line_total, 2),
                'product_name' => sanitize_text_field($item['name']),
                'product_barcode' => sanitize_text_field($item['barcode']),
                'created_at' => current_time('mysql')
            ];
            
            $result = $wpdb->insert(
                $this->tables['items'],
                $item_data,
                ['%d', '%d', '%d', '%f', '%f', '%d', '%f', '%f', '%f', '%f', '%s', '%s', '%s']
            );
            
            if ($result === false) {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Speichern des Artikels: ' . $item['name']
                ];
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Aktualisiert Produktbestände nach Verkauf
     * 
     * @param array $items Verkaufte Items
     * @param int $session_id Session ID für Referenz
     * @return array Ergebnis
     * @since 1.0.0
     */
    private function update_product_stocks(array $items, int $session_id): array
    {
        foreach ($items as $item) {
            $product_id = intval($item['product_id']);
            $quantity_sold = intval($item['quantity']);
            
            // Aktuelles Produkt laden
            $product = $this->products_manager->get_product_by_id($product_id);
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produkt nicht gefunden: ' . $product_id
                ];
            }
            
            $old_stock = $product['current_stock'];
            $new_stock = $old_stock - $quantity_sold;
            
            // Produkt-Bestand aktualisieren
            $update_result = $this->products_manager->update_product($product_id, [
                'current_stock' => $new_stock
            ]);
            
            if (!$update_result['success']) {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Aktualisieren des Bestands für: ' . $product['name']
                ];
            }
            
            // Stock-Movement protokollieren
            $movement_result = $this->log_stock_movement(
                $product_id,
                'sale',
                $quantity_sold,
                $old_stock,
                $new_stock,
                "Verkauf Session #{$session_id}"
            );
            
            if (!$movement_result) {
                return [
                    'success' => false,
                    'message' => 'Fehler beim Protokollieren der Lagerbewegung'
                ];
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Holt detaillierte Session-Informationen
     * 
     * @param int $session_id Session ID
     * @return array|false Session-Details oder false
     * @since 1.0.0
     */
    public function get_session_details(int $session_id)
    {
        global $wpdb;
        
        // Session-Grunddaten
        $session_sql = "
            SELECT s.*, u.display_name as user_name
            FROM {$this->tables['sessions']} s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.id = %d
        ";
        
        $session = $wpdb->get_row($wpdb->prepare($session_sql, $session_id), ARRAY_A);
        
        if (!$session) {
            return false;
        }
        
        // Session-Items
        $items_sql = "
            SELECT si.*, p.name as current_product_name, p.category_id,
                   c.name as category_name
            FROM {$this->tables['items']} si
            LEFT JOIN {$this->tables['products']} p ON si.product_id = p.id
            LEFT JOIN {$wpdb->prefix}amp_categories c ON p.category_id = c.id
            WHERE si.session_id = %d
            ORDER BY si.id
        ";
        
        $items = $wpdb->get_results($wpdb->prepare($items_sql, $session_id), ARRAY_A);
        
        // Session anreichern
        $session['items'] = $items;
        $session['receipt_number'] = $this->generate_receipt_number($session_id);
        $session['payment_method_label'] = $this->payment_methods[$session['payment_method']]['label'] ?? $session['payment_method'];
        $session['formatted_date'] = date_i18n('d.m.Y H:i', strtotime($session['session_end']));
        
        // Zusätzliche Berechnungen
        $session['profit_margin'] = $this->calculate_session_profit($items);
        $session['vat_breakdown'] = $this->calculate_vat_breakdown($items);
        
        return $session;
    }
    
    /**
     * Generiert PDF-Beleg
     * 
     * @param array $session Session-Details
     * @return array PDF-Ergebnis
     * @since 1.0.0
     */
    public function generate_receipt_pdf(array $session): array
    {
        try {
            // PDF-Bibliothek prüfen
            if (!class_exists('TCPDF')) {
                return [
                    'success' => false,
                    'message' => 'TCPDF Bibliothek nicht verfügbar'
                ];
            }
            
            // PDF erstellen
            $pdf = new \TCPDF(
                $this->pdf_config['orientation'],
                'mm',
                $this->pdf_config['page_size'],
                true,
                'UTF-8',
                false
            );
            
            // PDF-Metadaten
            $pdf->SetCreator('AutomatenManager Pro');
            $pdf->SetAuthor(get_bloginfo('name'));
            $pdf->SetTitle('Verkaufsbeleg #' . $session['receipt_number']);
            $pdf->SetSubject('Verkaufsbeleg');
            
            // Ränder setzen
            $pdf->SetMargins(
                $this->pdf_config['margin_left'],
                $this->pdf_config['margin_top'],
                $this->pdf_config['margin_right']
            );
            $pdf->SetAutoPageBreak(true, $this->pdf_config['margin_bottom']);
            
            // Header/Footer deaktivieren
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Seite hinzufügen
            $pdf->AddPage();
            
            // Content generieren
            $html_content = $this->generate_receipt_html($session);
            
            // HTML zu PDF
            $pdf->writeHTML($html_content, true, false, true, false, '');
            
            // PDF speichern
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/amp-receipts/';
            
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }
            
            $pdf_filename = 'receipt_' . $session['id'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
            $pdf_path = $pdf_dir . $pdf_filename;
            $pdf_url = $upload_dir['baseurl'] . '/amp-receipts/' . $pdf_filename;
            
            $pdf->Output($pdf_path, 'F');
            
            // PDF-Pfad in Session aktualisieren
            $this->update_session_pdf_path($session['id'], $pdf_path);
            
            return [
                'success' => true,
                'pdf_path' => $pdf_path,
                'pdf_url' => $pdf_url,
                'filename' => $pdf_filename
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler bei PDF-Generierung: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generiert HTML-Content für PDF-Beleg
     * 
     * @param array $session Session-Details
     * @return string HTML-Content
     * @since 1.0.0
     */
    private function generate_receipt_html(array $session): string
    {
        $company_name = get_bloginfo('name');
        $logo_url = get_option('amp_company_logo_url', '');
        
        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10pt; }
            .header { text-align: center; margin-bottom: 20px; }
            .logo { max-width: 200px; max-height: 80px; }
            .company-name { font-size: 16pt; font-weight: bold; margin-top: 10px; }
            .receipt-title { font-size: 14pt; font-weight: bold; margin: 20px 0; }
            .receipt-info { margin-bottom: 20px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .items-table th, .items-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .items-table th { background-color: #f5f5f5; font-weight: bold; }
            .text-right { text-align: right; }
            .totals-table { width: 60%; margin-left: auto; border-collapse: collapse; }
            .totals-table td { padding: 5px; border-top: 1px solid #ddd; }
            .total-final { font-weight: bold; font-size: 12pt; border-top: 2px solid #333; }
            .footer { margin-top: 30px; text-align: center; font-size: 8pt; color: #666; }
        </style>';
        
        $html .= '<div class="header">';
        if ($logo_url) {
            $html .= '<img src="' . esc_url($logo_url) . '" class="logo" alt="Logo">';
        }
        $html .= '<div class="company-name">' . esc_html($company_name) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="receipt-title">VERKAUFSBELEG</div>';
        
        $html .= '<div class="receipt-info">';
        $html .= '<strong>Beleg-Nr.:</strong> ' . esc_html($session['receipt_number']) . '<br>';
        $html .= '<strong>Datum:</strong> ' . esc_html($session['formatted_date']) . '<br>';
        $html .= '<strong>Verkäufer:</strong> ' . esc_html($session['user_name']) . '<br>';
        $html .= '<strong>Zahlungsart:</strong> ' . esc_html($session['payment_method_label']) . '<br>';
        $html .= '</div>';
        
        $html .= '<table class="items-table">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th>Artikel</th>';
        $html .= '<th>Menge</th>';
        $html .= '<th class="text-right">Einzelpreis</th>';
        $html .= '<th class="text-right">Pfand</th>';
        $html .= '<th class="text-right">Summe</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        foreach ($session['items'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . esc_html($item['product_name']) . '</td>';
            $html .= '<td>' . intval($item['quantity']) . '</td>';
            $html .= '<td class="text-right">' . number_format($item['unit_price'], 2, ',', '.') . ' €</td>';
            $html .= '<td class="text-right">' . number_format($item['unit_deposit'], 2, ',', '.') . ' €</td>';
            $html .= '<td class="text-right">' . number_format($item['line_total'], 2, ',', '.') . ' €</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '<table class="totals-table">';
        $html .= '<tr><td>Netto-Betrag:</td><td class="text-right">' . number_format($session['subtotal_net'], 2, ',', '.') . ' €</td></tr>';
        $html .= '<tr><td>MwSt (' . $session['vat_breakdown']['rate'] . '%):</td><td class="text-right">' . number_format($session['total_vat'], 2, ',', '.') . ' €</td></tr>';
        $html .= '<tr><td>Pfand:</td><td class="text-right">' . number_format($session['total_deposit'], 2, ',', '.') . ' €</td></tr>';
        $html .= '<tr class="total-final"><td>GESAMT:</td><td class="text-right">' . number_format($session['total_gross'], 2, ',', '.') . ' €</td></tr>';
        $html .= '</table>';
        
        if ($session['payment_method'] === 'cash' && $session['change_given'] > 0) {
            $html .= '<div style="margin-top: 20px;">';
            $html .= '<strong>Erhalten:</strong> ' . number_format($session['payment_received'], 2, ',', '.') . ' €<br>';
            $html .= '<strong>Rückgeld:</strong> ' . number_format($session['change_given'], 2, ',', '.') . ' €';
            $html .= '</div>';
        }
        
        $html .= '<div class="footer">';
        $html .= 'Vielen Dank für Ihren Einkauf!<br>';
        $html .= 'Erstellt mit AutomatenManager Pro am ' . date_i18n('d.m.Y H:i');
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Sendet Verkaufs-Benachrichtigung per E-Mail
     * 
     * @param array $session Session-Details
     * @param string|null $pdf_path Pfad zur PDF-Datei
     * @return array E-Mail-Ergebnis
     * @since 1.0.0
     */
    public function send_sales_notification(array $session, ?string $pdf_path = null): array
    {
        try {
            $to = get_option('amp_sales_email', get_option('admin_email'));
            $subject = 'Verkaufsbericht - ' . $session['formatted_date'];
            
            $message = $this->generate_sales_email_content($session);
            
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            $attachments = [];
            
            if ($pdf_path && file_exists($pdf_path)) {
                $attachments[] = $pdf_path;
            }
            
            $sent = wp_mail($to, $subject, $message, $headers, $attachments);
            
            // E-Mail-Log erstellen
            $this->log_email_sent('sales_report', $to, $subject, $sent);
            
            return [
                'success' => $sent,
                'message' => $sent ? 'E-Mail erfolgreich versendet' : 'Fehler beim E-Mail-Versand'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler beim E-Mail-Versand: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generiert E-Mail-Content für Verkaufs-Benachrichtigung
     * 
     * @param array $session Session-Details
     * @return string HTML-E-Mail-Content
     * @since 1.0.0
     */
    private function generate_sales_email_content(array $session): string
    {
        $company_name = get_bloginfo('name');
        
        $html = '<html><body style="font-family: Arial, sans-serif;">';
        $html .= '<h2>🛒 Verkaufsbericht</h2>';
        $html .= '<p><strong>Verkauf abgeschlossen:</strong> ' . $session['formatted_date'] . '</p>';
        
        $html .= '<table style="border-collapse: collapse; width: 100%; margin-bottom: 20px;">';
        $html .= '<tr style="background-color: #f5f5f5;"><th style="border: 1px solid #ddd; padding: 8px;">Detail</th><th style="border: 1px solid #ddd; padding: 8px;">Wert</th></tr>';
        $html .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">Beleg-Nr.</td><td style="border: 1px solid #ddd; padding: 8px;">' . $session['receipt_number'] . '</td></tr>';
        $html .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">Verkäufer</td><td style="border: 1px solid #ddd; padding: 8px;">' . $session['user_name'] . '</td></tr>';
        $html .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">Zahlungsart</td><td style="border: 1px solid #ddd; padding: 8px;">' . $session['payment_method_label'] . '</td></tr>';
        $html .= '<tr><td style="border: 1px solid #ddd; padding: 8px;">Artikel-Anzahl</td><td style="border: 1px solid #ddd; padding: 8px;">' . $session['total_items'] . '</td></tr>';
        $html .= '<tr style="background-color: #e8f5e8;"><td style="border: 1px solid #ddd; padding: 8px;"><strong>GESAMTBETRAG</strong></td><td style="border: 1px solid #ddd; padding: 8px;"><strong>' . number_format($session['total_gross'], 2, ',', '.') . ' €</strong></td></tr>';
        $html .= '</table>';
        
        $html .= '<h3>Verkaufte Artikel:</h3>';
        $html .= '<ul>';
        foreach ($session['items'] as $item) {
            $html .= '<li>' . $item['quantity'] . 'x ' . $item['product_name'] . ' (je ' . number_format($item['unit_price'], 2, ',', '.') . ' €) = ' . number_format($item['line_total'], 2, ',', '.') . ' €</li>';
        }
        $html .= '</ul>';
        
        $html .= '<h3>Finanzübersicht:</h3>';
        $html .= '<ul>';
        $html .= '<li>Warenwert (Netto): ' . number_format($session['subtotal_net'], 2, ',', '.') . ' €</li>';
        $html .= '<li>Mehrwertsteuer: ' . number_format($session['total_vat'], 2, ',', '.') . ' €</li>';
        $html .= '<li>Pfand: ' . number_format($session['total_deposit'], 2, ',', '.') . ' €</li>';
        $html .= '<li><strong>Endsumme: ' . number_format($session['total_gross'], 2, ',', '.') . ' €</strong></li>';
        $html .= '</ul>';
        
        $html .= '<p style="color: #666; font-size: 12px; margin-top: 30px;">PDF-Beleg im Anhang.<br>Erstellt mit AutomatenManager Pro</p>';
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Holt Verkaufs-Analytics für Dashboard
     * 
     * @param array $params Filter-Parameter
     * @return array Analytics-Daten
     * @since 1.0.0
     */
    public function get_sales_analytics(array $params = []): array
    {
        global $wpdb;
        
        // Default-Parameter
        $defaults = [
            'period' => 'today', // today, week, month, year, custom
            'start_date' => '',
            'end_date' => '',
            'include_details' => true
        ];
        
        $params = wp_parse_args($params, $defaults);
        
        // Datums-Filter basierend auf Periode
        $date_filter = $this->build_date_filter($params);
        
        // Grundlegende Sales-Statistiken
        $stats_sql = "
            SELECT 
                COUNT(*) as total_sessions,
                SUM(total_items) as total_items_sold,
                SUM(subtotal_net) as total_net,
                SUM(total_vat) as total_vat,
                SUM(total_deposit) as total_deposit,
                SUM(total_gross) as total_gross,
                AVG(total_gross) as avg_transaction_value,
                MIN(total_gross) as min_transaction,
                MAX(total_gross) as max_transaction
            FROM {$this->tables['sessions']}
            WHERE {$date_filter['where_clause']}
        ";
        
        $stats = $wpdb->get_row($wpdb->prepare($stats_sql, $date_filter['values']), ARRAY_A);
        
        // Zahlungsarten-Breakdown
        $payment_methods_sql = "
            SELECT 
                payment_method,
                COUNT(*) as transaction_count,
                SUM(total_gross) as total_amount,
                AVG(total_gross) as avg_amount
            FROM {$this->tables['sessions']}
            WHERE {$date_filter['where_clause']}
            GROUP BY payment_method
            ORDER BY total_amount DESC
        ";
        
        $payment_breakdown = $wpdb->get_results($wpdb->prepare($payment_methods_sql, $date_filter['values']), ARRAY_A);
        
        // Top-Selling Products
        $top_products_sql = "
            SELECT 
                si.product_name,
                si.product_barcode,
                SUM(si.quantity) as total_quantity,
                SUM(si.line_total) as total_revenue,
                AVG(si.unit_price) as avg_price,
                COUNT(DISTINCT si.session_id) as transaction_count
            FROM {$this->tables['items']} si
            INNER JOIN {$this->tables['sessions']} s ON si.session_id = s.id
            WHERE {$date_filter['where_clause']}
            GROUP BY si.product_id, si.product_name, si.product_barcode
            ORDER BY total_quantity DESC
            LIMIT 10
        ";
        
        $top_products = $wpdb->get_results($wpdb->prepare($top_products_sql, $date_filter['values']), ARRAY_A);
        
        // Hourly Sales (für heute oder diese Woche)
        $hourly_sales = [];
        if (in_array($params['period'], ['today', 'week'])) {
            $hourly_sql = "
                SELECT 
                    HOUR(session_end) as hour,
                    COUNT(*) as transaction_count,
                    SUM(total_gross) as hourly_revenue
                FROM {$this->tables['sessions']}
                WHERE {$date_filter['where_clause']}
                GROUP BY HOUR(session_end)
                ORDER BY hour
            ";
            
            $hourly_sales = $wpdb->get_results($wpdb->prepare($hourly_sql, $date_filter['values']), ARRAY_A);
        }
        
        return [
            'success' => true,
            'period' => $params['period'],
            'date_range' => $date_filter['label'],
            'summary' => [
                'total_sessions' => intval($stats['total_sessions']),
                'total_items_sold' => intval($stats['total_items_sold']),
                'total_revenue' => floatval($stats['total_gross']),
                'average_transaction' => floatval($stats['avg_transaction_value']),
                'min_transaction' => floatval($stats['min_transaction']),
                'max_transaction' => floatval($stats['max_transaction'])
            ],
            'financial' => [
                'net_sales' => floatval($stats['total_net']),
                'vat_collected' => floatval($stats['total_vat']),
                'deposit_collected' => floatval($stats['total_deposit']),
                'gross_revenue' => floatval($stats['total_gross'])
            ],
            'payment_methods' => $payment_breakdown,
            'top_products' => $top_products,
            'hourly_breakdown' => $hourly_sales
        ];
    }
    
    /**
     * Erstellt Datums-Filter für Analytics
     * 
     * @param array $params Parameter
     * @return array Filter-Informationen
     * @since 1.0.0
     */
    private function build_date_filter(array $params): array
    {
        $where_clause = '1=1';
        $values = [];
        $label = '';
        
        switch ($params['period']) {
            case 'today':
                $where_clause = 'DATE(session_end) = CURDATE()';
                $label = 'Heute';
                break;
                
            case 'week':
                $where_clause = 'session_end >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
                $label = 'Letzte 7 Tage';
                break;
                
            case 'month':
                $where_clause = 'session_end >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
                $label = 'Letzte 30 Tage';
                break;
                
            case 'year':
                $where_clause = 'YEAR(session_end) = YEAR(CURDATE())';
                $label = 'Dieses Jahr';
                break;
                
            case 'custom':
                if (!empty($params['start_date']) && !empty($params['end_date'])) {
                    $where_clause = 'DATE(session_end) BETWEEN %s AND %s';
                    $values = [$params['start_date'], $params['end_date']];
                    $label = $params['start_date'] . ' bis ' . $params['end_date'];
                }
                break;
        }
        
        return [
            'where_clause' => $where_clause,
            'values' => $values,
            'label' => $label
        ];
    }
    
    /**
     * Validiert Session-Daten vor Finalisierung
     * 
     * @param array $session_data Session-Daten
     * @return array Validierungsergebnis
     * @since 1.0.0
     */
    private function validate_session_data(array $session_data): array
    {
        $errors = [];
        
        // Items prüfen
        if (empty($session_data['items'])) {
            $errors[] = 'Keine Artikel im Warenkorb';
        }
        
        // Totals prüfen
        if (empty($session_data['totals']) || $session_data['totals']['total_gross'] <= 0) {
            $errors[] = 'Ungültige Gesamtsumme';
        }
        
        // Einzelne Items validieren
        foreach ($session_data['items'] as $index => $item) {
            if (empty($item['product_id']) || $item['quantity'] <= 0) {
                $errors[] = "Ungültiger Artikel an Position " . ($index + 1);
            }
        }
        
        if (empty($errors)) {
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'message' => 'Validierungsfehler: ' . implode(', ', $errors),
                'errors' => $errors
            ];
        }
    }
    
    /**
     * Prüft Verfügbarkeit aller Artikel im Warenkorb
     * 
     * @param array $items Warenkorb-Items
     * @return array Prüfungsergebnis
     * @since 1.0.0
     */
    private function validate_stock_availability(array $items): array
    {
        $insufficient_stock = [];
        
        foreach ($items as $item) {
            $product = $this->products_manager->get_product_by_id($item['product_id']);
            
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Produkt nicht gefunden: ' . $item['name']
                ];
            }
            
            if ($product['current_stock'] < $item['quantity']) {
                $insufficient_stock[] = [
                    'name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $product['current_stock']
                ];
            }
        }
        
        if (!empty($insufficient_stock)) {
            return [
                'success' => false,
                'error' => 'insufficient_stock',
                'message' => 'Nicht genügend Bestand für einige Artikel',
                'insufficient_items' => $insufficient_stock
            ];
        }
        
        return ['success' => true];
    }
    
    /**
     * Weitere Helper-Methoden für PDF-Updates, E-Mail-Logging, etc.
     */
    
    /**
     * Protokolliert Lagerbewegung
     * 
     * @param int $product_id Produkt ID
     * @param string $movement_type Art der Bewegung
     * @param int $quantity Menge
     * @param int $previous_stock Vorheriger Bestand
     * @param int $new_stock Neuer Bestand
     * @param string $reason Grund
     * @return bool Erfolg
     * @since 1.0.0
     */
    private function log_stock_movement(int $product_id, string $movement_type, int $quantity, int $previous_stock, int $new_stock, string $reason): bool
    {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->tables['stock_movements'],
            [
                'product_id' => $product_id,
                'movement_type' => $movement_type,
                'quantity' => $quantity,
                'previous_stock' => $previous_stock,
                'new_stock' => $new_stock,
                'reason' => $reason,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%d', '%d', '%d', '%s', '%d', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Protokolliert gesendete E-Mail
     * 
     * @param string $email_type E-Mail-Typ
     * @param string $recipient Empfänger
     * @param string $subject Betreff
     * @param bool $success Erfolg
     * @since 1.0.0
     */
    private function log_email_sent(string $email_type, string $recipient, string $subject, bool $success): void
    {
        global $wpdb;
        
        $wpdb->insert(
            $this->tables['email_log'],
            [
                'email_type' => $email_type,
                'recipient' => $recipient,
                'subject' => $subject,
                'sent_at' => current_time('mysql'),
                'status' => $success ? 'sent' : 'failed'
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Aktualisiert PDF-Pfad in Session
     * 
     * @param int $session_id Session ID
     * @param string $pdf_path PDF-Pfad
     * @since 1.0.0
     */
    private function update_session_pdf_path(int $session_id, string $pdf_path): void
    {
        global $wpdb;
        
        $wpdb->update(
            $this->tables['sessions'],
            ['receipt_pdf_path' => $pdf_path],
            ['id' => $session_id],
            ['%s'],
            ['%d']
        );
    }
    
    /**
     * Generiert Beleg-Nummer
     * 
     * @param int $session_id Session ID
     * @return string Beleg-Nummer
     * @since 1.0.0
     */
    private function generate_receipt_number(int $session_id): string
    {
        return 'AMP-' . date('Y') . '-' . str_pad($session_id, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Berechnet Session-Gewinn
     * 
     * @param array $items Session-Items
     * @return float Gewinn-Marge
     * @since 1.0.0
     */
    private function calculate_session_profit(array $items): float
    {
        $total_cost = 0;
        $total_revenue = 0;
        
        foreach ($items as $item) {
            $product = $this->products_manager->get_product_by_id($item['product_id']);
            if ($product) {
                $total_cost += $item['quantity'] * $product['buy_price'];
                $total_revenue += $item['line_total'];
            }
        }
        
        if ($total_cost > 0) {
            return round((($total_revenue - $total_cost) / $total_cost) * 100, 2);
        }
        
        return 0.0;
    }
    
    /**
     * Berechnet MwSt-Aufschlüsselung
     * 
     * @param array $items Session-Items
     * @return array MwSt-Details
     * @since 1.0.0
     */
    private function calculate_vat_breakdown(array $items): array
    {
        $vat_breakdown = ['7' => 0, '19' => 0];
        
        foreach ($items as $item) {
            $vat_rate = $item['vat_rate'];
            $vat_breakdown[$vat_rate] = ($vat_breakdown[$vat_rate] ?? 0) + $item['line_vat'];
        }
        
        return $vat_breakdown;
    }
    
    /**
     * AJAX: Verkauf finalisieren
     * 
     * @since 1.0.0
     */
    public function ajax_finalize_sale(): void
    {
        if (!check_ajax_referer('amp_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
        }
        
        if (!current_user_can('amp_process_sales')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $session_data = $_POST['session_data'] ?? [];
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? '');
        $payment_details = $_POST['payment_details'] ?? [];
        
        if (empty($session_data) || empty($payment_method)) {
            wp_send_json_error(['message' => 'Session-Daten oder Zahlungsart fehlt']);
        }
        
        $result = $this->finalize_sale($session_data, $payment_method, $payment_details);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Sales Analytics abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_sales_analytics(): void
    {
        if (!check_ajax_referer('amp_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
        }
        
        if (!current_user_can('amp_view_reports')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $params = [
            'period' => sanitize_text_field($_GET['period'] ?? 'today'),
            'start_date' => sanitize_text_field($_GET['start_date'] ?? ''),
            'end_date' => sanitize_text_field($_GET['end_date'] ?? ''),
            'include_details' => filter_var($_GET['include_details'] ?? true, FILTER_VALIDATE_BOOLEAN)
        ];
        
        $analytics = $this->get_sales_analytics($params);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Holt verfügbare Zahlungsarten
     * 
     * @return array Zahlungsarten
     * @since 1.0.0
     */
    public function get_payment_methods(): array
    {
        return $this->payment_methods;
    }
}