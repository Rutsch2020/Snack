<?php
/**
 * System-Einstellungen Template
 * 
 * Ultra-modernes Settings-Interface für AutomatenManager Pro
 * Samsung S25 Ultra optimiert mit Touch-Gesten und 4K Display
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

// Current user capabilities check
if (!current_user_can('amp_manage_settings')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'automaten-manager-pro'));
}

// Get current settings
$settings = get_option('amp_settings', []);
$company_name = $settings['company_name'] ?? 'Mein Unternehmen';
$company_logo = $settings['company_logo'] ?? '';
$currency = $settings['currency'] ?? 'EUR';
$timezone = $settings['timezone'] ?? 'Europe/Berlin';
?>

<div class="amp-container">
    <!-- Header Section -->
    <div class="amp-header-section">
        <div class="amp-header-content">
            <div class="amp-header-title">
                <h1>
                    <i class="amp-icon-settings"></i>
                    Systemeinstellungen
                </h1>
                <p class="amp-subtitle">Konfiguration und Verwaltung Ihres AutomatenManager Pro Systems</p>
            </div>
            <div class="amp-header-actions">
                <button type="button" class="amp-btn amp-btn-primary" id="amp-save-all-settings">
                    <i class="amp-icon-save"></i>
                    Alle Änderungen speichern
                </button>
                <button type="button" class="amp-btn amp-btn-secondary" id="amp-reset-settings">
                    <i class="amp-icon-refresh"></i>
                    Zurücksetzen
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Navigation -->
    <div class="amp-settings-nav">
        <nav class="amp-tab-nav">
            <button type="button" class="amp-tab-btn active" data-tab="general">
                <i class="amp-icon-building"></i>
                <span>Allgemein</span>
            </button>
            <button type="button" class="amp-tab-btn" data-tab="email">
                <i class="amp-icon-mail"></i>
                <span>E-Mail</span>
            </button>
            <button type="button" class="amp-tab-btn" data-tab="tax">
                <i class="amp-icon-calculator"></i>
                <span>Steuern</span>
            </button>
            <button type="button" class="amp-tab-btn" data-tab="scanner">
                <i class="amp-icon-scan"></i>
                <span>Scanner</span>
            </button>
            <button type="button" class="amp-tab-btn" data-tab="users">
                <i class="amp-icon-users"></i>
                <span>Benutzer</span>
            </button>
            <button type="button" class="amp-tab-btn" data-tab="system">
                <i class="amp-icon-cog"></i>
                <span>System</span>
            </button>
        </nav>
    </div>

    <!-- Settings Content -->
    <div class="amp-settings-content">
        
        <!-- General Settings Tab -->
        <div class="amp-tab-content active" id="amp-tab-general">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>🏢 Unternehmensdaten</h2>
                    <p>Grundlegende Informationen zu Ihrem Unternehmen</p>
                </div>

                <div class="amp-settings-grid">
                    <!-- Company Name -->
                    <div class="amp-setting-item">
                        <label for="amp-company-name" class="amp-setting-label">
                            Firmenname
                            <span class="amp-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="amp-company-name"
                            name="company_name"
                            class="amp-form-control"
                            value="<?php echo esc_attr($company_name); ?>"
                            placeholder="z.B. AutoMart GmbH"
                            required
                        />
                        <small class="amp-help-text">Erscheint in E-Mails und Berichten</small>
                    </div>

                    <!-- Company Logo -->
                    <div class="amp-setting-item">
                        <label class="amp-setting-label">
                            Firmenlogo
                        </label>
                        <div class="amp-logo-upload">
                            <div class="amp-logo-preview" id="amp-logo-preview">
                                <?php if ($company_logo): ?>
                                    <img src="<?php echo esc_url($company_logo); ?>" alt="Company Logo">
                                <?php else: ?>
                                    <div class="amp-logo-placeholder">
                                        <i class="amp-icon-image"></i>
                                        <span>Kein Logo hochgeladen</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="amp-logo-actions">
                                <button type="button" class="amp-btn amp-btn-primary" id="amp-upload-logo">
                                    <i class="amp-icon-upload"></i>
                                    Logo hochladen
                                </button>
                                <?php if ($company_logo): ?>
                                    <button type="button" class="amp-btn amp-btn-danger" id="amp-remove-logo">
                                        <i class="amp-icon-trash"></i>
                                        Entfernen
                                    </button>
                                <?php endif; ?>
                            </div>
                            <small class="amp-help-text">
                                Empfohlen: PNG/SVG, 300x100px, transparenter Hintergrund
                            </small>
                        </div>
                    </div>

                    <!-- Currency -->
                    <div class="amp-setting-item">
                        <label for="amp-currency" class="amp-setting-label">
                            Währung
                        </label>
                        <select id="amp-currency" name="currency" class="amp-form-control">
                            <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR (€)</option>
                            <option value="USD" <?php selected($currency, 'USD'); ?>>USD ($)</option>
                            <option value="CHF" <?php selected($currency, 'CHF'); ?>>CHF (₣)</option>
                            <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP (£)</option>
                        </select>
                    </div>

                    <!-- Timezone -->
                    <div class="amp-setting-item">
                        <label for="amp-timezone" class="amp-setting-label">
                            Zeitzone
                        </label>
                        <select id="amp-timezone" name="timezone" class="amp-form-control">
                            <option value="Europe/Berlin" <?php selected($timezone, 'Europe/Berlin'); ?>>Europe/Berlin (MEZ)</option>
                            <option value="Europe/Vienna" <?php selected($timezone, 'Europe/Vienna'); ?>>Europe/Vienna (MEZ)</option>
                            <option value="Europe/Zurich" <?php selected($timezone, 'Europe/Zurich'); ?>>Europe/Zurich (MEZ)</option>
                            <option value="UTC" <?php selected($timezone, 'UTC'); ?>>UTC</option>
                        </select>
                    </div>
                </div>

                <!-- Store Hours -->
                <div class="amp-section-header">
                    <h3>⏰ Öffnungszeiten</h3>
                    <p>Automatische Verkaufszeiten für Berichte</p>
                </div>

                <div class="amp-store-hours">
                    <?php
                    $days = [
                        'monday' => 'Montag',
                        'tuesday' => 'Dienstag', 
                        'wednesday' => 'Mittwoch',
                        'thursday' => 'Donnerstag',
                        'friday' => 'Freitag',
                        'saturday' => 'Samstag',
                        'sunday' => 'Sonntag'
                    ];
                    foreach ($days as $day_key => $day_name): 
                        $is_open = $settings['store_hours'][$day_key]['open'] ?? true;
                        $open_time = $settings['store_hours'][$day_key]['open_time'] ?? '08:00';
                        $close_time = $settings['store_hours'][$day_key]['close_time'] ?? '18:00';
                    ?>
                    <div class="amp-store-hour-row">
                        <div class="amp-day-toggle">
                            <label class="amp-switch">
                                <input type="checkbox" name="store_hours[<?php echo $day_key; ?>][open]" <?php checked($is_open); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <span class="amp-day-name"><?php echo $day_name; ?></span>
                        </div>
                        <div class="amp-time-inputs">
                            <input 
                                type="time" 
                                name="store_hours[<?php echo $day_key; ?>][open_time]"
                                value="<?php echo esc_attr($open_time); ?>"
                                class="amp-form-control"
                                <?php echo !$is_open ? 'disabled' : ''; ?>
                            />
                            <span class="amp-time-separator">bis</span>
                            <input 
                                type="time" 
                                name="store_hours[<?php echo $day_key; ?>][close_time]"
                                value="<?php echo esc_attr($close_time); ?>"
                                class="amp-form-control"
                                <?php echo !$is_open ? 'disabled' : ''; ?>
                            />
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Email Settings Tab -->
        <div class="amp-tab-content" id="amp-tab-email">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>📧 E-Mail Konfiguration</h2>
                    <p>SMTP-Einstellungen und automatische Benachrichtigungen</p>
                </div>

                <!-- SMTP Settings -->
                <div class="amp-setting-group">
                    <h3>📤 SMTP-Einstellungen</h3>
                    <div class="amp-settings-grid">
                        <div class="amp-setting-item">
                            <label for="amp-smtp-host" class="amp-setting-label">SMTP Server</label>
                            <input 
                                type="text" 
                                id="amp-smtp-host"
                                name="smtp_host"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['smtp_host'] ?? ''); ?>"
                                placeholder="mail.domain.de"
                            />
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-smtp-port" class="amp-setting-label">Port</label>
                            <input 
                                type="number" 
                                id="amp-smtp-port"
                                name="smtp_port"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['smtp_port'] ?? '587'); ?>"
                                placeholder="587"
                            />
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-smtp-username" class="amp-setting-label">Benutzername</label>
                            <input 
                                type="text" 
                                id="amp-smtp-username"
                                name="smtp_username"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['smtp_username'] ?? ''); ?>"
                                placeholder="automat@domain.de"
                            />
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-smtp-password" class="amp-setting-label">Passwort</label>
                            <input 
                                type="password" 
                                id="amp-smtp-password"
                                name="smtp_password"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['smtp_password'] ?? ''); ?>"
                                placeholder="••••••••"
                            />
                        </div>
                    </div>

                    <div class="amp-setting-actions">
                        <button type="button" class="amp-btn amp-btn-primary" id="amp-test-email">
                            <i class="amp-icon-send"></i>
                            Test-E-Mail senden
                        </button>
                    </div>
                </div>

                <!-- Email Recipients -->
                <div class="amp-setting-group">
                    <h3>📮 E-Mail Empfänger</h3>
                    <div class="amp-email-recipients">
                        <div class="amp-recipient-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-shopping-cart"></i>
                                Verkaufsberichte
                            </label>
                            <input 
                                type="email" 
                                name="email_sales"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['email_sales'] ?? ''); ?>"
                                placeholder="verkauf@firma.de"
                            />
                        </div>

                        <div class="amp-recipient-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-package"></i>
                                Lager-Berichte
                            </label>
                            <input 
                                type="email" 
                                name="email_inventory"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['email_inventory'] ?? ''); ?>"
                                placeholder="lager@firma.de"
                            />
                        </div>

                        <div class="amp-recipient-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-alert-triangle"></i>
                                Ablauf-Warnungen
                            </label>
                            <input 
                                type="email" 
                                name="email_expiry"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['email_expiry'] ?? ''); ?>"
                                placeholder="manager@firma.de"
                            />
                        </div>

                        <div class="amp-recipient-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-trash"></i>
                                Entsorgungs-Berichte
                            </label>
                            <input 
                                type="email" 
                                name="email_waste"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['email_waste'] ?? ''); ?>"
                                placeholder="controlling@firma.de"
                            />
                        </div>
                    </div>
                </div>

                <!-- Email Templates -->
                <div class="amp-setting-group">
                    <h3>📝 E-Mail Templates</h3>
                    <div class="amp-template-manager">
                        <div class="amp-template-list">
                            <div class="amp-template-item">
                                <div class="amp-template-info">
                                    <h4>Verkaufsbericht</h4>
                                    <p>Template für Verkaufs-Sessions</p>
                                </div>
                                <button type="button" class="amp-btn amp-btn-secondary" data-template="sales">
                                    <i class="amp-icon-edit"></i>
                                    Bearbeiten
                                </button>
                            </div>

                            <div class="amp-template-item">
                                <div class="amp-template-info">
                                    <h4>Lager-Auffüllung</h4>
                                    <p>Template für Wareneingang</p>
                                </div>
                                <button type="button" class="amp-btn amp-btn-secondary" data-template="inventory">
                                    <i class="amp-icon-edit"></i>
                                    Bearbeiten
                                </button>
                            </div>

                            <div class="amp-template-item">
                                <div class="amp-template-info">
                                    <h4>Ablauf-Warnung</h4>
                                    <p>Template für Haltbarkeitsdatum</p>
                                </div>
                                <button type="button" class="amp-btn amp-btn-secondary" data-template="expiry">
                                    <i class="amp-icon-edit"></i>
                                    Bearbeiten
                                </button>
                            </div>

                            <div class="amp-template-item">
                                <div class="amp-template-info">
                                    <h4>Entsorgung</h4>
                                    <p>Template für Verlustdokumentation</p>
                                </div>
                                <button type="button" class="amp-btn amp-btn-secondary" data-template="waste">
                                    <i class="amp-icon-edit"></i>
                                    Bearbeiten
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tax Settings Tab -->
        <div class="amp-tab-content" id="amp-tab-tax">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>💰 Steuer- und Pfandeinstellungen</h2>
                    <p>MwSt-Sätze und Pfandbeträge konfigurieren</p>
                </div>

                <!-- VAT Settings -->
                <div class="amp-setting-group">
                    <h3>📊 Mehrwertsteuer-Sätze</h3>
                    <div class="amp-vat-settings">
                        <div class="amp-vat-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-percent"></i>
                                Standard MwSt
                            </label>
                            <div class="amp-input-group">
                                <input 
                                    type="number" 
                                    name="vat_standard"
                                    class="amp-form-control"
                                    value="<?php echo esc_attr($settings['vat_standard'] ?? '19'); ?>"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                />
                                <span class="amp-input-addon">%</span>
                            </div>
                            <small class="amp-help-text">Für normale Waren</small>
                        </div>

                        <div class="amp-vat-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-percent"></i>
                                Reduzierte MwSt
                            </label>
                            <div class="amp-input-group">
                                <input 
                                    type="number" 
                                    name="vat_reduced"
                                    class="amp-form-control"
                                    value="<?php echo esc_attr($settings['vat_reduced'] ?? '7'); ?>"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                />
                                <span class="amp-input-addon">%</span>
                            </div>
                            <small class="amp-help-text">Für Lebensmittel</small>
                        </div>

                        <div class="amp-vat-item">
                            <label class="amp-setting-label">
                                <i class="amp-icon-percent"></i>
                                Steuerfreie Waren
                            </label>
                            <div class="amp-input-group">
                                <input 
                                    type="number" 
                                    name="vat_exempt"
                                    class="amp-form-control"
                                    value="0"
                                    readonly
                                />
                                <span class="amp-input-addon">%</span>
                            </div>
                            <small class="amp-help-text">Für steuerfreie Artikel</small>
                        </div>
                    </div>
                </div>

                <!-- Deposit Settings -->
                <div class="amp-setting-group">
                    <h3>🍾 Pfandbeträge</h3>
                    <div class="amp-deposit-settings">
                        <div class="amp-deposit-grid">
                            <div class="amp-deposit-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="deposit_008_enabled" <?php checked($settings['deposit_008_enabled'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <span class="amp-deposit-label">0,08 €</span>
                                <small>Dosen, kleine Flaschen</small>
                            </div>

                            <div class="amp-deposit-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="deposit_015_enabled" <?php checked($settings['deposit_015_enabled'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <span class="amp-deposit-label">0,15 €</span>
                                <small>Plastikflaschen bis 1,5L</small>
                            </div>

                            <div class="amp-deposit-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="deposit_025_enabled" <?php checked($settings['deposit_025_enabled'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <span class="amp-deposit-label">0,25 €</span>
                                <small>Glasflaschen, große Flaschen</small>
                            </div>

                            <div class="amp-deposit-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="deposit_custom_enabled" <?php checked($settings['deposit_custom_enabled'] ?? false); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <div class="amp-custom-deposit">
                                    <input 
                                        type="number" 
                                        name="deposit_custom_amount"
                                        class="amp-form-control"
                                        value="<?php echo esc_attr($settings['deposit_custom_amount'] ?? ''); ?>"
                                        step="0.01"
                                        placeholder="0,00"
                                    />
                                    <span>€</span>
                                </div>
                                <small>Individueller Pfandbetrag</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Receipt Settings -->
                <div class="amp-setting-group">
                    <h3>🧾 Beleg-Einstellungen</h3>
                    <div class="amp-receipt-settings">
                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="receipt_auto_print" <?php checked($settings['receipt_auto_print'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Automatischer Beleg-Druck</span>
                                <small>Belege nach jedem Verkauf automatisch drucken</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="receipt_show_tax" <?php checked($settings['receipt_show_tax'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">MwSt auf Beleg anzeigen</span>
                                <small>Steueraufschlüsselung auf Belegen</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="receipt_qr_code" <?php checked($settings['receipt_qr_code'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">QR-Code auf Beleg</span>
                                <small>Link zu digitaler Rechnung</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scanner Settings Tab -->
        <div class="amp-tab-content" id="amp-tab-scanner">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>📱 Scanner-Konfiguration</h2>
                    <p>Barcode-Scanner Einstellungen und Kamera-Optimierung</p>
                </div>

                <!-- Scanner General Settings -->
                <div class="amp-setting-group">
                    <h3>🔧 Allgemeine Scanner-Einstellungen</h3>
                    <div class="amp-scanner-settings">
                        <div class="amp-setting-item">
                            <label for="amp-scanner-sensitivity" class="amp-setting-label">
                                Scanner-Empfindlichkeit
                            </label>
                            <div class="amp-range-slider">
                                <input 
                                    type="range" 
                                    id="amp-scanner-sensitivity"
                                    name="scanner_sensitivity"
                                    min="1" 
                                    max="10" 
                                    value="<?php echo esc_attr($settings['scanner_sensitivity'] ?? '7'); ?>"
                                    class="amp-slider"
                                />
                                <div class="amp-range-labels">
                                    <span>Niedrig</span>
                                    <span>Hoch</span>
                                </div>
                            </div>
                            <small class="amp-help-text">Höhere Werte = schnellere Erkennung, mehr Fehlalarme</small>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="scanner_vibration" <?php checked($settings['scanner_vibration'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Vibration bei Scan</span>
                                <small>Haptisches Feedback für Samsung S25 Ultra</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="scanner_sound" <?php checked($settings['scanner_sound'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Sound bei Scan</span>
                                <small>Akustisches Feedback</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="scanner_torch" <?php checked($settings['scanner_torch'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Taschenlampe automatisch</span>
                                <small>LED bei schlechten Lichtverhältnissen</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barcode Formats -->
                <div class="amp-setting-group">
                    <h3>🏷️ Barcode-Formate</h3>
                    <div class="amp-barcode-formats">
                        <div class="amp-format-grid">
                            <div class="amp-format-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="barcode_ean13" <?php checked($settings['barcode_ean13'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <div class="amp-format-info">
                                    <span class="amp-format-name">EAN-13</span>
                                    <small>Standard Produktbarcodes</small>
                                </div>
                            </div>

                            <div class="amp-format-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="barcode_ean8" <?php checked($settings['barcode_ean8'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <div class="amp-format-info">
                                    <span class="amp-format-name">EAN-8</span>
                                    <small>Kurze Produktbarcodes</small>
                                </div>
                            </div>

                            <div class="amp-format-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="barcode_code128" <?php checked($settings['barcode_code128'] ?? true); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <div class="amp-format-info">
                                    <span class="amp-format-name">Code 128</span>
                                    <small>Interne Barcodes</small>
                                </div>
                            </div>

                            <div class="amp-format-item">
                                <label class="amp-switch">
                                    <input type="checkbox" name="barcode_qr" <?php checked($settings['barcode_qr'] ?? false); ?>>
                                    <span class="amp-switch-slider"></span>
                                </label>
                                <div class="amp-format-info">
                                    <span class="amp-format-name">QR-Code</span>
                                    <small>2D Barcodes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Camera Settings -->
                <div class="amp-setting-group">
                    <h3>📸 Kamera-Einstellungen</h3>
                    <div class="amp-camera-settings">
                        <div class="amp-setting-item">
                            <label for="amp-camera-resolution" class="amp-setting-label">
                                Auflösung
                            </label>
                            <select id="amp-camera-resolution" name="camera_resolution" class="amp-form-control">
                                <option value="hd" <?php selected($settings['camera_resolution'] ?? 'fhd', 'hd'); ?>>HD (1280x720)</option>
                                <option value="fhd" <?php selected($settings['camera_resolution'] ?? 'fhd', 'fhd'); ?>>Full HD (1920x1080)</option>
                                <option value="4k" <?php selected($settings['camera_resolution'] ?? 'fhd', '4k'); ?>>4K (3840x2160)</option>
                            </select>
                            <small class="amp-help-text">Höhere Auflösung = bessere Erkennung, mehr Akku-Verbrauch</small>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-camera-fps" class="amp-setting-label">
                                Bildrate
                            </label>
                            <select id="amp-camera-fps" name="camera_fps" class="amp-form-control">
                                <option value="15" <?php selected($settings['camera_fps'] ?? '30', '15'); ?>>15 FPS</option>
                                <option value="30" <?php selected($settings['camera_fps'] ?? '30', '30'); ?>>30 FPS</option>
                                <option value="60" <?php selected($settings['camera_fps'] ?? '30', '60'); ?>>60 FPS</option>
                            </select>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="camera_autofocus" <?php checked($settings['camera_autofocus'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Autofokus</span>
                                <small>Automatische Schärfeneinstellung</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scanner Test -->
                <div class="amp-setting-group">
                    <h3>🧪 Scanner-Test</h3>
                    <div class="amp-scanner-test">
                        <div class="amp-test-area">
                            <div class="amp-test-preview" id="amp-scanner-test-preview">
                                <div class="amp-test-placeholder">
                                    <i class="amp-icon-camera"></i>
                                    <span>Kamera-Test bereit</span>
                                </div>
                            </div>
                            <div class="amp-test-actions">
                                <button type="button" class="amp-btn amp-btn-primary" id="amp-test-scanner">
                                    <i class="amp-icon-play"></i>
                                    Scanner testen
                                </button>
                                <button type="button" class="amp-btn amp-btn-secondary" id="amp-test-settings">
                                    <i class="amp-icon-settings"></i>
                                    Einstellungen testen
                                </button>
                            </div>
                            <div class="amp-test-results" id="amp-test-results" style="display: none;">
                                <div class="amp-test-status">
                                    <i class="amp-icon-check-circle"></i>
                                    <span>Scanner funktioniert korrekt</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Settings Tab -->
        <div class="amp-tab-content" id="amp-tab-users">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>👥 Benutzer & Berechtigungen</h2>
                    <p>Benutzerrollen und Zugriffsrechte verwalten</p>
                </div>

                <!-- User Roles -->
                <div class="amp-setting-group">
                    <h3>🔐 Benutzerrollen</h3>
                    <div class="amp-user-roles">
                        <div class="amp-role-item">
                            <div class="amp-role-header">
                                <div class="amp-role-info">
                                    <h4>👑 Administrator</h4>
                                    <p>Vollzugriff auf alle Funktionen</p>
                                </div>
                                <span class="amp-badge amp-badge-success">5 Benutzer</span>
                            </div>
                            <div class="amp-role-permissions">
                                <div class="amp-permission-grid">
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Scanner nutzen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Produkte verwalten</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Verkäufe abwickeln</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Lager verwalten</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Berichte einsehen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Einstellungen ändern</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Daten exportieren</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Benutzer verwalten</span>
                                </div>
                            </div>
                        </div>

                        <div class="amp-role-item">
                            <div class="amp-role-header">
                                <div class="amp-role-info">
                                    <h4>📦 Lagerverwalter</h4>
                                    <p>Scanner und Lagerverwaltung</p>
                                </div>
                                <span class="amp-badge amp-badge-primary">2 Benutzer</span>
                            </div>
                            <div class="amp-role-permissions">
                                <div class="amp-permission-grid">
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Scanner nutzen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Produkte hinzufügen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Lager auffüllen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Entsorgung dokumentieren</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Lager-Berichte</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Verkaufsberichte</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Einstellungen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Benutzer verwalten</span>
                                </div>
                            </div>
                        </div>

                        <div class="amp-role-item">
                            <div class="amp-role-header">
                                <div class="amp-role-info">
                                    <h4>🛒 Kassierer</h4>
                                    <p>Nur Verkaufsfunktionen</p>
                                </div>
                                <span class="amp-badge amp-badge-warning">3 Benutzer</span>
                            </div>
                            <div class="amp-role-permissions">
                                <div class="amp-permission-grid">
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Scanner (Verkaufen)</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Produkte anzeigen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Lager auffüllen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Entsorgung</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Berichte</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Einstellungen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Daten exportieren</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Benutzer verwalten</span>
                                </div>
                            </div>
                        </div>

                        <div class="amp-role-item">
                            <div class="amp-role-header">
                                <div class="amp-role-info">
                                    <h4>👀 Berichtseinsicht</h4>
                                    <p>Nur Berichte und Auswertungen</p>
                                </div>
                                <span class="amp-badge amp-badge-info">1 Benutzer</span>
                            </div>
                            <div class="amp-role-permissions">
                                <div class="amp-permission-grid">
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Scanner nutzen</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Produkte anzeigen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Lager verwalten</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Verkäufe abwickeln</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Alle Berichte</span>
                                    <span class="amp-permission"><i class="amp-icon-check"></i> Daten exportieren</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Einstellungen</span>
                                    <span class="amp-permission"><i class="amp-icon-x"></i> Benutzer verwalten</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Management -->
                <div class="amp-setting-group">
                    <h3>👤 Benutzer-Verwaltung</h3>
                    <div class="amp-user-management">
                        <div class="amp-user-actions">
                            <button type="button" class="amp-btn amp-btn-primary" id="amp-add-user">
                                <i class="amp-icon-user-plus"></i>
                                Neuen Benutzer hinzufügen
                            </button>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-import-users">
                                <i class="amp-icon-upload"></i>
                                Benutzer importieren
                            </button>
                        </div>

                        <div class="amp-user-list">
                            <div class="amp-user-item">
                                <div class="amp-user-avatar">
                                    <i class="amp-icon-user"></i>
                                </div>
                                <div class="amp-user-info">
                                    <h4>Max Mustermann</h4>
                                    <p>Administrator</p>
                                    <small>max.mustermann@firma.de</small>
                                </div>
                                <div class="amp-user-status">
                                    <span class="amp-badge amp-badge-success">Aktiv</span>
                                </div>
                                <div class="amp-user-actions">
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-secondary">
                                        <i class="amp-icon-edit"></i>
                                    </button>
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-danger">
                                        <i class="amp-icon-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="amp-user-item">
                                <div class="amp-user-avatar">
                                    <i class="amp-icon-user"></i>
                                </div>
                                <div class="amp-user-info">
                                    <h4>Anna Schmidt</h4>
                                    <p>Lagerverwalter</p>
                                    <small>anna.schmidt@firma.de</small>
                                </div>
                                <div class="amp-user-status">
                                    <span class="amp-badge amp-badge-success">Aktiv</span>
                                </div>
                                <div class="amp-user-actions">
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-secondary">
                                        <i class="amp-icon-edit"></i>
                                    </button>
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-danger">
                                        <i class="amp-icon-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="amp-user-item">
                                <div class="amp-user-avatar">
                                    <i class="amp-icon-user"></i>
                                </div>
                                <div class="amp-user-info">
                                    <h4>Tom Weber</h4>
                                    <p>Kassierer</p>
                                    <small>tom.weber@firma.de</small>
                                </div>
                                <div class="amp-user-status">
                                    <span class="amp-badge amp-badge-warning">Inaktiv</span>
                                </div>
                                <div class="amp-user-actions">
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-secondary">
                                        <i class="amp-icon-edit"></i>
                                    </button>
                                    <button type="button" class="amp-btn amp-btn-sm amp-btn-danger">
                                        <i class="amp-icon-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="amp-setting-group">
                    <h3>🔒 Sicherheits-Einstellungen</h3>
                    <div class="amp-security-settings">
                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="security_2fa" <?php checked($settings['security_2fa'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Zwei-Faktor-Authentifizierung</span>
                                <small>Zusätzliche Sicherheit für Administrator-Accounts</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="security_session_timeout" <?php checked($settings['security_session_timeout'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Automatisches Abmelden</span>
                                <small>Session-Timeout nach Inaktivität</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-session-duration" class="amp-setting-label">
                                Session-Dauer
                            </label>
                            <select id="amp-session-duration" name="session_duration" class="amp-form-control">
                                <option value="30" <?php selected($settings['session_duration'] ?? '60', '30'); ?>>30 Minuten</option>
                                <option value="60" <?php selected($settings['session_duration'] ?? '60', '60'); ?>>1 Stunde</option>
                                <option value="240" <?php selected($settings['session_duration'] ?? '60', '240'); ?>>4 Stunden</option>
                                <option value="480" <?php selected($settings['session_duration'] ?? '60', '480'); ?>>8 Stunden</option>
                            </select>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="security_login_attempts" <?php checked($settings['security_login_attempts'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Login-Versuche begrenzen</span>
                                <small>Account-Sperrung nach fehlgeschlagenen Versuchen</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Settings Tab -->
        <div class="amp-tab-content" id="amp-tab-system">
            <div class="amp-settings-section">
                <div class="amp-section-header">
                    <h2>⚙️ System-Verwaltung</h2>
                    <p>Wartung, Backup und Performance-Einstellungen</p>
                </div>

                <!-- System Status -->
                <div class="amp-setting-group">
                    <h3>📊 System-Status</h3>
                    <div class="amp-system-status">
                        <div class="amp-status-grid">
                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-success">
                                    <i class="amp-icon-check-circle"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">Database</span>
                                    <span class="amp-status-value">Online</span>
                                </div>
                            </div>

                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-success">
                                    <i class="amp-icon-check-circle"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">E-Mail</span>
                                    <span class="amp-status-value">Funktional</span>
                                </div>
                            </div>

                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-warning">
                                    <i class="amp-icon-alert-triangle"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">Backup</span>
                                    <span class="amp-status-value">3 Tage alt</span>
                                </div>
                            </div>

                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-success">
                                    <i class="amp-icon-check-circle"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">Scanner</span>
                                    <span class="amp-status-value">Bereit</span>
                                </div>
                            </div>

                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-info">
                                    <i class="amp-icon-info"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">Version</span>
                                    <span class="amp-status-value">1.0.0</span>
                                </div>
                            </div>

                            <div class="amp-status-item">
                                <div class="amp-status-icon amp-status-success">
                                    <i class="amp-icon-check-circle"></i>
                                </div>
                                <div class="amp-status-info">
                                    <span class="amp-status-label">Performance</span>
                                    <span class="amp-status-value">Optimal</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance -->
                <div class="amp-setting-group">
                    <h3>🔧 Wartung</h3>
                    <div class="amp-maintenance-tools">
                        <div class="amp-maintenance-item">
                            <div class="amp-maintenance-info">
                                <h4>🧹 Cache leeren</h4>
                                <p>Temporäre Dateien und Zwischenspeicher löschen</p>
                                <small>Letzte Bereinigung: vor 2 Stunden</small>
                            </div>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-clear-cache">
                                <i class="amp-icon-trash"></i>
                                Cache leeren
                            </button>
                        </div>

                        <div class="amp-maintenance-item">
                            <div class="amp-maintenance-info">
                                <h4>📝 Logs bereinigen</h4>
                                <p>Alte Log-Dateien archivieren oder löschen</p>
                                <small>Log-Größe: 24.3 MB (47 Dateien)</small>
                            </div>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-clean-logs">
                                <i class="amp-icon-file-text"></i>
                                Logs bereinigen
                            </button>
                        </div>

                        <div class="amp-maintenance-item">
                            <div class="amp-maintenance-info">
                                <h4>🗄️ Datenbank optimieren</h4>
                                <p>Tabellen optimieren und defragmentieren</p>
                                <small>Letzte Optimierung: vor 1 Woche</small>
                            </div>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-optimize-db">
                                <i class="amp-icon-database"></i>
                                DB optimieren
                            </button>
                        </div>

                        <div class="amp-maintenance-item">
                            <div class="amp-maintenance-info">
                                <h4>🔄 Plugin-Update</h4>
                                <p>Nach verfügbaren Updates suchen</p>
                                <small>Aktuelle Version: 1.0.0</small>
                            </div>
                            <button type="button" class="amp-btn amp-btn-primary" id="amp-check-updates">
                                <i class="amp-icon-download"></i>
                                Updates prüfen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Backup Settings -->
                <div class="amp-setting-group">
                    <h3>💾 Backup & Export</h3>
                    <div class="amp-backup-settings">
                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="backup_auto" <?php checked($settings['backup_auto'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Automatisches Backup</span>
                                <small>Tägliche Sicherung aller Daten</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-backup-frequency" class="amp-setting-label">
                                Backup-Häufigkeit
                            </label>
                            <select id="amp-backup-frequency" name="backup_frequency" class="amp-form-control">
                                <option value="daily" <?php selected($settings['backup_frequency'] ?? 'daily', 'daily'); ?>>Täglich</option>
                                <option value="weekly" <?php selected($settings['backup_frequency'] ?? 'daily', 'weekly'); ?>>Wöchentlich</option>
                                <option value="monthly" <?php selected($settings['backup_frequency'] ?? 'daily', 'monthly'); ?>>Monatlich</option>
                            </select>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-backup-retention" class="amp-setting-label">
                                Backup-Aufbewahrung
                            </label>
                            <select id="amp-backup-retention" name="backup_retention" class="amp-form-control">
                                <option value="7" <?php selected($settings['backup_retention'] ?? '30', '7'); ?>>7 Tage</option>
                                <option value="30" <?php selected($settings['backup_retention'] ?? '30', '30'); ?>>30 Tage</option>
                                <option value="90" <?php selected($settings['backup_retention'] ?? '30', '90'); ?>>90 Tage</option>
                                <option value="365" <?php selected($settings['backup_retention'] ?? '30', '365'); ?>>1 Jahr</option>
                            </select>
                        </div>

                        <div class="amp-backup-actions">
                            <button type="button" class="amp-btn amp-btn-primary" id="amp-create-backup">
                                <i class="amp-icon-download"></i>
                                Backup jetzt erstellen
                            </button>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-restore-backup">
                                <i class="amp-icon-upload"></i>
                                Backup wiederherstellen
                            </button>
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-export-data">
                                <i class="amp-icon-file-text"></i>
                                Daten exportieren
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Performance Settings -->
                <div class="amp-setting-group">
                    <h3>⚡ Performance</h3>
                    <div class="amp-performance-settings">
                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="performance_cache" <?php checked($settings['performance_cache'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Cache aktivieren</span>
                                <small>Zwischenspeicher für bessere Performance</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="performance_minify" <?php checked($settings['performance_minify'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">CSS/JS minimieren</span>
                                <small>Dateien komprimieren für schnellere Ladezeiten</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="performance_lazy_load" <?php checked($settings['performance_lazy_load'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Lazy Loading</span>
                                <small>Bilder erst bei Bedarf laden</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-cache-duration" class="amp-setting-label">
                                Cache-Dauer
                            </label>
                            <select id="amp-cache-duration" name="cache_duration" class="amp-form-control">
                                <option value="300" <?php selected($settings['cache_duration'] ?? '3600', '300'); ?>>5 Minuten</option>
                                <option value="1800" <?php selected($settings['cache_duration'] ?? '3600', '1800'); ?>>30 Minuten</option>
                                <option value="3600" <?php selected($settings['cache_duration'] ?? '3600', '3600'); ?>>1 Stunde</option>
                                <option value="86400" <?php selected($settings['cache_duration'] ?? '3600', '86400'); ?>>24 Stunden</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Expiry Monitoring -->
                <div class="amp-setting-group">
                    <h3>⏰ Haltbarkeitsdatum-Überwachung</h3>
                    <div class="amp-expiry-settings">
                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="expiry_monitoring" <?php checked($settings['expiry_monitoring'] ?? true); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Überwachung aktivieren</span>
                                <small>Automatische Prüfung der Haltbarkeitsdaten</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-expiry-warning-days" class="amp-setting-label">
                                Warnung vor Ablauf
                            </label>
                            <div class="amp-input-group">
                                <input 
                                    type="number" 
                                    id="amp-expiry-warning-days"
                                    name="expiry_warning_days"
                                    class="amp-form-control"
                                    value="<?php echo esc_attr($settings['expiry_warning_days'] ?? '2'); ?>"
                                    min="1"
                                    max="30"
                                />
                                <span class="amp-input-addon">Tage</span>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-expiry-check-time" class="amp-setting-label">
                                Prüfung um
                            </label>
                            <input 
                                type="time" 
                                id="amp-expiry-check-time"
                                name="expiry_check_time"
                                class="amp-form-control"
                                value="<?php echo esc_attr($settings['expiry_check_time'] ?? '09:00'); ?>"
                            />
                        </div>

                        <div class="amp-setting-item">
                            <label class="amp-switch">
                                <input type="checkbox" name="expiry_auto_discount" <?php checked($settings['expiry_auto_discount'] ?? false); ?>>
                                <span class="amp-switch-slider"></span>
                            </label>
                            <div class="amp-setting-info">
                                <span class="amp-setting-label">Automatische Preisreduzierung</span>
                                <small>Preise kurz vor Ablauf automatisch senken</small>
                            </div>
                        </div>

                        <div class="amp-setting-item">
                            <label for="amp-expiry-discount-percent" class="amp-setting-label">
                                Rabatt-Prozentsatz
                            </label>
                            <div class="amp-input-group">
                                <input 
                                    type="number" 
                                    id="amp-expiry-discount-percent"
                                    name="expiry_discount_percent"
                                    class="amp-form-control"
                                    value="<?php echo esc_attr($settings['expiry_discount_percent'] ?? '25'); ?>"
                                    min="5"
                                    max="75"
                                    step="5"
                                />
                                <span class="amp-input-addon">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- License & Updates -->
                <div class="amp-setting-group">
                    <h3>🔑 Lizenz & Updates</h3>
                    <div class="amp-license-info">
                        <div class="amp-license-status">
                            <div class="amp-license-badge amp-license-active">
                                <i class="amp-icon-check-circle"></i>
                                <span>Lizenz aktiv</span>
                            </div>
                            <div class="amp-license-details">
                                <p><strong>AutomatenManager Pro</strong></p>
                                <p>Lizenz-Typ: Commercial</p>
                                <p>Gültig bis: 31.12.2025</p>
                                <p>Domain: automat.local</p>
                            </div>
                        </div>

                        <div class="amp-license-actions">
                            <button type="button" class="amp-btn amp-btn-secondary" id="amp-validate-license">
                                <i class="amp-icon-refresh"></i>
                                Lizenz validieren
                            </button>
                            <button type="button" class="amp-btn amp-btn-primary" id="amp-upgrade-license">
                                <i class="amp-icon-star"></i>
                                Lizenz erweitern
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Progress Indicator -->
    <div class="amp-save-indicator" id="amp-save-indicator" style="display: none;">
        <div class="amp-spinner"></div>
        <span>Speichere Einstellungen...</span>
    </div>

    <!-- Settings Modals -->
    <div class="amp-modal" id="amp-email-template-modal" style="display: none;">
        <div class="amp-modal-content">
            <div class="amp-modal-header">
                <h3>E-Mail Template bearbeiten</h3>
                <button type="button" class="amp-modal-close" data-modal="amp-email-template-modal">
                    <i class="amp-icon-x"></i>
                </button>
            </div>
            <div class="amp-modal-body">
                <div class="amp-template-editor">
                    <div class="amp-editor-toolbar">
                        <button type="button" class="amp-btn amp-btn-sm" data-action="bold">
                            <i class="amp-icon-bold"></i>
                        </button>
                        <button type="button" class="amp-btn amp-btn-sm" data-action="italic">
                            <i class="amp-icon-italic"></i>
                        </button>
                        <button type="button" class="amp-btn amp-btn-sm" data-action="link">
                            <i class="amp-icon-link"></i>
                        </button>
                    </div>
                    <textarea 
                        id="amp-template-content" 
                        class="amp-template-textarea"
                        rows="15"
                        placeholder="E-Mail Template HTML..."
                    ></textarea>
                    <div class="amp-template-variables">
                        <h4>Verfügbare Variablen:</h4>
                        <div class="amp-variable-tags">
                            <span class="amp-tag" data-variable="{company_name}">Firmenname</span>
                            <span class="amp-tag" data-variable="{date}">Datum</span>
                            <span class="amp-tag" data-variable="{time}">Uhrzeit</span>
                            <span class="amp-tag" data-variable="{total}">Gesamtsumme</span>
                            <span class="amp-tag" data-variable="{items}">Artikel-Liste</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="amp-modal-footer">
                <button type="button" class="amp-btn amp-btn-secondary" data-modal="amp-email-template-modal">
                    Abbrechen
                </button>
                <button type="button" class="amp-btn amp-btn-primary" id="amp-save-template">
                    Template speichern
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation
    const tabButtons = document.querySelectorAll('.amp-tab-btn');
    const tabContents = document.querySelectorAll('.amp-tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            
            // Remove active class from all tabs and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById('amp-tab-' + tabId).classList.add('active');
        });
    });
    
    // Store Hours Toggle
    document.querySelectorAll('.amp-store-hour-row input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const row = this.closest('.amp-store-hour-row');
            const timeInputs = row.querySelectorAll('input[type="time"]');
            
            timeInputs.forEach(input => {
                input.disabled = !this.checked;
            });
        });
    });
    
    // Logo Upload
    document.getElementById('amp-upload-logo')?.addEventListener('click', function() {
        // WordPress Media Manager integration would go here
        console.log('Logo upload clicked');
    });
    
    // Logo Remove
    document.getElementById('amp-remove-logo')?.addEventListener('click', function() {
        if (confirm('Logo wirklich entfernen?')) {
            document.getElementById('amp-logo-preview').innerHTML = `
                <div class="amp-logo-placeholder">
                    <i class="amp-icon-image"></i>
                    <span>Kein Logo hochgeladen</span>
                </div>
            `;
        }
    });
    
    // Test Email
    document.getElementById('amp-test-email')?.addEventListener('click', function() {
        const button = this;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<i class="amp-icon-loader amp-spin"></i> Sende...';
        button.disabled = true;
        
        // Simulate API call
        setTimeout(() => {
            button.innerHTML = '<i class="amp-icon-check"></i> Test erfolgreich';
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        }, 2000);
    });
    
    // Scanner Test
    document.getElementById('amp-test-scanner')?.addEventListener('click', function() {
        const preview = document.getElementById('amp-scanner-test-preview');
        const results = document.getElementById('amp-test-results');
        
        preview.innerHTML = `
            <div class="amp-test-loading">
                <div class="amp-spinner"></div>
                <span>Scanner wird getestet...</span>
            </div>
        `;
        
        setTimeout(() => {
            preview.innerHTML = `
                <div class="amp-test-success">
                    <i class="amp-icon-check-circle"></i>
                    <span>Kamera erfolgreich gestartet</span>
                </div>
            `;
            results.style.display = 'block';
        }, 3000);
    });
    
    // Email Template Editor
    document.querySelectorAll('button[data-template]').forEach(button => {
        button.addEventListener('click', function() {
            const templateType = this.dataset.template;
            const modal = document.getElementById('amp-email-template-modal');
            
            // Load template content based on type
            const textarea = modal.querySelector('#amp-template-content');
            
            const templates = {
                sales: 'Verkaufsbericht Template...',
                inventory: 'Lager Template...',
                expiry: 'Ablauf-Warnung Template...',
                waste: 'Entsorgungs Template...'
            };
            
            textarea.value = templates[templateType] || '';
            modal.style.display = 'flex';
        });
    });
    
    // Variable Tags
    document.querySelectorAll('.amp-tag[data-variable]').forEach(tag => {
        tag.addEventListener('click', function() {
            const variable = this.dataset.variable;
            const textarea = document.getElementById('amp-template-content');
            
            // Insert variable at cursor position
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            
            textarea.value = text.substring(0, start) + variable + text.substring(end);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        });
    });
    
    // Modal Close
    document.querySelectorAll('.amp-modal-close, .amp-btn[data-modal]').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modalId = this.dataset.modal || this.closest('.amp-modal').id;
            document.getElementById(modalId).style.display = 'none';
        });
    });
    
    // Save All Settings
    document.getElementById('amp-save-all-settings')?.addEventListener('click', function() {
        const indicator = document.getElementById('amp-save-indicator');
        indicator.style.display = 'flex';
        
        // Simulate save process
        setTimeout(() => {
            indicator.style.display = 'none';
            
            // Show success notification
            const notification = document.createElement('div');
            notification.className = 'amp-notification amp-notification-success';
            notification.innerHTML = `
                <i class="amp-icon-check"></i>
                <span>Einstellungen erfolgreich gespeichert!</span>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('amp-notification-show');
            }, 100);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }, 2000);
    });
    
    // Range Slider Display
    document.querySelectorAll('.amp-slider').forEach(slider => {
        const updateValue = () => {
            const value = slider.value;
            const percent = ((value - slider.min) / (slider.max - slider.min)) * 100;
            slider.style.background = `linear-gradient(to right, var(--amp-primary) ${percent}%, #ddd ${percent}%)`;
        };
        
        slider.addEventListener('input', updateValue);
        updateValue(); // Initial call
    });
    
    // Maintenance Actions
    const maintenanceActions = {
        'amp-clear-cache': 'Cache wird geleert...',
        'amp-clean-logs': 'Logs werden bereinigt...',
        'amp-optimize-db': 'Datenbank wird optimiert...',
        'amp-check-updates': 'Updates werden geprüft...'
    };
    
    Object.keys(maintenanceActions).forEach(actionId => {
        document.getElementById(actionId)?.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            
            button.innerHTML = `<i class="amp-icon-loader amp-spin"></i> ${maintenanceActions[actionId]}`;
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = '<i class="amp-icon-check"></i> Abgeschlossen';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
            }, 3000);
        });
    });
});
</script>

<style>
/* Settings specific styles */
.amp-settings-nav {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 8px;
    margin-bottom: 24px;
    overflow-x: auto;
}

.amp-tab-nav {
    display: flex;
    gap: 4px;
    min-width: max-content;
}

.amp-tab-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border: none;
    background: transparent;
    border-radius: 8px;
    color: var(--amp-text);
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 500;
}

.amp-tab-btn:hover {
    background: rgba(0, 123, 255, 0.1);
    color: var(--amp-primary);
}

.amp-tab-btn.active {
    background: var(--amp-primary);
    color: white;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.3);
}

.amp-tab-content {
    display: none;
}

.amp-tab-content.active {
    display: block;
}

.amp-settings-section {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
}

.amp-section-header {
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--amp-card);
}

.amp-section-header h2 {
    font-size: 24px;
    font-weight: 600;
    color: var(--amp-text);
    margin: 0 0 8px 0;
}

.amp-section-header h3 {
    font-size: 18px;
    font-weight: 600;
    color: var(--amp-text);
    margin: 24px 0 16px 0;
}

.amp-section-header p {
    color: #6B7280;
    margin: 0;
    font-size: 16px;
}

.amp-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.amp-setting-item {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.amp-setting-label {
    font-weight: 600;
    color: var(--amp-text);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.amp-required {
    color: var(--amp-danger);
}

.amp-help-text {
    color: #6B7280;
    font-size: 12px;
    margin-top: 4px;
}

.amp-logo-upload {
    border: 2px dashed #D1D5DB;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    background: var(--amp-card);
}

.amp-logo-preview {
    margin-bottom: 16px;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.amp-logo-preview img {
    max-height: 80px;
    max-width: 200px;
}

.amp-logo-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: #6B7280;
}

.amp-logo-placeholder i {
    font-size: 32px;
}

.amp-logo-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
}

.amp-store-hours {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.amp-store-hour-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: var(--amp-card);
    border-radius: 12px;
}

.amp-day-toggle {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 120px;
}

.amp-day-name {
    font-weight: 500;
    color: var(--amp-text);
}

.amp-time-inputs {
    display: flex;
    align-items: center;
    gap: 12px;
}

.amp-time-separator {
    color: #6B7280;
    font-size: 14px;
}

.amp-switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 24px;
}

.amp-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.amp-switch-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #D1D5DB;
    transition: 0.2s;
    border-radius: 24px;
}

.amp-switch-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.2s;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.amp-switch input:checked + .amp-switch-slider {
    background-color: var(--amp-primary);
}

.amp-switch input:checked + .amp-switch-slider:before {
    transform: translateX(24px);
}

.amp-setting-group {
    margin-bottom: 48px;
    padding-bottom: 32px;
    border-bottom: 1px solid var(--amp-card);
}

.amp-setting-group:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.amp-email-recipients {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.amp-recipient-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--amp-card);
    border-radius: 12px;
}

.amp-recipient-item .amp-setting-label {
    min-width: 140px;
    margin: 0;
}

.amp-input-group {
    display: flex;
    align-items: center;
}

.amp-input-addon {
    background: var(--amp-card);
    border: 1px solid #D1D5DB;
    border-left: none;
    padding: 10px 12px;
    border-radius: 0 8px 8px 0;
    font-size: 14px;
    color: #6B7280;
}

.amp-input-group .amp-form-control {
    border-radius: 8px 0 0 8px;
    border-right: none;
}

.amp-vat-settings {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
}

.amp-vat-item {
    padding: 20px;
    background: var(--amp-card);
    border-radius: 12px;
    text-align: center;
}

.amp-deposit-settings {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-deposit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.amp-deposit-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px;
    background: white;
    border-radius: 8px;
    text-align: center;
}

.amp-deposit-label {
    font-size: 18px;
    font-weight: 600;
    color: var(--amp-primary);
}

.amp-custom-deposit {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
}

.amp-custom-deposit input {
    width: 80px;
    text-align: center;
}

.amp-range-slider {
    position: relative;
    margin: 16px 0;
}

.amp-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: #ddd;
    outline: none;
    appearance: none;
}

.amp-slider::-webkit-slider-thumb {
    appearance: none;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--amp-primary);
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
}

.amp-slider::-moz-range-thumb {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--amp-primary);
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 6px rgba(0, 123, 255, 0.3);
}

.amp-range-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 8px;
    font-size: 12px;
    color: #6B7280;
}

.amp-barcode-formats,
.amp-camera-settings,
.amp-scanner-settings {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-format-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.amp-format-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: 8px;
}

.amp-format-info {
    flex-grow: 1;
}

.amp-format-name {
    font-weight: 600;
    color: var(--amp-text);
    display: block;
}

.amp-scanner-test {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-test-area {
    text-align: center;
}

.amp-test-preview {
    width: 100%;
    height: 200px;
    background: #000;
    border-radius: 12px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.amp-test-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.amp-test-placeholder i {
    font-size: 32px;
}

.amp-test-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-bottom: 16px;
}

.amp-test-results {
    padding: 16px;
    background: rgba(16, 185, 129, 0.1);
    border-radius: 8px;
    color: var(--amp-success);
}

.amp-user-roles {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.amp-role-item {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-role-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.amp-role-info h4 {
    margin: 0 0 4px 0;
    font-size: 18px;
    font-weight: 600;
}

.amp-role-info p {
    margin: 0;
    color: #6B7280;
}

.amp-permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 8px;
}

.amp-permission {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    padding: 4px 0;
}

.amp-permission i {
    font-size: 16px;
}

.amp-user-management {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-user-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
}

.amp-user-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.amp-user-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: white;
    border-radius: 8px;
}

.amp-user-avatar {
    width: 48px;
    height: 48px;
    background: var(--amp-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 20px;
}

.amp-user-info {
    flex-grow: 1;
}

.amp-user-info h4 {
    margin: 0 0 4px 0;
    font-weight: 600;
}

.amp-user-info p {
    margin: 0 0 4px 0;
    color: #6B7280;
    font-size: 14px;
}

.amp-user-info small {
    color: #9CA3AF;
    font-size: 12px;
}

.amp-user-actions {
    display: flex;
    gap: 8px;
}

.amp-system-status {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 32px;
}

.amp-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.amp-status-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: 8px;
}

.amp-status-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.amp-status-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--amp-success);
}

.amp-status-warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--amp-warning);
}

.amp-status-info {
    background: rgba(0, 123, 255, 0.1);
    color: var(--amp-primary);
}

.amp-status-info {
    flex-grow: 1;
}

.amp-status-label {
    font-weight: 600;
    color: var(--amp-text);
    display: block;
    margin-bottom: 2px;
}

.amp-status-value {
    color: #6B7280;
    font-size: 14px;
}

.amp-maintenance-tools {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.amp-maintenance-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: var(--amp-card);
    border-radius: 12px;
}

.amp-maintenance-info h4 {
    margin: 0 0 8px 0;
    font-weight: 600;
}

.amp-maintenance-info p {
    margin: 0 0 4px 0;
    color: #6B7280;
}

.amp-maintenance-info small {
    color: #9CA3AF;
    font-size: 12px;
}

.amp-backup-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.amp-license-info {
    background: var(--amp-card);
    border-radius: 12px;
    padding: 24px;
}

.amp-license-status {
    display: flex;
    gap: 24px;
    align-items: center;
    margin-bottom: 24px;
}

.amp-license-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 8px;
    font-weight: 600;
}

.amp-license-active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--amp-success);
}

.amp-license-details p {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.amp-license-actions {
    display: flex;
    gap: 12px;
}

.amp-save-indicator {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 24px 32px;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 12px;
    z-index: 1000;
}

.amp-template-editor {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.amp-editor-toolbar {
    display: flex;
    gap: 8px;
    padding: 12px;
    background: var(--amp-card);
    border-radius: 8px;
}

.amp-template-textarea {
    width: 100%;
    border: 1px solid #D1D5DB;
    border-radius: 8px;
    padding: 12px;
    font-family: monospace;
    font-size: 14px;
    resize: vertical;
}

.amp-template-variables {
    padding: 16px;
    background: var(--amp-card);
    border-radius: 8px;
}

.amp-template-variables h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
}

.amp-variable-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.amp-tag {
    display: inline-flex;
    align-items: center;
    padding: 4px 8px;
    background: var(--amp-primary);
    color: white;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.amp-tag:hover {
    background: #0056b3;
}

/* Samsung S25 Ultra optimizations */
@media (max-width: 480px) {
    .amp-tab-nav {
        flex-wrap: nowrap;
        overflow-x: auto;
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    
    .amp-tab-nav::-webkit-scrollbar {
        display: none;
    }
    
    .amp-settings-grid {
        grid-template-columns: 1fr;
    }
    
    .amp-store-hour-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .amp-time-inputs {
        width: 100%;
        justify-content: space-between;
    }
    
    .amp-maintenance-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    
    .amp-user-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .amp-license-status {
        flex-direction: column;
        align-items: flex-start;
    }
}

/* Haptic feedback simulation */
@media (hover: none) and (pointer: coarse) {
    .amp-btn:active,
    .amp-tab-btn:active,
    .amp-switch:active {
        transform: scale(0.98);
        transition: transform 0.1s;
    }
}
</style>