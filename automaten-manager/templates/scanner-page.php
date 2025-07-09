<?php
/**
 * Scanner Page Template
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <div class="am-admin-wrapper">
        <div class="am-container">
            <div class="am-header">
                <div>
                    <h1 class="am-title">
                        <i class="fas fa-qrcode"></i> Barcode Scanner
                    </h1>
                    <p class="am-subtitle">Scanne Barcodes um Produkte zu verwalten</p>
                </div>
                <div class="am-header-actions">
                    <a href="<?php echo admin_url('admin.php?page=am-products'); ?>" class="am-btn am-btn-secondary">
                        <i class="fas fa-list"></i>
                        Zur Produktliste
                    </a>
                </div>
            </div>
            
            <div class="am-grid am-grid-2">
                <div class="am-card">
                    <div class="am-card-header">
                        <h2><i class="fas fa-camera"></i> Scanner starten</h2>
                    </div>
                    <div class="am-card-body">
                        <p>Nutze den Barcode Scanner um Produkte schnell zu verwalten:</p>
                        <ul class="am-feature-list">
                            <li><i class="fas fa-check" style="color: #00ff88;"></i> Produkte verkaufen</li>
                            <li><i class="fas fa-check" style="color: #00ff88;"></i> Lagerbestand auffüllen</li>
                            <li><i class="fas fa-check" style="color: #00ff88;"></i> Neue Produkte anlegen</li>
                        </ul>
                        <button id="am-open-scanner" class="am-btn am-btn-primary am-btn-large">
                            <i class="fas fa-barcode"></i> Scanner öffnen
                        </button>
                    </div>
                </div>
                
                <div class="am-card">
                    <div class="am-card-header">
                        <h2><i class="fas fa-info-circle"></i> Hinweise</h2>
                    </div>
                    <div class="am-card-body">
                        <p><strong>Unterstützte Barcode-Formate:</strong></p>
                        <p>EAN-13, EAN-8, Code 128, Code 39, UPC-A, UPC-E</p>
                        
                        <p style="margin-top: 15px;"><strong>Tipps für optimales Scannen:</strong></p>
                        <ul class="am-tips-list">
                            <li>Gute Beleuchtung verwenden</li>
                            <li>Barcode mittig im Scanbereich positionieren</li>
                            <li>Ruhig halten und auf Autofokus warten</li>
                            <li>Bei Problemen: Barcode manuell eingeben</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="am-card" style="margin-top: 2rem;">
                <div class="am-card-header">
                    <h2><i class="fas fa-mobile-alt"></i> Kamera-Anforderungen</h2>
                </div>
                <div class="am-card-body">
                    <div class="am-grid am-grid-3">
                        <div class="am-requirement-item">
                            <div class="am-requirement-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h4>HTTPS erforderlich</h4>
                            <p>Für Kamerazugriff ist eine sichere Verbindung notwendig</p>
                        </div>
                        <div class="am-requirement-item">
                            <div class="am-requirement-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Berechtigung erteilen</h4>
                            <p>Kamerazugriff in den Browsereinstellungen erlauben</p>
                        </div>
                        <div class="am-requirement-item">
                            <div class="am-requirement-icon">
                                <i class="fas fa-mobile"></i>
                            </div>
                            <h4>Mobile optimiert</h4>
                            <p>Funktioniert optimal auf Smartphones und Tablets</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.am-card-header {
    border-bottom: 1px solid var(--gray-200);
    padding-bottom: 1rem;
    margin-bottom: 1.5rem;
}

.am-card-header h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.am-card-body {
    color: var(--gray-700);
    line-height: 1.6;
}

.am-feature-list,
.am-tips-list {
    list-style: none;
    padding: 0;
    margin: 1rem 0;
}

.am-feature-list li,
.am-tips-list li {
    padding: 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.am-btn-large {
    padding: 1rem 2rem;
    font-size: 1.125rem;
    margin-top: 1.5rem;
    width: 100%;
    justify-content: center;
}

.am-requirement-item {
    text-align: center;
    padding: 1.5rem;
}

.am-requirement-icon {
    width: 60px;
    height: 60px;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
}

.am-requirement-item h4 {
    margin: 0 0 0.5rem 0;
    color: var(--gray-800);
}

.am-requirement-item p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--gray-600);
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .am-grid-2,
    .am-grid-3 {
        grid-template-columns: 1fr;
    }
    
    .am-requirement-item {
        padding: 1rem;
    }
    
    .am-requirement-icon {
        width: 50px;
        height: 50px;
        font-size: 1.25rem;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Scanner öffnen
    $('#am-open-scanner').on('click', function(e) {
        e.preventDefault();
        
        if (typeof initScanner === 'function') {
            initScanner();
        } else {
            console.error('Scanner nicht verfügbar');
            alert('Scanner konnte nicht gestartet werden. Bitte lade die Seite neu.');
        }
    });
});
</script>