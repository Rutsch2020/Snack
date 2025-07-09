<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="am-scanner-wrapper">
    <div id="am-scanner-container" class="am-scanner-container">
        <div class="am-scanner-header">
            <button class="am-scanner-close" id="am-scanner-close" type="button" aria-label="Scanner schließen">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
            <h2 class="am-scanner-title">Barcode Scanner</h2>
            <div class="am-scanner-settings">
                <button class="am-settings-btn" id="am-toggle-flash" type="button" style="display: none;" aria-label="Taschenlampe">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="am-camera-container">
            <video id="am-scanner-video" class="am-scanner-video" playsinline autoplay muted></video>
            <canvas id="am-scanner-canvas" class="am-scanner-canvas"></canvas>
            
            <div class="am-scanner-overlay">
                <div class="am-scan-region">
                    <div class="am-scan-line"></div>
                    <div class="am-corner am-corner-tl"></div>
                    <div class="am-corner am-corner-tr"></div>
                    <div class="am-corner am-corner-bl"></div>
                    <div class="am-corner am-corner-br"></div>
                </div>
                <div class="am-scan-hint">Richte den Barcode in den Scanbereich</div>
            </div>

            <div class="am-scanner-status" id="am-scanner-status"></div>
        </div>

        <div class="am-manual-input">
            <input type="text" 
                   id="am-manual-barcode" 
                   placeholder="Barcode manuell eingeben..." 
                   class="am-input-field"
                   autocomplete="off"
                   inputmode="numeric">
            <button class="am-manual-submit" id="am-manual-submit" type="button" aria-label="Barcode absenden">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M5 12h14M12 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="am-scanner-modal" id="am-scanner-modal">
        <div class="am-modal-content">
            <div class="am-modal-header">
                <h3 class="am-modal-title">Barcode erkannt!</h3>
                <button class="am-modal-close" id="am-modal-close" type="button" aria-label="Modal schließen">×</button>
            </div>
            <div class="am-modal-body">
                <div class="am-barcode-result" id="am-barcode-result"></div>
                
                <div class="am-product-found" id="am-product-found" style="display: none;">
                    <div class="am-product-info">
                        <img class="am-product-image" id="am-product-image" src="" alt="Produktbild">
                        <div class="am-product-details">
                            <h4 id="am-product-name"></h4>
                            <p class="am-product-price">Preis: <span id="am-product-price"></span></p>
                            <p class="am-product-stock">Bestand: <span id="am-product-stock"></span></p>
                        </div>
                    </div>
                    <div class="am-product-actions" id="am-product-actions">
                        <button class="am-btn am-btn-sell" id="am-btn-sell" type="button">
                            <i class="fas fa-shopping-cart"></i>
                            Verkaufen
                        </button>
                        <button class="am-btn am-btn-restock" id="am-btn-restock" type="button">
                            <i class="fas fa-box-open"></i>
                            Auffüllen
                        </button>
                    </div>
                </div>

                <div class="am-product-not-found" id="am-product-not-found" style="display: none;">
                    <p>Produkt nicht gefunden!</p>
                    <button class="am-btn am-btn-create" id="am-btn-create" type="button">
                        <i class="fas fa-plus-circle"></i>
                        Neues Produkt anlegen
                    </button>
                </div>

                <div class="am-quantity-selector" id="am-quantity-selector" style="display: none;">
                    <label for="am-quantity">Menge:</label>
                    <div class="am-quantity-controls">
                        <button class="am-qty-btn" id="am-qty-minus" type="button" aria-label="Menge verringern">-</button>
                        <input type="number" 
                               id="am-quantity" 
                               value="1" 
                               min="1" 
                               class="am-qty-input"
                               inputmode="numeric">
                        <button class="am-qty-btn" id="am-qty-plus" type="button" aria-label="Menge erhöhen">+</button>
                    </div>
                    <button class="am-btn am-btn-confirm" id="am-confirm-action" type="button">Bestätigen</button>
                </div>
            </div>
        </div>
    </div>
</div>

<audio id="am-scan-sound" preload="auto">
    <source src="<?php echo AUTOMATEN_MANAGER_URL . 'assets/sounds/scan.mp3'; ?>" type="audio/mpeg">
    <source src="<?php echo AUTOMATEN_MANAGER_URL . 'assets/sounds/scan.wav'; ?>" type="audio/wav">
</audio>
<audio id="am-success-sound" preload="auto">
    <source src="<?php echo AUTOMATEN_MANAGER_URL . 'assets/sounds/success.mp3'; ?>" type="audio/mpeg">
    <source src="<?php echo AUTOMATEN_MANAGER_URL . 'assets/sounds/success.wav'; ?>" type="audio/wav">
</audio>