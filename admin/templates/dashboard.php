<?php
/**
 * Dashboard Template - Ultra-Modern Light Design
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Admin/Templates
 * @version     1.0.0
 * @since       1.0.0
 */

// Sicherheitscheck
if (!defined('ABSPATH')) {
    exit;
}

// Template-Variablen
$page_title = $page_title ?? __('Dashboard', 'automaten-manager-pro');
$current_user = $current_user ?? wp_get_current_user();
$quick_stats = $quick_stats ?? array();

// Default-Statistiken falls keine vorhanden
$stats = array_merge(array(
    'products_total' => 0,
    'products_active' => 0,
    'products_low_stock' => 0,
    'products_expired' => 0,
    'sales_today' => 0,
    'sales_week' => 0,
    'revenue_today' => 0.00,
    'revenue_week' => 0.00
), $quick_stats);
?>

<style>
/* Ultra-Modern Light Design - GLASSMORPHISM & MODERNE EFFEKTE */
.amp-dashboard-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    margin: -20px -20px -20px -2px;
    padding: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.amp-dashboard-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 40px 30px;
}

/* Header mit Glassmorphism */
.amp-dashboard-header {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 24px;
    padding: 32px 40px;
    margin-bottom: 32px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.amp-dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
}

.amp-dashboard-title {
    color: #ffffff;
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 8px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.amp-dashboard-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 1.1rem;
    font-weight: 400;
    margin: 0;
}

.amp-dashboard-welcome {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.amp-welcome-user {
    color: rgba(255, 255, 255, 0.95);
    font-size: 1rem;
    font-weight: 500;
}

/* Scanner-Button - Ultra prominent */
.amp-scanner-button {
    background: linear-gradient(135deg, #00d4ff 0%, #0056ff 100%);
    color: white;
    padding: 16px 32px;
    border: none;
    border-radius: 16px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 24px rgba(0, 86, 255, 0.3);
    position: relative;
    overflow: hidden;
}

.amp-scanner-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.6s ease;
}

.amp-scanner-button:hover::before {
    left: 100%;
}

.amp-scanner-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 36px rgba(0, 86, 255, 0.4);
    color: white;
    text-decoration: none;
}

.amp-scanner-icon {
    font-size: 1.4rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

/* Stats Grid - Ultra-moderne Karten */
.amp-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.amp-stat-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 20px;
    padding: 28px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

.amp-stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--card-accent, linear-gradient(90deg, #00d4ff, #0056ff));
}

.amp-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
    background: rgba(255, 255, 255, 0.95);
}

.amp-stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.amp-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: var(--icon-bg, linear-gradient(135deg, #667eea, #764ba2));
}

.amp-stat-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #1a202c;
    margin: 0;
    line-height: 1;
}

.amp-stat-label {
    font-size: 0.9rem;
    color: #718096;
    font-weight: 500;
    margin: 8px 0 0 0;
}

.amp-stat-change {
    font-size: 0.85rem;
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 8px;
    display: inline-block;
}

.amp-stat-change.positive {
    background: rgba(72, 187, 120, 0.1);
    color: #38a169;
}

.amp-stat-change.negative {
    background: rgba(245, 101, 101, 0.1);
    color: #e53e3e;
}

/* Spezielle Stat-Card Farben */
.amp-stat-card.products { --card-accent: linear-gradient(90deg, #667eea, #764ba2); --icon-bg: linear-gradient(135deg, #667eea, #764ba2); }
.amp-stat-card.sales { --card-accent: linear-gradient(90deg, #48bb78, #38a169); --icon-bg: linear-gradient(135deg, #48bb78, #38a169); }
.amp-stat-card.revenue { --card-accent: linear-gradient(90deg, #ed8936, #dd6b20); --icon-bg: linear-gradient(135deg, #ed8936, #dd6b20); }
.amp-stat-card.alerts { --card-accent: linear-gradient(90deg, #f56565, #e53e3e); --icon-bg: linear-gradient(135deg, #f56565, #e53e3e); }

/* Quick Actions - Moderne Button-Grid */
.amp-quick-actions {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 40px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.amp-section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #2d3748;
    margin: 0 0 24px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.amp-section-icon {
    font-size: 1.1rem;
}

.amp-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}

.amp-action-button {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 16px;
    padding: 20px;
    text-decoration: none;
    color: #4a5568;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    position: relative;
    overflow: hidden;
}

.amp-action-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.6), transparent);
    transition: left 0.6s ease;
}

.amp-action-button:hover::before {
    left: 100%;
}

.amp-action-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: var(--button-color, #667eea);
    color: var(--button-color, #667eea);
    text-decoration: none;
}

.amp-action-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: white;
    background: var(--button-color, linear-gradient(135deg, #667eea, #764ba2));
    flex-shrink: 0;
}

.amp-action-content h4 {
    margin: 0 0 4px 0;
    font-size: 1rem;
    font-weight: 600;
    color: #2d3748;
}

.amp-action-content p {
    margin: 0;
    font-size: 0.85rem;
    color: #718096;
    line-height: 1.4;
}

/* Spezielle Button-Farben */
.amp-action-button.scanner { --button-color: #0056ff; }
.amp-action-button.products { --button-color: #48bb78; }
.amp-action-button.inventory { --button-color: #ed8936; }
.amp-action-button.reports { --button-color: #9f7aea; }

/* Info-Grid - Drei-Spalten Layout */
.amp-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.amp-info-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.5);
    border-radius: 20px;
    padding: 28px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.amp-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.amp-info-card h3 {
    margin: 0 0 20px 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.amp-info-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.amp-info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f7fafc;
    font-size: 0.9rem;
}

.amp-info-item:last-child {
    border-bottom: none;
}

.amp-info-label {
    color: #4a5568;
    font-weight: 500;
}

.amp-info-value {
    color: #2d3748;
    font-weight: 600;
}

.amp-status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.amp-status-ok {
    background: rgba(72, 187, 120, 0.1);
    color: #38a169;
}

.amp-status-warning {
    background: rgba(237, 137, 54, 0.1);
    color: #dd6b20;
}

.amp-status-error {
    background: rgba(245, 101, 101, 0.1);
    color: #e53e3e;
}

/* News & Updates Card */
.amp-news-content {
    text-align: center;
    padding: 40px 20px;
    color: #718096;
}

.amp-news-icon {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* Loading Animation */
.amp-loading {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 50%;
    border-top-color: #0056ff;
    animation: spin 1s ease-in-out infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .amp-dashboard-container {
        padding: 20px 15px;
    }
    
    .amp-dashboard-header {
        padding: 24px 20px;
        margin-bottom: 24px;
    }
    
    .amp-dashboard-title {
        font-size: 2rem;
    }
    
    .amp-dashboard-welcome {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .amp-stats-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    
    .amp-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .amp-info-grid {
        grid-template-columns: 1fr;
    }
}

/* Dark Mode Unterstützung (falls gewünscht) */
@media (prefers-color-scheme: dark) {
    .amp-dashboard-wrapper {
        background: linear-gradient(135deg, #1a202c 0%, #2d3748 100%);
    }
}

/* Smooth Scrolling */
html {
    scroll-behavior: smooth;
}

/* Focus States für Accessibility */
.amp-action-button:focus,
.amp-scanner-button:focus {
    outline: 3px solid rgba(0, 86, 255, 0.3);
    outline-offset: 2px;
}
</style>

<div class="amp-dashboard-wrapper">
    <div class="amp-dashboard-container">
        
        <!-- Header mit Glassmorphism -->
        <div class="amp-dashboard-header">
            <div class="amp-dashboard-welcome">
                <div>
                    <h1 class="amp-dashboard-title">📊 <?php echo esc_html($page_title); ?></h1>
                    <p class="amp-dashboard-subtitle">
                        Willkommen zurück, <?php echo esc_html($current_user->display_name); ?>. Hier ist Ihre Lagerverwaltung im Überblick.
                    </p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=amp-scanner'); ?>" class="amp-scanner-button">
                    <span class="amp-scanner-icon">📱</span>
                    Scanner öffnen
                </a>
            </div>
        </div>

        <!-- Statistik-Karten Grid -->
        <div class="amp-stats-grid">
            
            <!-- Aktive Produkte -->
            <div class="amp-stat-card products">
                <div class="amp-stat-header">
                    <div class="amp-stat-icon">📦</div>
                    <span class="amp-stat-change positive">+<?php echo esc_html($stats['products_active']); ?></span>
                </div>
                <h2 class="amp-stat-value"><?php echo esc_html($stats['products_total']); ?></h2>
                <p class="amp-stat-label">Aktive Produkte</p>
                <?php if ($stats['products_total'] > 0): ?>
                    <small style="color: #718096;">von <?php echo esc_html($stats['products_total']); ?> gesamt</small>
                <?php endif; ?>
            </div>

            <!-- Verkäufe heute -->
            <div class="amp-stat-card sales">
                <div class="amp-stat-header">
                    <div class="amp-stat-icon">🛒</div>
                    <span class="amp-stat-change positive">Heute</span>
                </div>
                <h2 class="amp-stat-value"><?php echo esc_html($stats['sales_today']); ?></h2>
                <p class="amp-stat-label">Verkäufe heute</p>
                <?php if ($stats['sales_week'] > 0): ?>
                    <small style="color: #718096;"><?php echo esc_html($stats['sales_week']); ?> diese Woche</small>
                <?php endif; ?>
            </div>

            <!-- Umsatz heute -->
            <div class="amp-stat-card revenue">
                <div class="amp-stat-header">
                    <div class="amp-stat-icon">💰</div>
                    <span class="amp-stat-change positive">+<?php echo number_format($stats['revenue_today'], 2); ?>€</span>
                </div>
                <h2 class="amp-stat-value"><?php echo number_format($stats['revenue_today'], 2); ?>€</h2>
                <p class="amp-stat-label">Umsatz heute</p>
                <?php if ($stats['revenue_week'] > 0): ?>
                    <small style="color: #718096;"><?php echo number_format($stats['revenue_week'], 2); ?>€ diese Woche</small>
                <?php endif; ?>
            </div>

            <!-- Warnungen -->
            <div class="amp-stat-card alerts">
                <div class="amp-stat-header">
                    <div class="amp-stat-icon">⚠️</div>
                    <span class="amp-stat-change <?php echo ($stats['products_low_stock'] + $stats['products_expired'] > 0) ? 'negative' : 'positive'; ?>">
                        <?php echo ($stats['products_low_stock'] + $stats['products_expired'] > 0) ? 'Achtung' : 'Alles OK'; ?>
                    </span>
                </div>
                <h2 class="amp-stat-value"><?php echo esc_html($stats['products_low_stock'] + $stats['products_expired']); ?></h2>
                <p class="amp-stat-label">Warnungen</p>
                <small style="color: #718096;">
                    <?php echo esc_html($stats['products_low_stock']); ?> wenig Bestand, 
                    <?php echo esc_html($stats['products_expired']); ?> abgelaufen
                </small>
            </div>

        </div>

        <!-- Schnellzugriff-Aktionen -->
        <div class="amp-quick-actions">
            <h2 class="amp-section-title">
                <span class="amp-section-icon">⚡</span>
                Schnellzugriff
            </h2>
            <p style="color: #718096; margin-bottom: 24px; font-size: 0.95rem;">
                Die wichtigsten Funktionen mit einem Klick
            </p>
            
            <div class="amp-actions-grid">
                
                <a href="<?php echo admin_url('admin.php?page=amp-scanner'); ?>" class="amp-action-button scanner">
                    <div class="amp-action-icon">📱</div>
                    <div class="amp-action-content">
                        <h4>Scanner öffnen</h4>
                        <p>Barcode scannen und Aktionen durchführen</p>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=amp-products&action=add'); ?>" class="amp-action-button products">
                    <div class="amp-action-icon">➕</div>
                    <div class="amp-action-content">
                        <h4>Neues Produkt</h4>
                        <p>Produkt manuell hinzufügen und konfigurieren</p>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=amp-inventory'); ?>" class="amp-action-button inventory">
                    <div class="amp-action-icon">📋</div>
                    <div class="amp-action-content">
                        <h4>Lager-Übersicht</h4>
                        <p>Bestände prüfen und verwalten</p>
                    </div>
                </a>

                <a href="<?php echo admin_url('admin.php?page=amp-reports'); ?>" class="amp-action-button reports">
                    <div class="amp-action-icon">📊</div>
                    <div class="amp-action-content">
                        <h4>Berichte</h4>
                        <p>Statistiken und Analysen einsehen</p>
                    </div>
                </a>

            </div>
        </div>

        <!-- Info-Grid (System-Status, Links, News) -->
        <div class="amp-info-grid">
            
            <!-- System-Status -->
            <div class="amp-info-card">
                <h3>
                    <span style="color: #48bb78;">⚙️</span>
                    System-Status
                </h3>
                <ul class="amp-info-list">
                    <li class="amp-info-item">
                        <span class="amp-info-label">Plugin-Version</span>
                        <span class="amp-status-badge amp-status-ok">1.0.0</span>
                    </li>
                    <li class="amp-info-item">
                        <span class="amp-info-label">WordPress</span>
                        <span class="amp-status-badge amp-status-ok"><?php echo get_bloginfo('version'); ?></span>
                    </li>
                    <li class="amp-info-item">
                        <span class="amp-info-label">PHP-Version</span>
                        <span class="amp-status-badge amp-status-ok"><?php echo PHP_VERSION; ?></span>
                    </li>
                    <li class="amp-info-item">
                        <span class="amp-info-label">Datenbank</span>
                        <span class="amp-status-badge amp-status-ok">Verbunden</span>
                    </li>
                </ul>
                <p style="margin-top: 20px; font-size: 0.85rem; color: #718096;">
                    Vollständige System-Info
                </p>
            </div>

            <!-- Nützliche Links -->
            <div class="amp-info-card">
                <h3>
                    <span style="color: #0056ff;">🔗</span>
                    Nützliche Links
                </h3>
                <ul class="amp-info-list">
                    <li class="amp-info-item">
                        <a href="<?php echo admin_url('admin.php?page=amp-products'); ?>" style="color: #0056ff; text-decoration: none;">
                            📦 Alle Produkte
                        </a>
                    </li>
                    <li class="amp-info-item">
                        <a href="<?php echo admin_url('admin.php?page=amp-sales'); ?>" style="color: #0056ff; text-decoration: none;">
                            🛒 Verkaufshistorie
                        </a>
                    </li>
                    <li class="amp-info-item">
                        <a href="<?php echo admin_url('admin.php?page=amp-waste'); ?>" style="color: #0056ff; text-decoration: none;">
                            🗑️ Entsorgungsprotokoll
                        </a>
                    </li>
                    <li class="amp-info-item">
                        <a href="<?php echo admin_url('admin.php?page=amp-settings'); ?>" style="color: #0056ff; text-decoration: none;">
                            ⚙️ Einstellungen
                        </a>
                    </li>
                </ul>
            </div>

            <!-- News & Updates -->
            <div class="amp-info-card">
                <h3>
                    <span style="color: #ed8936;">📰</span>
                    News & Updates
                </h3>
                <div class="amp-news-content">
                    <div class="amp-news-icon">✨</div>
                    <p style="margin: 0; font-weight: 500; color: #4a5568;">
                        Hier erscheinen zukünftig Plugin-Updates und Neuigkeiten.
                    </p>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth hover animations
    const cards = document.querySelectorAll('.amp-stat-card, .amp-info-card, .amp-action-button');
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Scanner Button special effects
    const scannerButton = document.querySelector('.amp-scanner-button');
    if (scannerButton) {
        scannerButton.addEventListener('click', function(e) {
            // Add click ripple effect
            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                background: rgba(255,255,255,0.5);
                border-radius: 50%;
                width: 20px;
                height: 20px;
                pointer-events: none;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                left: ${e.offsetX - 10}px;
                top: ${e.offsetY - 10}px;
            `;
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }

    // Add ripple animation keyframes
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // Auto-refresh stats every 30 seconds (optional)
    setInterval(function() {
        // Here you could add AJAX call to refresh stats
        console.log('Stats could be refreshed here');
    }, 30000);
});
</script>