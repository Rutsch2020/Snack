<?php
// Sicherheit: Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// Kategorien für Dropdown abrufen (korrekte Tabellennamen)
global $wpdb;
$categories = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}automaten_categories WHERE is_active = 1 ORDER BY name");
?>

<div class="wrap">
    <style>
    .automaten-products {
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
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .automaten-btn-primary:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
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
    
    .automaten-btn-danger {
        background: #dc3545;
        color: white;
    }
    
    .automaten-btn-danger:hover {
        background: #c82333;
        color: white;
        text-decoration: none;
    }
    
    .automaten-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e1e5e9;
        margin-bottom: 20px;
    }
    
    .automaten-card-body {
        padding: 20px;
    }
    
    .automaten-grid {
        display: grid;
        gap: 20px;
    }
    
    .automaten-grid-3 {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    }
    
    .automaten-form-group {
        margin-bottom: 15px;
    }
    
    .automaten-label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #495057;
    }
    
    .automaten-input, .automaten-select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
    }
    
    .automaten-input:focus, .automaten-select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .automaten-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .automaten-product-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        overflow: hidden;
        border: 1px solid #e1e5e9;
        transition: all 0.3s ease;
    }
    
    .automaten-product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .automaten-product-image {
        height: 200px;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6c757d;
        font-size: 3rem;
        position: relative;
        overflow: hidden;
    }
    
    .automaten-product-content {
        padding: 20px;
    }
    
    .automaten-product-category {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        color: white;
        font-weight: 600;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .automaten-product-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #495057;
        margin: 0 0 10px 0;
        line-height: 1.3;
    }
    
    .automaten-product-price {
        font-size: 1.5rem;
        font-weight: 700;
        color: #28a745;
        margin-bottom: 15px;
    }
    
    .automaten-product-stock {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .automaten-stock-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    
    .automaten-stock-good {
        background-color: #28a745;
    }
    
    .automaten-stock-low {
        background-color: #ffc107;
    }
    
    .automaten-stock-empty {
        background-color: #dc3545;
    }
    
    .automaten-product-actions {
        display: flex;
        gap: 10px;
    }
    
    .automaten-product-actions .automaten-btn {
        flex: 1;
        justify-content: center;
        padding: 8px 12px;
        font-size: 13px;
    }
    
    .automaten-text-center {
        text-align: center;
    }
    
    .automaten-loading {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    /* Modal Styles */
    .automaten-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
    }
    
    .automaten-modal-content {
        background: white;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 800px;
        width: 100%;
        max-height: 90vh;
        overflow: hidden;
        position: relative;
    }
    
    .automaten-modal-header {
        padding: 25px 30px;
        border-bottom: 1px solid #e1e5e9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .automaten-modal-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }
    
    .automaten-modal-body {
        padding: 30px;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .automaten-modal-footer {
        padding: 20px 30px;
        border-top: 1px solid #e1e5e9;
        background: #f8f9fa;
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }
    
    .automaten-btn-icon-only {
        padding: 8px;
        background: rgba(255, 255, 255, 0.2);
        border: 2px solid rgba(255, 255, 255, 0.3);
    }
    
    .automaten-textarea {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e1e5e9;
        border-radius: 8px;
        font-size: 14px;
        transition: border-color 0.3s ease;
        min-height: 80px;
        resize: vertical;
        font-family: inherit;
    }
    
    .automaten-textarea:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .automaten-grid-2 {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
    
    .automaten-flex {
        display: flex;
    }
    
    .automaten-gap-2 {
        gap: 10px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .automaten-page-header {
            flex-direction: column;
            text-align: center;
        }
        
        .automaten-products-grid {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        
        .automaten-modal {
            padding: 10px;
        }
        
        .automaten-modal-body {
            padding: 20px;
        }
        
        .automaten-modal-footer {
            flex-direction: column;
        }
        
        .automaten-grid-2 {
            grid-template-columns: 1fr;
        }
    }
    </style>

    <div class="automaten-products">
        <!-- Page Header -->
        <div class="automaten-page-header">
            <div>
                <h1 class="automaten-page-title">
                    <i class="fas fa-boxes"></i>
                    Produkte
                </h1>
                <p class="automaten-page-subtitle">
                    Verwalten Sie Ihre Automaten-Produkte
                </p>
            </div>
            <button id="addProductBtn" class="automaten-btn automaten-btn-primary">
                <i class="fas fa-plus"></i>
                Neues Produkt
            </button>
        </div>

        <!-- Filter & Search -->
        <div class="automaten-card">
            <div class="automaten-card-body">
                <div class="automaten-grid automaten-grid-3">
                    <div class="automaten-form-group">
                        <label class="automaten-label">Suchen</label>
                        <input type="text" id="searchProducts" class="automaten-input" placeholder="Name oder Barcode eingeben...">
                    </div>
                    
                    <div class="automaten-form-group">
                        <label class="automaten-label">Kategorie</label>
                        <select id="filterCategory" class="automaten-select">
                            <option value="">Alle Kategorien</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="automaten-form-group">
                        <label class="automaten-label">Bestandsstatus</label>
                        <select id="filterStock" class="automaten-select">
                            <option value="">Alle</option>
                            <option value="good">Ausreichend</option>
                            <option value="low">Niedrig</option>
                            <option value="empty">Leer</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="automaten-text-center" style="display: none; margin: 40px 0;">
            <div class="automaten-loading"></div>
            <p style="margin-top: 20px; color: #6c757d;">Lade Produkte...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" class="automaten-text-center" style="display: none; margin: 40px 0;">
            <i class="fas fa-cube" style="font-size: 4rem; color: #dee2e6; margin-bottom: 20px;"></i>
            <h3 style="color: #6c757d; margin-bottom: 10px;">Keine Produkte gefunden</h3>
            <p style="color: #6c757d; margin-bottom: 30px;">Erstellen Sie Ihr erstes Produkt oder passen Sie die Filter an.</p>
            <button class="automaten-btn automaten-btn-primary" onclick="document.getElementById('addProductBtn').click();">
                <i class="fas fa-plus"></i>
                Erstes Produkt erstellen
            </button>
        </div>

        <!-- Produkte Grid -->
        <div id="productsContainer" class="automaten-products-grid">
            <!-- Produkte werden hier dynamisch geladen -->
        </div>

    </div>
</div>

<!-- Produkt Modal -->
<div id="productModal" class="automaten-modal" style="display: none;">
    <div class="automaten-modal-content">
        <div class="automaten-modal-header">
            <h3 id="modalTitle">Neues Produkt</h3>
            <button id="closeModalBtn" class="automaten-btn automaten-btn-icon-only">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form id="productForm" class="automaten-modal-body">
            <input type="hidden" id="productId" value="">
            
            <div class="automaten-grid automaten-grid-2">
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productBarcode">Barcode *</label>
                    <div class="automaten-flex automaten-gap-2">
                        <input type="text" id="productBarcode" class="automaten-input" required placeholder="Barcode eingeben oder scannen">
                        <button type="button" id="scanBarcodeBtn" class="automaten-btn automaten-btn-secondary">
                            <i class="fas fa-qrcode"></i>
                        </button>
                    </div>
                </div>
                
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productName">Produktname *</label>
                    <input type="text" id="productName" class="automaten-input" required placeholder="z.B. Coca Cola 0,5L">
                </div>
            </div>
            
            <div class="automaten-grid automaten-grid-2">
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productPrice">Preis (€) *</label>
                    <input type="number" id="productPrice" class="automaten-input" step="0.01" min="0" required placeholder="0.00">
                </div>
                
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productCategory">Kategorie *</label>
                    <select id="productCategory" class="automaten-select" required>
                        <option value="">Kategorie wählen</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category->id; ?>"><?php echo esc_html($category->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="automaten-grid automaten-grid-2">
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productStock">Aktueller Bestand</label>
                    <input type="number" id="productStock" class="automaten-input" min="0" value="0" placeholder="0">
                </div>
                
                <div class="automaten-form-group">
                    <label class="automaten-label" for="productMinStock">Mindestbestand</label>
                    <input type="number" id="productMinStock" class="automaten-input" min="0" value="10" placeholder="10">
                </div>
            </div>
            
            <div class="automaten-form-group">
                <label class="automaten-label" for="productDescription">Beschreibung</label>
                <textarea id="productDescription" class="automaten-textarea" placeholder="Optionale Produktbeschreibung..."></textarea>
            </div>
            
            <div class="automaten-form-group">
                <label class="automaten-label" for="productImage">Bild-URL</label>
                <input type="url" id="productImage" class="automaten-input" placeholder="https://example.com/bild.jpg">
            </div>
        </form>
        
        <div class="automaten-modal-footer">
            <button type="button" id="cancelBtn" class="automaten-btn automaten-btn-secondary">
                Abbrechen
            </button>
            <button type="submit" form="productForm" id="saveProductBtn" class="automaten-btn automaten-btn-primary">
                <i class="fas fa-save"></i>
                Speichern
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elemente
    const productsContainer = document.getElementById('productsContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const emptyState = document.getElementById('emptyState');
    const productModal = document.getElementById('productModal');
    const productForm = document.getElementById('productForm');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const searchInput = document.getElementById('searchProducts');
    const categoryFilter = document.getElementById('filterCategory');
    const stockFilter = document.getElementById('filterStock');
    
    let products = [];
    let currentEditId = null;
    
    // Initial laden
    loadProducts();
    
    // Event Listeners
    addProductBtn.addEventListener('click', openAddModal);
    closeModalBtn.addEventListener('click', closeModal);
    cancelBtn.addEventListener('click', closeModal);
    productForm.addEventListener('submit', saveProduct);
    
    // Search & Filter mit Debouncing
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(filterProducts, 300);
    });
    
    categoryFilter.addEventListener('change', filterProducts);
    stockFilter.addEventListener('change', filterProducts);
    
    // Modal schließen bei Overlay-Klick
    productModal.addEventListener('click', function(e) {
        if (e.target === productModal) {
            closeModal();
        }
    });
    
    // ESC-Taste für Modal schließen
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && productModal.style.display !== 'none') {
            closeModal();
        }
    });
    
    /**
     * Produkte laden
     */
    function loadProducts() {
        showLoading();
        
        fetch(automatenAjax.ajaxurl + '?action=automaten_get_products&nonce=' + automatenAjax.nonce)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    products = data.data.products || data.data || [];
                    displayProducts(products);
                } else {
                    showNotification('Fehler beim Laden der Produkte', 'error');
                    displayProducts([]);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Netzwerkfehler beim Laden', 'error');
                displayProducts([]);
            })
            .finally(() => {
                hideLoading();
            });
    }
    
    /**
     * Produkte anzeigen
     */
    function displayProducts(productsToShow) {
        if (productsToShow.length === 0) {
            productsContainer.style.display = 'none';
            emptyState.style.display = 'block';
            return;
        }
        
        productsContainer.style.display = 'grid';
        emptyState.style.display = 'none';
        
        productsContainer.innerHTML = productsToShow.map(product => {
            const stockStatus = getStockStatus(product);
            const stockClass = stockStatus === 'empty' ? 'automaten-stock-empty' : 
                              stockStatus === 'low' ? 'automaten-stock-low' : 'automaten-stock-good';
            
            return `
                <div class="automaten-product-card" data-product-id="${product.id}">
                    <div class="automaten-product-image">
                        ${product.image_url ? 
                            `<img src="${product.image_url}" alt="${product.name}" style="width: 100%; height: 100%; object-fit: cover;">` :
                            `<i class="fas fa-cube"></i>`
                        }
                    </div>
                    <div class="automaten-product-content">
                        <div class="automaten-product-category" style="background-color: ${product.category_color || '#6b7280'};">
                            ${product.category_name || 'Allgemein'}
                        </div>
                        <h4 class="automaten-product-name">${product.name}</h4>
                        <div class="automaten-product-price">${parseFloat(product.price || 0).toFixed(2)}€</div>
                        <div class="automaten-product-stock">
                            <span class="automaten-stock-indicator ${stockClass}"></span>
                            <span>${product.stock_quantity || 0} auf Lager</span>
                        </div>
                        <div class="automaten-product-actions">
                            <button class="automaten-btn automaten-btn-secondary edit-product" data-id="${product.id}">
                                <i class="fas fa-edit"></i>
                                Bearbeiten
                            </button>
                            <button class="automaten-btn automaten-btn-danger delete-product" data-id="${product.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
        // Event Listeners für Aktions-Buttons
        document.querySelectorAll('.edit-product').forEach(btn => {
            btn.addEventListener('click', (e) => editProduct(e.target.dataset.id));
        });
        
        document.querySelectorAll('.delete-product').forEach(btn => {
            btn.addEventListener('click', (e) => deleteProduct(e.target.dataset.id));
        });
    }
    
    /**
     * Produkte filtern
     */
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const categoryId = categoryFilter.value;
        const stockStatus = stockFilter.value;
        
        const filtered = products.filter(product => {
            const matchesSearch = !searchTerm || 
                product.name.toLowerCase().includes(searchTerm) ||
                (product.barcode && product.barcode.toLowerCase().includes(searchTerm));
            
            const matchesCategory = !categoryId || product.category_id == categoryId;
            
            const productStockStatus = getStockStatus(product);
            const matchesStock = !stockStatus || productStockStatus === stockStatus;
            
            return matchesSearch && matchesCategory && matchesStock;
        });
        
        displayProducts(filtered);
    }
    
    /**
     * Stock-Status ermitteln
     */
    function getStockStatus(product) {
        const stock = parseInt(product.stock_quantity || 0);
        const minStock = parseInt(product.min_stock || 10);
        
        if (stock === 0) return 'empty';
        if (stock <= minStock) return 'low';
        return 'good';
    }
    
    /**
     * Modal öffnen (Hinzufügen)
     */
    function openAddModal() {
        currentEditId = null;
        document.getElementById('modalTitle').textContent = 'Neues Produkt';
        productForm.reset();
        document.getElementById('productId').value = '';
        productModal.style.display = 'flex';
        document.getElementById('productBarcode').focus();
    }
    
    /**
     * Modal öffnen (Bearbeiten)
     */
    function editProduct(productId) {
        const product = products.find(p => p.id == productId);
        if (!product) return;
        
        currentEditId = productId;
        document.getElementById('modalTitle').textContent = 'Produkt bearbeiten';
        
        // Formular füllen
        document.getElementById('productId').value = product.id;
        document.getElementById('productBarcode').value = product.barcode || '';
        document.getElementById('productName').value = product.name || '';
        document.getElementById('productPrice').value = product.price || '';
        document.getElementById('productCategory').value = product.category_id || '';
        document.getElementById('productStock').value = product.stock_quantity || 0;
        document.getElementById('productMinStock').value = product.min_stock || 10;
        document.getElementById('productDescription').value = product.description || '';
        document.getElementById('productImage').value = product.image_url || '';
        
        productModal.style.display = 'flex';
        document.getElementById('productName').focus();
    }
    
    /**
     * Modal schließen
     */
    function closeModal() {
        productModal.style.display = 'none';
        currentEditId = null;
        productForm.reset();
    }
    
    /**
     * Produkt speichern
     */
    function saveProduct(e) {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('action', 'automaten_save_product');
        formData.append('nonce', automatenAjax.nonce);
        formData.append('product_id', document.getElementById('productId').value);
        formData.append('barcode', document.getElementById('productBarcode').value);
        formData.append('name', document.getElementById('productName').value);
        formData.append('price', document.getElementById('productPrice').value);
        formData.append('category_id', document.getElementById('productCategory').value);
        formData.append('stock_quantity', document.getElementById('productStock').value);
        formData.append('min_stock', document.getElementById('productMinStock').value);
        formData.append('description', document.getElementById('productDescription').value);
        formData.append('image_url', document.getElementById('productImage').value);
        
        // Button deaktivieren
        const saveBtn = document.getElementById('saveProductBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Speichern...';
        
        fetch(automatenAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Produkt erfolgreich gespeichert!', 'success');
                closeModal();
                loadProducts(); // Neu laden
            } else {
                showNotification(data.data.message || 'Fehler beim Speichern', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Netzwerkfehler beim Speichern', 'error');
        })
        .finally(() => {
            // Button wieder aktivieren
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Speichern';
        });
    }
    
    /**
     * Produkt löschen
     */
    function deleteProduct(productId) {
        if (!confirm(automatenAjax.messages.delete_confirm)) return;
        
        const formData = new FormData();
        formData.append('action', 'automaten_delete_product');
        formData.append('nonce', automatenAjax.nonce);
        formData.append('product_id', productId);
        
        fetch(automatenAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Produkt gelöscht!', 'success');
                loadProducts(); // Neu laden
            } else {
                showNotification(data.data.message || 'Fehler beim Löschen', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Netzwerkfehler beim Löschen', 'error');
        });
    }
    
    /**
     * Loading anzeigen
     */
    function showLoading() {
        loadingIndicator.style.display = 'block';
        productsContainer.style.display = 'none';
        emptyState.style.display = 'none';
    }
    
    /**
     * Loading ausblenden
     */
    function hideLoading() {
        loadingIndicator.style.display = 'none';
    }
    
    /**
     * Notification anzeigen
     */
    function showNotification(message, type = 'info') {
        // Einfache Alert-Box (kann später durch Toast ersetzt werden)
        if (type === 'success') {
            alert('✅ ' + message);
        } else if (type === 'error') {
            alert('❌ ' + message);
        } else {
            alert('ℹ️ ' + message);
        }
    }
});
</script>