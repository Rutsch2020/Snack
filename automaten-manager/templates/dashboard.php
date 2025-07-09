<?php
/**
 * Dashboard Template
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}

// Statistiken aus der Datenbank laden
global $wpdb;
$products_table = $wpdb->prefix . 'am_products';
$categories_table = $wpdb->prefix . 'am_categories';

$total_products = $wpdb->get_var("SELECT COUNT(*) FROM $products_table");
$total_value = $wpdb->get_var("SELECT SUM(price * stock) FROM $products_table");
$low_stock_items = $wpdb->get_var("SELECT COUNT(*) FROM $products_table WHERE stock <= min_stock");
$categories_count = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");

// Fallback-Werte falls keine Daten vorhanden
$total_products = $total_products ?: 0;
$total_value = $total_value ?: 0;
$low_stock_items = $low_stock_items ?: 0;
$categories_count = $categories_count ?: 0;

// Kürzlich hinzugefügte Produkte
$recent_products = $wpdb->get_results(
    "SELECT p.*, c.name as category_name, c.color as category_color 
     FROM $products_table p 
     LEFT JOIN $categories_table c ON p.category_id = c.id 
     ORDER BY p.created_at DESC 
     LIMIT 3"
);
?>

<div class="am-admin-wrapper">
    <div class="am-dark-mode-toggle">
        <label class="am-toggle">
            <input type="checkbox" id="am-dark-mode-toggle">
            <span class="am-toggle-slider"></span>
        </label>
    </div>
    
    <div class="am-container">
        <div class="am-header">
            <div>
                <h1 class="am-title">Automaten Dashboard</h1>
                <p class="am-subtitle">Willkommen im ultramodernen Verwaltungssystem</p>
            </div>
            <div class="am-header-actions">
                <button class="am-btn am-btn-primary" id="am-header-quick-scan">
                    <i class="fas fa-qrcode"></i>
                    Quick Scan
                </button>
            </div>
        </div>
        
        <div class="am-grid am-grid-4 am-animate">
            <div class="am-stat-card">
                <div class="am-stat-icon">
                    <i class="fas fa-box fa-2x"></i>
                </div>
                <div class="am-stat-value"><?php echo $total_products; ?></div>
                <div class="am-stat-label">Produkte im System</div>
                <div class="am-stat-trend">
                    <i class="fas fa-arrow-up"></i> Gesamt verfügbar
                </div>
            </div>
            
            <div class="am-stat-card" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <div class="am-stat-icon">
                    <i class="fas fa-euro-sign fa-2x"></i>
                </div>
                <div class="am-stat-value">€<?php echo number_format($total_value, 2, ',', '.'); ?></div>
                <div class="am-stat-label">Gesamtwert Lager</div>
                <div class="am-stat-trend">
                    <i class="fas fa-chart-line"></i> Lagerwert
                </div>
            </div>
            
            <div class="am-stat-card" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="am-stat-icon">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <div class="am-stat-value"><?php echo $low_stock_items; ?></div>
                <div class="am-stat-label">Niedriger Bestand</div>
                <div class="am-stat-trend">
                    <?php if ($low_stock_items > 0): ?>
                        Artikel nachfüllen erforderlich
                    <?php else: ?>
                        Alle Bestände ausreichend
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="am-stat-card" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
                <div class="am-stat-icon">
                    <i class="fas fa-tags fa-2x"></i>
                </div>
                <div class="am-stat-value"><?php echo $categories_count; ?></div>
                <div class="am-stat-label">Kategorien</div>
                <div class="am-stat-trend">
                    Gut organisiert
                </div>
            </div>
        </div>

        <div class="am-quick-scan-section am-animate">
            <h2 class="am-section-title">
                <i class="fas fa-bolt"></i> Quick Scan
            </h2>
            
            <div class="am-glass-card am-quick-scan-card">
                <div class="am-card-body">
                    <div class="am-quick-scan-content">
                        <div class="am-quick-scan-icon">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="am-quick-scan-info">
                            <h3>Barcode scannen</h3>
                            <p>Scanne einen Barcode um das Produkt schnell zu verkaufen oder den Bestand zu aktualisieren.</p>
                        </div>
                        <button class="am-btn am-btn-primary am-btn-scan-now" id="am-dashboard-scanner">
                            <i class="fas fa-camera"></i>
                            Jetzt scannen
                        </button>
                    </div>
                    
                    <div class="am-quick-scan-manual">
                        <div class="am-divider">
                            <span>oder</span>
                        </div>
                        <div class="am-manual-barcode-input">
                            <input type="text" 
                                   id="am-quick-barcode-input" 
                                   class="am-input" 
                                   placeholder="Barcode manuell eingeben...">
                            <button class="am-btn am-btn-secondary" id="am-quick-barcode-submit">
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="am-card am-animate" style="margin-top: 2rem;">
            <h2 class="am-card-title">
                <i class="fas fa-rocket"></i> Schnellaktionen
            </h2>
            <div class="am-grid am-grid-3" style="margin-top: 1.5rem;">
                <a href="<?php echo admin_url('admin.php?page=am-products'); ?>" class="am-btn am-btn-primary">
                    <i class="fas fa-plus-circle"></i>
                    Neues Produkt
                </a>
                <button class="am-btn am-btn-success" id="am-inventory-scan">
                    <i class="fas fa-clipboard-check"></i>
                    Inventur Scanner
                </button>
                <a href="<?php echo admin_url('admin.php?page=am-scanner'); ?>" class="am-btn am-btn-secondary">
                    <i class="fas fa-barcode"></i>
                    Scanner öffnen
                </a>
            </div>
        </div>
        
        <div class="am-grid am-grid-2" style="margin-top: 2rem;">
            <div class="am-card am-animate">
                <h2 class="am-card-title">
                    <i class="fas fa-clock"></i> Kürzlich hinzugefügt
                </h2>
                <div class="am-recent-items">
                    <?php if ($recent_products): ?>
                        <?php foreach ($recent_products as $product): ?>
                            <div class="am-recent-item">
                                <div class="am-recent-icon" style="background: <?php echo ($product->category_color ?: '#3498db') . '20'; ?>; color: <?php echo $product->category_color ?: '#3498db'; ?>;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div class="am-recent-details">
                                    <h4><?php echo esc_html($product->name); ?></h4>
                                    <p><?php echo human_time_diff(strtotime($product->created_at), current_time('timestamp')); ?> hinzugefügt</p>
                                </div>
                                <div class="am-recent-price">€<?php echo number_format($product->price, 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="am-empty-state" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-inbox" style="font-size: 2rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                            <p style="color: var(--gray-500);">Noch keine Produkte vorhanden</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="am-card am-animate">
                <h2 class="am-card-title">
                    <i class="fas fa-heartbeat"></i> System Status
                </h2>
                <div class="am-system-status">
                    <div class="am-status-item">
                        <div class="am-status-indicator am-status-success"></div>
                        <div class="am-status-details">
                            <h4>Datenbank</h4>
                            <p>Alle Systeme funktionieren einwandfrei</p>
                        </div>
                    </div>
                    <div class="am-status-item">
                        <div class="am-status-indicator am-status-success"></div>
                        <div class="am-status-details">
                            <h4>Scanner-Modul</h4>
                            <p>Bereit für mobile Scans</p>
                        </div>
                    </div>
                    <div class="am-status-item">
                        <div class="am-status-indicator <?php echo is_ssl() ? 'am-status-success' : 'am-status-warning'; ?>"></div>
                        <div class="am-status-details">
                            <h4>HTTPS</h4>
                            <p><?php echo is_ssl() ? 'SSL-Verschlüsselung aktiv' : 'HTTPS für Kamera empfohlen'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="am-card am-animate" style="margin-top: 2rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
            <h2 style="color: white;">
                <i class="fas fa-star"></i> Verfügbare Features
            </h2>
            <div class="am-grid am-grid-3" style="margin-top: 1.5rem;">
                <div class="am-feature-preview">
                    <i class="fas fa-camera fa-3x" style="opacity: 0.8;"></i>
                    <h3>Barcode Scanner</h3>
                    <p>Direkt mit der Handykamera scannen</p>
                    <span class="am-badge am-badge-active">Jetzt verfügbar!</span>
                </div>
                <div class="am-feature-preview">
                    <i class="fas fa-mobile-alt fa-3x" style="opacity: 0.8;"></i>
                    <h3>Mobile optimiert</h3>
                    <p>Perfekt für Smartphones und Tablets</p>
                    <span class="am-badge am-badge-active">Verfügbar</span>
                </div>
                <div class="am-feature-preview">
                    <i class="fas fa-chart-bar fa-3x" style="opacity: 0.8;"></i>
                    <h3>Analytics Dashboard</h3>
                    <p>Detaillierte Verkaufsstatistiken</p>
                    <span class="am-badge">In Planung</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Additional Dashboard Styles */
.am-subtitle {
    color: var(--gray-500);
    margin-top: 0.5rem;
}

.am-stat-icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    opacity: 0.3;
}

.am-stat-trend {
    font-size: 0.875rem;
    margin-top: 0.5rem;
    opacity: 0.9;
}

.am-stat-trend i {
    margin-right: 0.25rem;
}

.am-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--gray-800);
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Quick Scan Section Styles */
.am-quick-scan-section {
    margin-top: 3rem;
}

.am-section-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.am-quick-scan-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
}

.am-card-body {
    padding: 0;
}

.am-quick-scan-content {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 2rem;
}

.am-quick-scan-icon {
    font-size: 4rem;
    color: #00ff88;
    opacity: 0.8;
}

.am-quick-scan-info {
    flex: 1;
}

.am-quick-scan-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.25rem;
    color: var(--gray-800);
}

.am-quick-scan-info p {
    margin: 0;
    color: var(--gray-600);
}

.am-btn-scan-now {
    background: linear-gradient(135deg, #00ff88, #00cc6a);
    color: white;
    padding: 1rem 2rem;
    font-size: 1rem;
    border: none;
    white-space: nowrap;
}

.am-btn-scan-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 255, 136, 0.4);
}

.am-quick-scan-manual {
    padding: 2rem;
    background: rgba(0, 0, 0, 0.02);
    border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.am-divider {
    text-align: center;
    margin: 0 0 1.5rem 0;
    position: relative;
}

.am-divider span {
    background: white;
    padding: 0 1rem;
    color: var(--gray-500);
    position: relative;
    z-index: 1;
}

.am-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gray-300);
}

.am-manual-barcode-input {
    display: flex;
    gap: 0.5rem;
}

.am-manual-barcode-input .am-input {
    flex: 1;
}

.am-manual-barcode-input .am-btn {
    padding: 0.75rem 1.5rem;
}

/* Recent Items */
.am-recent-items {
    margin-top: 1.5rem;
}

.am-recent-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 0.75rem;
    transition: all var(--transition-fast);
    cursor: pointer;
}

.am-recent-item:hover {
    background: var(--gray-50);
}

.am-recent-icon {
    width: 50px;
    height: 50px;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.am-recent-details h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.am-recent-details p {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: var(--gray-500);
}

.am-recent-price {
    margin-left: auto;
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--primary);
}

/* System Status */
.am-system-status {
    margin-top: 1.5rem;
}

.am-status-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--gray-100);
}

.am-status-item:last-child {
    border-bottom: none;
}

.am-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    position: relative;
}

.am-status-indicator::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.am-status-success {
    background: var(--success);
}

.am-status-success::before {
    background: var(--success);
}

.am-status-warning {
    background: var(--warning);
}

.am-status-warning::before {
    background: var(--warning);
}

.am-status-details h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.am-status-details p {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: var(--gray-500);
}

/* Feature Preview */
.am-feature-preview {
    text-align: center;
    padding: 1.5rem;
}

.am-feature-preview h3 {
    margin: 1rem 0 0.5rem 0;
    font-size: 1.125rem;
}

.am-feature-preview p {
    font-size: 0.875rem;
    opacity: 0.9;
}

.am-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 2rem;
    font-size: 0.75rem;
    font-weight: 600;
    margin-top: 0.5rem;
}

.am-badge-active {
    background: #00ff88;
    color: #004d2d;
}

/* Animation Classes */
.am-animate {
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.6s ease-out;
}

.am-animated {
    opacity: 1;
    transform: translateY(0);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .am-quick-scan-content {
        flex-direction: column;
        text-align: center;
    }
    
    .am-btn-scan-now {
        width: 100%;
    }
    
    .am-header-actions {
        display: none;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Animate elements on load
    setTimeout(function() {
        $('.am-animate').each(function(index) {
            setTimeout(() => {
                $(this).addClass('am-animated');
            }, index * 100);
        });
    }, 100);

    // Dark Mode Toggle
    $('#am-dark-mode-toggle').on('change', function() {
        $('body').toggleClass('am-dark-mode');
        localStorage.setItem('am-dark-mode', $(this).is(':checked'));
    });

    // Check saved dark mode preference
    if (localStorage.getItem('am-dark-mode') === 'true') {
        $('#am-dark-mode-toggle').prop('checked', true);
        $('body').addClass('am-dark-mode');
    }

    // Header Quick Scan Button
    $('#am-header-quick-scan').on('click', function(e) {
        e.preventDefault();
        if (typeof initScanner === 'function') {
            initScanner();
        } else {
            console.error('Scanner JS nicht geladen.');
            showNotification('Scanner ist nicht verfügbar. Bitte lade die Seite neu.', 'error');
        }
    });

    // Dashboard Scanner Button
    $('#am-dashboard-scanner').on('click', function(e) {
        e.preventDefault();
        if (typeof initScanner === 'function') {
            initScanner();
        } else {
            console.error('Scanner JS nicht geladen.');
            showNotification('Scanner ist nicht verfügbar. Bitte lade die Seite neu.', 'error');
        }
    });

    // Inventory Scanner
    $('#am-inventory-scan').on('click', function(e) {
        e.preventDefault();
        if (typeof initScanner === 'function') {
            initScanner();
        } else {
            console.error('Scanner JS nicht geladen.');
            showNotification('Scanner ist nicht verfügbar. Bitte lade die Seite neu.', 'error');
        }
    });
    
    // Quick barcode submit (manual input)
    $('#am-quick-barcode-submit').on('click', function() {
        const barcode = $('#am-quick-barcode-input').val().trim();
        if (barcode) {
            if (typeof handleBarcodeDetected === 'function') {
                handleBarcodeDetected(barcode, true);
            } else {
                console.error('handleBarcodeDetected nicht geladen.');
                showNotification('Scanner-Logik nicht verfügbar.', 'error');
            }
        } else {
            showNotification('Bitte gib einen Barcode ein.', 'error');
        }
    });
    
    // Enter key on quick input
    $('#am-quick-barcode-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#am-quick-barcode-submit').click();
        }
    });
});
</script>