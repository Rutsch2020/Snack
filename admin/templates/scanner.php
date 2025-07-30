<?php
/**
 * Scanner Template - REPARIERT
 * 
 * @package     AutomatenManagerPro
 * @subpackage  Templates
 * @version     1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="amp-scanner-page">
    <div class="amp-page-header">
        <h1>📱 Barcode Scanner</h1>
        <p>Scannen Sie Barcodes für 4 verschiedene Aktionen</p>
    </div>

    <!-- Scanner Container -->
    <div class="amp-scanner-container">
        <div class="scanner-video-container">
            <video id="amp-scanner-video" autoplay muted playsinline></video>
            <div id="amp-scanner-overlay">
                <div class="scan-line"></div>
                <div class="scan-corners">
                    <div class="corner top-left"></div>
                    <div class="corner top-right"></div>
                    <div class="corner bottom-left"></div>
                    <div class="corner bottom-right"></div>
                </div>
            </div>
            <div id="amp-scanner-status" class="scanner-status">
                📷 Kamera wird initialisiert...
            </div>
        </div>

        <!-- Action Buttons -->
        <div id="amp-action-buttons" class="action-buttons" style="display: none;">
            <h3>Produkt gefunden! Wählen Sie eine Aktion:</h3>
            <div class="product-info" id="scanned-product-info">
                <!-- Wird dynamisch gefüllt -->
            </div>
            <div class="button-grid">
                <button type="button" class="amp-action-btn sell-btn" data-action="sell">
                    🛒 Verkaufen
                </button>
                <button type="button" class="amp-action-btn restock-btn" data-action="restock">
                    📦 Lager auffüllen
                </button>
                <button type="button" class="amp-action-btn dispose-btn" data-action="dispose">
                    🗑️ Wegschmeißen
                </button>
                <button type="button" class="amp-action-btn new-btn" data-action="create_new">
                    🆕 Neues Produkt
                </button>
            </div>
            <button type="button" id="amp-scan-again" class="amp-btn-secondary">
                🔄 Erneut scannen
            </button>
        </div>

        <!-- New Product Modal -->
        <div id="amp-new-product-modal" class="amp-modal" style="display: none;">
            <div class="modal-content">
                <h3>🆕 Neues Produkt erstellen</h3>
                <p>Barcode nicht gefunden. Möchten Sie ein neues Produkt erstellen?</p>
                <div class="modal-buttons">
                    <button type="button" id="amp-create-new-product" class="amp-btn-primary">
                        ✅ Ja, erstellen
                    </button>
                    <button type="button" id="amp-cancel-new-product" class="amp-btn-secondary">
                        ❌ Abbrechen
                    </button>
                </div>
            </div>
        </div>

        <!-- Quantity Modal -->
        <div id="amp-quantity-modal" class="amp-modal" style="display: none;">
            <div class="modal-content">
                <h3 id="quantity-modal-title">Menge eingeben</h3>
                <div class="quantity-input">
                    <label for="amp-quantity">Anzahl:</label>
                    <input type="number" id="amp-quantity" min="1" value="1" />
                </div>
                <div id="disposal-reason" style="display: none;">
                    <label for="amp-disposal-reason">Entsorgungsgrund:</label>
                    <select id="amp-disposal-reason">
                        <option value="expired">Abgelaufen</option>
                        <option value="damaged">Beschädigt</option>
                        <option value="contaminated">Kontaminiert</option>
                        <option value="recall">Rückruf</option>
                        <option value="inventory">Inventur-Korrektur</option>
                        <option value="other">Sonstiges</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" id="amp-confirm-action" class="amp-btn-primary">
                        ✅ Bestätigen
                    </button>
                    <button type="button" id="amp-cancel-action" class="amp-btn-secondary">
                        ❌ Abbrechen
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scanner Statistics -->
    <div class="scanner-stats">
        <div class="stat-card">
            <h4>📊 Scan-Statistiken</h4>
            <p>Heute gescannt: <span id="scans-today">0</span></p>
            <p>Erfolgreich: <span id="scans-success">0</span></p>
            <p>Neue Produkte: <span id="scans-new">0</span></p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const Scanner = {
        video: null,
        quagga: null,
        isScanning: false,
        currentProduct: null,
        currentAction: null,
        
        init: function() {
            this.video = document.getElementById('amp-scanner-video');
            this.setupEventListeners();
            this.startCamera();
        },
        
        setupEventListeners: function() {
            // Action buttons
            $('.amp-action-btn').on('click', (e) => {
                this.currentAction = $(e.target).data('action');
                this.handleAction(this.currentAction);
            });
            
            // Modal buttons
            $('#amp-scan-again').on('click', () => this.restartScanning());
            $('#amp-create-new-product').on('click', () => this.createNewProduct());
            $('#amp-cancel-new-product').on('click', () => this.hideModal('#amp-new-product-modal'));
            $('#amp-confirm-action').on('click', () => this.confirmAction());
            $('#amp-cancel-action').on('click', () => this.hideModal('#amp-quantity-modal'));
        },
        
        startCamera: function() {
            if (typeof Quagga === 'undefined') {
                this.showStatus('❌ Scanner-Library nicht geladen', 'error');
                return;
            }
            
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: this.video,
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: [
                        "ean_reader",
                        "ean_8_reader",
                        "code_128_reader",
                        "code_39_reader"
                    ]
                }
            }, (err) => {
                if (err) {
                    this.showStatus('❌ Kamera-Fehler: ' + err.message, 'error');
                    return;
                }
                
                Quagga.start();
                this.isScanning = true;
                this.showStatus('✅ Scanner bereit - Barcode vor die Kamera halten', 'success');
            });
            
            Quagga.onDetected((data) => {
                if (this.isScanning) {
                    this.onBarcodeDetected(data.codeResult.code);
                }
            });
        },
        
        onBarcodeDetected: function(barcode) {
            this.isScanning = false;
            Quagga.stop();
            
            this.showStatus('🔍 Barcode erkannt: ' + barcode, 'info');
            
            // AJAX-Request zum Server
            $.ajax({
                url: ampAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'amp_scan_barcode',
                    barcode: barcode,
                    nonce: ampAjax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.handleScanResult(response.data);
                    } else {
                        this.showStatus('❌ ' + response.data.message, 'error');
                        this.restartScanning();
                    }
                },
                error: () => {
                    this.showStatus('❌ Verbindungsfehler', 'error');
                    this.restartScanning();
                }
            });
        },
        
        handleScanResult: function(data) {
            if (data.found) {
                this.currentProduct = data.product;
                this.showProductActions(data.product);
            } else {
                this.showNewProductModal(data.barcode);
            }
        },
        
        showProductActions: function(product) {
            const productInfo = `
                <div class="product-card">
                    <h4>${product.name}</h4>
                    <p><strong>Barcode:</strong> ${product.barcode}</p>
                    <p><strong>Bestand:</strong> ${product.current_stock} Stück</p>
                    <p><strong>Preis:</strong> ${product.sell_price}€</p>
                </div>
            `;
            
            $('#scanned-product-info').html(productInfo);
            $('#amp-action-buttons').fadeIn();
            $('#amp-scanner-video').hide();
        },
        
        showNewProductModal: function(barcode) {
            this.currentBarcode = barcode;
            this.showModal('#amp-new-product-modal');
        },
        
        handleAction: function(action) {
            if (action === 'create_new') {
                this.createNewProduct();
                return;
            }
            
            // Für andere Aktionen Menge abfragen
            let title = 'Menge eingeben';
            switch(action) {
                case 'sell':
                    title = '🛒 Verkaufsmenge';
                    break;
                case 'restock':
                    title = '📦 Auffüllmenge';
                    break;
                case 'dispose':
                    title = '🗑️ Entsorgen';
                    $('#disposal-reason').show();
                    break;
            }
            
            $('#quantity-modal-title').text(title);
            this.showModal('#amp-quantity-modal');
        },
        
        confirmAction: function() {
            const quantity = parseInt($('#amp-quantity').val()) || 1;
            const reason = $('#amp-disposal-reason').val();
            
            const requestData = {
                action: 'amp_scanner_action',
                action_type: this.currentAction,
                product_id: this.currentProduct.id,
                quantity: quantity,
                nonce: ampAjax.nonce
            };
            
            if (this.currentAction === 'dispose') {
                requestData.reason = reason;
            }
            
            $.ajax({
                url: ampAjax.ajaxurl,
                type: 'POST',
                data: requestData,
                success: (response) => {
                    if (response.success) {
                        this.showStatus('✅ ' + response.data.message, 'success');
                        this.updateStats(this.currentAction);
                    } else {
                        this.showStatus('❌ ' + response.data.message, 'error');
                    }
                    this.hideModal('#amp-quantity-modal');
                    this.restartScanning();
                },
                error: () => {
                    this.showStatus('❌ Aktion fehlgeschlagen', 'error');
                    this.hideModal('#amp-quantity-modal');
                }
            });
        },
        
        createNewProduct: function() {
            const barcode = this.currentBarcode || this.currentProduct?.barcode;
            const url = `<?php echo admin_url('admin.php?page=amp-products&action=new'); ?>&barcode=${encodeURIComponent(barcode)}`;
            window.location.href = url;
        },
        
        restartScanning: function() {
            $('#amp-action-buttons').hide();
            $('#amp-scanner-video').show();
            $('#disposal-reason').hide();
            $('#amp-quantity').val(1);
            this.startCamera();
        },
        
        showModal: function(selector) {
            $(selector).fadeIn();
        },
        
        hideModal: function(selector) {
            $(selector).fadeOut();
        },
        
        showStatus: function(message, type = 'info') {
            const statusEl = $('#amp-scanner-status');
            statusEl.removeClass('success error info warning').addClass(type);
            statusEl.text(message);
        },
        
        updateStats: function(action) {
            // Einfache lokale Statistik-Updates
            const todayEl = $('#scans-today');
            const successEl = $('#scans-success');
            const newEl = $('#scans-new');
            
            todayEl.text(parseInt(todayEl.text()) + 1);
            
            if (action !== 'create_new') {
                successEl.text(parseInt(successEl.text()) + 1);
            } else {
                newEl.text(parseInt(newEl.text()) + 1);
            }
        }
    };
    
    // Scanner initialisieren
    Scanner.init();
});
</script>