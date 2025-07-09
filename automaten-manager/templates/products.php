<?php
/**
 * Products Template
 */

// Sicherheit
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="am-admin-wrapper" id="am-products-page">
    <div class="am-container">
        <div class="am-header">
            <div>
                <h1 class="am-title">Produkte</h1>
                <p class="am-subtitle">Verwalte deine Automaten-Produkte</p>
            </div>
            <div class="am-header-actions">
                <button class="am-btn am-btn-secondary" id="am-open-scanner">
                    <i class="fas fa-barcode"></i>
                    Scanner
                </button>
                <button class="am-btn am-btn-primary" id="am-add-product">
                    <i class="fas fa-plus"></i>
                    Neues Produkt
                </button>
            </div>
        </div>
        
        <div class="am-card am-filter-bar">
            <div class="am-filter-row">
                <div class="am-search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="am-search-products" 
                           class="am-input am-search-input" 
                           placeholder="Suche nach Name oder Barcode...">
                </div>
                <div class="am-filter-actions">
                    <select class="am-select" id="am-filter-category">
                        <option value="">Alle Kategorien</option>
                    </select>
                    <select class="am-select" id="am-sort-products">
                        <option value="name">Name</option>
                        <option value="price">Preis</option>
                        <option value="stock">Lagerbestand</option>
                        <option value="date">Datum</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="am-grid am-grid-3" id="am-products-container">
            </div>
    </div>
    
    <div id="am-product-modal" class="am-modal">
        <div class="am-modal-content">
            <div class="am-modal-header">
                <h2>Produkt bearbeiten</h2>
                <button class="am-modal-close" type="button">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form id="am-product-form" class="am-form">
                <input type="hidden" id="am-product-id" name="id">
                
                <div class="am-form-group">
                    <label class="am-label">Barcode *</label>
                    <div class="am-input-group">
                        <input type="text" 
                               id="am-product-barcode" 
                               name="barcode" 
                               class="am-input" 
                               placeholder="Barcode eingeben oder scannen" 
                               required>
                        <button type="button" class="am-btn am-btn-secondary am-btn-icon" id="am-scan-barcode-btn">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
                                <rect x="7" y="7" width="10" height="10"/>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <div class="am-form-group">
                    <label class="am-label">Produktname *</label>
                    <input type="text" 
                           id="am-product-name" 
                           name="name" 
                           class="am-input" 
                           placeholder="z.B. Coca Cola 0.5L" 
                           required>
                </div>
                
                <div class="am-grid am-grid-2">
                    <div class="am-form-group">
                        <label class="am-label">Preis (€) *</label>
                        <input type="number" 
                               id="am-product-price" 
                               name="price" 
                               class="am-input" 
                               step="0.01" 
                               min="0" 
                               placeholder="1.50" 
                               required>
                    </div>
                    
                    <div class="am-form-group">
                        <label class="am-label">Lagerbestand *</label>
                        <input type="number" 
                               id="am-product-stock" 
                               name="stock" 
                               class="am-input" 
                               min="0" 
                               placeholder="50" 
                               required>
                    </div>
                </div>
                
                <div class="am-form-group">
                    <label class="am-label">Kategorie</label>
                    <select id="am-product-category" 
                            name="category_id" 
                            class="am-select">
                        <option value="">Keine Kategorie</option>
                    </select>
                </div>
                
                <div class="am-form-group">
                    <label class="am-label">Produktbild URL (Optional)</label>
                    <input type="url" 
                           id="am-product-image" 
                           name="image_url" 
                           class="am-input" 
                           placeholder="https://beispiel.de/bild.jpg">
                    <div class="am-image-preview" id="am-image-preview">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span>Klicke hier für Bildvorschau</span>
                    </div>
                </div>
            </form>
            
            <div class="am-modal-footer">
                <button type="button" class="am-btn am-btn-secondary" id="am-cancel-product">
                    <i class="fas fa-times"></i>
                    Abbrechen
                </button>
                <button type="button" id="am-save-product" class="am-btn am-btn-primary">
                    <i class="fas fa-save"></i>
                    <span>Speichern</span>
                </button>
            </div>
        </div>
    </div>
    
    <div class="am-fab-container">
        <button class="am-fab am-btn-primary" id="am-fab-add">
            <i class="fas fa-plus"></i>
        </button>
        <button class="am-fab am-btn-secondary" id="am-fab-scanner">
            <i class="fas fa-barcode"></i>
        </button>
    </div>
</div>

<style>
/* Products Page Specific Styles */
.am-filter-bar {
    margin-bottom: 2rem;
}

.am-filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.am-search-box {
    flex: 1;
    position: relative;
    min-width: 250px;
}

.am-search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-400);
}

.am-search-input {
    padding-left: 3rem;
}

.am-filter-actions {
    display: flex;
    gap: 1rem;
}

.am-input-group {
    display: flex;
    gap: 0.5rem;
}

.am-input-group .am-input {
    flex: 1;
}

.am-btn-icon {
    padding: 0.75rem 1rem;
}

.am-product-card {
    cursor: pointer;
    height: 100%;
}

.am-product-image-placeholder {
    width: 100%;
    height: 200px;
    background: var(--gray-100);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    color: var(--gray-400);
}

.am-product-name {
    font-size: 1.125rem;
    font-weight: 600;
    margin: 0.5rem 0;
    color: var(--gray-800);
}

.am-product-barcode {
    font-size: 0.875rem;
    color: var(--gray-500);
    font-family: monospace;
    margin: 0.5rem 0;
}

.am-product-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 1rem 0;
}

.am-product-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
}

.am-product-stock {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: var(--gray-100);
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 500;
}

.am-product-stock.am-low-stock {
    background: var(--danger);
    color: white;
}

.am-product-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.am-btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.am-empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray-400);
}

.am-empty-state h3 {
    margin: 1rem 0 0.5rem 0;
    color: var(--gray-600);
}

.am-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.am-modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 700;
}

.am-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--gray-200);
}

.am-form {
    max-width: none;
}

/* Scanner Button Styles */
#am-open-scanner {
    background: linear-gradient(135deg, #00ff88, #00cc6a);
    color: white;
    border: none;
}

#am-open-scanner:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0, 255, 136, 0.4);
}

/* FAB Container for multiple FABs */
.am-fab-container {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    display: flex;
    flex-direction: column-reverse;
    gap: 1rem;
    z-index: 100;
}

.am-fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    cursor: pointer;
    transition: all 0.3s ease;
}

.am-fab:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.am-fab.am-btn-primary {
    background: var(--primary);
    color: white;
}

.am-fab.am-btn-secondary {
    background: linear-gradient(135deg, #00ff88, #00cc6a);
    color: white;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
    .am-filter-row {
        flex-direction: column;
    }
    
    .am-search-box {
        width: 100%;
        min-width: auto;
    }
    
    .am-filter-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .am-filter-actions .am-select {
        flex: 1;
    }
    
    .am-product-actions {
        flex-direction: column;
    }
    
    .am-product-actions .am-btn {
        width: 100%;
    }
    
    .am-fab-container {
        display: flex;
    }
    
    .am-header-actions {
        display: none;
    }
}

@media (min-width: 769px) {
    .am-fab-container {
        display: none;
    }
}
</style>

<script>
// Enhanced JavaScript with Scanner Support
jQuery(document).ready(function($) {
    // FAB Click Handlers
    $('#am-fab-add').on('click', function() {
        $('#am-add-product').click();
    });
    
    $('#am-fab-scanner').on('click', function() {
        $('#am-open-scanner').click();
    });
    
    // Cancel Button for product edit modal
    $('#am-cancel-product').on('click', function() {
        $('#am-product-modal').fadeOut(300);
    });
    
    // Image Preview for product edit modal
    $('#am-product-image').on('input', function() {
        const url = $(this).val();
        const preview = $('#am-image-preview');
        
        if (url) {
            preview.html(`<img src="${url}" style="max-width: 100%; max-height: 200px; border-radius: 8px;" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'fas fa-exclamation-triangle\\'></i><span>Bild konnte nicht geladen werden</span>'; this.parentElement.style.color='var(--danger)';">`);
        } else {
            preview.html('<i class="fas fa-cloud-upload-alt"></i><span>Klicke hier für Bildvorschau</span>');
        }
    });
    
    // Scan Barcode Button in Product Edit Modal
    $('#am-scan-barcode-btn').on('click', function() {
        // Temporarily close product edit modal
        $('#am-product-modal').fadeOut(300);
        
        // Open scanner with callback
        if (typeof initScanner === 'function') {
            // Store original barcode detection handler from scanner.js
            var originalHandleBarcodeDetected = window.handleBarcodeDetected;
            
            // Override barcode detection specifically for this modal
            window.handleBarcodeDetected = function(barcode, isManual) {
                // Fill barcode field in the product edit modal
                $('#am-product-barcode').val(barcode);
                
                // Close the scanner
                $('.am-scanner-wrapper').removeClass('active');
                if (typeof Quagga !== 'undefined') {
                    Quagga.stop();
                }
                
                // Reopen the product edit modal
                $('#am-product-modal').fadeIn(300);
                
                // Restore the original barcode detection handler
                window.handleBarcodeDetected = originalHandleBarcodeDetected;
                
                // Optionally, trigger a product check for the newly scanned barcode
                // This would involve loading product data into the edit form if it exists
                // (Currently, this logic is not here, but it's where it would go if desired)
            };
            
            // Initialize the scanner
            initScanner();
        } else {
            showNotification('Scanner ist nicht verfügbar. Bitte stelle sicher, dass alle Scanner-Dateien geladen sind.', 'error'); // Use global notification
            $('#am-product-modal').fadeIn(300); // Reopen modal if scanner fails to initialize
        }
    });
    
    // Check for barcode parameter in URL (e.g., if redirected from scanner page after "new product")
    const urlParams = new URLSearchParams(window.location.search);
    const barcodeParam = urlParams.get('barcode');
    if (barcodeParam && urlParams.get('action') === 'new') {
        // Open new product modal with barcode pre-filled
        setTimeout(function() {
            // Check if initProductModal is available (from product-create-modal.js)
            if (typeof window.initProductModal === 'function') {
                window.initProductModal(barcodeParam);
            } else {
                // Fallback to old behavior if initProductModal is not available
                $('#am-add-product').click();
                $('#am-product-barcode').val(barcodeParam);
            }
        }, 500);
    }

    // Function to load products - placeholder, assuming it's in admin-script.js or defined elsewhere
    // If not, you'll need to define it or integrate product loading logic here.
    if (typeof loadProducts === 'undefined') {
        window.loadProducts = function() {
            console.warn('loadProducts function is not defined. Products list will not refresh automatically.');
            // Implement AJAX call to load products and render them in #am-products-container
            // This would typically be in admin-script.js
        };
    }
});
</script>