<?php
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Statistiken abrufen (korrekte Tabellennamen)
global $wpdb;

$total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE is_active = 1");
$total_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}automaten_categories WHERE is_active = 1");
$low_stock_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE stock_quantity <= min_stock AND is_active = 1");
$total_value = $wpdb->get_var("SELECT SUM(price * stock_quantity) FROM {$wpdb->prefix}automaten_products WHERE is_active = 1");

// Letzte Scans
$recent_scans = $wpdb->get_results("
    SELECT sl.*, p.name as product_name, u.display_name as user_name
    FROM {$wpdb->prefix}automaten_scan_logs sl
    LEFT JOIN {$wpdb->prefix}automaten_products p ON sl.barcode = p.barcode
    LEFT JOIN {$wpdb->prefix}users u ON sl.user_id = u.ID
    ORDER BY sl.scanned_at DESC
    LIMIT 10
");

// Top-Produkte nach Scans
$top_products = $wpdb->get_results("
    SELECT p.name, p.barcode, p.stock_quantity, p.price, c.name as category_name, c.color as category_color,
           COUNT(sl.id) as scan_count
    FROM {$wpdb->prefix}automaten_products p
    LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id
    LEFT JOIN {$wpdb->prefix}automaten_scan_logs sl ON p.barcode = sl.barcode
    WHERE p.is_active = 1
    GROUP BY p.id
    ORDER BY scan_count DESC
    LIMIT 5
");

// Produkte mit niedrigem Bestand
$low_stock_products = $wpdb->get_results("
    SELECT p.*, c.name as category_name, c.color as category_color
    FROM {$wpdb->prefix}automaten_products p
    LEFT JOIN {$wpdb->prefix}automaten_categories c ON p.category_id = c.id
    WHERE p.stock_quantity <= p.min_stock AND p.is_active = 1
    ORDER BY p.stock_quantity ASC
    LIMIT 10
");
?>

<div class="wrap">
    <style>
    .automaten-dashboard {
        max-width: 1400px;
        margin: 0;
        padding: 20px 0;
    }
    
    .automaten-page-header {
        margin-bottom: 30px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
    
    .automaten-page-title {
        font-size: 2.5rem;
        margin: 0 0 10px 0;
        font-weight: 700;
    }
    
    .automaten-page-subtitle {
        font-size: 1.1rem;
        margin: 0;
        opacity: 0.9;
    }
    
    .automaten-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .automaten-stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    .automaten-stat-card:hover {
        transform: translateY(-5px);
    }
    
    .automaten-stat-card-icon {
        font-size: 2.5rem;
        margin-bottom: 15px;
        opacity: 0.9;
    }
    
    .automaten-stat-card-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .automaten-stat-card-label {
        font-size: 1rem;
        opacity: 0.9;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .automaten-grid {
        display: grid;
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .automaten-grid-2 {
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    }
    
    .automaten-grid-3 {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
    
    .automaten-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e1e5e9;
    }
    
    .automaten-card-header {
        padding: 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #e1e5e9;
    }
    
    .automaten-card-title {
        margin: 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #495057;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .automaten-card-body {
        padding: 20px;
    }
    
    .automaten-card-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        border-top: 1px solid #e1e5e9;
    }
    
    .automaten-text-center {
        text-align: center;
    }
    
    .automaten-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .automaten-btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .automaten-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .automaten-flex {
        display: flex;
    }
    
    .automaten-items-center {
        align-items: center;
    }
    
    .automaten-justify-between {
        justify-content: space-between;
    }
    
    .automaten-gap-4 {
        gap: 16px;
    }
    
    .automaten-product-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 15px 0;
        border-bottom: 1px solid #e1e5e9;
    }
    
    .automaten-product-item:last-child {
        border-bottom: none;
    }
    
    .automaten-product-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .automaten-stock-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .automaten-stock-good {
        background-color: #28a745;
    }
    
    .automaten-stock-low {
        background-color: #ffc107;
    }
    
    .automaten-stock-empty {
        background-color: #dc3545;
    }
    
    .automaten-product-category {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        color: white;
        font-weight: 500;
    }
    
    .automaten-activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #e1e5e9;
    }
    
    .automaten-activity-item:last-child {
        border-bottom: none;
    }
    
    .automaten-activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    .automaten-activity-sell {
        background-color: #dc3545;
    }
    
    .automaten-activity-restock {
        background-color: #28a745;
    }
    
    .automaten-activity-content {
        flex: 1;
    }
    
    .automaten-activity-title {
        font-weight: 600;
        color: #495057;
        margin: 0 0 4px 0;
    }
    
    .automaten-activity-subtitle {
        font-size: 0.875rem;
        color: #6c757d;
        margin: 0;
    }
    
    .automaten-activity-time {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: right;
    }
    
    .automaten-quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .automaten-quick-action {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 30px 20px;
        border-radius: 12px;
        text-decoration: none;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .automaten-quick-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        color: white;
        text-decoration: none;
    }
    
    .automaten-quick-action i {
        font-size: 2rem;
    }
    
    .automaten-action-scanner {
        background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
    }
    
    .automaten-action-product {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .automaten-action-category {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }
    
    .automaten-action-reports {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }
    
    @media (max-width: 768px) {
        .automaten-stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .automaten-grid-2 {
            grid-template-columns: 1fr;
        }
        
        .automaten-quick-actions {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .automaten-page-title {
            font-size: 2rem;
        }
    }
    </style>

    <div class="automaten-dashboard">
        <!-- Page Header -->
        <div class="automaten-page-header">
            <h1 class="automaten-page-title">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </h1>
            <p class="automaten-page-subtitle">
                Übersicht über Ihre Automaten-Verwaltung
            </p>
        </div>

        <!-- Statistik-Karten -->
        <div class="automaten-stats-grid">
            <div class="automaten-stat-card">
                <div class="automaten-stat-card-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="automaten-stat-card-value"><?php echo number_format($total_products); ?></div>
                <div class="automaten-stat-card-label">Gesamt Produkte</div>
            </div>

            <div class="automaten-stat-card" style="background: linear-gradient(135deg, #10b981, #059669);">
                <div class="automaten-stat-card-icon">
                    <i class="fas fa-tags"></i>
                </div>
                <div class="automaten-stat-card-value"><?php echo number_format($total_categories); ?></div>
                <div class="automaten-stat-card-label">Kategorien</div>
            </div>

            <div class="automaten-stat-card" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                <div class="automaten-stat-card-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="automaten-stat-card-value"><?php echo number_format($low_stock_count); ?></div>
                <div class="automaten-stat-card-label">Niedriger Bestand</div>
            </div>

            <div class="automaten-stat-card" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                <div class="automaten-stat-card-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="automaten-stat-card-value"><?php echo number_format($total_value ?: 0, 2); ?>€</div>
                <div class="automaten-stat-card-label">Lagerwert</div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="automaten-grid automaten-grid-2">
            
            <!-- Produkte mit niedrigem Bestand -->
            <div class="automaten-card">
                <div class="automaten-card-header">
                    <h3 class="automaten-card-title">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        Niedriger Bestand
                    </h3>
                </div>
                <div class="automaten-card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="automaten-text-center" style="padding: 40px 20px; color: #6c757d;">
                            <i class="fas fa-check-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 20px;"></i>
                            <p>Alle Produkte haben ausreichend Bestand!</p>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="automaten-product-item">
                                    <div class="automaten-product-info">
                                        <span class="automaten-stock-indicator <?php echo $product->stock_quantity == 0 ? 'automaten-stock-empty' : 'automaten-stock-low'; ?>"></span>
                                        <div>
                                            <div style="font-weight: 500; color: #495057;">
                                                <?php echo esc_html($product->name); ?>
                                            </div>
                                            <div style="font-size: 0.875rem; color: #6c757d;">
                                                <span class="automaten-product-category" style="background-color: <?php echo esc_attr($product->category_color ?: '#6b7280'); ?>;">
                                                    <?php echo esc_html($product->category_name ?: 'Allgemein'); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 1.25rem; font-weight: 600; color: <?php echo $product->stock_quantity == 0 ? '#dc3545' : '#f59e0b'; ?>;">
                                            <?php echo $product->stock_quantity; ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6c757d;">
                                            Min: <?php echo $product->min_stock; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="automaten-card-footer">
                            <a href="<?php echo admin_url('admin.php?page=automaten-products'); ?>" class="automaten-btn automaten-btn-primary">
                                <i class="fas fa-boxes"></i>
                                Alle Produkte verwalten
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Letzte Aktivitäten -->
            <div class="automaten-card">
                <div class="automaten-card-header">
                    <h3 class="automaten-card-title">
                        <i class="fas fa-history" style="color: #667eea;"></i>
                        Letzte Aktivitäten
                    </h3>
                </div>
                <div class="automaten-card-body">
                    <?php if (empty($recent_scans)): ?>
                        <div class="automaten-text-center" style="padding: 40px 20px; color: #6c757d;">
                            <i class="fas fa-qrcode" style="font-size: 3rem; margin-bottom: 20px;"></i>
                            <p>Noch keine Scanner-Aktivitäten</p>
                            <a href="<?php echo admin_url('admin.php?page=automaten-scanner'); ?>" class="automaten-btn automaten-btn-primary" style="margin-top: 15px;">
                                <i class="fas fa-camera"></i>
                                Scanner öffnen
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recent_scans as $scan): ?>
                                <div class="automaten-activity-item">
                                    <div class="automaten-activity-icon <?php echo $scan->action_type == 'sell' ? 'automaten-activity-sell' : 'automaten-activity-restock'; ?>">
                                        <i class="fas <?php echo $scan->action_type == 'sell' ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
                                    </div>
                                    <div class="automaten-activity-content">
                                        <div class="automaten-activity-title">
                                            <?php echo esc_html($scan->product_name ?: 'Unbekanntes Produkt'); ?>
                                        </div>
                                        <div class="automaten-activity-subtitle">
                                            <?php echo ucfirst($scan->action_type == 'sell' ? 'Verkauf' : 'Nachfüllung'); ?> 
                                            von <?php echo esc_html($scan->user_name ?: 'Unbekannt'); ?>
                                        </div>
                                    </div>
                                    <div class="automaten-activity-time">
                                        <div style="font-size: 1.125rem; font-weight: 600; color: #495057;">
                                            <?php echo $scan->action_type == 'sell' ? '-' : '+'; ?><?php echo $scan->quantity ?: 1; ?>
                                        </div>
                                        <div style="font-size: 0.75rem; color: #6c757d;">
                                            <?php echo date_i18n('H:i', strtotime($scan->scanned_at)); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Schnellaktionen -->
        <div class="automaten-card">
            <div class="automaten-card-header">
                <h3 class="automaten-card-title">
                    <i class="fas fa-bolt" style="color: #00b4db;"></i>
                    Schnellaktionen
                </h3>
            </div>
            <div class="automaten-card-body">
                <div class="automaten-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=automaten-scanner'); ?>" class="automaten-quick-action automaten-action-scanner">
                        <i class="fas fa-qrcode"></i>
                        <span>Scanner öffnen</span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=automaten-products'); ?>" class="automaten-quick-action automaten-action-product">
                        <i class="fas fa-plus"></i>
                        <span>Produkt hinzufügen</span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=automaten-categories'); ?>" class="automaten-quick-action automaten-action-category">
                        <i class="fas fa-tags"></i>
                        <span>Kategorien</span>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=automaten-reports'); ?>" class="automaten-quick-action automaten-action-reports">
                        <i class="fas fa-chart-line"></i>
                        <span>Berichte</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Top-Produkte -->
        <?php if (!empty($top_products)): ?>
        <div class="automaten-card" style="margin-top: 30px;">
            <div class="automaten-card-header">
                <h3 class="automaten-card-title">
                    <i class="fas fa-star" style="color: #f59e0b;"></i>
                    Beliebteste Produkte
                </h3>
            </div>
            <div class="automaten-card-body">
                <div class="automaten-grid automaten-grid-3">
                    <?php foreach ($top_products as $product): ?>
                        <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 2rem; margin-bottom: 10px; color: #667eea;">
                                <i class="fas fa-cube"></i>
                            </div>
                            <div style="font-weight: 600; margin-bottom: 5px;">
                                <?php echo esc_html($product->name); ?>
                            </div>
                            <div style="font-size: 1.2rem; color: #28a745; margin-bottom: 10px;">
                                <?php echo number_format($product->price, 2); ?>€
                            </div>
                            <div style="font-size: 0.875rem; color: #6c757d;">
                                <?php echo $product->scan_count; ?> Scans
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Statistik-Karten Animation
    const statCards = document.querySelectorAll('.automaten-stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});
</script>