<?php
/**
 * Scanner Manager - REPARIERT
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Scanner
 * @version     1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Scanner;

use AutomatenManagerPro\Products\AMP_Products_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 4-Button Scanner System
 */
class AMP_Scanner_Manager
{
    private AMP_Products_Manager $products_manager;
    
    public function __construct()
    {
        $this->products_manager = new AMP_Products_Manager();
        $this->register_ajax_handlers();
    }
    
    private function register_ajax_handlers(): void
    {
        add_action('wp_ajax_amp_scan_barcode', [$this, 'ajax_scan_barcode']);
        add_action('wp_ajax_amp_scanner_action', [$this, 'ajax_scanner_action']);
        add_action('wp_ajax_amp_create_from_scan', [$this, 'ajax_create_from_scan']);
    }
    
    public function process_barcode_scan(string $barcode): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        // Produkt in Datenbank suchen
        $product = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE barcode = %s AND status = 'active'",
                $barcode
            ),
            ARRAY_A
        );
        
        if ($product) {
            return [
                'found' => true,
                'product' => $product,
                'actions' => [
                    'sell' => 'Verkaufen',
                    'restock' => 'Lager auffüllen',
                    'dispose' => 'Wegschmeißen',
                    'new' => 'Neues Produkt'
                ]
            ];
        } else {
            return [
                'found' => false,
                'barcode' => $barcode,
                'suggested_action' => 'create_new'
            ];
        }
    }
    
    public function handle_scanner_action(string $action, array $data): array
    {
        switch ($action) {
            case 'sell':
                return $this->handle_sell_action($data);
                
            case 'restock':
                return $this->handle_restock_action($data);
                
            case 'dispose':
                return $this->handle_dispose_action($data);
                
            case 'create_new':
                return $this->handle_create_new_action($data);
                
            default:
                throw new \Exception('Unbekannte Scanner-Aktion: ' . $action);
        }
    }
    
    private function handle_sell_action(array $data): array
    {
        $product_id = absint($data['product_id']);
        $quantity = absint($data['quantity'] ?? 1);
        
        // TODO: Sales-Session Integration
        // Für jetzt einfache Bestands-Reduzierung
        $this->update_stock($product_id, -$quantity, 'sale');
        
        return [
            'success' => true,
            'message' => "Verkauf registriert: {$quantity} Stück",
            'action' => 'sell'
        ];
    }
    
    private function handle_restock_action(array $data): array
    {
        $product_id = absint($data['product_id']);
        $quantity = absint($data['quantity'] ?? 1);
        
        $this->update_stock($product_id, $quantity, 'restock');
        
        return [
            'success' => true,
            'message' => "Lager aufgefüllt: +{$quantity} Stück",
            'action' => 'restock'
        ];
    }
    
    private function handle_dispose_action(array $data): array
    {
        $product_id = absint($data['product_id']);
        $quantity = absint($data['quantity'] ?? 1);
        $reason = sanitize_text_field($data['reason'] ?? 'Unbekannt');
        
        // Bestand reduzieren
        $this->update_stock($product_id, -$quantity, 'disposal');
        
        // Entsorgung protokollieren
        $this->log_disposal($product_id, $quantity, $reason);
        
        return [
            'success' => true,
            'message' => "Entsorgung registriert: {$quantity} Stück",
            'action' => 'dispose'
        ];
    }
    
    private function handle_create_new_action(array $data): array
    {
        $barcode = sanitize_text_field($data['barcode']);
        
        return [
            'success' => true,
            'redirect' => admin_url("admin.php?page=amp-products&action=new&barcode=" . urlencode($barcode)),
            'message' => 'Neues Produkt erstellen',
            'action' => 'create_new'
        ];
    }
    
    private function update_stock(int $product_id, int $quantity_change, string $reason): void
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        $movements_table = $wpdb->prefix . 'amp_stock_movements';
        
        // Aktuellen Bestand holen
        $current_stock = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT current_stock FROM {$table_name} WHERE id = %d",
                $product_id
            )
        );
        
        $new_stock = max(0, $current_stock + $quantity_change);
        
        // Bestand aktualisieren
        $wpdb->update(
            $table_name,
            ['current_stock' => $new_stock, 'updated_at' => current_time('mysql')],
            ['id' => $product_id],
            ['%d', '%s'],
            ['%d']
        );
        
        // Bewegung protokollieren
        $wpdb->insert(
            $movements_table,
            [
                'product_id' => $product_id,
                'movement_type' => $quantity_change > 0 ? 'in' : 'out',
                'quantity' => abs($quantity_change),
                'previous_stock' => $current_stock,
                'new_stock' => $new_stock,
                'reason' => $reason,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    private function log_disposal(int $product_id, int $quantity, string $reason): void
    {
        global $wpdb;
        
        $waste_table = $wpdb->prefix . 'amp_waste_log';
        
        // Produkt-Wert für Verlustberechnung
        $product = $this->products_manager->get_product($product_id);
        $disposal_value = ($product['buy_price'] ?? 0) * $quantity;
        
        $wpdb->insert(
            $waste_table,
            [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'disposal_reason' => $reason,
                'disposal_value' => $disposal_value,
                'user_id' => get_current_user_id(),
                'created_at' => current_time('mysql')
            ]
        );
    }
    
    // AJAX Handlers
    public function ajax_scan_barcode(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        
        if (empty($barcode)) {
            wp_send_json_error(['message' => 'Barcode ist erforderlich']);
            return;
        }
        
        try {
            $result = $this->process_barcode_scan($barcode);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function ajax_scanner_action(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $action = sanitize_text_field($_POST['action_type'] ?? '');
        
        if (empty($action)) {
            wp_send_json_error(['message' => 'Aktion ist erforderlich']);
            return;
        }
        
        try {
            $result = $this->handle_scanner_action($action, $_POST);
            wp_send_json_success($result);
        } catch (\Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function ajax_create_from_scan(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $barcode = sanitize_text_field($_POST['barcode'] ?? '');
        
        if (empty($barcode)) {
            wp_send_json_error(['message' => 'Barcode ist erforderlich']);
            return;
        }
        
        // Weiterleitung zum Produkt-Erstellen-Formular
        wp_send_json_success([
            'redirect' => admin_url("admin.php?page=amp-products&action=new&barcode=" . urlencode($barcode))
        ]);
    }
}