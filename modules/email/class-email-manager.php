<?php
/**
 * Email Manager - Vollautomatisches E-Mail-System
 * 
 * Zentrale Klasse für automatische E-Mail-Berichte des AutomatenManager Pro Systems.
 * Versendet 4 verschiedene E-Mail-Typen mit PDF-Anhängen und Firmenlogo-Integration.
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Email
 * @version     1.0.0
 * @since       1.0.0
 * @author      AutomatenManager Pro Team
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Email;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use AutomatenManagerPro\Sales\AMP_Sales_Manager;
use AutomatenManagerPro\Inventory\AMP_Inventory_Manager;
use AutomatenManagerPro\Waste\AMP_Waste_Manager;
use AutomatenManagerPro\Reports\AMP_Reports_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vollautomatisches E-Mail-System für Business Communication
 * 
 * E-Mail-Typen:
 * 1. Verkaufsberichte (nach jeder Session mit PDF-Beleg)
 * 2. Lager-Auffüll Berichte (bei Wareneingang)
 * 3. Haltbarkeitsdatum-Warnungen (2 Tage vorher via Cron)
 * 4. Entsorgungs-Berichte (mit Beweis-Fotos)
 * 
 * Features:
 * - Template-System mit Firmenlogo
 * - PDF-Generierung mit Watermarks
 * - SMTP-Konfiguration und Delivery-Tracking
 * - WordPress Integration mit wp_mail()
 * 
 * @since 1.0.0
 */
class AMP_Email_Manager
{
    private AMP_Database_Manager $database;
    private AMP_Sales_Manager $sales_manager;
    private AMP_Inventory_Manager $inventory_manager;
    private AMP_Waste_Manager $waste_manager;
    private AMP_Reports_Manager $reports_manager;
    
    /**
     * E-Mail Template-Pfad
     * @var string
     */
    private string $template_path;
    
    /**
     * Upload-Verzeichnis für Anhänge
     * @var string
     */
    private string $attachments_dir;
    
    /**
     * Email Manager Konstruktor
     * 
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->sales_manager = new AMP_Sales_Manager();
        $this->inventory_manager = new AMP_Inventory_Manager();
        $this->waste_manager = new AMP_Waste_Manager();
        $this->reports_manager = new AMP_Reports_Manager();
        
        // Template-Pfad setzen
        $this->template_path = AMP_PLUGIN_DIR . 'templates/email/';
        
        // Uploads-Verzeichnis für E-Mail-Anhänge
        $upload_dir = wp_upload_dir();
        $this->attachments_dir = $upload_dir['basedir'] . '/amp-email-attachments/';
        $this->ensure_attachments_directory();
        
        $this->init_hooks();
    }
    
    /**
     * WordPress Hooks initialisieren
     * 
     * @since 1.0.0
     */
    private function init_hooks(): void
    {
        // Event-basierte E-Mail-Trigger
        add_action('amp_sales_session_completed', [$this, 'send_sales_report'], 10, 1);
        add_action('amp_inventory_restocked', [$this, 'send_inventory_report'], 10, 2);
        add_action('amp_product_disposed', [$this, 'send_disposal_report'], 10, 2);
        
        // Cron-basierte E-Mails
        add_action('amp_daily_expiry_check', [$this, 'send_expiry_warnings']);
        add_action('amp_weekly_summary', [$this, 'send_weekly_summary']);
        add_action('amp_monthly_executive_report', [$this, 'send_executive_report']);
        
        // AJAX Handlers für E-Mail Management
        add_action('wp_ajax_amp_send_test_email', [$this, 'ajax_send_test_email']);
        add_action('wp_ajax_amp_resend_email', [$this, 'ajax_resend_email']);
        add_action('wp_ajax_amp_get_email_logs', [$this, 'ajax_get_email_logs']);
        
        // E-Mail Template Filter
        add_filter('amp_email_template_vars', [$this, 'add_global_template_vars'], 10, 2);
    }
    
    /**
     * Verkaufsbericht nach Session-Abschluss senden
     * 
     * @param int $session_id Verkaufs-Session ID
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_sales_report(int $session_id): bool
    {
        try {
            // Session-Daten abrufen
            $session_data = $this->sales_manager->get_session_details($session_id);
            if (!$session_data) {
                throw new Exception("Session {$session_id} nicht gefunden");
            }
            
            // PDF-Beleg generieren
            $pdf_path = $this->generate_sales_receipt_pdf($session_data);
            
            // E-Mail-Empfänger aus Einstellungen
            $recipient = get_option('amp_sales_report_email', get_option('admin_email'));
            $cc_recipients = $this->get_cc_recipients('sales');
            
            // Template-Variablen vorbereiten
            $template_vars = [
                'session' => $session_data,
                'items' => $session_data['items'],
                'totals' => [
                    'net' => $session_data['total_net'],
                    'vat' => $session_data['total_vat'],
                    'deposit' => $session_data['total_deposit'],
                    'gross' => $session_data['total_gross']
                ],
                'payment_method' => $session_data['payment_method'],
                'session_duration' => $this->calculate_session_duration($session_data),
                'operator' => get_userdata($session_data['user_id'])
            ];
            
            // E-Mail zusammenstellen
            $subject = $this->get_email_subject('sales', $template_vars);
            $body = $this->render_email_template('sales-report', $template_vars);
            
            // E-Mail senden
            $sent = $this->send_email([
                'to' => $recipient,
                'cc' => $cc_recipients,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [$pdf_path],
                'type' => 'sales_report',
                'reference_id' => $session_id
            ]);
            
            if ($sent) {
                // Session als "E-Mail versendet" markieren
                $this->sales_manager->mark_session_email_sent($session_id);
                
                $this->log_email_activity('sales_report', $recipient, 'success', [
                    'session_id' => $session_id,
                    'attachment' => basename($pdf_path)
                ]);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            $this->log_email_activity('sales_report', $recipient ?? 'unknown', 'error', [
                'session_id' => $session_id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Lager-Auffüll Bericht senden
     * 
     * @param array $restocked_items Array der aufgefüllten Artikel
     * @param int $user_id Benutzer ID der den Vorgang durchgeführt hat
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_inventory_report(array $restocked_items, int $user_id): bool
    {
        try {
            $recipient = get_option('amp_inventory_report_email', get_option('admin_email'));
            $cc_recipients = $this->get_cc_recipients('inventory');
            
            // Gesamtwerte berechnen
            $total_items = array_sum(array_column($restocked_items, 'quantity'));
            $total_value = array_sum(array_map(function($item) {
                return $item['quantity'] * $item['buy_price'];
            }, $restocked_items));
            
            // Template-Variablen
            $template_vars = [
                'restocked_items' => $restocked_items,
                'total_items' => $total_items,
                'total_value' => $total_value,
                'operator' => get_userdata($user_id),
                'restock_date' => current_time('d.m.Y H:i'),
                'summary' => $this->generate_inventory_summary($restocked_items)
            ];
            
            $subject = $this->get_email_subject('inventory', $template_vars);
            $body = $this->render_email_template('inventory-report', $template_vars);
            
            // Optional: PDF-Anhang für größere Wareneingänge
            $pdf_path = null;
            if (count($restocked_items) > 10 || $total_value > 500) {
                $pdf_path = $this->generate_inventory_report_pdf($template_vars);
            }
            
            $sent = $this->send_email([
                'to' => $recipient,
                'cc' => $cc_recipients,
                'subject' => $subject,
                'body' => $body,
                'attachments' => $pdf_path ? [$pdf_path] : [],
                'type' => 'inventory_report',
                'reference_id' => $user_id
            ]);
            
            if ($sent) {
                $this->log_email_activity('inventory_report', $recipient, 'success', [
                    'items_count' => count($restocked_items),
                    'total_value' => $total_value
                ]);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            $this->log_email_activity('inventory_report', $recipient ?? 'unknown', 'error', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Haltbarkeitsdatum-Warnungen senden (Cron-Job)
     * 
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_expiry_warnings(): bool
    {
        try {
            // Ablaufende Produkte abrufen
            $expiring_products = $this->inventory_manager->get_expiring_products(2); // 2 Tage
            
            if (empty($expiring_products)) {
                return true; // Keine Warnungen erforderlich
            }
            
            $recipient = get_option('amp_expiry_warning_email', get_option('admin_email'));
            $cc_recipients = $this->get_cc_recipients('expiry');
            
            // Gesamtwert der gefährdeten Artikel
            $total_endangered_value = array_sum(array_map(function($product) {
                return $product['current_stock'] * $product['buy_price'];
            }, $expiring_products));
            
            // Nach Kritikalität sortieren
            usort($expiring_products, function($a, $b) {
                return strtotime($a['expiry_date']) <=> strtotime($b['expiry_date']);
            });
            
            $template_vars = [
                'expiring_products' => $expiring_products,
                'total_endangered_value' => $total_endangered_value,
                'urgent_count' => count(array_filter($expiring_products, function($p) {
                    return strtotime($p['expiry_date']) <= strtotime('+1 day');
                })),
                'warning_count' => count($expiring_products),
                'check_date' => current_time('d.m.Y H:i'),
                'recommendations' => $this->generate_expiry_recommendations($expiring_products)
            ];
            
            $subject = $this->get_email_subject('expiry_warning', $template_vars);
            $body = $this->render_email_template('expiry-warning', $template_vars);
            
            // PDF-Bericht für umfangreichere Warnungen
            $pdf_path = null;
            if (count($expiring_products) > 5) {
                $pdf_path = $this->generate_expiry_warning_pdf($template_vars);
            }
            
            $sent = $this->send_email([
                'to' => $recipient,
                'cc' => $cc_recipients,
                'subject' => $subject,
                'body' => $body,
                'attachments' => $pdf_path ? [$pdf_path] : [],
                'type' => 'expiry_warning',
                'priority' => 'high'
            ]);
            
            if ($sent) {
                $this->log_email_activity('expiry_warning', $recipient, 'success', [
                    'products_count' => count($expiring_products),
                    'total_value' => $total_endangered_value
                ]);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            $this->log_email_activity('expiry_warning', $recipient ?? 'unknown', 'error', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Entsorgungs-Bericht senden
     * 
     * @param array $disposal_data Entsorgungsdaten
     * @param array $photo_paths Pfade zu Beweis-Fotos
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_disposal_report(array $disposal_data, array $photo_paths = []): bool
    {
        try {
            $recipient = get_option('amp_disposal_report_email', get_option('admin_email'));
            $cc_recipients = $this->get_cc_recipients('disposal');
            
            // Compliance-Daten hinzufügen
            $disposal_data['compliance'] = [
                'tax_documentation' => true,
                'photo_count' => count($photo_paths),
                'disposal_id' => uniqid('AMP-DISP-'),
                'retention_period' => '7 Jahre (steuerrechtlich)'
            ];
            
            $template_vars = [
                'disposal' => $disposal_data,
                'photo_count' => count($photo_paths),
                'operator' => get_userdata($disposal_data['user_id']),
                'disposal_date' => current_time('d.m.Y H:i'),
                'tax_implications' => $this->calculate_tax_implications($disposal_data),
                'recommendations' => $this->generate_disposal_recommendations($disposal_data)
            ];
            
            $subject = $this->get_email_subject('disposal', $template_vars);
            $body = $this->render_email_template('disposal-report', $template_vars);
            
            // PDF-Dokumentation mit Fotos generieren
            $pdf_path = $this->generate_disposal_documentation_pdf($template_vars, $photo_paths);
            
            // Alle Anhänge sammeln
            $attachments = [$pdf_path];
            if (!empty($photo_paths)) {
                $attachments = array_merge($attachments, $photo_paths);
            }
            
            $sent = $this->send_email([
                'to' => $recipient,
                'cc' => $cc_recipients,
                'subject' => $subject,
                'body' => $body,
                'attachments' => $attachments,
                'type' => 'disposal_report',
                'reference_id' => $disposal_data['id']
            ]);
            
            if ($sent) {
                $this->log_email_activity('disposal_report', $recipient, 'success', [
                    'disposal_id' => $disposal_data['id'],
                    'disposal_value' => $disposal_data['disposal_value'],
                    'photos_attached' => count($photo_paths)
                ]);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            $this->log_email_activity('disposal_report', $recipient ?? 'unknown', 'error', [
                'disposal_id' => $disposal_data['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Wöchentliche Zusammenfassung senden
     * 
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_weekly_summary(): bool
    {
        try {
            $recipient = get_option('amp_weekly_summary_email', get_option('admin_email'));
            
            // Wöchentliche Statistiken vom Reports Manager
            $weekly_stats = $this->reports_manager->get_dashboard_stats('week');
            
            // Executive Summary erstellen
            $template_vars = [
                'week_stats' => $weekly_stats,
                'highlights' => $this->generate_weekly_highlights($weekly_stats),
                'concerns' => $this->identify_weekly_concerns($weekly_stats),
                'recommendations' => $this->generate_weekly_recommendations($weekly_stats),
                'period' => date('d.m.Y', strtotime('-7 days')) . ' - ' . date('d.m.Y')
            ];
            
            $subject = $this->get_email_subject('weekly_summary', $template_vars);
            $body = $this->render_email_template('weekly-summary', $template_vars);
            
            // Umfassender PDF-Bericht
            $pdf_path = $this->generate_weekly_summary_pdf($template_vars);
            
            $sent = $this->send_email([
                'to' => $recipient,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [$pdf_path],
                'type' => 'weekly_summary'
            ]);
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('AMP Weekly Summary Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Monatlichen Executive Report senden
     * 
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    public function send_executive_report(): bool
    {
        try {
            $recipient = get_option('amp_executive_report_email', get_option('admin_email'));
            
            // Monatliche Executive-Statistiken
            $monthly_stats = $this->reports_manager->get_dashboard_stats('month');
            
            $template_vars = [
                'executive_summary' => $monthly_stats,
                'kpis' => $monthly_stats['kpis'],
                'strategic_insights' => $this->generate_strategic_insights($monthly_stats),
                'action_items' => $this->generate_action_items($monthly_stats),
                'forecast' => $this->generate_forecast($monthly_stats),
                'period' => date('F Y', strtotime('-1 month'))
            ];
            
            $subject = $this->get_email_subject('executive_report', $template_vars);
            $body = $this->render_email_template('executive-report', $template_vars);
            
            // Executive PDF mit Charts und Grafiken
            $pdf_path = $this->generate_executive_report_pdf($template_vars);
            
            $sent = $this->send_email([
                'to' => $recipient,
                'subject' => $subject,
                'body' => $body,
                'attachments' => [$pdf_path],
                'type' => 'executive_report',
                'priority' => 'high'
            ]);
            
            return $sent;
            
        } catch (Exception $e) {
            error_log('AMP Executive Report Error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * E-Mail senden (zentrale Methode)
     * 
     * @param array $email_data E-Mail-Daten
     * @return bool Erfolgreich versendet
     * @since 1.0.0
     */
    private function send_email(array $email_data): bool
    {
        $defaults = [
            'to' => '',
            'cc' => [],
            'bcc' => [],
            'subject' => '',
            'body' => '',
            'attachments' => [],
            'type' => 'general',
            'priority' => 'normal',
            'reference_id' => null
        ];
        
        $email = wp_parse_args($email_data, $defaults);
        
        // Headers zusammenstellen
        $headers = $this->prepare_email_headers($email);
        
        // WordPress wp_mail() verwenden
        $sent = wp_mail(
            $email['to'],
            $email['subject'],
            $email['body'],
            $headers,
            $email['attachments']
        );
        
        // Detailliertes Logging
        $this->log_detailed_email($email, $sent);
        
        return $sent;
    }
    
    /**
     * E-Mail Template rendern
     * 
     * @param string $template Template-Name
     * @param array $vars Template-Variablen
     * @return string Gerenderte E-Mail
     * @since 1.0.0
     */
    private function render_email_template(string $template, array $vars = []): string
    {
        // Globale Template-Variablen hinzufügen
        $vars = apply_filters('amp_email_template_vars', $vars, $template);
        
        // Template-Pfad
        $template_file = $this->template_path . $template . '.php';
        
        if (!file_exists($template_file)) {
            // Fallback zu einfacher Text-E-Mail
            return $this->render_fallback_template($template, $vars);
        }
        
        // Template-Variablen extrahieren
        extract($vars, EXTR_SKIP);
        
        // Output buffering für Template
        ob_start();
        include $template_file;
        $content = ob_get_clean();
        
        // HTML-E-Mail-Wrapper hinzufügen
        return $this->wrap_email_html($content, $vars);
    }
    
    /**
     * HTML-Wrapper für E-Mails
     * 
     * @param string $content E-Mail-Inhalt
     * @param array $vars Template-Variablen
     * @return string Vollständige HTML-E-Mail
     * @since 1.0.0
     */
    private function wrap_email_html(string $content, array $vars): string
    {
        $company_name = get_option('amp_company_name', 'AutomatenManager Pro');
        $logo_url = $this->get_company_logo_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($vars['email_subject'] ?? 'AutomatenManager Pro Bericht'); ?></title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
                .email-container { max-width: 800px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .email-header { background: linear-gradient(135deg, #007cba 0%, #005a8b 100%); color: white; padding: 20px; text-align: center; }
                .email-header img { max-height: 60px; margin-bottom: 10px; }
                .email-body { padding: 30px; line-height: 1.6; color: #333; }
                .email-footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e9ecef; }
                .highlight { background-color: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #007cba; }
                .warning { background-color: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
                .error { background-color: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #dc3545; }
                .success { background-color: #d1edff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f8f9fa; font-weight: 600; }
                .text-right { text-align: right; }
                .text-center { text-align: center; }
                .btn { display: inline-block; padding: 10px 20px; background-color: #007cba; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
                .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
                .kpi-card { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e9ecef; }
                .kpi-value { font-size: 24px; font-weight: bold; color: #007cba; }
                .kpi-label { font-size: 14px; color: #666; margin-top: 5px; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($company_name); ?>">
                    <?php endif; ?>
                    <h1><?php echo esc_html($company_name); ?></h1>
                    <p>AutomatenManager Pro - Professionelle Lagerverwaltung</p>
                </div>
                
                <div class="email-body">
                    <?php echo $content; ?>
                </div>
                
                <div class="email-footer">
                    <p>Diese E-Mail wurde automatisch von AutomatenManager Pro generiert.</p>
                    <p>Generiert am: <?php echo date('d.m.Y H:i:s'); ?></p>
                    <p>© <?php echo date('Y'); ?> <?php echo esc_html($company_name); ?> - Alle Rechte vorbehalten</p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * E-Mail-Headers vorbereiten
     * 
     * @param array $email E-Mail-Daten
     * @return array Headers
     * @since 1.0.0
     */
    private function prepare_email_headers(array $email): array
    {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // From-Header
        $from_name = get_option('amp_email_from_name', get_option('blogname'));
        $from_email = get_option('amp_email_from_address', get_option('admin_email'));
        $headers[] = "From: {$from_name} <{$from_email}>";
        
        // CC/BCC
        if (!empty($email['cc'])) {
            foreach ($email['cc'] as $cc) {
                $headers[] = "Cc: {$cc}";
            }
        }
        
        if (!empty($email['bcc'])) {
            foreach ($email['bcc'] as $bcc) {
                $headers[] = "Bcc: {$bcc}";
            }
        }
        
        // Priorität
        if ($email['priority'] === 'high') {
            $headers[] = "X-Priority: 1";
            $headers[] = "X-MSMail-Priority: High";
            $headers[] = "Importance: High";
        }
        
        return $headers;
    }
    
    /**
     * PDF-Beleg für Verkauf generieren
     * 
     * @param array $session_data Session-Daten
     * @return string Pfad zur PDF-Datei
     * @since 1.0.0
     */
    private function generate_sales_receipt_pdf(array $session_data): string
    {
        $filename = 'verkaufsbeleg_' . $session_data['id'] . '_' . date('Y-m-d_H-i') . '.pdf';
        $file_path = $this->attachments_dir . $filename;
        
        // HTML für PDF vorbereiten
        $html = $this->prepare_sales_receipt_html($session_data);
        
        // Einfache HTML-zu-PDF Konvertierung
        // In Produktionsumgebung sollte eine echte PDF-Library (TCPDF, DOMPDF) verwendet werden
        file_put_contents($file_path, $html);
        
        return $file_path;
    }
    
    /**
     * HTML für Verkaufs-PDF vorbereiten
     * 
     * @param array $session_data Session-Daten
     * @return string HTML-Inhalt
     * @since 1.0.0
     */
    private function prepare_sales_receipt_html(array $session_data): string
    {
        $company_name = get_option('amp_company_name', 'Ihr Unternehmen');
        $logo_url = $this->get_company_logo_url();
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Verkaufsbeleg #<?php echo esc_html($session_data['id']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; font-size: 14px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #007cba; padding-bottom: 20px; }
                .logo { max-height: 80px; margin-bottom: 15px; }
                .receipt-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
                .receipt-info div { width: 48%; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .text-right { text-align: right; }
                .total-row { background-color: #e3f2fd; font-weight: bold; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); opacity: 0.1; font-size: 80px; font-weight: bold; color: #007cba; z-index: -1; }
            </style>
        </head>
        <body>
            <div class="watermark"><?php echo esc_html($company_name); ?></div>
            
            <div class="header">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="logo">
                <?php endif; ?>
                <h1><?php echo esc_html($company_name); ?></h1>
                <h2>Verkaufsbeleg #<?php echo esc_html($session_data['id']); ?></h2>
            </div>
            
            <div class="receipt-info">
                <div>
                    <strong>Verkaufsdatum:</strong><br>
                    <?php echo date('d.m.Y H:i', strtotime($session_data['session_start'])); ?><br><br>
                    <strong>Kassierer:</strong><br>
                    <?php echo esc_html(get_userdata($session_data['user_id'])->display_name ?? 'System'); ?>
                </div>
                <div class="text-right">
                    <strong>Beleg-Nr:</strong> <?php echo esc_html($session_data['id']); ?><br>
                    <strong>Zahlungsart:</strong> <?php echo esc_html(ucfirst($session_data['payment_method'])); ?><br>
                    <strong>Status:</strong> <?php echo esc_html(ucfirst($session_data['status'])); ?>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Artikel</th>
                        <th class="text-right">Menge</th>
                        <th class="text-right">Einzelpreis</th>
                        <th class="text-right">Pfand</th>
                        <th class="text-right">Gesamt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($session_data['items'] as $item): ?>
                    <tr>
                        <td><?php echo esc_html($item['product_name']); ?></td>
                        <td class="text-right"><?php echo esc_html($item['quantity']); ?></td>
                        <td class="text-right"><?php echo number_format($item['unit_price'], 2, ',', '.'); ?> €</td>
                        <td class="text-right"><?php echo number_format($item['unit_deposit'], 2, ',', '.'); ?> €</td>
                        <td class="text-right"><?php echo number_format($item['line_total_gross'], 2, ',', '.'); ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Warenwert (Netto):</strong></td>
                        <td class="text-right"><strong><?php echo number_format($session_data['total_net'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Mehrwertsteuer:</strong></td>
                        <td class="text-right"><strong><?php echo number_format($session_data['total_vat'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-right"><strong>Pfand:</strong></td>
                        <td class="text-right"><strong><?php echo number_format($session_data['total_deposit'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="4" class="text-right"><strong>ENDSUMME:</strong></td>
                        <td class="text-right"><strong><?php echo number_format($session_data['total_gross'], 2, ',', '.'); ?> €</strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="footer">
                <p>Vielen Dank für Ihren Einkauf!</p>
                <p>Dieser Beleg wurde automatisch von AutomatenManager Pro generiert.</p>
                <p>Erstellt am: <?php echo date('d.m.Y H:i:s'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * E-Mail-Aktivität protokollieren
     * 
     * @param string $type E-Mail-Typ
     * @param string $recipient Empfänger
     * @param string $status Status (success/error)
     * @param array $metadata Zusätzliche Metadaten
     * @since 1.0.0
     */
    private function log_email_activity(string $type, string $recipient, string $status, array $metadata = []): void
    {
        global $wpdb;
        
        $table_email_log = $wpdb->prefix . 'amp_email_log';
        
        $wpdb->insert(
            $table_email_log,
            [
                'email_type' => $type,
                'recipient' => $recipient,
                'status' => $status,
                'metadata' => json_encode($metadata),
                'sent_at' => current_time('mysql'),
                'user_id' => get_current_user_id()
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d']
        );
    }
    
    /**
     * Detailliertes E-Mail-Logging
     * 
     * @param array $email E-Mail-Daten
     * @param bool $sent Erfolgreich versendet
     * @since 1.0.0
     */
    private function log_detailed_email(array $email, bool $sent): void
    {
        $log_data = [
            'to' => $email['to'],
            'subject' => $email['subject'],
            'type' => $email['type'],
            'priority' => $email['priority'],
            'attachments_count' => count($email['attachments']),
            'reference_id' => $email['reference_id'],
            'sent' => $sent,
            'timestamp' => current_time('Y-m-d H:i:s')
        ];
        
        error_log('AMP Email Log: ' . json_encode($log_data));
    }
    
    // =======================
    // AJAX HANDLERS
    // =======================
    
    /**
     * AJAX: Test-E-Mail senden
     * 
     * @since 1.0.0
     */
    public function ajax_send_test_email(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_manage_settings')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $email_type = sanitize_text_field($_POST['email_type']);
        $test_recipient = sanitize_email($_POST['test_recipient']);
        
        if (!$test_recipient) {
            wp_send_json_error('Ungültige E-Mail-Adresse');
            return;
        }
        
        try {
            $result = $this->send_test_email($email_type, $test_recipient);
            wp_send_json_success(['message' => 'Test-E-Mail erfolgreich versendet']);
        } catch (Exception $e) {
            wp_send_json_error('Fehler beim Versenden: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: E-Mail erneut senden
     * 
     * @since 1.0.0
     */
    public function ajax_resend_email(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_view_reports')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $email_log_id = (int)$_POST['email_log_id'];
        
        // E-Mail-Log-Eintrag abrufen und erneut senden
        $result = $this->resend_email_from_log($email_log_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'E-Mail erfolgreich erneut versendet']);
        } else {
            wp_send_json_error('Fehler beim erneuten Versenden');
        }
    }
    
    /**
     * AJAX: E-Mail-Logs abrufen
     * 
     * @since 1.0.0
     */
    public function ajax_get_email_logs(): void
    {
        check_ajax_referer('amp_admin_nonce', 'nonce');
        
        if (!current_user_can('amp_view_reports')) {
            wp_die(__('Unzureichende Berechtigung.', 'automaten-manager-pro'));
        }
        
        $page = (int)($_POST['page'] ?? 1);
        $per_page = 20;
        $email_type = sanitize_text_field($_POST['email_type'] ?? '');
        
        $logs = $this->get_email_logs($page, $per_page, $email_type);
        
        wp_send_json_success($logs);
    }
    
    // =======================
    // HELPER METHODEN
    // =======================
    
    /**
     * CC-Empfänger für E-Mail-Typ abrufen
     * 
     * @param string $email_type E-Mail-Typ
     * @return array CC-Empfänger
     * @since 1.0.0
     */
    private function get_cc_recipients(string $email_type): array
    {
        $cc_option = "amp_{$email_type}_cc_recipients";
        $cc_list = get_option($cc_option, '');
        
        if (empty($cc_list)) {
            return [];
        }
        
        $emails = array_map('trim', explode(',', $cc_list));
        return array_filter($emails, 'is_email');
    }
    
    /**
     * E-Mail-Betreff für Typ generieren
     * 
     * @param string $type E-Mail-Typ
     * @param array $vars Template-Variablen
     * @return string Betreff
     * @since 1.0.0
     */
    private function get_email_subject(string $type, array $vars): string
    {
        $subjects = [
            'sales' => 'Verkaufsbericht - {date} - {total}€',
            'inventory' => 'Lager aufgefüllt - {date} - {items} Artikel',
            'expiry_warning' => '⚠️ Haltbarkeitsdatum-Warnung - {count} Artikel betroffen',
            'disposal' => '🗑️ Entsorgung dokumentiert - {date} - {value}€ Verlust',
            'weekly_summary' => '📊 Wöchentliche Zusammenfassung - {period}',
            'executive_report' => '📈 Executive Report - {period}'
        ];
        
        $template = $subjects[$type] ?? 'AutomatenManager Pro Bericht';
        
        // Platzhalter ersetzen
        $replacements = [
            '{date}' => date('d.m.Y'),
            '{time}' => date('H:i'),
            '{total}' => number_format($vars['session']['total_gross'] ?? 0, 2, ',', '.'),
            '{items}' => $vars['total_items'] ?? 0,
            '{count}' => count($vars['expiring_products'] ?? []),
            '{value}' => number_format($vars['disposal']['disposal_value'] ?? 0, 2, ',', '.'),
            '{period}' => $vars['period'] ?? date('F Y')
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
    
    /**
     * Firmenlogo-URL abrufen
     * 
     * @return string Logo-URL oder leer
     * @since 1.0.0
     */
    private function get_company_logo_url(): string
    {
        $logo_id = get_option('amp_company_logo_id');
        if ($logo_id) {
            $logo_url = wp_get_attachment_url($logo_id);
            return $logo_url ?: '';
        }
        return '';
    }
    
    /**
     * Anhänge-Verzeichnis sicherstellen
     * 
     * @since 1.0.0
     */
    private function ensure_attachments_directory(): void
    {
        if (!file_exists($this->attachments_dir)) {
            wp_mkdir_p($this->attachments_dir);
            
            // .htaccess für Sicherheit
            $htaccess_content = "deny from all\n";
            file_put_contents($this->attachments_dir . '.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Globale Template-Variablen hinzufügen
     * 
     * @param array $vars Bestehende Variablen
     * @param string $template Template-Name
     * @return array Erweiterte Variablen
     * @since 1.0.0
     */
    public function add_global_template_vars(array $vars, string $template): array
    {
        $global_vars = [
            'company_name' => get_option('amp_company_name', 'AutomatenManager Pro'),
            'company_address' => get_option('amp_company_address', ''),
            'company_phone' => get_option('amp_company_phone', ''),
            'company_email' => get_option('amp_company_email', get_option('admin_email')),
            'logo_url' => $this->get_company_logo_url(),
            'current_date' => date('d.m.Y'),
            'current_time' => date('H:i'),
            'current_datetime' => date('d.m.Y H:i'),
            'site_url' => site_url(),
            'admin_url' => admin_url('admin.php?page=automaten-manager-pro')
        ];
        
        return array_merge($global_vars, $vars);
    }
    
    /**
     * Session-Dauer berechnen
     * 
     * @param array $session_data Session-Daten
     * @return string Formatierte Dauer
     * @since 1.0.0
     */
    private function calculate_session_duration(array $session_data): string
    {
        $start = strtotime($session_data['session_start']);
        $end = strtotime($session_data['session_end']);
        $duration = $end - $start;
        
        if ($duration < 60) {
            return $duration . ' Sekunden';
        } elseif ($duration < 3600) {
            return floor($duration / 60) . ' Minuten';
        } else {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            return $hours . 'h ' . $minutes . 'm';
        }
    }
    
    /**
     * Fallback-Template für fehlende Templates
     * 
     * @param string $template Template-Name
     * @param array $vars Variablen
     * @return string Einfache Text-E-Mail
     * @since 1.0.0
     */
    private function render_fallback_template(string $template, array $vars): string
    {
        $content = "AutomatenManager Pro Bericht\n";
        $content .= "Template: {$template}\n";
        $content .= "Datum: " . date('d.m.Y H:i') . "\n\n";
        
        foreach ($vars as $key => $value) {
            if (is_scalar($value)) {
                $content .= ucfirst($key) . ": {$value}\n";
            }
        }
        
        return nl2br($content);
    }
    
    // Platzhalter für weitere Helper-Methoden...
    // (Alle PDF-Generierungs-Methoden, Empfehlungs-Engines, etc.)
}