<?php
/**
 * Products Manager - REPARIERT
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Products
 * @version     1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Products;

use AutomatenManagerPro\Database\AMP_Database_Manager;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Vollständige Produkt-CRUD Verwaltung
 */
class AMP_Products_Manager
{
    private AMP_Database_Manager $database;
    
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->register_ajax_handlers();
    }
    
    private function register_ajax_handlers(): void
    {
        add_action('wp_ajax_amp_create_product', [$this, 'ajax_create_product']);
        add_action('wp_ajax_amp_update_product', [$this, 'ajax_update_product']);
        add_action('wp_ajax_amp_delete_product', [$this, 'ajax_delete_product']);
        add_action('wp_ajax_amp_get_product', [$this, 'ajax_get_product']);
        add_action('wp_ajax_amp_search_products', [$this, 'ajax_search_products']);
    }
    
    public function create_product(array $data): int
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        $product_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'barcode' => sanitize_text_field($data['barcode']),
            'image_id' => absint($data['image_id'] ?? 0),
            'buy_price' => floatval($data['buy_price'] ?? 0),
            'sell_price' => floatval($data['sell_price'] ?? 0),
            'vat_rate' => floatval($data['vat_rate'] ?? 19),
            'deposit' => floatval($data['deposit'] ?? 0),
            'current_stock' => intval($data['current_stock'] ?? 0),
            'min_stock' => intval($data['min_stock'] ?? 0),
            'expiry_date' => $data['expiry_date'] ?? null,
            'category_id' => absint($data['category_id'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($table_name, $product_data);
        
        if ($result === false) {
            throw new \Exception('Fehler beim Erstellen des Produkts: ' . $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    public function update_product(int $id, array $data): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        $update_data = [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'barcode' => sanitize_text_field($data['barcode']),
            'image_id' => absint($data['image_id'] ?? 0),
            'buy_price' => floatval($data['buy_price'] ?? 0),
            'sell_price' => floatval($data['sell_price'] ?? 0),
            'vat_rate' => floatval($data['vat_rate'] ?? 19),
            'deposit' => floatval($data['deposit'] ?? 0),
            'current_stock' => intval($data['current_stock'] ?? 0),
            'min_stock' => intval($data['min_stock'] ?? 0),
            'expiry_date' => $data['expiry_date'] ?? null,
            'category_id' => absint($data['category_id'] ?? 0),
            'status' => sanitize_text_field($data['status'] ?? 'active'),
            'updated_at' => current_time('mysql')
        ];
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $id],
            null,
            ['%d']
        );
        
        return $result !== false;
    }
    
    public function delete_product(int $id): bool
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    public function get_product(int $id): ?array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        $product = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $id),
            ARRAY_A
        );
        
        return $product ?: null;
    }
    
    public function get_products(array $args = []): array
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'amp_products';
        
        $limit = absint($args['limit'] ?? 50);
        $offset = absint($args['offset'] ?? 0);
        $search = sanitize_text_field($args['search'] ?? '');
        $status = sanitize_text_field($args['status'] ?? 'active');
        
        $where_clauses = ["status = %s"];
        $where_values = [$status];
        
        if (!empty($search)) {
            $where_clauses[] = "(name LIKE %s OR barcode LIKE %s)";
            $where_values[] = "%{$search}%";
            $where_values[] = "%{$search}%";
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE {$where_sql} ORDER BY name ASC LIMIT %d OFFSET %d",
            array_merge($where_values, [$limit, $offset])
        );
        
        return $wpdb->get_results($sql, ARRAY_A) ?: [];
    }
    
    // AJAX Handlers
    public function ajax_create_product(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        try {
            $product_id = $this->create_product($_POST);
            
            wp_send_json_success([
                'id' => $product_id,
                'message' => 'Produkt erfolgreich erstellt'
            ]);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function ajax_update_product(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $product_id = absint($_POST['id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'Ungültige Produkt-ID']);
            return;
        }
        
        try {
            $result = $this->update_product($product_id, $_POST);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Produkt erfolgreich aktualisiert'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Fehler beim Aktualisieren des Produkts'
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function ajax_delete_product(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $product_id = absint($_POST['id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'Ungültige Produkt-ID']);
            return;
        }
        
        try {
            $result = $this->delete_product($product_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Produkt erfolgreich gelöscht'
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Fehler beim Löschen des Produkts'
                ]);
            }
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function ajax_get_product(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $product_id = absint($_GET['id'] ?? 0);
        
        if (!$product_id) {
            wp_send_json_error(['message' => 'Ungültige Produkt-ID']);
            return;
        }
        
        $product = $this->get_product($product_id);
        
        if ($product) {
            wp_send_json_success($product);
        } else {
            wp_send_json_error(['message' => 'Produkt nicht gefunden']);
        }
    }
    
    public function ajax_search_products(): void
    {
        check_ajax_referer('amp_nonce', 'nonce');
        
        $products = $this->get_products($_GET);
        
        wp_send_json_success($products);
    }
}