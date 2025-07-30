<?php
/**
 * Categories Manager for AutomatenManager Pro
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Categories
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

namespace AutomatenManagerPro\Categories;

use AutomatenManagerPro\Database\AMP_Database_Manager;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Categories Manager Class
 * 
 * @since 1.0.0
 */
class AMP_Categories_Manager
{
    private AMP_Database_Manager $database;
    
    public function __construct()
    {
        $this->database = new AMP_Database_Manager();
        $this->register_ajax_handlers();
        error_log('AMP Categories Manager: Initialisiert');
    }
    
    private function register_ajax_handlers(): void
    {
        add_action('wp_ajax_amp_create_category', [$this, 'ajax_create_category']);
        add_action('wp_ajax_amp_load_categories', [$this, 'ajax_load_categories']);
        add_action('wp_ajax_amp_update_category', [$this, 'ajax_update_category']);
        add_action('wp_ajax_amp_delete_category', [$this, 'ajax_delete_category']);
    }
    
    public function create_category(array $category_data): int
    {
        $validated_data = $this->validate_category_data($category_data);
        
        if (is_wp_error($validated_data)) {
            throw new Exception($validated_data->get_error_message());
        }
        
        $category_id = $this->database->insert('categories', $validated_data);
        
        if (!$category_id) {
            throw new Exception('Fehler beim Erstellen der Kategorie');
        }
        
        return $category_id;
    }
    
    private function validate_category_data(array $data): array
    {
        $errors = new \WP_Error();
        
        if (empty($data['name'])) {
            $errors->add('invalid_name', 'Kategorie-Name ist erforderlich');
        }
        
        if ($errors->has_errors()) {
            return $errors;
        }
        
        return [
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'color' => sanitize_hex_color($data['color'] ?? '#007cba'),
            'parent_id' => intval($data['parent_id'] ?? 0) ?: null,
            'sort_order' => intval($data['sort_order'] ?? 0),
            'status' => 'active',
            'created_at' => current_time('mysql')
        ];
    }
    
    public function ajax_create_category(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        try {
            $category_id = $this->create_category($_POST);
            wp_send_json_success([
                'message' => 'Kategorie erfolgreich erstellt',
                'category_id' => $category_id
            ]);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function ajax_load_categories(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        global $wpdb;
        $table = $this->database->get_table('categories');
        
        $categories = $wpdb->get_results("
            SELECT * FROM {$table} 
            WHERE status = 'active' 
            ORDER BY sort_order, name
        ", ARRAY_A);
        
        wp_send_json_success(['categories' => $categories]);
    }
    
    public function ajax_update_category(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $category_id = intval($_POST['category_id'] ?? 0);
        
        if (!$category_id) {
            wp_send_json_error(['message' => 'Kategorie-ID ist erforderlich']);
            return;
        }
        
        try {
            $validated_data = $this->validate_category_data($_POST);
            
            if (is_wp_error($validated_data)) {
                throw new Exception($validated_data->get_error_message());
            }
            
            $result = $this->database->update('categories', $validated_data, ['id' => $category_id]);
            
            if ($result === false) {
                throw new Exception('Fehler beim Aktualisieren der Kategorie');
            }
            
            wp_send_json_success(['message' => 'Kategorie erfolgreich aktualisiert']);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    public function ajax_delete_category(): void
    {
        if (!check_ajax_referer('amp_admin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Sicherheitsprüfung fehlgeschlagen']);
            return;
        }
        
        $category_id = intval($_POST['category_id'] ?? 0);
        
        if (!$category_id) {
            wp_send_json_error(['message' => 'Kategorie-ID ist erforderlich']);
            return;
        }
        
        // Prüfen ob Kategorie in Verwendung
        global $wpdb;
        $products_table = $this->database->get_table('products');
        
        $products_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$products_table} WHERE category_id = %d AND status = 'active'",
            $category_id
        ));
        
        if ($products_count > 0) {
            wp_send_json_error(['message' => 'Kategorie kann nicht gelöscht werden: Wird von {$products_count} Produkten verwendet']);
            return;
        }
        
        // Soft delete
        $result = $this->database->update('categories', 
            ['status' => 'inactive'], 
            ['id' => $category_id]
        );
        
        if ($result === false) {
            wp_send_json_error(['message' => 'Fehler beim Löschen der Kategorie']);
            return;
        }
        
        wp_send_json_success(['message' => 'Kategorie erfolgreich gelöscht']);
    }
}