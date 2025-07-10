<?php
if (!defined('WPINC')) {
    die;
}

// Get categories for quick selection
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}automaten_categories ORDER BY name ASC");
?>

<div class="automaten-scanner-wrapper">
    <!-- Scanner Header -->
    <div class="scanner-header glass-card">
        <div class="scanner-header-content">
            <div class="scanner-title">
                <h1>🎯 Produkt Scanner</h1>
                <p>Scannen Sie Barcodes oder fügen Sie manuell Produkte hinzu</p>
            </div>
            <div class="scanner-stats">
                <div class="stat-item">
                    <span class="stat-value" id="scans-today">0</span>
                    <span class="stat-label">Heute gescannt</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value" id="total-products">0</span>
                    <span class="stat-label">Produkte total</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Interface -->
    <div class="scanner-interface">
        <!-- Camera Section -->
        <div class="scanner-camera-section glass-card">
            <div class="camera-container">
                <div class="camera-header">
                    <h3>📷 Barcode Scanner</h3>
                    <div class="camera-controls">
                        <button id="start-camera" class="btn btn-primary">
                            <span class="btn-icon">📹</span>
                            Kamera starten
                        </button>
                        <button id="stop-camera" class="btn btn-secondary" style="display: none;">
                            <span class="btn-icon">⏹️</span>
                            Stoppen
                        </button>
                        <button id="switch-camera" class="btn btn-outline" style="display: none;">
                            <span class="btn-icon">🔄</span>
                            Kamera wechseln
                        </button>
                    </div>
                </div>
                
                <!-- Video Element -->
                <div class="video-container">
                    <video id="scanner-video" autoplay playsinline></video>
                    <div class="scanner-overlay">
                        <div class="scan-frame">
                            <div class="scan-line"></div>
                            <div class="corner corner-tl"></div>
                            <div class="corner corner-tr"></div>
                            <div class="corner corner-bl"></div>
                            <div class="corner corner-br"></div>
                        </div>
                        <div class="scan-instructions">
                            <p>Barcode in den Rahmen positionieren</p>
                        </div>
                    </div>
                </div>

                <!-- Manual Input -->
                <div class="manual-input-section">
                    <div class="input-group">
                        <input type="text" id="manual-barcode" placeholder="Barcode manuell eingeben..." class="form-input">
                        <button id="scan-manual" class="btn btn-primary">
                            <span class="btn-icon">🔍</span>
                            Scannen
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Add Section -->
        <div class="quick-add-section glass-card">
            <h3>⚡ Schnell hinzufügen</h3>
            <form id="quick-add-form" class="quick-add-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="quick-name">Produktname*</label>
                        <input type="text" id="quick-name" name="name" required class="form-input" placeholder="z.B. Coca Cola 0,5L">
                    </div>
                    
                    <div class="form-group">
                        <label for="quick-category">Kategorie*</label>
                        <select id="quick-category" name="category_id" required class="form-select">
                            <option value="">Kategorie wählen...</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->id); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quick-price">Preis (€)</label>
                        <input type="number" id="quick-price" name="price" step="0.01" min="0" class="form-input" placeholder="2.50">
                    </div>

                    <div class="form-group">
                        <label for="quick-stock">Bestand</label>
                        <input type="number" id="quick-stock" name="stock_quantity" min="0" class="form-input" placeholder="10">
                    </div>

                    <div class="form-group">
                        <label for="quick-barcode">Barcode</label>
                        <input type="text" id="quick-barcode" name="barcode" class="form-input" placeholder="4006381333634">
                    </div>

                    <div class="form-group">
                        <label for="quick-description">Beschreibung</label>
                        <textarea id="quick-description" name="description" class="form-textarea" rows="2" placeholder="Kurze Beschreibung..."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <span class="btn-icon">💾</span>
                        Produkt hinzufügen
                    </button>
                    <button type="reset" class="btn btn-outline">
                        <span class="btn-icon">🔄</span>
                        Zurücksetzen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scan Results -->
    <div class="scan-results-section">
        <div class="results-header">
            <h3>📊 Scan-Ergebnisse</h3>
            <div class="results-filter">
                <select id="results-filter" class="form-select">
                    <option value="all">Alle anzeigen</option>
                    <option value="success">Erfolgreich</option>
                    <option value="new">Neu hinzugefügt</option>
                    <option value="updated">Aktualisiert</option>
                    <option value="error">Fehler</option>
                </select>
                <button id="clear-results" class="btn btn-outline">
                    <span class="btn-icon">🗑️</span>
                    Leeren
                </button>
            </div>
        </div>

        <div id="scan-results" class="scan-results">
            <!-- Results will be populated by JavaScript -->
        </div>
    </div>
</div>

<!-- Scan Result Modal -->
<div id="scan-result-modal" class="modal-overlay">
    <div class="modal-content glass-modal">
        <div class="modal-header">
            <h3 id="scan-modal-title">Scan-Ergebnis</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="scan-modal-content">
                <!-- Content will be populated by JavaScript -->
            </div>
        </div>
        <div class="modal-footer">
            <button id="scan-modal-close" class="btn btn-outline">Schließen</button>
            <button id="scan-modal-edit" class="btn btn-primary" style="display: none;">Bearbeiten</button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="scanner-loading" class="loading-overlay" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <p>Barcode wird verarbeitet...</p>
    </div>
</div>

<!-- Notifications Container -->
<div id="scanner-notifications" class="notifications-container"></div>

<style>
/* Scanner-specific styles are included inline for immediate functionality */
/* These will be moved to scanner.css in the final version */

.automaten-scanner-wrapper {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
    background: linear-gradient(135deg, 
        rgba(99, 102, 241, 0.1) 0%, 
        rgba(168, 85, 247, 0.1) 50%, 
        rgba(236, 72, 153, 0.1) 100%);
    min-height: 100vh;
}

.scanner-header {
    margin-bottom: 30px;
    padding: 25px;
    border-radius: 20px;
}

.scanner-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.scanner-title h1 {
    font-size: 2.5em;
    margin: 0 0 10px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.scanner-stats {
    display: flex;
    gap: 30px;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2em;
    font-weight: bold;
    color: var(--primary-color);
}

.stat-label {
    font-size: 0.9em;
    color: var(--text-muted);
}

.scanner-interface {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.scanner-camera-section,
.quick-add-section {
    padding: 25px;
    border-radius: 20px;
}

.camera-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.camera-controls {
    display: flex;
    gap: 10px;
}

.video-container {
    position: relative;
    background: #000;
    border-radius: 15px;
    overflow: hidden;
    margin-bottom: 20px;
    min-height: 300px;
}

#scanner-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
}

.scan-frame {
    width: 250px;
    height: 250px;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.5);
    border-radius: 10px;
}

.scan-line {
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, #00ff41, transparent);
    animation: scanLine 2s linear infinite;
}

@keyframes scanLine {
    0%, 100% { transform: translateY(-125px); opacity: 0; }
    50% { transform: translateY(0); opacity: 1; }
}

.corner {
    position: absolute;
    width: 20px;
    height: 20px;
    border: 3px solid #00ff41;
}

.corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; }
.corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; }
.corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; }
.corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; }

.scan-instructions {
    margin-top: 20px;
    text-align: center;
    color: white;
    text-shadow: 0 2px 4px rgba(0,0,0,0.8);
}

.manual-input-section .input-group {
    display: flex;
    gap: 10px;
}

.manual-input-section .form-input {
    flex: 1;
}

.quick-add-form .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-primary);
}

.form-input,
.form-select,
.form-textarea {
    padding: 12px 16px;
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    font-size: 16px;
    transition: all 0.3s ease;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-actions {
    grid-column: 1 / -1;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
    margin-top: 20px;
}

.scan-results-section {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.results-filter {
    display: flex;
    gap: 10px;
    align-items: center;
}

.scan-results {
    display: grid;
    gap: 15px;
}

.result-item {
    display: flex;
    align-items: center;
    padding: 15px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    border-left: 4px solid var(--primary-color);
    transition: all 0.3s ease;
}

.result-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
}

.result-icon {
    font-size: 24px;
    margin-right: 15px;
}

.result-content {
    flex: 1;
}

.result-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.result-details {
    font-size: 0.9em;
    color: var(--text-muted);
}

.result-time {
    font-size: 0.8em;
    color: var(--text-muted);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.loading-content {
    text-align: center;
    color: white;
}

.loading-spinner {
    width: 50px;
    height: 50px;
    border: 3px solid rgba(255, 255, 255, 0.3);
    border-top: 3px solid #00ff41;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.notifications-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.notification {
    padding: 15px 20px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    animation: slideIn 0.3s ease;
    min-width: 300px;
}

.notification.success {
    border-left: 4px solid #10b981;
}

.notification.error {
    border-left: 4px solid #ef4444;
}

.notification.warning {
    border-left: 4px solid #f59e0b;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .scanner-interface {
        grid-template-columns: 1fr;
    }
    
    .scanner-header-content {
        flex-direction: column;
        text-align: center;
    }
    
    .quick-add-form .form-grid {
        grid-template-columns: 1fr;
    }
    
    .camera-header {
        flex-direction: column;
        text-align: center;
    }
    
    .results-header {
        flex-direction: column;
        text-align: center;
    }
    
    .manual-input-section .input-group {
        flex-direction: column;
    }
}
</style>

<script>
// Initialize scanner when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    if (typeof AutomatenScanner !== 'undefined') {
        AutomatenScanner.init();
    }
});
</script>