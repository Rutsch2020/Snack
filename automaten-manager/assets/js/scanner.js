/**
 * Automaten Manager - Advanced Scanner Module
 * ZXing Integration + Modern Web APIs
 */

class AutomatenScanner {
    constructor() {
        this.codeReader = null;
        this.selectedDeviceId = null;
        this.videoElement = null;
        this.isScanning = false;
        this.lastScanTime = 0;
        this.scanCooldown = 2000; // 2 seconds between scans
        this.scanResults = [];
        this.videoConstraints = {
            audio: false,
            video: {
                facingMode: 'environment', // Use back camera
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        
        this.bindEvents();
        this.loadZXing();
        this.updateStats();
    }

    /**
     * Load ZXing library dynamically
     */
    async loadZXing() {
        try {
            // Load ZXing from CDN
            if (!window.ZXing) {
                await this.loadScript('https://cdnjs.cloudflare.com/ajax/libs/zxing-library/0.20.0/umd/index.min.js');
            }
            
            this.codeReader = new ZXing.BrowserMultiFormatReader();
            this.showNotification('Scanner bereit! 📷', 'success');
            console.log('✅ ZXing Scanner initialisiert');
            
            // Get available cameras
            await this.getCameras();
            
        } catch (error) {
            console.error('❌ Fehler beim Laden von ZXing:', error);
            this.showNotification('Scanner konnte nicht geladen werden', 'error');
        }
    }

    /**
     * Load external script
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Get available cameras
     */
    async getCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            if (videoDevices.length > 1) {
                const switchBtn = document.getElementById('switch-camera');
                if (switchBtn) {
                    switchBtn.style.display = 'inline-flex';
                }
            }
            
            // Prefer back camera
            const backCamera = videoDevices.find(device => 
                device.label.toLowerCase().includes('back') || 
                device.label.toLowerCase().includes('rear') ||
                device.label.toLowerCase().includes('environment')
            );
            
            this.selectedDeviceId = backCamera ? backCamera.deviceId : videoDevices[0]?.deviceId;
            
        } catch (error) {
            console.error('❌ Fehler beim Abrufen der Kameras:', error);
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Camera controls
        const startBtn = document.getElementById('start-camera');
        const stopBtn = document.getElementById('stop-camera');
        const switchBtn = document.getElementById('switch-camera');
        
        if (startBtn) startBtn.addEventListener('click', () => this.startCamera());
        if (stopBtn) stopBtn.addEventListener('click', () => this.stopCamera());
        if (switchBtn) switchBtn.addEventListener('click', () => this.switchCamera());

        // Manual input
        const manualInput = document.getElementById('manual-barcode');
        const scanManualBtn = document.getElementById('scan-manual');
        
        if (manualInput) {
            manualInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.scanManualBarcode();
                }
            });
        }
        
        if (scanManualBtn) {
            scanManualBtn.addEventListener('click', () => this.scanManualBarcode());
        }

        // Quick add form
        const quickAddForm = document.getElementById('quick-add-form');
        if (quickAddForm) {
            quickAddForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleQuickAdd();
            });
        }

        // Results filter
        const resultsFilter = document.getElementById('results-filter');
        if (resultsFilter) {
            resultsFilter.addEventListener('change', (e) => {
                this.filterResults(e.target.value);
            });
        }

        // Clear results
        const clearResultsBtn = document.getElementById('clear-results');
        if (clearResultsBtn) {
            clearResultsBtn.addEventListener('click', () => this.clearResults());
        }

        // Modal controls
        const modal = document.getElementById('scan-result-modal');
        const closeModal = document.getElementById('scan-modal-close');
        const modalOverlay = document.querySelector('.modal-overlay');
        
        if (closeModal) {
            closeModal.addEventListener('click', () => this.closeModal());
        }
        
        if (modalOverlay) {
            modalOverlay.addEventListener('click', (e) => {
                if (e.target === modalOverlay) {
                    this.closeModal();
                }
            });
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.handleQuickAdd();
            }
        });

        // Video element reference
        this.videoElement = document.getElementById('scanner-video');
    }

    /**
     * Start camera scanning
     */
    async startCamera() {
        if (!this.codeReader) {
            this.showNotification('Scanner noch nicht bereit', 'warning');
            return;
        }

        try {
            this.showLoading(true);
            
            // Update UI
            this.updateCameraControls(true);
            
            // Start scanning
            await this.codeReader.decodeFromVideoDevice(
                this.selectedDeviceId, 
                this.videoElement, 
                (result, error) => {
                    if (result) {
                        this.handleScanResult(result.text);
                    }
                    if (error && error.name !== 'NotFoundException') {
                        console.log('Scanner error:', error);
                    }
                }
            );
            
            this.isScanning = true;
            this.showLoading(false);
            this.showNotification('Kamera gestartet 📸', 'success');
            
        } catch (error) {
            console.error('❌ Fehler beim Starten der Kamera:', error);
            this.showNotification('Kamera konnte nicht gestartet werden', 'error');
            this.showLoading(false);
            this.updateCameraControls(false);
        }
    }

    /**
     * Stop camera scanning
     */
    stopCamera() {
        try {
            if (this.codeReader) {
                this.codeReader.reset();
            }
            
            this.isScanning = false;
            this.updateCameraControls(false);
            this.showNotification('Kamera gestoppt ⏹️', 'info');
            
        } catch (error) {
            console.error('❌ Fehler beim Stoppen der Kamera:', error);
        }
    }

    /**
     * Switch camera (front/back)
     */
    async switchCamera() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            
            if (videoDevices.length <= 1) return;
            
            const currentIndex = videoDevices.findIndex(device => device.deviceId === this.selectedDeviceId);
            const nextIndex = (currentIndex + 1) % videoDevices.length;
            this.selectedDeviceId = videoDevices[nextIndex].deviceId;
            
            if (this.isScanning) {
                this.stopCamera();
                setTimeout(() => this.startCamera(), 500);
            }
            
            this.showNotification('Kamera gewechselt 🔄', 'info');
            
        } catch (error) {
            console.error('❌ Fehler beim Wechseln der Kamera:', error);
        }
    }

    /**
     * Update camera control buttons
     */
    updateCameraControls(scanning) {
        const startBtn = document.getElementById('start-camera');
        const stopBtn = document.getElementById('stop-camera');
        const switchBtn = document.getElementById('switch-camera');
        
        if (startBtn) startBtn.style.display = scanning ? 'none' : 'inline-flex';
        if (stopBtn) stopBtn.style.display = scanning ? 'inline-flex' : 'none';
        if (switchBtn) switchBtn.style.display = scanning ? 'inline-flex' : 'none';
    }

    /**
     * Handle scan result
     */
    async handleScanResult(barcode) {
        const now = Date.now();
        
        // Prevent rapid successive scans
        if (now - this.lastScanTime < this.scanCooldown) {
            return;
        }
        
        this.lastScanTime = now;
        
        try {
            this.showLoading(true);
            
            // Check if product exists
            const existingProduct = await this.checkProduct(barcode);
            
            if (existingProduct) {
                this.showScanResult(existingProduct, 'existing');
                this.addScanResult(barcode, 'success', `Produkt gefunden: ${existingProduct.name}`);
            } else {
                // Try to fetch product data from external API
                const productData = await this.fetchProductData(barcode);
                
                if (productData) {
                    this.showScanResult(productData, 'new');
                    this.addScanResult(barcode, 'new', `Neues Produkt: ${productData.name}`);
                } else {
                    this.showUnknownProductModal(barcode);
                    this.addScanResult(barcode, 'warning', 'Unbekanntes Produkt - Manuell hinzufügen');
                }
            }
            
            this.updateStats();
            
        } catch (error) {
            console.error('❌ Fehler beim Verarbeiten des Scans:', error);
            this.showNotification('Fehler beim Verarbeiten des Barcodes', 'error');
            this.addScanResult(barcode, 'error', 'Verarbeitungsfehler');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Scan manual barcode input
     */
    async scanManualBarcode() {
        const input = document.getElementById('manual-barcode');
        const barcode = input?.value?.trim();
        
        if (!barcode) {
            this.showNotification('Bitte Barcode eingeben', 'warning');
            return;
        }
        
        await this.handleScanResult(barcode);
        input.value = '';
    }

    /**
     * Check if product exists in database
     */
    async checkProduct(barcode) {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_check_product',
                    nonce: automatenNonce,
                    barcode: barcode
                })
            });
            
            const data = await response.json();
            return data.success ? data.data : null;
            
        } catch (error) {
            console.error('❌ Fehler beim Prüfen des Produkts:', error);
            return null;
        }
    }

    /**
     * Fetch product data from external API (OpenFoodFacts, etc.)
     */
    async fetchProductData(barcode) {
        try {
            // Try OpenFoodFacts API first
            const response = await fetch(`https://world.openfoodfacts.org/api/v0/product/${barcode}.json`);
            const data = await response.json();
            
            if (data.status === 1 && data.product) {
                const product = data.product;
                return {
                    name: product.product_name || product.product_name_de || 'Unbekanntes Produkt',
                    barcode: barcode,
                    description: product.ingredients_text || '',
                    image_url: product.image_url || '',
                    brand: product.brands || '',
                    categories: product.categories || '',
                    external_data: {
                        source: 'OpenFoodFacts',
                        nutrition_grade: product.nutrition_grade_fr,
                        ecoscore_grade: product.ecoscore_grade
                    }
                };
            }
            
            // Try alternative APIs here if needed
            return null;
            
        } catch (error) {
            console.error('❌ Fehler beim Abrufen der Produktdaten:', error);
            return null;
        }
    }

    /**
     * Show scan result modal
     */
    showScanResult(product, type) {
        const modal = document.getElementById('scan-result-modal');
        const title = document.getElementById('scan-modal-title');
        const content = document.getElementById('scan-modal-content');
        const editBtn = document.getElementById('scan-modal-edit');
        
        if (!modal || !title || !content) return;
        
        // Set modal title
        title.textContent = type === 'existing' ? 'Produkt gefunden!' : 'Neues Produkt gefunden!';
        
        // Create product display
        const productHtml = `
            <div class="scan-result-product">
                ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}" class="product-image">` : ''}
                <div class="product-details">
                    <h4>${product.name}</h4>
                    <p><strong>Barcode:</strong> ${product.barcode}</p>
                    ${product.brand ? `<p><strong>Marke:</strong> ${product.brand}</p>` : ''}
                    ${product.price ? `<p><strong>Preis:</strong> €${product.price}</p>` : ''}
                    ${product.stock_quantity !== undefined ? `<p><strong>Bestand:</strong> ${product.stock_quantity}</p>` : ''}
                    ${product.description ? `<p><strong>Beschreibung:</strong> ${product.description}</p>` : ''}
                    ${product.external_data ? `
                        <div class="external-data">
                            <small>Quelle: ${product.external_data.source}</small>
                            ${product.external_data.nutrition_grade ? `<span class="grade">Nutri-Score: ${product.external_data.nutrition_grade.toUpperCase()}</span>` : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
            
            ${type === 'new' ? `
                <div class="new-product-actions">
                    <button class="btn btn-primary" onclick="AutomatenScanner.addNewProduct(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                        <span class="btn-icon">💾</span>
                        Produkt hinzufügen
                    </button>
                </div>
            ` : ''}
        `;
        
        content.innerHTML = productHtml;
        
        // Show edit button for existing products
        if (editBtn) {
            editBtn.style.display = type === 'existing' ? 'inline-flex' : 'none';
            editBtn.onclick = () => this.editProduct(product.id);
        }
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Show unknown product modal
     */
    showUnknownProductModal(barcode) {
        const modal = document.getElementById('scan-result-modal');
        const title = document.getElementById('scan-modal-title');
        const content = document.getElementById('scan-modal-content');
        
        if (!modal || !title || !content) return;
        
        title.textContent = 'Unbekanntes Produkt';
        
        content.innerHTML = `
            <div class="unknown-product">
                <div class="unknown-icon">❓</div>
                <h4>Produkt nicht gefunden</h4>
                <p><strong>Barcode:</strong> ${barcode}</p>
                <p>Dieses Produkt ist nicht in unserer Datenbank und konnte auch nicht über externe Quellen gefunden werden.</p>
                
                <div class="unknown-actions">
                    <button class="btn btn-primary" onclick="AutomatenScanner.fillQuickAdd('${barcode}')">
                        <span class="btn-icon">📝</span>
                        Manuell hinzufügen
                    </button>
                    <button class="btn btn-outline" onclick="AutomatenScanner.closeModal()">
                        <span class="btn-icon">❌</span>
                        Abbrechen
                    </button>
                </div>
            </div>
        `;
        
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    /**
     * Add new product to database
     */
    async addNewProduct(productData) {
        try {
            this.showLoading(true);
            
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_add_product',
                    nonce: automatenNonce,
                    product_data: JSON.stringify(productData)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Produkt erfolgreich hinzugefügt! ✅', 'success');
                this.closeModal();
                this.updateStats();
                this.addScanResult(productData.barcode, 'success', `Neues Produkt hinzugefügt: ${productData.name}`);
            } else {
                throw new Error(data.data || 'Unbekannter Fehler');
            }
            
        } catch (error) {
            console.error('❌ Fehler beim Hinzufügen des Produkts:', error);
            this.showNotification('Fehler beim Hinzufügen des Produkts', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Fill quick add form with barcode
     */
    fillQuickAdd(barcode) {
        const barcodeInput = document.getElementById('quick-barcode');
        if (barcodeInput) {
            barcodeInput.value = barcode;
            barcodeInput.focus();
        }
        this.closeModal();
        
        // Scroll to quick add section
        const quickAddSection = document.querySelector('.quick-add-section');
        if (quickAddSection) {
            quickAddSection.scrollIntoView({ behavior: 'smooth' });
        }
    }

    /**
     * Handle quick add form submission
     */
    async handleQuickAdd() {
        const form = document.getElementById('quick-add-form');
        if (!form) return;
        
        const formData = new FormData(form);
        const productData = {};
        
        for (let [key, value] of formData.entries()) {
            if (value.trim()) {
                productData[key] = value.trim();
            }
        }
        
        // Validate required fields
        if (!productData.name || !productData.category_id) {
            this.showNotification('Name und Kategorie sind erforderlich', 'warning');
            return;
        }
        
        try {
            this.showLoading(true);
            
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_add_product',
                    nonce: automatenNonce,
                    product_data: JSON.stringify(productData)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Produkt erfolgreich hinzugefügt! ✅', 'success');
                form.reset();
                this.updateStats();
                
                if (productData.barcode) {
                    this.addScanResult(productData.barcode, 'success', `Manuell hinzugefügt: ${productData.name}`);
                }
            } else {
                throw new Error(data.data || 'Unbekannter Fehler');
            }
            
        } catch (error) {
            console.error('❌ Fehler beim Hinzufügen des Produkts:', error);
            this.showNotification('Fehler beim Hinzufügen des Produkts', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Add scan result to history
     */
    addScanResult(barcode, type, message) {
        const result = {
            id: Date.now(),
            barcode: barcode,
            type: type,
            message: message,
            timestamp: new Date().toLocaleString('de-DE')
        };
        
        this.scanResults.unshift(result);
        
        // Keep only last 50 results
        if (this.scanResults.length > 50) {
            this.scanResults = this.scanResults.slice(0, 50);
        }
        
        this.renderResults();
        this.logScan(barcode, type, message);
    }

    /**
     * Log scan to database
     */
    async logScan(barcode, type, message) {
        try {
            await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_log_scan',
                    nonce: automatenNonce,
                    barcode: barcode,
                    scan_type: type,
                    result_message: message
                })
            });
        } catch (error) {
            console.error('❌ Fehler beim Loggen des Scans:', error);
        }
    }

    /**
     * Render scan results
     */
    renderResults() {
        const container = document.getElementById('scan-results');
        if (!container) return;
        
        const filter = document.getElementById('results-filter')?.value || 'all';
        const filteredResults = filter === 'all' ? 
            this.scanResults : 
            this.scanResults.filter(result => result.type === filter);
        
        if (filteredResults.length === 0) {
            container.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">📭</div>
                    <p>Keine Scan-Ergebnisse vorhanden</p>
                </div>
            `;
            return;
        }
        
        const resultsHtml = filteredResults.map(result => {
            const icon = this.getResultIcon(result.type);
            return `
                <div class="result-item ${result.type}" data-id="${result.id}">
                    <div class="result-icon">${icon}</div>
                    <div class="result-content">
                        <div class="result-title">${result.message}</div>
                        <div class="result-details">Barcode: ${result.barcode}</div>
                        <div class="result-time">${result.timestamp}</div>
                    </div>
                </div>
            `;
        }).join('');
        
        container.innerHTML = resultsHtml;
    }

    /**
     * Get icon for result type
     */
    getResultIcon(type) {
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            new: '🆕',
            existing: '📦'
        };
        return icons[type] || '📄';
    }

    /**
     * Filter results
     */
    filterResults(filter) {
        this.renderResults();
    }

    /**
     * Clear all results
     */
    clearResults() {
        this.scanResults = [];
        this.renderResults();
        this.showNotification('Ergebnisse gelöscht', 'info');
    }

    /**
     * Update statistics
     */
    async updateStats() {
        try {
            const response = await fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'automaten_get_stats',
                    nonce: automatenNonce
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                const stats = data.data;
                
                const scansToday = document.getElementById('scans-today');
                const totalProducts = document.getElementById('total-products');
                
                if (scansToday) this.animateCounter(scansToday, stats.scans_today || 0);
                if (totalProducts) this.animateCounter(totalProducts, stats.total_products || 0);
            }
            
        } catch (error) {
            console.error('❌ Fehler beim Abrufen der Statistiken:', error);
        }
    }

    /**
     * Animate counter
     */
    animateCounter(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const increment = Math.ceil(Math.abs(targetValue - currentValue) / 20);
        
        if (currentValue < targetValue) {
            element.textContent = Math.min(currentValue + increment, targetValue);
            if (parseInt(element.textContent) < targetValue) {
                setTimeout(() => this.animateCounter(element, targetValue), 50);
            }
        } else if (currentValue > targetValue) {
            element.textContent = Math.max(currentValue - increment, targetValue);
            if (parseInt(element.textContent) > targetValue) {
                setTimeout(() => this.animateCounter(element, targetValue), 50);
            }
        }
    }

    /**
     * Show loading overlay
     */
    showLoading(show) {
        const loading = document.getElementById('scanner-loading');
        if (loading) {
            loading.style.display = show ? 'flex' : 'none';
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const container = document.getElementById('scanner-notifications');
        if (!container) return;
        
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        container.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('removing');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 400);
        }, 5000);
    }

    /**
     * Close modal
     */
    closeModal() {
        const modal = document.getElementById('scan-result-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }

    /**
     * Edit product
     */
    editProduct(productId) {
        // Navigate to products page with edit mode
        window.location.href = `admin.php?page=automaten-products&edit=${productId}`;
    }

    /**
     * Initialize scanner
     */
    static init() {
        if (!window.automatenScannerInstance) {
            window.automatenScannerInstance = new AutomatenScanner();
            window.AutomatenScanner = window.automatenScannerInstance;
        }
        return window.automatenScannerInstance;
    }
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', AutomatenScanner.init);
} else {
    AutomatenScanner.init();
}

// Export for global access
window.AutomatenScanner = AutomatenScanner;