<?php
/**
 * Inventory Template - Lager-Management & Bestandsübersicht
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Admin/Templates
 * @version     1.0.0
 * @since       1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Security Check
if (!current_user_can('amp_manage_inventory')) {
    wp_die(__('Sie haben keine Berechtigung für diese Seite.', 'automaten-manager-pro'));
}

// Mock data for demonstration (will be replaced by actual database queries)
$mock_inventory = [
    [
        'id' => 1,
        'name' => 'Coca Cola 0,5L',
        'barcode' => '4711234567890',
        'category' => 'Getränke',
        'current_stock' => 45,
        'min_stock' => 20,
        'max_stock' => 100,
        'buy_price' => 0.65,
        'sell_price' => 1.75,
        'expiry_date' => '2025-08-15',
        'last_movement' => '2025-07-27 14:30:00',
        'movement_type' => 'out',
        'turnover_days' => 3.2,
        'image_url' => 'coca-cola.jpg'
    ],
    [
        'id' => 2,
        'name' => 'Milch 3,5%',
        'barcode' => '9876543210123',
        'category' => 'Kühlprodukte',
        'current_stock' => 8,
        'min_stock' => 15,
        'max_stock' => 50,
        'buy_price' => 0.89,
        'sell_price' => 1.20,
        'expiry_date' => '2025-07-29',
        'last_movement' => '2025-07-27 13:45:00',
        'movement_type' => 'out',
        'turnover_days' => 5.8,
        'image_url' => 'milch.jpg'
    ],
    [
        'id' => 3,
        'name' => 'Chips Paprika',
        'barcode' => '1357924680135',
        'category' => 'Snacks',
        'current_stock' => 22,
        'min_stock' => 10,
        'max_stock' => 60,
        'buy_price' => 1.20,
        'sell_price' => 2.50,
        'expiry_date' => '2025-12-31',
        'last_movement' => '2025-07-27 12:15:00',
        'movement_type' => 'in',
        'turnover_days' => 7.4,
        'image_url' => 'chips.jpg'
    ],
    [
        'id' => 4,
        'name' => 'Brot Vollkorn',
        'barcode' => '2468135792468',
        'category' => 'Backwaren',
        'current_stock' => 3,
        'min_stock' => 5,
        'max_stock' => 25,
        'buy_price' => 1.80,
        'sell_price' => 2.50,
        'expiry_date' => '2025-07-28',
        'last_movement' => '2025-07-27 09:30:00',
        'movement_type' => 'disposal',
        'turnover_days' => 2.1,
        'image_url' => 'brot.jpg'
    ],
    [
        'id' => 5,
        'name' => 'Energy Drink',
        'barcode' => '3691472583690',
        'category' => 'Getränke',
        'current_stock' => 35,
        'min_stock' => 10,
        'max_stock' => 40,
        'buy_price' => 1.50,
        'sell_price' => 3.20,
        'expiry_date' => '2026-06-15',
        'last_movement' => '2025-07-20 16:00:00',
        'movement_type' => 'in',
        'turnover_days' => 45.0,
        'image_url' => 'energy.jpg'
    ]
];

// Calculate inventory statistics
$total_products = count($mock_inventory);
$low_stock_items = array_filter($mock_inventory, fn($item) => $item['current_stock'] <= $item['min_stock']);
$expiring_soon = array_filter($mock_inventory, function($item) {
    $days_until_expiry = (strtotime($item['expiry_date']) - time()) / (60 * 60 * 24);
    return $days_until_expiry <= 7;
});
$overstock_items = array_filter($mock_inventory, fn($item) => $item['current_stock'] > $item['max_stock'] * 0.9);

$total_value = array_sum(array_map(fn($item) => $item['current_stock'] * $item['buy_price'], $mock_inventory));
$total_units = array_sum(array_column($mock_inventory, 'current_stock'));
$avg_turnover = array_sum(array_column($mock_inventory, 'turnover_days')) / $total_products;

function getStockStatus($current, $min, $max) {
    if ($current <= $min) return 'critical';
    if ($current <= $min * 1.5) return 'low';
    if ($current >= $max * 0.9) return 'high';
    return 'normal';
}

function getExpiryStatus($expiry_date) {
    $days = (strtotime($expiry_date) - time()) / (60 * 60 * 24);
    if ($days < 0) return 'expired';
    if ($days <= 2) return 'critical';
    if ($days <= 7) return 'warning';
    return 'good';
}

function getTurnoverClass($days) {
    if ($days <= 7) return 'fast';
    if ($days <= 21) return 'medium';
    return 'slow';
}
?>

<div class="amp-container">
    <!-- Header Section -->
    <div class="amp-header-section">
        <div class="amp-header-content">
            <div class="amp-header-left">
                <h1 class="amp-page-title">
                    <i class="amp-icon-cube"></i>
                    <?php _e('Lager & Inventur', 'automaten-manager-pro'); ?>
                </h1>
                <p class="amp-page-subtitle">
                    <?php _e('Bestandsübersicht, Bewegungshistorie und Inventur-Management', 'automaten-manager-pro'); ?>
                </p>
            </div>
            <div class="amp-header-actions">
                <button class="amp-btn amp-btn-primary" id="amp-start-inventory-btn">
                    <i class="amp-icon-clipboard-list"></i>
                    <?php _e('Inventur starten', 'automaten-manager-pro'); ?>
                </button>
                <div class="amp-btn-group">
                    <button class="amp-btn amp-btn-secondary" id="amp-stock-movement-btn">
                        <i class="amp-icon-arrows-right-left"></i>
                        <?php _e('Bewegungen', 'automaten-manager-pro'); ?>
                    </button>
                    <button class="amp-btn amp-btn-secondary" id="amp-abc-analysis-btn">
                        <i class="amp-icon-chart-bar"></i>
                        <?php _e('ABC-Analyse', 'automaten-manager-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Stats Grid -->
    <div class="amp-inventory-stats-grid">
        <div class="amp-inventory-stat-card amp-card-total-value">
            <div class="amp-stat-icon">
                <i class="amp-icon-banknotes"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo number_format($total_value, 2); ?>€</div>
                <div class="amp-stat-label"><?php _e('Lagerwert (Einkauf)', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-positive">+5.2%</div>
            </div>
        </div>
        
        <div class="amp-inventory-stat-card amp-card-total-products">
            <div class="amp-stat-icon">
                <i class="amp-icon-cube"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo $total_products; ?></div>
                <div class="amp-stat-label"><?php _e('Verschiedene Artikel', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-meta"><?php echo $total_units; ?> Stück gesamt</div>
            </div>
        </div>
        
        <div class="amp-inventory-stat-card amp-card-low-stock">
            <div class="amp-stat-icon">
                <i class="amp-icon-exclamation-triangle"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo count($low_stock_items); ?></div>
                <div class="amp-stat-label"><?php _e('Niedrigbestand', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-negative">+2 seit gestern</div>
            </div>
        </div>
        
        <div class="amp-inventory-stat-card amp-card-expiring">
            <div class="amp-stat-icon">
                <i class="amp-icon-clock"></i>
            </div>
            <div class="amp-stat-content">
                <div class="amp-stat-value"><?php echo count($expiring_soon); ?></div>
                <div class="amp-stat-label"><?php _e('Bald ablaufend (7 Tage)', 'automaten-manager-pro'); ?></div>
                <div class="amp-stat-change amp-warning">Prüfung erforderlich</div>
            </div>
        </div>
    </div>

    <!-- Quick Action Cards -->
    <div class="amp-quick-actions-grid">
        <div class="amp-quick-action-card amp-action-critical" onclick="ampFilterInventory('critical')">
            <div class="amp-action-icon">
                <i class="amp-icon-exclamation-circle"></i>
            </div>
            <div class="amp-action-content">
                <div class="amp-action-title"><?php _e('Kritische Bestände', 'automaten-manager-pro'); ?></div>
                <div class="amp-action-count"><?php echo count($low_stock_items); ?> Artikel</div>
            </div>
            <i class="amp-icon-chevron-right amp-action-arrow"></i>
        </div>
        
        <div class="amp-quick-action-card amp-action-expiring" onclick="ampFilterInventory('expiring')">
            <div class="amp-action-icon">
                <i class="amp-icon-calendar-days"></i>
            </div>
            <div class="amp-action-content">
                <div class="amp-action-title"><?php _e('Bald ablaufend', 'automaten-manager-pro'); ?></div>
                <div class="amp-action-count"><?php echo count($expiring_soon); ?> Artikel</div>
            </div>
            <i class="amp-icon-chevron-right amp-action-arrow"></i>
        </div>
        
        <div class="amp-quick-action-card amp-action-overstock" onclick="ampFilterInventory('overstock')">
            <div class="amp-action-icon">
                <i class="amp-icon-archive-box"></i>
            </div>
            <div class="amp-action-content">
                <div class="amp-action-title"><?php _e('Überbestände', 'automaten-manager-pro'); ?></div>
                <div class="amp-action-count"><?php echo count($overstock_items); ?> Artikel</div>
            </div>
            <i class="amp-icon-chevron-right amp-action-arrow"></i>
        </div>
        
        <div class="amp-quick-action-card amp-action-turnover" onclick="ampShowTurnoverAnalysis()">
            <div class="amp-action-icon">
                <i class="amp-icon-arrow-path"></i>
            </div>
            <div class="amp-action-content">
                <div class="amp-action-title"><?php _e('Ø Umschlag', 'automaten-manager-pro'); ?></div>
                <div class="amp-action-count"><?php echo number_format($avg_turnover, 1); ?> Tage</div>
            </div>
            <i class="amp-icon-chevron-right amp-action-arrow"></i>
        </div>
    </div>

    <!-- Filter & Search Section -->
    <div class="amp-card amp-inventory-filter-card">
        <div class="amp-filter-header">
            <h3><?php _e('Filter & Suche', 'automaten-manager-pro'); ?></h3>
            <div class="amp-filter-actions">
                <button class="amp-btn amp-btn-ghost" id="amp-reset-inventory-filters-btn">
                    <i class="amp-icon-refresh"></i>
                    <?php _e('Zurücksetzen', 'automaten-manager-pro'); ?>
                </button>
                <button class="amp-btn amp-btn-ghost" id="amp-save-filter-preset-btn">
                    <i class="amp-icon-bookmark"></i>
                    <?php _e('Filter speichern', 'automaten-manager-pro'); ?>
                </button>
            </div>
        </div>
        
        <div class="amp-inventory-filter-grid">
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Kategorie', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-category">
                    <option value=""><?php _e('Alle Kategorien', 'automaten-manager-pro'); ?></option>
                    <option value="getränke">Getränke</option>
                    <option value="snacks">Snacks</option>
                    <option value="kühlprodukte">Kühlprodukte</option>
                    <option value="backwaren">Backwaren</option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Bestandsstatus', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-stock-status">
                    <option value=""><?php _e('Alle Status', 'automaten-manager-pro'); ?></option>
                    <option value="critical"><?php _e('Kritisch (≤ Mindestbestand)', 'automaten-manager-pro'); ?></option>
                    <option value="low"><?php _e('Niedrig', 'automaten-manager-pro'); ?></option>
                    <option value="normal"><?php _e('Normal', 'automaten-manager-pro'); ?></option>
                    <option value="high"><?php _e('Hoch', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Haltbarkeit', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-expiry">
                    <option value=""><?php _e('Alle Haltbarkeiten', 'automaten-manager-pro'); ?></option>
                    <option value="expired"><?php _e('Abgelaufen', 'automaten-manager-pro'); ?></option>
                    <option value="critical"><?php _e('Kritisch (≤ 2 Tage)', 'automaten-manager-pro'); ?></option>
                    <option value="warning"><?php _e('Warnung (≤ 7 Tage)', 'automaten-manager-pro'); ?></option>
                    <option value="good"><?php _e('Gut (> 7 Tage)', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
            
            <div class="amp-filter-group">
                <label class="amp-label"><?php _e('Umschlagshäufigkeit', 'automaten-manager-pro'); ?></label>
                <select class="amp-form-control" id="amp-filter-turnover">
                    <option value=""><?php _e('Alle Kategorien', 'automaten-manager-pro'); ?></option>
                    <option value="fast"><?php _e('Schnelldreher (≤ 7 Tage)', 'automaten-manager-pro'); ?></option>
                    <option value="medium"><?php _e('Mitteldreher (8-21 Tage)', 'automaten-manager-pro'); ?></option>
                    <option value="slow"><?php _e('Langsamdreher (> 21 Tage)', 'automaten-manager-pro'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="amp-search-row">
            <div class="amp-search-box amp-search-expanded">
                <input type="text" class="amp-form-control" placeholder="<?php _e('Produktname, Barcode oder Kategorie suchen...', 'automaten-manager-pro'); ?>" id="amp-search-inventory">
                <i class="amp-icon-search"></i>
            </div>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="amp-card amp-inventory-table-card">
        <div class="amp-table-header">
            <h3><?php _e('Lagerbestand', 'automaten-manager-pro'); ?></h3>
            <div class="amp-table-actions">
                <div class="amp-view-toggle">
                    <button class="amp-btn amp-btn-sm amp-btn-ghost amp-view-active" data-view="table">
                        <i class="amp-icon-table-cells"></i>
                        <?php _e('Tabelle', 'automaten-manager-pro'); ?>
                    </button>
                    <button class="amp-btn amp-btn-sm amp-btn-ghost" data-view="cards">
                        <i class="amp-icon-squares-2x2"></i>
                        <?php _e('Karten', 'automaten-manager-pro'); ?>
                    </button>
                </div>
                <button class="amp-btn amp-btn-secondary" id="amp-export-inventory-btn">
                    <i class="amp-icon-arrow-down-tray"></i>
                    <?php _e('Export', 'automaten-manager-pro'); ?>
                </button>
            </div>
        </div>
        
        <!-- Table View -->
        <div class="amp-inventory-view amp-table-view" id="amp-table-view">
            <div class="amp-table-responsive">
                <table class="amp-table" id="amp-inventory-table">
                    <thead>
                        <tr>
                            <th class="amp-sortable" data-sort="name">
                                <?php _e('Produkt', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th class="amp-sortable" data-sort="category">
                                <?php _e('Kategorie', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th class="amp-sortable" data-sort="current_stock">
                                <?php _e('Bestand', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th class="amp-sortable" data-sort="expiry_date">
                                <?php _e('Haltbarkeit', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th class="amp-sortable" data-sort="turnover_days">
                                <?php _e('Umschlag', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th class="amp-sortable" data-sort="value">
                                <?php _e('Wert', 'automaten-manager-pro'); ?>
                                <i class="amp-icon-chevron-up-down"></i>
                            </th>
                            <th><?php _e('Aktionen', 'automaten-manager-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mock_inventory as $item): 
                            $stock_status = getStockStatus($item['current_stock'], $item['min_stock'], $item['max_stock']);
                            $expiry_status = getExpiryStatus($item['expiry_date']);
                            $turnover_class = getTurnoverClass($item['turnover_days']);
                            $item_value = $item['current_stock'] * $item['buy_price'];
                            $expiry_date = new DateTime($item['expiry_date']);
                            $days_until_expiry = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                        ?>
                        <tr class="amp-inventory-row" 
                            data-product-id="<?php echo $item['id']; ?>"
                            data-category="<?php echo strtolower($item['category']); ?>"
                            data-stock-status="<?php echo $stock_status; ?>"
                            data-expiry-status="<?php echo $expiry_status; ?>"
                            data-turnover-class="<?php echo $turnover_class; ?>">
                            
                            <td class="amp-product-info">
                                <div class="amp-product-display">
                                    <div class="amp-product-image">
                                        <img src="<?php echo AMP_PLUGIN_URL; ?>admin/assets/images/placeholder.png" 
                                             alt="<?php echo esc_attr($item['name']); ?>" 
                                             class="amp-product-thumb">
                                    </div>
                                    <div class="amp-product-details">
                                        <div class="amp-product-name"><?php echo esc_html($item['name']); ?></div>
                                        <div class="amp-product-barcode"><?php echo esc_html($item['barcode']); ?></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="amp-category">
                                <span class="amp-category-badge"><?php echo esc_html($item['category']); ?></span>
                            </td>
                            
                            <td class="amp-stock-info">
                                <div class="amp-stock-display">
                                    <div class="amp-stock-main">
                                        <span class="amp-current-stock amp-stock-<?php echo $stock_status; ?>">
                                            <?php echo $item['current_stock']; ?>
                                        </span>
                                        <span class="amp-stock-unit">Stück</span>
                                    </div>
                                    <div class="amp-stock-range">
                                        Min: <?php echo $item['min_stock']; ?> | Max: <?php echo $item['max_stock']; ?>
                                    </div>
                                    <div class="amp-stock-bar">
                                        <?php 
                                        $fill_percentage = min(100, ($item['current_stock'] / $item['max_stock']) * 100);
                                        ?>
                                        <div class="amp-stock-fill amp-stock-fill-<?php echo $stock_status; ?>" 
                                             style="width: <?php echo $fill_percentage; ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="amp-expiry-info">
                                <div class="amp-expiry-display amp-expiry-<?php echo $expiry_status; ?>">
                                    <div class="amp-expiry-date"><?php echo $expiry_date->format('d.m.Y'); ?></div>
                                    <div class="amp-expiry-days">
                                        <?php if ($days_until_expiry < 0): ?>
                                            <?php printf(__('%d Tage abgelaufen', 'automaten-manager-pro'), abs($days_until_expiry)); ?>
                                        <?php elseif ($days_until_expiry == 0): ?>
                                            <?php _e('Läuft heute ab', 'automaten-manager-pro'); ?>
                                        <?php else: ?>
                                            <?php printf(__('noch %d Tage', 'automaten-manager-pro'), $days_until_expiry); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="amp-turnover-info">
                                <div class="amp-turnover-display amp-turnover-<?php echo $turnover_class; ?>">
                                    <div class="amp-turnover-days"><?php echo number_format($item['turnover_days'], 1); ?></div>
                                    <div class="amp-turnover-label">Tage</div>
                                </div>
                            </td>
                            
                            <td class="amp-value-info">
                                <div class="amp-value-display">
                                    <div class="amp-total-value"><?php echo number_format($item_value, 2); ?>€</div>
                                    <div class="amp-unit-price"><?php echo number_format($item['buy_price'], 2); ?>€/St</div>
                                </div>
                            </td>
                            
                            <td class="amp-inventory-actions">
                                <div class="amp-action-buttons">
                                    <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                            onclick="ampAdjustStock(<?php echo $item['id']; ?>)" 
                                            title="<?php _e('Bestand anpassen', 'automaten-manager-pro'); ?>">
                                        <i class="amp-icon-calculator"></i>
                                    </button>
                                    <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                            onclick="ampViewMovements(<?php echo $item['id']; ?>)" 
                                            title="<?php _e('Bewegungshistorie', 'automaten-manager-pro'); ?>">
                                        <i class="amp-icon-list-bullet"></i>
                                    </button>
                                    <button class="amp-btn amp-btn-sm amp-btn-ghost" 
                                            onclick="ampEditProduct(<?php echo $item['id']; ?>)" 
                                            title="<?php _e('Produkt bearbeiten', 'automaten-manager-pro'); ?>">
                                        <i class="amp-icon-pencil"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Cards View -->
        <div class="amp-inventory-view amp-cards-view" id="amp-cards-view" style="display: none;">
            <div class="amp-inventory-cards-grid">
                <?php foreach ($mock_inventory as $item): 
                    $stock_status = getStockStatus($item['current_stock'], $item['min_stock'], $item['max_stock']);
                    $expiry_status = getExpiryStatus($item['expiry_date']);
                    $turnover_class = getTurnoverClass($item['turnover_days']);
                    $item_value = $item['current_stock'] * $item['buy_price'];
                    $days_until_expiry = ceil((strtotime($item['expiry_date']) - time()) / (60 * 60 * 24));
                ?>
                <div class="amp-inventory-card amp-card-<?php echo $stock_status; ?>" data-product-id="<?php echo $item['id']; ?>">
                    <div class="amp-card-header">
                        <div class="amp-product-image-large">
                            <img src="<?php echo AMP_PLUGIN_URL; ?>admin/assets/images/placeholder.png" 
                                 alt="<?php echo esc_attr($item['name']); ?>">
                        </div>
                        <div class="amp-status-indicators">
                            <span class="amp-stock-indicator amp-stock-<?php echo $stock_status; ?>"></span>
                            <span class="amp-expiry-indicator amp-expiry-<?php echo $expiry_status; ?>"></span>
                        </div>
                    </div>
                    <div class="amp-card-body">
                        <h4 class="amp-card-title"><?php echo esc_html($item['name']); ?></h4>
                        <p class="amp-card-barcode"><?php echo esc_html($item['barcode']); ?></p>
                        
                        <div class="amp-card-stats">
                            <div class="amp-stat-item">
                                <span class="amp-stat-label"><?php _e('Bestand', 'automaten-manager-pro'); ?></span>
                                <span class="amp-stat-value amp-stock-<?php echo $stock_status; ?>">
                                    <?php echo $item['current_stock']; ?> Stück
                                </span>
                            </div>
                            <div class="amp-stat-item">
                                <span class="amp-stat-label"><?php _e('Haltbarkeit', 'automaten-manager-pro'); ?></span>
                                <span class="amp-stat-value amp-expiry-<?php echo $expiry_status; ?>">
                                    <?php if ($days_until_expiry < 0): ?>
                                        <?php _e('Abgelaufen', 'automaten-manager-pro'); ?>
                                    <?php elseif ($days_until_expiry <= 7): ?>
                                        <?php echo $days_until_expiry; ?> Tage
                                    <?php else: ?>
                                        <?php echo date('d.m.Y', strtotime($item['expiry_date'])); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="amp-stat-item">
                                <span class="amp-stat-label"><?php _e('Umschlag', 'automaten-manager-pro'); ?></span>
                                <span class="amp-stat-value amp-turnover-<?php echo $turnover_class; ?>">
                                    <?php echo number_format($item['turnover_days'], 1); ?> Tage
                                </span>
                            </div>
                            <div class="amp-stat-item">
                                <span class="amp-stat-label"><?php _e('Wert', 'automaten-manager-pro'); ?></span>
                                <span class="amp-stat-value"><?php echo number_format($item_value, 2); ?>€</span>
                            </div>
                        </div>
                    </div>
                    <div class="amp-card-footer">
                        <button class="amp-btn amp-btn-sm amp-btn-primary" onclick="ampAdjustStock(<?php echo $item['id']; ?>)">
                            <i class="amp-icon-calculator"></i>
                            <?php _e('Anpassen', 'automaten-manager-pro'); ?>
                        </button>
                        <button class="amp-btn amp-btn-sm amp-btn-ghost" onclick="ampViewMovements(<?php echo $item['id']; ?>)">
                            <i class="amp-icon-list-bullet"></i>
                        </button>
                        <button class="amp-btn amp-btn-sm amp-btn-ghost" onclick="ampEditProduct(<?php echo $item['id']; ?>)">
                            <i class="amp-icon-pencil"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="amp-table-pagination">
            <div class="amp-pagination-info">
                <?php _e('Zeige 1-5 von 5 Produkten', 'automaten-manager-pro'); ?>
            </div>
            <div class="amp-pagination-controls">
                <button class="amp-btn amp-btn-sm amp-btn-ghost" disabled>
                    <i class="amp-icon-chevron-left"></i>
                    <?php _e('Zurück', 'automaten-manager-pro'); ?>
                </button>
                <span class="amp-pagination-current">1</span>
                <button class="amp-btn amp-btn-sm amp-btn-ghost" disabled>
                    <?php _e('Weiter', 'automaten-manager-pro'); ?>
                    <i class="amp-icon-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="amp-modal" id="amp-stock-adjustment-modal">
    <div class="amp-modal-backdrop" onclick="ampCloseModal('amp-stock-adjustment-modal')"></div>
    <div class="amp-modal-content">
        <div class="amp-modal-header">
            <h3><?php _e('Bestand anpassen', 'automaten-manager-pro'); ?></h3>
            <button class="amp-modal-close" onclick="ampCloseModal('amp-stock-adjustment-modal')">
                <i class="amp-icon-x"></i>
            </button>
        </div>
        <div class="amp-modal-body" id="amp-stock-adjustment-content">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Inventory Modal -->
<div class="amp-modal" id="amp-inventory-modal">
    <div class="amp-modal-backdrop" onclick="ampCloseModal('amp-inventory-modal')"></div>
    <div class="amp-modal-content amp-modal-large">
        <div class="amp-modal-header">
            <h3><?php _e('Inventur durchführen', 'automaten-manager-pro'); ?></h3>
            <button class="amp-modal-close" onclick="ampCloseModal('amp-inventory-modal')">
                <i class="amp-icon-x"></i>
            </button>
        </div>
        <div class="amp-modal-body">
            <div class="amp-inventory-instructions">
                <div class="amp-instruction-card">
                    <i class="amp-icon-info-circle"></i>
                    <div class="amp-instruction-content">
                        <h4><?php _e('Inventur-Hinweise', 'automaten-manager-pro'); ?></h4>
                        <p><?php _e('Scannen Sie alle Produkte einzeln oder verwenden Sie die manuelle Eingabe. Die Differenzen werden automatisch als Bestandskorrekturen dokumentiert.', 'automaten-manager-pro'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="amp-inventory-actions">
                <a href="<?php echo admin_url('admin.php?page=automaten-manager-scanner&mode=inventory'); ?>" 
                   class="amp-btn amp-btn-primary amp-btn-lg">
                    <i class="amp-icon-qr-code"></i>
                    <?php _e('Scanner-Inventur starten', 'automaten-manager-pro'); ?>
                </a>
                <button class="amp-btn amp-btn-secondary amp-btn-lg" onclick="ampStartManualInventory()">
                    <i class="amp-icon-clipboard-list"></i>
                    <?php _e('Manuelle Inventur', 'automaten-manager-pro'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Inventory-specific styles */
.amp-inventory-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.amp-inventory-stat-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.9));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.amp-inventory-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 16px 16px 0 0;
}

.amp-card-total-value::before { background: linear-gradient(90deg, #10B981, #059669); }
.amp-card-total-products::before { background: linear-gradient(90deg, #007BFF, #0056b3); }
.amp-card-low-stock::before { background: linear-gradient(90deg, #F59E0B, #d97706); }
.amp-card-expiring::before { background: linear-gradient(90deg, #EF4444, #DC2626); }

.amp-inventory-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.amp-card-total-value .amp-stat-icon { background: linear-gradient(135deg, #10B981, #059669); }
.amp-card-total-products .amp-stat-icon { background: linear-gradient(135deg, #007BFF, #0056b3); }
.amp-card-low-stock .amp-stat-icon { background: linear-gradient(135deg, #F59E0B, #d97706); }
.amp-card-expiring .amp-stat-icon { background: linear-gradient(135deg, #EF4444, #DC2626); }

.amp-stat-meta {
    font-size: 0.75rem;
    color: #9CA3AF;
    margin-top: 0.25rem;
}

.amp-quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.amp-quick-action-card {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.amp-quick-action-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    background: rgba(255, 255, 255, 0.95);
}

.amp-action-critical:hover { border-color: #F59E0B; }
.amp-action-expiring:hover { border-color: #EF4444; }
.amp-action-overstock:hover { border-color: #8B5CF6; }
.amp-action-turnover:hover { border-color: #10B981; }

.amp-action-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.amp-action-critical .amp-action-icon { background: linear-gradient(135deg, #F59E0B, #d97706); }
.amp-action-expiring .amp-action-icon { background: linear-gradient(135deg, #EF4444, #DC2626); }
.amp-action-overstock .amp-action-icon { background: linear-gradient(135deg, #8B5CF6, #7C3AED); }
.amp-action-turnover .amp-action-icon { background: linear-gradient(135deg, #10B981, #059669); }

.amp-action-content {
    flex: 1;
}

.amp-action-title {
    font-weight: 600;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-action-count {
    font-size: 0.875rem;
    color: #6B7280;
}

.amp-action-arrow {
    color: #9CA3AF;
    transition: transform 0.3s;
}

.amp-quick-action-card:hover .amp-action-arrow {
    transform: translateX(4px);
}

.amp-inventory-filter-card {
    margin-bottom: 2rem;
}

.amp-inventory-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.amp-search-row {
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #E5E7EB;
}

.amp-search-expanded {
    max-width: none;
}

.amp-view-toggle {
    display: flex;
    background: rgba(255, 255, 255, 0.8);
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #E5E7EB;
}

.amp-view-toggle .amp-btn {
    border-radius: 0;
    border: none;
    background: transparent;
}

.amp-view-toggle .amp-btn.amp-view-active {
    background: var(--amp-primary);
    color: white;
}

.amp-product-display {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.amp-product-image {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    overflow: hidden;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amp-product-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.amp-product-name {
    font-weight: 600;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-product-barcode {
    font-size: 0.75rem;
    color: #6B7280;
    font-family: monospace;
}

.amp-category-badge {
    background: rgba(59, 130, 246, 0.1);
    color: var(--amp-primary);
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
}

.amp-stock-display {
    text-align: center;
}

.amp-stock-main {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.amp-current-stock {
    font-size: 1.25rem;
    font-weight: 700;
}

.amp-stock-critical { color: #EF4444; }
.amp-stock-low { color: #F59E0B; }
.amp-stock-normal { color: #10B981; }
.amp-stock-high { color: #8B5CF6; }

.amp-stock-unit {
    font-size: 0.75rem;
    color: #6B7280;
}

.amp-stock-range {
    font-size: 0.75rem;
    color: #6B7280;
    margin-bottom: 0.5rem;
}

.amp-stock-bar {
    width: 100%;
    height: 4px;
    background: #E5E7EB;
    border-radius: 2px;
    overflow: hidden;
}

.amp-stock-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.3s;
}

.amp-stock-fill-critical { background: #EF4444; }
.amp-stock-fill-low { background: #F59E0B; }
.amp-stock-fill-normal { background: #10B981; }
.amp-stock-fill-high { background: #8B5CF6; }

.amp-expiry-display {
    text-align: center;
    padding: 0.5rem;
    border-radius: 8px;
}

.amp-expiry-expired { background: rgba(239, 68, 68, 0.1); color: #DC2626; }
.amp-expiry-critical { background: rgba(239, 68, 68, 0.1); color: #DC2626; }
.amp-expiry-warning { background: rgba(245, 158, 11, 0.1); color: #D97706; }
.amp-expiry-good { background: rgba(16, 185, 129, 0.1); color: #059669; }

.amp-expiry-date {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.amp-expiry-days {
    font-size: 0.75rem;
    opacity: 0.8;
}

.amp-turnover-display {
    text-align: center;
    padding: 0.5rem;
    border-radius: 8px;
}

.amp-turnover-fast { background: rgba(16, 185, 129, 0.1); color: #059669; }
.amp-turnover-medium { background: rgba(245, 158, 11, 0.1); color: #D97706; }
.amp-turnover-slow { background: rgba(239, 68, 68, 0.1); color: #DC2626; }

.amp-turnover-days {
    font-size: 1.125rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.amp-turnover-label {
    font-size: 0.75rem;
    opacity: 0.8;
}

.amp-value-display {
    text-align: right;
}

.amp-total-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--amp-text);
    margin-bottom: 0.25rem;
}

.amp-unit-price {
    font-size: 0.75rem;
    color: #6B7280;
}

/* Cards View Styles */
.amp-inventory-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.5rem;
}

.amp-inventory-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 249, 250, 0.9));
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.amp-inventory-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    border-radius: 16px 16px 0 0;
}

.amp-card-critical::before { background: #EF4444; }
.amp-card-low::before { background: #F59E0B; }
.amp-card-normal::before { background: #10B981; }
.amp-card-high::before { background: #8B5CF6; }

.amp-inventory-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.amp-card-header {
    position: relative;
    padding: 1.5rem;
    text-align: center;
}

.amp-product-image-large {
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    border-radius: 12px;
    overflow: hidden;
    background: #F3F4F6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amp-product-image-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.amp-status-indicators {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.25rem;
}

.amp-stock-indicator,
.amp-expiry-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.amp-stock-indicator.amp-stock-critical { background: #EF4444; }
.amp-stock-indicator.amp-stock-low { background: #F59E0B; }
.amp-stock-indicator.amp-stock-normal { background: #10B981; }
.amp-stock-indicator.amp-stock-high { background: #8B5CF6; }

.amp-expiry-indicator.amp-expiry-expired { background: #DC2626; }
.amp-expiry-indicator.amp-expiry-critical { background: #EF4444; }
.amp-expiry-indicator.amp-expiry-warning { background: #F59E0B; }
.amp-expiry-indicator.amp-expiry-good { background: #10B981; }

.amp-card-body {
    padding: 0 1.5rem 1rem;
}

.amp-card-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--amp-text);
    margin-bottom: 0.5rem;
    text-align: center;
}

.amp-card-barcode {
    font-size: 0.75rem;
    color: #6B7280;
    font-family: monospace;
    text-align: center;
    margin-bottom: 1rem;
}

.amp-card-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.amp-stat-item {
    text-align: center;
}

.amp-stat-label {
    display: block;
    font-size: 0.75rem;
    color: #6B7280;
    margin-bottom: 0.25rem;
}

.amp-stat-value {
    font-weight: 600;
    color: var(--amp-text);
}

.amp-card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #E5E7EB;
    display: flex;
    gap: 0.5rem;
}

.amp-card-footer .amp-btn:first-child {
    flex: 1;
}

.amp-inventory-instructions {
    margin-bottom: 2rem;
}

.amp-instruction-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 12px;
    border: 1px solid rgba(59, 130, 246, 0.2);
}

.amp-instruction-card i {
    font-size: 1.5rem;
    color: var(--amp-primary);
    margin-top: 0.25rem;
}

.amp-instruction-content h4 {
    margin-bottom: 0.5rem;
    color: var(--amp-text);
}

.amp-inventory-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Samsung S25 Ultra optimizations */
@media (max-width: 430px) and (min-height: 900px) {
    .amp-inventory-stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    
    .amp-inventory-stat-card {
        padding: 1rem;
    }
    
    .amp-quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .amp-inventory-filter-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .amp-inventory-cards-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .amp-card-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .amp-inventory-actions {
        flex-direction: column;
    }
    
    .amp-table {
        min-width: 600px;
    }
}

/* Touch optimizations */
@media (pointer: coarse) {
    .amp-quick-action-card {
        min-height: 60px;
    }
    
    .amp-inventory-card {
        min-height: 200px;
    }
    
    .amp-action-buttons .amp-btn {
        min-width: 44px;
        min-height: 44px;
    }
}

/* High DPI displays */
@media (-webkit-min-device-pixel-ratio: 3), (min-resolution: 3dppx) {
    .amp-inventory-card {
        border-width: 0.5px;
    }
    
    .amp-stock-bar {
        height: 3px;
    }
    
    .amp-status-indicators span {
        width: 10px;
        height: 10px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize inventory page functionality
    initInventoryPage();
});

function initInventoryPage() {
    // Filter handling
    setupInventoryFilters();
    
    // View toggle
    setupViewToggle();
    
    // Table sorting
    setupTableSorting();
    
    // Search functionality
    const searchInput = document.getElementById('amp-search-inventory');
    if (searchInput) {
        searchInput.addEventListener('input', ampFilterInventorySearch);
    }
    
    // Button handlers
    setupInventoryButtons();
}

function setupInventoryFilters() {
    const filters = ['category', 'stock-status', 'expiry', 'turnover'];
    
    filters.forEach(filter => {
        const element = document.getElementById(`amp-filter-${filter}`);
        if (element) {
            element.addEventListener('change', ampApplyInventoryFilters);
        }
    });
    
    const resetBtn = document.getElementById('amp-reset-inventory-filters-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', ampResetInventoryFilters);
    }
}

function setupViewToggle() {
    const viewButtons = document.querySelectorAll('.amp-view-toggle .amp-btn');
    const tableView = document.getElementById('amp-table-view');
    const cardsView = document.getElementById('amp-cards-view');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            // Update active button
            viewButtons.forEach(b => b.classList.remove('amp-view-active'));
            this.classList.add('amp-view-active');
            
            // Toggle views
            if (view === 'table') {
                tableView.style.display = 'block';
                cardsView.style.display = 'none';
            } else {
                tableView.style.display = 'none';
                cardsView.style.display = 'block';
            }
        });
    });
}

function setupTableSorting() {
    const sortableHeaders = document.querySelectorAll('.amp-sortable');
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sortField = this.dataset.sort;
            const icon = this.querySelector('i');
            
            // Toggle sort direction
            if (icon.classList.contains('amp-icon-chevron-up')) {
                icon.className = 'amp-icon-chevron-down';
            } else if (icon.classList.contains('amp-icon-chevron-down')) {
                icon.className = 'amp-icon-chevron-up-down';
            } else {
                icon.className = 'amp-icon-chevron-up';
            }
            
            // Reset other headers
            sortableHeaders.forEach(otherHeader => {
                if (otherHeader !== this) {
                    const otherIcon = otherHeader.querySelector('i');
                    otherIcon.className = 'amp-icon-chevron-up-down';
                }
            });
            
            ampSortInventoryTable(sortField, icon.classList.contains('amp-icon-chevron-up') ? 'asc' : 'desc');
        });
    });
}

function setupInventoryButtons() {
    const startInventoryBtn = document.getElementById('amp-start-inventory-btn');
    if (startInventoryBtn) {
        startInventoryBtn.addEventListener('click', () => ampShowModal('amp-inventory-modal'));
    }
    
    const stockMovementBtn = document.getElementById('amp-stock-movement-btn');
    if (stockMovementBtn) {
        stockMovementBtn.addEventListener('click', ampShowStockMovements);
    }
    
    const abcAnalysisBtn = document.getElementById('amp-abc-analysis-btn');
    if (abcAnalysisBtn) {
        abcAnalysisBtn.addEventListener('click', ampShowABCAnalysis);
    }
    
    const exportBtn = document.getElementById('amp-export-inventory-btn');
    if (exportBtn) {
        exportBtn.addEventListener('click', ampExportInventory);
    }
}

function ampFilterInventory(type) {
    const rows = document.querySelectorAll('.amp-inventory-row');
    const cards = document.querySelectorAll('.amp-inventory-card');
    
    // Reset all filters first
    const filters = document.querySelectorAll('.amp-inventory-filter-card select');
    filters.forEach(filter => filter.value = '');
    
    // Apply specific filter
    let filterAttribute, filterValue;
    
    switch(type) {
        case 'critical':
            filterAttribute = 'data-stock-status';
            filterValue = 'critical';
            document.getElementById('amp-filter-stock-status').value = 'critical';
            break;
        case 'expiring':
            filterAttribute = 'data-expiry-status';
            filterValue = 'critical';
            document.getElementById('amp-filter-expiry').value = 'critical';
            break;
        case 'overstock':
            filterAttribute = 'data-stock-status';
            filterValue = 'high';
            document.getElementById('amp-filter-stock-status').value = 'high';
            break;
        default:
            // Show all
            rows.forEach(row => row.style.display = '');
            cards.forEach(card => card.style.display = '');
            return;
    }
    
    // Filter rows and cards
    rows.forEach(row => {
        const value = row.getAttribute(filterAttribute);
        row.style.display = value === filterValue ? '' : 'none';
    });
    
    cards.forEach(card => {
        const value = card.getAttribute(filterAttribute);
        card.style.display = value === filterValue ? '' : 'none';
    });
    
    ampShowNotification(`Filter angewendet: ${getFilterLabel(type)}`, 'success');
}

function getFilterLabel(type) {
    const labels = {
        'critical': 'Kritische Bestände',
        'expiring': 'Bald ablaufend',
        'overstock': 'Überbestände'
    };
    return labels[type] || type;
}

function ampApplyInventoryFilters() {
    const categoryFilter = document.getElementById('amp-filter-category').value;
    const stockFilter = document.getElementById('amp-filter-stock-status').value;
    const expiryFilter = document.getElementById('amp-filter-expiry').value;
    const turnoverFilter = document.getElementById('amp-filter-turnover').value;
    
    const rows = document.querySelectorAll('.amp-inventory-row');
    const cards = document.querySelectorAll('.amp-inventory-card');
    
    function shouldShow(element) {
        if (categoryFilter && element.dataset.category !== categoryFilter) return false;
        if (stockFilter && element.dataset.stockStatus !== stockFilter) return false;
        if (expiryFilter && element.dataset.expiryStatus !== expiryFilter) return false;
        if (turnoverFilter && element.dataset.turnoverClass !== turnoverFilter) return false;
        return true;
    }
    
    rows.forEach(row => {
        row.style.display = shouldShow(row) ? '' : 'none';
    });
    
    cards.forEach(card => {
        card.style.display = shouldShow(card) ? '' : 'none';
    });
}

function ampFilterInventorySearch() {
    const searchTerm = document.getElementById('amp-search-inventory').value.toLowerCase();
    const rows = document.querySelectorAll('.amp-inventory-row');
    const cards = document.querySelectorAll('.amp-inventory-card');
    
    function matchesSearch(element) {
        const text = element.textContent.toLowerCase();
        return text.includes(searchTerm);
    }
    
    rows.forEach(row => {
        row.style.display = matchesSearch(row) ? '' : 'none';
    });
    
    cards.forEach(card => {
        card.style.display = matchesSearch(card) ? '' : 'none';
    });
}

function ampResetInventoryFilters() {
    // Reset all filter inputs
    const filters = document.querySelectorAll('.amp-inventory-filter-card select, .amp-inventory-filter-card input');
    filters.forEach(filter => filter.value = '');
    
    // Show all items
    const rows = document.querySelectorAll('.amp-inventory-row');
    const cards = document.querySelectorAll('.amp-inventory-card');
    
    rows.forEach(row => row.style.display = '');
    cards.forEach(card => card.style.display = '');
    
    ampShowNotification('Filter zurückgesetzt', 'success');
}

function ampSortInventoryTable(field, direction) {
    // Table sorting logic would go here
    console.log(`Sorting inventory by ${field} in ${direction} order`);
    ampShowNotification(`Tabelle sortiert nach ${field}`, 'success');
}

function ampAdjustStock(productId) {
    // Load stock adjustment modal
    const modal = document.getElementById('amp-stock-adjustment-modal');
    const content = document.getElementById('amp-stock-adjustment-content');
    
    ampShowModal('amp-stock-adjustment-modal');
    
    // Simulate loading content
    content.innerHTML = `
        <div class="amp-loading-spinner">
            <div class="amp-spinner"></div>
            <p>Lade Produktdaten...</p>
        </div>
    `;
    
    setTimeout(() => {
        content.innerHTML = `
            <div class="amp-stock-adjustment-form">
                <div class="amp-product-summary">
                    <h4>Coca Cola 0,5L</h4>
                    <p>Barcode: 4711234567890</p>
                    <p>Aktueller Bestand: <strong>45 Stück</strong></p>
                </div>
                
                <div class="amp-adjustment-options">
                    <div class="amp-form-group">
                        <label class="amp-label">Anpassungstyp:</label>
                        <select class="amp-form-control" id="amp-adjustment-type">
                            <option value="correction">Inventur-Korrektur</option>
                            <option value="damage">Beschädigung</option>
                            <option value="theft">Schwund</option>
                            <option value="return">Rückgabe</option>
                        </select>
                    </div>
                    
                    <div class="amp-form-group">
                        <label class="amp-label">Neue Anzahl:</label>
                        <div class="amp-quantity-input">
                            <button type="button" class="amp-btn amp-btn-sm amp-btn-ghost" onclick="ampAdjustQuantity(-1)">-</button>
                            <input type="number" class="amp-form-control" id="amp-new-quantity" value="45" min="0">
                            <button type="button" class="amp-btn amp-btn-sm amp-btn-ghost" onclick="ampAdjustQuantity(1)">+</button>
                        </div>
                        <small class="amp-help-text">Differenz: <span id="amp-quantity-diff">0</span> Stück</small>
                    </div>
                    
                    <div class="amp-form-group">
                        <label class="amp-label">Grund (optional):</label>
                        <textarea class="amp-form-control" rows="3" placeholder="Beschreibung der Bestandsanpassung..."></textarea>
                    </div>
                </div>
                
                <div class="amp-modal-actions">
                    <button class="amp-btn amp-btn-primary" onclick="ampSaveStockAdjustment(${productId})">
                        <i class="amp-icon-check"></i>
                        Bestand anpassen
                    </button>
                    <button class="amp-btn amp-btn-secondary" onclick="ampCloseModal('amp-stock-adjustment-modal')">
                        Abbrechen
                    </button>
                </div>
            </div>
        `;
        
        // Setup quantity adjustment
        const quantityInput = document.getElementById('amp-new-quantity');
        const diffDisplay = document.getElementById('amp-quantity-diff');
        
        quantityInput.addEventListener('input', function() {
            const newQty = parseInt(this.value) || 0;
            const currentQty = 45; // Would come from data
            const diff = newQty - currentQty;
            diffDisplay.textContent = diff > 0 ? `+${diff}` : diff.toString();
            diffDisplay.className = diff > 0 ? 'text-success' : diff < 0 ? 'text-danger' : '';
        });
    }, 500);
}

function ampAdjustQuantity(change) {
    const input = document.getElementById('amp-new-quantity');
    const currentValue = parseInt(input.value) || 0;
    const newValue = Math.max(0, currentValue + change);
    input.value = newValue;
    input.dispatchEvent(new Event('input'));
}

function ampSaveStockAdjustment(productId) {
    // Save stock adjustment logic
    ampCloseModal('amp-stock-adjustment-modal');
    ampShowNotification('Bestandsanpassung gespeichert', 'success');
}

function ampViewMovements(productId) {
    // Show movement history
    ampShowNotification('Bewegungshistorie wird geladen...', 'info');
}

function ampEditProduct(productId) {
    // Redirect to product edit page
    window.location.href = `admin.php?page=automaten-manager-products&action=edit&id=${productId}`;
}

function ampShowStockMovements() {
    ampShowNotification('Lager-Bewegungen werden geladen...', 'info');
}

function ampShowTurnoverAnalysis() {
    ampShowModal('amp-inventory-modal');
}

function ampShowABCAnalysis() {
    ampShowNotification('ABC-Analyse wird vorbereitet...', 'info');
}

function ampExportInventory() {
    ampShowNotification('Lagerbestand wird exportiert...', 'success');
}

function ampStartManualInventory() {
    ampCloseModal('amp-inventory-modal');
    ampShowNotification('Manuelle Inventur gestartet', 'success');
}
</script>