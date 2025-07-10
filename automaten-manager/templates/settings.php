<?php
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Aktuelle Einstellungen laden
$settings = get_option('automaten_settings', array());

// Standard-Werte definieren
$defaults = array(
    'scanner_settings' => array(
        'auto_save_scans' => true,
        'scan_cooldown' => 2000,
        'camera_quality' => 'high',
        'sound_enabled' => true,
        'vibration_enabled' => true,
        'auto_focus' => true,
        'torch_enabled' => false,
        'front_camera_enabled' => true
    ),
    'product_settings' => array(
        'auto_generate_barcode' => false,
        'default_category_id' => 1,
        'auto_calculate_margin' => true,
        'stock_alert_threshold' => 10,
        'enable_expiry_alerts' => true,
        'auto_image_optimization' => true,
        'enable_duplicate_detection' => true
    ),
    'api_settings' => array(
        'openfoodfacts_enabled' => true,
        'barcode_lookup_enabled' => true,
        'external_api_timeout' => 10,
        'cache_external_data' => true,
        'cache_duration' => 86400
    ),
    'notification_settings' => array(
        'email_notifications' => true,
        'low_stock_notifications' => true,
        'expiry_notifications' => true,
        'scan_error_notifications' => false,
        'daily_report_email' => false,
        'notification_email' => get_option('admin_email')
    ),
    'security_settings' => array(
        'log_scan_activities' => true,
        'max_failed_scans' => 50,
        'session_timeout' => 3600,
        'require_confirmation_delete' => true,
        'backup_before_import' => true,
        'enable_audit_log' => true
    ),
    'performance_settings' => array(
        'cache_enabled' => true,
        'lazy_load_images' => true,
        'compress_data' => true,
        'cleanup_old_logs' => true,
        'log_retention_days' => 90,
        'optimize_database' => false
    ),
    'ui_settings' => array(
        'dark_mode' => false,
        'compact_mode' => false,
        'animations_enabled' => true,
        'show_tooltips' => true,
        'items_per_page' => 25,
        'default_view' => 'grid'
    ),
    'backup_settings' => array(
        'auto_backup_enabled' => false,
        'backup_frequency' => 'weekly',
        'backup_location' => 'local',
        'max_backups' => 5,
        'include_images' => false
    )
);

// Einstellungen mit Defaults mergen
$settings = array_merge($defaults, $settings);

// Kategorien für Default-Kategorie-Dropdown
global $wpdb;
$categories = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}automaten_categories WHERE is_active = 1 ORDER BY name");
?>

<div class="wrap">
    <style>
    .automaten-settings {
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
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
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
    
    .settings-navigation {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #e1e5e9;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .settings-tabs {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid #e1e5e9;
        overflow-x: auto;
    }
    
    .settings-tab {
        padding: 15px 25px;
        border: none;
        background: none;
        color: #6c757d;
        cursor: pointer;
        font-weight: 500;
        white-space: nowrap;
        transition: all 0.3s ease;
        position: relative;
        border-bottom: 3px solid transparent;
    }
    
    .settings-tab.active {
        color: #667eea;
        background: white;
        border-bottom-color: #667eea;
    }
    
    .settings-tab:hover {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }
    
    .settings-content {
        padding: 30px;
    }
    
    .settings-section {
        display: none;
    }
    
    .settings-section.active {
        display: block;
    }
    
    .settings-group {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #e1e5e9;
        margin-bottom: 25px;
        overflow: hidden;
    }
    
    .settings-group-header {
        padding: 20px 25px;
        background: #f8f9fa;
        border-bottom: 1px solid #e1e5e9;
    }
    
    .settings-group-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #495057;
        margin: 0 0 5px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .settings-group-description {
        color: #6c757d;
        margin: 0;
        font-size: 0.9rem;
    }
    
    .settings-group-body {
        padding: 25px;
    }
    
    .settings-row {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .settings-row:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .setting-info {
        flex: 1;
    }
    
    .setting-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        font-size: 1rem;
    }
    
    .setting-description {
        color: #6c757d;
        font-size: 0.875rem;
        line-height: 1.4;
    }
    
    .setting-control {
        flex-shrink: 0;
        min-width: 200px;
    }
    
    .automaten-toggle {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }
    
    .automaten-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    
    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 34px;
    }
    
    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 26px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }
    
    input:checked + .toggle-slider {
        background-color: #667eea;
    }
    
    input:checked + .toggle-slider:before {
        transform: translateX(26px);
    }
    
    .automaten-input,
    .automaten-select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }
    
    .automaten-input:focus,
    .automaten-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .automaten-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 14px;
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
    
    .automaten-btn-secondary {
        background: #6c757d;
        color: white;
    }
    
    .automaten-btn-secondary:hover {
        background: #5a6268;
        color: white;
        text-decoration: none;
    }
    
    .automaten-btn-success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }
    
    .automaten-btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .automaten-btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }
    
    .automaten-btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        color: white;
        text-decoration: none;
    }
    
    .settings-actions {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border: 1px solid #e1e5e9;
        padding: 25px;
        display: flex;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    
    .settings-actions-left {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .settings-actions-right {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .status-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .status-success {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }
    
    .status-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706;
    }
    
    .status-error {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }
    
    .backup-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 15px;
    }
    
    .backup-info h4 {
        margin: 0 0 10px 0;
        color: #495057;
    }
    
    .backup-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .backup-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e9ecef;
    }
    
    .backup-item:last-child {
        border-bottom: none;
    }
    
    .debug-info {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
        font-family: monospace;
        font-size: 0.875rem;
    }
    
    .debug-info pre {
        margin: 0;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .automaten-page-header {
            flex-direction: column;
            text-align: center;
        }
        
        .settings-tabs {
            flex-direction: column;
        }
        
        .settings-tab {
            border-bottom: 1px solid #e1e5e9;
            border-right: none;
        }
        
        .settings-tab.active {
            border-bottom-color: transparent;
            border-left: 3px solid #667eea;
        }
        
        .settings-row {
            flex-direction: column;
            gap: 10px;
        }
        
        .setting-control {
            min-width: auto;
            width: 100%;
        }
        
        .settings-actions {
            flex-direction: column;
            text-align: center;
        }
        
        .settings-actions-left,
        .settings-actions-right {
            width: 100%;
            justify-content: center;
        }
    }
    </style>

    <div class="automaten-settings">
        <!-- Page Header -->
        <div class="automaten-page-header">
            <div>
                <h1 class="automaten-page-title">
                    <i class="fas fa-cog"></i>
                    Einstellungen
                </h1>
                <p class="automaten-page-subtitle">
                    Konfigurieren Sie Ihren Automaten Manager Pro
                </p>
            </div>
            <div class="status-indicator status-success">
                <i class="fas fa-check-circle"></i>
                System läuft
            </div>
        </div>

        <form id="settings-form" method="post" action="">
            <?php wp_nonce_field('automaten_settings', 'automaten_nonce'); ?>
            
            <!-- Settings Navigation -->
            <div class="settings-navigation">
                <div class="settings-tabs">
                    <button type="button" class="settings-tab active" data-tab="scanner">
                        <i class="fas fa-qrcode"></i> Scanner
                    </button>
                    <button type="button" class="settings-tab" data-tab="products">
                        <i class="fas fa-boxes"></i> Produkte
                    </button>
                    <button type="button" class="settings-tab" data-tab="api">
                        <i class="fas fa-plug"></i> API & Extern
                    </button>
                    <button type="button" class="settings-tab" data-tab="notifications">
                        <i class="fas fa-bell"></i> Benachrichtigungen
                    </button>
                    <button type="button" class="settings-tab" data-tab="security">
                        <i class="fas fa-shield-alt"></i> Sicherheit
                    </button>
                    <button type="button" class="settings-tab" data-tab="performance">
                        <i class="fas fa-tachometer-alt"></i> Performance
                    </button>
                    <button type="button" class="settings-tab" data-tab="ui">
                        <i class="fas fa-paint-brush"></i> Benutzeroberfläche
                    </button>
                    <button type="button" class="settings-tab" data-tab="backup">
                        <i class="fas fa-download"></i> Backup & Import
                    </button>
                </div>
            </div>

            <!-- Scanner Settings -->
            <div class="settings-section active" id="tab-scanner">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-camera"></i>
                            Scanner-Einstellungen
                        </h3>
                        <p class="settings-group-description">
                            Konfigurieren Sie das Verhalten des Barcode-Scanners
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Automatisches Speichern</div>
                                <div class="setting-description">
                                    Scan-Ergebnisse automatisch in der Datenbank speichern
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="scanner_settings[auto_save_scans]" value="1" 
                                           <?php checked($settings['scanner_settings']['auto_save_scans']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Scan-Cooldown (ms)</div>
                                <div class="setting-description">
                                    Mindestabstand zwischen zwei Scans in Millisekunden
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="scanner_settings[scan_cooldown]" 
                                       value="<?php echo esc_attr($settings['scanner_settings']['scan_cooldown']); ?>"
                                       min="500" max="10000" step="100" class="automaten-input">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Kamera-Qualität</div>
                                <div class="setting-description">
                                    Bevorzugte Auflösung für die Kamera
                                </div>
                            </div>
                            <div class="setting-control">
                                <select name="scanner_settings[camera_quality]" class="automaten-select">
                                    <option value="low" <?php selected($settings['scanner_settings']['camera_quality'], 'low'); ?>>Niedrig (480p)</option>
                                    <option value="medium" <?php selected($settings['scanner_settings']['camera_quality'], 'medium'); ?>>Mittel (720p)</option>
                                    <option value="high" <?php selected($settings['scanner_settings']['camera_quality'], 'high'); ?>>Hoch (1080p)</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Scan-Sound</div>
                                <div class="setting-description">
                                    Akustisches Feedback bei erfolgreichem Scan
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="scanner_settings[sound_enabled]" value="1" 
                                           <?php checked($settings['scanner_settings']['sound_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Vibration (Mobile)</div>
                                <div class="setting-description">
                                    Haptisches Feedback auf mobilen Geräten
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="scanner_settings[vibration_enabled]" value="1" 
                                           <?php checked($settings['scanner_settings']['vibration_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Autofokus</div>
                                <div class="setting-description">
                                    Automatische Scharfstellung der Kamera
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="scanner_settings[auto_focus]" value="1" 
                                           <?php checked($settings['scanner_settings']['auto_focus']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Settings -->
            <div class="settings-section" id="tab-products">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-box"></i>
                            Produkt-Einstellungen
                        </h3>
                        <p class="settings-group-description">
                            Standardverhalten für neue Produkte und Lagerbestände
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Standard-Kategorie</div>
                                <div class="setting-description">
                                    Kategorie für neue Produkte ohne Zuordnung
                                </div>
                            </div>
                            <div class="setting-control">
                                <select name="product_settings[default_category_id]" class="automaten-select">
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category->id; ?>" 
                                                <?php selected($settings['product_settings']['default_category_id'], $category->id); ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Marge automatisch berechnen</div>
                                <div class="setting-description">
                                    Gewinnmarge basierend auf Einkaufs- und Verkaufspreis berechnen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="product_settings[auto_calculate_margin]" value="1" 
                                           <?php checked($settings['product_settings']['auto_calculate_margin']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Mindestbestand-Schwellenwert</div>
                                <div class="setting-description">
                                    Standard-Mindestbestand für neue Produkte
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="product_settings[stock_alert_threshold]" 
                                       value="<?php echo esc_attr($settings['product_settings']['stock_alert_threshold']); ?>"
                                       min="1" max="1000" class="automaten-input">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Ablaufdatum-Warnungen</div>
                                <div class="setting-description">
                                    Benachrichtigungen für bald ablaufende Produkte
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="product_settings[enable_expiry_alerts]" value="1" 
                                           <?php checked($settings['product_settings']['enable_expiry_alerts']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Duplikat-Erkennung</div>
                                <div class="setting-description">
                                    Warnung vor doppelten Barcodes oder Produktnamen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="product_settings[enable_duplicate_detection]" value="1" 
                                           <?php checked($settings['product_settings']['enable_duplicate_detection']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- API Settings -->
            <div class="settings-section" id="tab-api">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-cloud"></i>
                            API & Externe Dienste
                        </h3>
                        <p class="settings-group-description">
                            Konfiguration für externe Produktdatenquellen
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">OpenFoodFacts aktiviert</div>
                                <div class="setting-description">
                                    Produktdaten von OpenFoodFacts abrufen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="api_settings[openfoodfacts_enabled]" value="1" 
                                           <?php checked($settings['api_settings']['openfoodfacts_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Barcode-Lookup</div>
                                <div class="setting-description">
                                    Automatische Produktsuche bei unbekannten Barcodes
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="api_settings[barcode_lookup_enabled]" value="1" 
                                           <?php checked($settings['api_settings']['barcode_lookup_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">API-Timeout (Sekunden)</div>
                                <div class="setting-description">
                                    Maximale Wartezeit für externe API-Anfragen
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="api_settings[external_api_timeout]" 
                                       value="<?php echo esc_attr($settings['api_settings']['external_api_timeout']); ?>"
                                       min="5" max="60" class="automaten-input">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Daten-Cache</div>
                                <div class="setting-description">
                                    Externe Daten zwischenspeichern für bessere Performance
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="api_settings[cache_external_data]" value="1" 
                                           <?php checked($settings['api_settings']['cache_external_data']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Cache-Dauer (Sekunden)</div>
                                <div class="setting-description">
                                    Wie lange externe Daten gespeichert werden
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="api_settings[cache_duration]" 
                                       value="<?php echo esc_attr($settings['api_settings']['cache_duration']); ?>"
                                       min="3600" max="604800" class="automaten-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notification Settings -->
            <div class="settings-section" id="tab-notifications">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-envelope"></i>
                            E-Mail Benachrichtigungen
                        </h3>
                        <p class="settings-group-description">
                            Automatische Benachrichtigungen per E-Mail
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">E-Mail-Benachrichtigungen</div>
                                <div class="setting-description">
                                    Benachrichtigungen per E-Mail aktivieren
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="notification_settings[email_notifications]" value="1" 
                                           <?php checked($settings['notification_settings']['email_notifications']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Benachrichtigungs-E-Mail</div>
                                <div class="setting-description">
                                    E-Mail-Adresse für Systembenachrichtigungen
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="email" name="notification_settings[notification_email]" 
                                       value="<?php echo esc_attr($settings['notification_settings']['notification_email']); ?>"
                                       class="automaten-input">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Niedrige Lagerbestände</div>
                                <div class="setting-description">
                                    Benachrichtigung bei unterschrittenem Mindestbestand
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="notification_settings[low_stock_notifications]" value="1" 
                                           <?php checked($settings['notification_settings']['low_stock_notifications']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Ablaufdatum-Warnungen</div>
                                <div class="setting-description">
                                    Benachrichtigung bei bald ablaufenden Produkten
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="notification_settings[expiry_notifications]" value="1" 
                                           <?php checked($settings['notification_settings']['expiry_notifications']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Täglicher Bericht</div>
                                <div class="setting-description">
                                    Tägliche Zusammenfassung per E-Mail
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="notification_settings[daily_report_email]" value="1" 
                                           <?php checked($settings['notification_settings']['daily_report_email']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="settings-section" id="tab-security">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-lock"></i>
                            Sicherheits-Einstellungen
                        </h3>
                        <p class="settings-group-description">
                            Datenschutz und Sicherheitsmaßnahmen
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Scan-Aktivitäten loggen</div>
                                <div class="setting-description">
                                    Alle Scanner-Aktivitäten in der Datenbank speichern
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="security_settings[log_scan_activities]" value="1" 
                                           <?php checked($settings['security_settings']['log_scan_activities']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Lösch-Bestätigung</div>
                                <div class="setting-description">
                                    Bestätigung vor dem Löschen von Produkten verlangen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="security_settings[require_confirmation_delete]" value="1" 
                                           <?php checked($settings['security_settings']['require_confirmation_delete']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Backup vor Import</div>
                                <div class="setting-description">
                                    Automatisches Backup vor Datenimporten erstellen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="security_settings[backup_before_import]" value="1" 
                                           <?php checked($settings['security_settings']['backup_before_import']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Audit-Log aktivieren</div>
                                <div class="setting-description">
                                    Detaillierte Protokollierung aller Benutzeraktionen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="security_settings[enable_audit_log]" value="1" 
                                           <?php checked($settings['security_settings']['enable_audit_log']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Session-Timeout (Sekunden)</div>
                                <div class="setting-description">
                                    Automatische Abmeldung bei Inaktivität
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="security_settings[session_timeout]" 
                                       value="<?php echo esc_attr($settings['security_settings']['session_timeout']); ?>"
                                       min="300" max="86400" class="automaten-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Settings -->
            <div class="settings-section" id="tab-performance">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-rocket"></i>
                            Performance-Optimierung
                        </h3>
                        <p class="settings-group-description">
                            Einstellungen zur Geschwindigkeitsoptimierung
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Cache aktivieren</div>
                                <div class="setting-description">
                                    Zwischenspeicherung für bessere Performance
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="performance_settings[cache_enabled]" value="1" 
                                           <?php checked($settings['performance_settings']['cache_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Lazy Loading für Bilder</div>
                                <div class="setting-description">
                                    Bilder erst beim Scrollen laden
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="performance_settings[lazy_load_images]" value="1" 
                                           <?php checked($settings['performance_settings']['lazy_load_images']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Daten komprimieren</div>
                                <div class="setting-description">
                                    AJAX-Daten komprimiert übertragen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="performance_settings[compress_data]" value="1" 
                                           <?php checked($settings['performance_settings']['compress_data']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Alte Logs bereinigen</div>
                                <div class="setting-description">
                                    Automatische Bereinigung alter Scan-Logs
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="performance_settings[cleanup_old_logs]" value="1" 
                                           <?php checked($settings['performance_settings']['cleanup_old_logs']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Log-Aufbewahrung (Tage)</div>
                                <div class="setting-description">
                                    Wie lange Scan-Logs gespeichert werden
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="performance_settings[log_retention_days]" 
                                       value="<?php echo esc_attr($settings['performance_settings']['log_retention_days']); ?>"
                                       min="7" max="365" class="automaten-input">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- UI Settings -->
            <div class="settings-section" id="tab-ui">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-desktop"></i>
                            Benutzeroberfläche
                        </h3>
                        <p class="settings-group-description">
                            Anpassung der Benutzeroberfläche
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Animationen aktivieren</div>
                                <div class="setting-description">
                                    Sanfte Übergangseffekte und Animationen
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="ui_settings[animations_enabled]" value="1" 
                                           <?php checked($settings['ui_settings']['animations_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Tooltips anzeigen</div>
                                <div class="setting-description">
                                    Hilfreiche Tooltips bei Mauszeiger-Hover
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="ui_settings[show_tooltips]" value="1" 
                                           <?php checked($settings['ui_settings']['show_tooltips']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Elemente pro Seite</div>
                                <div class="setting-description">
                                    Anzahl der Produkte pro Seite in Listen
                                </div>
                            </div>
                            <div class="setting-control">
                                <select name="ui_settings[items_per_page]" class="automaten-select">
                                    <option value="10" <?php selected($settings['ui_settings']['items_per_page'], 10); ?>>10</option>
                                    <option value="25" <?php selected($settings['ui_settings']['items_per_page'], 25); ?>>25</option>
                                    <option value="50" <?php selected($settings['ui_settings']['items_per_page'], 50); ?>>50</option>
                                    <option value="100" <?php selected($settings['ui_settings']['items_per_page'], 100); ?>>100</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Standard-Ansicht</div>
                                <div class="setting-description">
                                    Bevorzugte Ansicht für Produktlisten
                                </div>
                            </div>
                            <div class="setting-control">
                                <select name="ui_settings[default_view]" class="automaten-select">
                                    <option value="grid" <?php selected($settings['ui_settings']['default_view'], 'grid'); ?>>Raster</option>
                                    <option value="list" <?php selected($settings['ui_settings']['default_view'], 'list'); ?>>Liste</option>
                                    <option value="table" <?php selected($settings['ui_settings']['default_view'], 'table'); ?>>Tabelle</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Settings -->
            <div class="settings-section" id="tab-backup">
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-save"></i>
                            Backup & Datenimport
                        </h3>
                        <p class="settings-group-description">
                            Datensicherung und Import/Export-Funktionen
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Automatisches Backup</div>
                                <div class="setting-description">
                                    Regelmäßige automatische Datensicherung
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="backup_settings[auto_backup_enabled]" value="1" 
                                           <?php checked($settings['backup_settings']['auto_backup_enabled']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Backup-Häufigkeit</div>
                                <div class="setting-description">
                                    Intervall für automatische Backups
                                </div>
                            </div>
                            <div class="setting-control">
                                <select name="backup_settings[backup_frequency]" class="automaten-select">
                                    <option value="daily" <?php selected($settings['backup_settings']['backup_frequency'], 'daily'); ?>>Täglich</option>
                                    <option value="weekly" <?php selected($settings['backup_settings']['backup_frequency'], 'weekly'); ?>>Wöchentlich</option>
                                    <option value="monthly" <?php selected($settings['backup_settings']['backup_frequency'], 'monthly'); ?>>Monatlich</option>
                                </select>
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Maximale Backup-Anzahl</div>
                                <div class="setting-description">
                                    Anzahl der aufbewahrten Backup-Dateien
                                </div>
                            </div>
                            <div class="setting-control">
                                <input type="number" name="backup_settings[max_backups]" 
                                       value="<?php echo esc_attr($settings['backup_settings']['max_backups']); ?>"
                                       min="1" max="50" class="automaten-input">
                            </div>
                        </div>

                        <div class="settings-row">
                            <div class="setting-info">
                                <div class="setting-label">Bilder in Backup einschließen</div>
                                <div class="setting-description">
                                    Produktbilder in Backup-Archiv speichern
                                </div>
                            </div>
                            <div class="setting-control">
                                <label class="automaten-toggle">
                                    <input type="checkbox" name="backup_settings[include_images]" value="1" 
                                           <?php checked($settings['backup_settings']['include_images']); ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Backup Actions -->
                        <div class="backup-info">
                            <h4>Aktionen</h4>
                            <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px;">
                                <button type="button" id="create-backup-btn" class="automaten-btn automaten-btn-success">
                                    <i class="fas fa-download"></i>
                                    Backup erstellen
                                </button>
                                <button type="button" id="export-csv-btn" class="automaten-btn automaten-btn-secondary">
                                    <i class="fas fa-file-csv"></i>
                                    CSV-Export
                                </button>
                                <label for="import-file" class="automaten-btn automaten-btn-secondary" style="cursor: pointer;">
                                    <i class="fas fa-upload"></i>
                                    CSV-Import
                                    <input type="file" id="import-file" accept=".csv" style="display: none;">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Information -->
                <div class="settings-group">
                    <div class="settings-group-header">
                        <h3 class="settings-group-title">
                            <i class="fas fa-info-circle"></i>
                            System-Information
                        </h3>
                        <p class="settings-group-description">
                            Technische Details und Diagnose-Informationen
                        </p>
                    </div>
                    <div class="settings-group-body">
                        <div class="debug-info">
                            <pre><?php
                            global $wpdb;
                            $system_info = array(
                                'Plugin Version' => AUTOMATEN_VERSION,
                                'WordPress Version' => get_bloginfo('version'),
                                'PHP Version' => PHP_VERSION,
                                'MySQL Version' => $wpdb->db_version(),
                                'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unbekannt',
                                'Aktive Produkte' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}automaten_products WHERE is_active = 1"),
                                'Gesamte Scans' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}automaten_scan_logs"),
                                'Letzter Scan' => $wpdb->get_var("SELECT scanned_at FROM {$wpdb->prefix}automaten_scan_logs ORDER BY scanned_at DESC LIMIT 1") ?: 'Nie',
                                'Cache Status' => $settings['performance_settings']['cache_enabled'] ? 'Aktiviert' : 'Deaktiviert',
                                'Upload Max Size' => ini_get('upload_max_filesize'),
                                'Memory Limit' => ini_get('memory_limit'),
                                'Execution Time' => ini_get('max_execution_time') . 's'
                            );
                            
                            foreach ($system_info as $key => $value) {
                                echo str_pad($key . ':', 25) . $value . "\n";
                            }
                            ?></pre>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Actions -->
            <div class="settings-actions">
                <div class="settings-actions-left">
                    <button type="submit" name="automaten_save_settings" class="automaten-btn automaten-btn-primary">
                        <i class="fas fa-save"></i>
                        Einstellungen speichern
                    </button>
                    <button type="button" id="reset-settings-btn" class="automaten-btn automaten-btn-secondary">
                        <i class="fas fa-undo"></i>
                        Zurücksetzen
                    </button>
                </div>
                <div class="settings-actions-right">
                    <button type="button" id="test-connection-btn" class="automaten-btn automaten-btn-secondary">
                        <i class="fas fa-wifi"></i>
                        Verbindung testen
                    </button>
                    <button type="button" id="clear-cache-btn" class="automaten-btn automaten-btn-secondary">
                        <i class="fas fa-trash"></i>
                        Cache leeren
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab-Navigation
    const tabs = document.querySelectorAll('.settings-tab');
    const sections = document.querySelectorAll('.settings-section');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.dataset.tab;
            
            // Aktive Tab setzen
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Aktive Sektion anzeigen
            sections.forEach(section => {
                if (section.id === 'tab-' + targetTab) {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });
        });
    });
    
    // Settings Form Handler
    const settingsForm = document.getElementById('settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichern...';
            
            // Form wird normal submitted, Reset nach Response
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 2000);
        });
    }
    
    // Backup erstellen
    const createBackupBtn = document.getElementById('create-backup-btn');
    if (createBackupBtn) {
        createBackupBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Erstelle Backup...';
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_create_backup',
                    nonce: automatenAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Backup erfolgreich erstellt!');
                    if (data.data.download_url) {
                        window.location.href = data.data.download_url;
                    }
                } else {
                    alert('❌ Fehler beim Erstellen des Backups: ' + (data.data || 'Unbekannter Fehler'));
                }
            })
            .catch(error => {
                console.error('Backup Error:', error);
                alert('❌ Netzwerkfehler beim Erstellen des Backups');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-download"></i> Backup erstellen';
            });
        });
    }
    
    // CSV Export
    const exportCsvBtn = document.getElementById('export-csv-btn');
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exportiere...';
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_export_csv',
                    nonce: automatenAjax.nonce
                })
            })
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = 'automaten-export-' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                alert('✅ CSV-Export erfolgreich!');
            })
            .catch(error => {
                console.error('Export Error:', error);
                alert('❌ Fehler beim CSV-Export');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-file-csv"></i> CSV-Export';
            });
        });
    }
    
    // CSV Import
    const importFile = document.getElementById('import-file');
    if (importFile) {
        importFile.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (!file.name.toLowerCase().endsWith('.csv')) {
                alert('❌ Bitte wählen Sie eine CSV-Datei aus.');
                return;
            }
            
            if (!confirm('Möchten Sie die CSV-Datei wirklich importieren? Dies kann bestehende Daten überschreiben.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'automaten_import_csv');
            formData.append('nonce', automatenAjax.nonce);
            formData.append('csv_file', file);
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ CSV-Import erfolgreich! ' + (data.data.message || ''));
                } else {
                    alert('❌ Fehler beim CSV-Import: ' + (data.data || 'Unbekannter Fehler'));
                }
            })
            .catch(error => {
                console.error('Import Error:', error);
                alert('❌ Netzwerkfehler beim CSV-Import');
            })
            .finally(() => {
                // Input zurücksetzen
                this.value = '';
            });
        });
    }
    
    // Einstellungen zurücksetzen
    const resetSettingsBtn = document.getElementById('reset-settings-btn');
    if (resetSettingsBtn) {
        resetSettingsBtn.addEventListener('click', function() {
            if (!confirm('Möchten Sie wirklich alle Einstellungen auf die Standardwerte zurücksetzen?')) {
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Zurücksetzen...';
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_reset_settings',
                    nonce: automatenAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Einstellungen zurückgesetzt!');
                    location.reload(); // Seite neu laden
                } else {
                    alert('❌ Fehler beim Zurücksetzen der Einstellungen');
                }
            })
            .catch(error => {
                console.error('Reset Error:', error);
                alert('❌ Netzwerkfehler beim Zurücksetzen');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-undo"></i> Zurücksetzen';
            });
        });
    }
    
    // Verbindung testen
    const testConnectionBtn = document.getElementById('test-connection-btn');
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Teste...';
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_test_connection',
                    nonce: automatenAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Alle Verbindungen funktionieren korrekt!\n\n' + 
                          'Details:\n' + (data.data.details || ''));
                } else {
                    alert('⚠️ Verbindungsprobleme festgestellt:\n\n' + 
                          (data.data || 'Unbekannter Fehler'));
                }
            })
            .catch(error => {
                console.error('Connection Test Error:', error);
                alert('❌ Fehler beim Verbindungstest');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-wifi"></i> Verbindung testen';
            });
        });
    }
    
    // Cache leeren
    const clearCacheBtn = document.getElementById('clear-cache-btn');
    if (clearCacheBtn) {
        clearCacheBtn.addEventListener('click', function() {
            if (!confirm('Möchten Sie wirklich den Cache leeren?')) {
                return;
            }
            
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Leere Cache...';
            
            fetch(automatenAjax.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_clear_cache',
                    nonce: automatenAjax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Cache erfolgreich geleert!');
                } else {
                    alert('❌ Fehler beim Leeren des Caches');
                }
            })
            .catch(error => {
                console.error('Clear Cache Error:', error);
                alert('❌ Netzwerkfehler beim Leeren des Caches');
            })
            .finally(() => {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash"></i> Cache leeren';
            });
        });
    }
    
    // Live-Validierung für E-Mail-Felder
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !isValidEmail(value)) {
                this.style.borderColor = '#ef4444';
                showTooltip(this, 'Bitte geben Sie eine gültige E-Mail-Adresse ein');
            } else {
                this.style.borderColor = '';
                hideTooltip(this);
            }
        });
    });
    
    // Live-Validierung für Zahlen-Felder
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            const value = parseInt(this.value);
            const min = parseInt(this.getAttribute('min'));
            const max = parseInt(this.getAttribute('max'));
            
            if (value < min || value > max) {
                this.style.borderColor = '#ef4444';
                showTooltip(this, `Wert muss zwischen ${min} und ${max} liegen`);
            } else {
                this.style.borderColor = '';
                hideTooltip(this);
            }
        });
    });
    
    // Auto-Save für Einstellungen (nach 3 Sekunden Inaktivität)
    let autoSaveTimeout;
    const autoSaveInputs = document.querySelectorAll('input, select, textarea');
    autoSaveInputs.forEach(input => {
        input.addEventListener('change', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                autoSaveSettings();
            }, 3000);
        });
    });
    
    // Performance-Monitor
    if (typeof performance !== 'undefined') {
        const startTime = performance.now();
        window.addEventListener('load', function() {
            const loadTime = performance.now() - startTime;
            console.log(`Settings-Seite geladen in ${loadTime.toFixed(2)}ms`);
        });
    }
    
    // Accessibility: Keyboard-Navigation für Tabs
    tabs.forEach((tab, index) => {
        tab.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                const direction = e.key === 'ArrowLeft' ? -1 : 1;
                const nextIndex = (index + direction + tabs.length) % tabs.length;
                tabs[nextIndex].click();
                tabs[nextIndex].focus();
            }
        });
    });
    
    // Erweiterte Funktionen
    initAdvancedFeatures();
});

// Hilfsfunktionen
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showTooltip(element, message) {
    hideTooltip(element); // Vorherige Tooltip entfernen
    
    const tooltip = document.createElement('div');
    tooltip.className = 'settings-tooltip';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        z-index: 1000;
        max-width: 200px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        pointer-events: none;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    tooltip.style.left = (rect.left + window.scrollX) + 'px';
    
    element._tooltip = tooltip;
    
    setTimeout(() => hideTooltip(element), 3000);
}

function hideTooltip(element) {
    if (element._tooltip) {
        element._tooltip.remove();
        element._tooltip = null;
    }
}

function autoSaveSettings() {
    const formData = new FormData(document.getElementById('settings-form'));
    formData.append('action', 'automaten_auto_save_settings');
    formData.append('nonce', automatenAjax.nonce);
    
    fetch(automatenAjax.ajaxurl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTemporaryMessage('Einstellungen automatisch gespeichert', 'success');
        }
    })
    .catch(error => {
        console.log('Auto-Save Error:', error);
    });
}

function showTemporaryMessage(message, type = 'info') {
    const msgDiv = document.createElement('div');
    msgDiv.className = `temporary-message ${type}`;
    msgDiv.textContent = message;
    msgDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 9999;
        animation: slideIn 0.3s ease;
        ${type === 'success' ? 'background: #10b981;' : 
          type === 'error' ? 'background: #ef4444;' : 
          'background: #6366f1;'}
    `;
    
    document.body.appendChild(msgDiv);
    
    setTimeout(() => {
        msgDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => msgDiv.remove(), 300);
    }, 2000);
}

function initAdvancedFeatures() {
    // Theme-Detection
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.body.classList.add('auto-dark-mode');
    }
    
    // Performance-Überwachung
    if ('connection' in navigator) {
        const connection = navigator.connection;
        if (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g') {
            // Reduzierte Animationen für langsame Verbindungen
            document.body.classList.add('reduced-motion');
        }
    }
    
    // Service Worker für Offline-Funktionalität (falls verfügbar)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/wp-content/plugins/automaten-manager/sw.js')
            .catch(error => console.log('SW registration failed'));
    }
    
    // Erweiterte Tastatur-Shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S = Speichern
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.querySelector('[name="automaten_save_settings"]').click();
        }
        
        // Ctrl/Cmd + Shift + R = Reset
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'R') {
            e.preventDefault();
            document.getElementById('reset-settings-btn').click();
        }
        
        // Ctrl/Cmd + Shift + B = Backup
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'B') {
            e.preventDefault();
            document.getElementById('create-backup-btn').click();
        }
    });
    
    // Erweiterte Formular-Validierung
    const form = document.getElementById('settings-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateAdvancedForm()) {
                e.preventDefault();
                showTemporaryMessage('Bitte korrigieren Sie die Fehler im Formular', 'error');
            }
        });
    }
}

function validateAdvancedForm() {
    let isValid = true;
    const errors = [];
    
    // E-Mail-Validierung
    const emailField = document.querySelector('input[name="notification_settings[notification_email]"]');
    if (emailField && emailField.value && !isValidEmail(emailField.value)) {
        isValid = false;
        errors.push('Ungültige E-Mail-Adresse');
        emailField.focus();
    }
    
    // Zahlenbereich-Validierung
    const numberFields = document.querySelectorAll('input[type="number"]');
    numberFields.forEach(field => {
        const value = parseInt(field.value);
        const min = parseInt(field.getAttribute('min'));
        const max = parseInt(field.getAttribute('max'));
        
        if (value < min || value > max) {
            isValid = false;
            errors.push(`${field.previousElementSibling.textContent} muss zwischen ${min} und ${max} liegen`);
            if (isValid) field.focus(); // Fokus auf ersten Fehler
        }
    });
    
    // Cache-Dauer Validierung
    const cacheDuration = document.querySelector('input[name="api_settings[cache_duration]"]');
    if (cacheDuration && parseInt(cacheDuration.value) < 3600) {
        isValid = false;
        errors.push('Cache-Dauer sollte mindestens 1 Stunde (3600 Sekunden) betragen');
    }
    
    if (!isValid) {
        console.log('Validierungsfehler:', errors);
    }
    
    return isValid;
}

// CSS für zusätzliche Animationen
const additionalCSS = `
<style>
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(100%); opacity: 0; }
}

.auto-dark-mode {
    filter: invert(1) hue-rotate(180deg);
}

.auto-dark-mode img,
.auto-dark-mode video {
    filter: invert(1) hue-rotate(180deg);
}

.reduced-motion *,
.reduced-motion *::before,
.reduced-motion *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
}

.settings-tooltip {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Fokus-Styles für bessere Accessibility */
.settings-tab:focus,
.automaten-btn:focus,
.automaten-input:focus,
.automaten-select:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

/* Hochkontrast-Modus */
@media (prefers-contrast: high) {
    .settings-tab {
        border: 2px solid;
    }
    
    .automaten-toggle .toggle-slider {
        border: 2px solid #000;
    }
    
    .settings-group {
        border: 2px solid #000;
    }
}

/* Animationen für Einstellungsänderungen */
.setting-control input:checked + .toggle-slider {
    animation: toggleSuccess 0.3s ease;
}

@keyframes toggleSuccess {
    0% { background-color: #ccc; }
    50% { background-color: #4ade80; }
    100% { background-color: #667eea; }
}

/* Loading-States für Buttons */
.automaten-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.automaten-btn .fa-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive Verbesserungen */
@media (max-width: 480px) {
    .automaten-page-title {
        font-size: 1.8rem;
    }
    
    .settings-content {
        padding: 15px;
    }
    
    .settings-group-body {
        padding: 15px;
    }
    
    .settings-row {
        gap: 10px;
    }
}

/* Print-Styles für Einstellungen */
@media print {
    .settings-actions,
    .automaten-btn {
        display: none !important;
    }
    
    .settings-section {
        display: block !important;
    }
    
    .settings-group {
        break-inside: avoid;
        margin-bottom: 20px;
    }
}
</style>
`;

// CSS zum Head hinzufügen
if (document.head) {
    document.head.insertAdjacentHTML('beforeend', additionalCSS);
}
</script>

<?php
// Einstellungen verarbeiten wenn das Formular abgesendet wurde
if (isset($_POST['automaten_save_settings']) && wp_verify_nonce($_POST['automaten_nonce'], 'automaten_settings')) {
    
    // Sanitize und validiere alle Eingaben
    $new_settings = array();
    
    // Scanner Settings
    $new_settings['scanner_settings'] = array(
        'auto_save_scans' => isset($_POST['scanner_settings']['auto_save_scans']),
        'scan_cooldown' => intval($_POST['scanner_settings']['scan_cooldown']),
        'camera_quality' => sanitize_text_field($_POST['scanner_settings']['camera_quality']),
        'sound_enabled' => isset($_POST['scanner_settings']['sound_enabled']),
        'vibration_enabled' => isset($_POST['scanner_settings']['vibration_enabled']),
        'auto_focus' => isset($_POST['scanner_settings']['auto_focus']),
        'torch_enabled' => isset($_POST['scanner_settings']['torch_enabled']),
        'front_camera_enabled' => isset($_POST['scanner_settings']['front_camera_enabled'])
    );
    
    // Product Settings
    $new_settings['product_settings'] = array(
        'auto_generate_barcode' => isset($_POST['product_settings']['auto_generate_barcode']),
        'default_category_id' => intval($_POST['product_settings']['default_category_id']),
        'auto_calculate_margin' => isset($_POST['product_settings']['auto_calculate_margin']),
        'stock_alert_threshold' => intval($_POST['product_settings']['stock_alert_threshold']),
        'enable_expiry_alerts' => isset($_POST['product_settings']['enable_expiry_alerts']),
        'auto_image_optimization' => isset($_POST['product_settings']['auto_image_optimization']),
        'enable_duplicate_detection' => isset($_POST['product_settings']['enable_duplicate_detection'])
    );
    
    // API Settings
    $new_settings['api_settings'] = array(
        'openfoodfacts_enabled' => isset($_POST['api_settings']['openfoodfacts_enabled']),
        'barcode_lookup_enabled' => isset($_POST['api_settings']['barcode_lookup_enabled']),
        'external_api_timeout' => max(5, min(60, intval($_POST['api_settings']['external_api_timeout']))),
        'cache_external_data' => isset($_POST['api_settings']['cache_external_data']),
        'cache_duration' => max(3600, intval($_POST['api_settings']['cache_duration']))
    );
    
    // Notification Settings
    $new_settings['notification_settings'] = array(
        'email_notifications' => isset($_POST['notification_settings']['email_notifications']),
        'low_stock_notifications' => isset($_POST['notification_settings']['low_stock_notifications']),
        'expiry_notifications' => isset($_POST['notification_settings']['expiry_notifications']),
        'scan_error_notifications' => isset($_POST['notification_settings']['scan_error_notifications']),
        'daily_report_email' => isset($_POST['notification_settings']['daily_report_email']),
        'notification_email' => sanitize_email($_POST['notification_settings']['notification_email'])
    );
    
    // Security Settings
    $new_settings['security_settings'] = array(
        'log_scan_activities' => isset($_POST['security_settings']['log_scan_activities']),
        'max_failed_scans' => intval($_POST['security_settings']['max_failed_scans']),
        'session_timeout' => max(300, min(86400, intval($_POST['security_settings']['session_timeout']))),
        'require_confirmation_delete' => isset($_POST['security_settings']['require_confirmation_delete']),
        'backup_before_import' => isset($_POST['security_settings']['backup_before_import']),
        'enable_audit_log' => isset($_POST['security_settings']['enable_audit_log'])
    );
    
    // Performance Settings
    $new_settings['performance_settings'] = array(
        'cache_enabled' => isset($_POST['performance_settings']['cache_enabled']),
        'lazy_load_images' => isset($_POST['performance_settings']['lazy_load_images']),
        'compress_data' => isset($_POST['performance_settings']['compress_data']),
        'cleanup_old_logs' => isset($_POST['performance_settings']['cleanup_old_logs']),
        'log_retention_days' => max(7, min(365, intval($_POST['performance_settings']['log_retention_days']))),
        'optimize_database' => isset($_POST['performance_settings']['optimize_database'])
    );
    
    // UI Settings
    $new_settings['ui_settings'] = array(
        'dark_mode' => isset($_POST['ui_settings']['dark_mode']),
        'compact_mode' => isset($_POST['ui_settings']['compact_mode']),
        'animations_enabled' => isset($_POST['ui_settings']['animations_enabled']),
        'show_tooltips' => isset($_POST['ui_settings']['show_tooltips']),
        'items_per_page' => intval($_POST['ui_settings']['items_per_page']),
        'default_view' => sanitize_text_field($_POST['ui_settings']['default_view'])
    );
    
    // Backup Settings
    $new_settings['backup_settings'] = array(
        'auto_backup_enabled' => isset($_POST['backup_settings']['auto_backup_enabled']),
        'backup_frequency' => sanitize_text_field($_POST['backup_settings']['backup_frequency']),
        'backup_location' => sanitize_text_field($_POST['backup_settings']['backup_location']),
        'max_backups' => max(1, min(50, intval($_POST['backup_settings']['max_backups']))),
        'include_images' => isset($_POST['backup_settings']['include_images'])
    );
    
    // Einstellungen speichern
    update_option('automaten_settings', $new_settings);
    
    // Cache leeren wenn Performance-Einstellungen geändert wurden
    if (isset($_POST['performance_settings'])) {
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        delete_transient('automaten_cache');
    }
    
    // Erfolgs-Message
    echo '<div class="notice notice-success is-dismissible">';
    echo '<p><strong>✅ Einstellungen erfolgreich gespeichert!</strong></p>';
    echo '</div>';
    
    // Einstellungen neu laden für Anzeige
    $settings = $new_settings;
}
?>